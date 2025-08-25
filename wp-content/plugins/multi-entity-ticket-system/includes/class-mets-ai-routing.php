<?php
/**
 * METS AI Routing Engine
 *
 * Handles intelligent ticket routing using AI analysis and agent expertise
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class METS_AI_Routing {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * AI Service instance
     */
    private $ai_service;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->ai_service = METS_AI_Service::get_instance();
    }
    
    /**
     * Analyze ticket and suggest best agent assignment
     *
     * @param array $ticket_data Ticket information
     * @return array|WP_Error Routing analysis result
     */
    public function analyze_ticket_for_routing( $ticket_data ) {
        if ( ! $this->ai_service->is_configured() ) {
            return new WP_Error( 'ai_not_configured', did_action( 'init' ) ? __( 'AI service not configured', 'multi-entity-ticket-system' ) : 'AI service not configured' );
        }
        
        // Get AI analysis of ticket content
        $ai_analysis = $this->ai_service->analyze_for_routing(
            $ticket_data['subject'],
            $ticket_data['description'],
            $ticket_data['category'] ?? '',
            $ticket_data['priority'] ?? 'medium'
        );
        
        if ( is_wp_error( $ai_analysis ) ) {
            return $ai_analysis;
        }
        
        // Get available agents with their expertise
        $available_agents = $this->get_available_agents( $ticket_data['entity_id'] ?? null );
        
        if ( empty( $available_agents ) ) {
            return new WP_Error( 'no_agents_available', did_action( 'init' ) ? __( 'No agents available for assignment', 'multi-entity-ticket-system' ) : 'No agents available for assignment' );
        }
        
        // Calculate routing scores for each agent
        $routing_scores = array();
        foreach ( $available_agents as $agent ) {
            $score = $this->calculate_routing_score( $agent, $ai_analysis, $ticket_data );
            if ( $score > 0 ) {
                $routing_scores[] = array(
                    'agent' => $agent,
                    'score' => $score,
                    'reasoning' => $this->build_routing_reasoning( $agent, $ai_analysis, $score )
                );
            }
        }
        
        // Sort by score (highest first)
        usort( $routing_scores, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );
        
        return array(
            'ai_analysis' => $ai_analysis,
            'routing_scores' => $routing_scores,
            'recommended_agent' => ! empty( $routing_scores ) ? $routing_scores[0] : null,
            'confidence' => ! empty( $routing_scores ) ? $routing_scores[0]['score'] : 0
        );
    }
    
    /**
     * Get best agent for ticket assignment
     *
     * @param array $analysis AI routing analysis
     * @param array $availability Agent availability constraints
     * @return int|null Best agent ID or null if none found
     */
    public function get_best_agent( $analysis, $availability = array() ) {
        if ( empty( $analysis['routing_scores'] ) ) {
            return null;
        }
        
        // Apply availability filters
        $filtered_scores = $analysis['routing_scores'];
        if ( ! empty( $availability ) ) {
            $filtered_scores = array_filter( $filtered_scores, function( $score ) use ( $availability ) {
                return $this->check_agent_availability( $score['agent'], $availability );
            } );
        }
        
        if ( empty( $filtered_scores ) ) {
            // Fallback to round-robin if no agents meet criteria
            return $this->get_round_robin_agent();
        }
        
        return $filtered_scores[0]['agent']['user_id'];
    }
    
    /**
     * Calculate routing score for an agent
     *
     * @param array $agent Agent data with expertise
     * @param array $ai_analysis AI analysis of ticket
     * @param array $ticket_data Original ticket data
     * @return float Routing score (0-1)
     */
    private function calculate_routing_score( $agent, $ai_analysis, $ticket_data ) {
        $score = 0.0;
        $factors = array();
        
        // Category expertise match (40% weight)
        $category_score = $this->calculate_category_score( $agent, $ticket_data['category'] ?? '', $ai_analysis );
        $factors['category'] = $category_score * 0.4;
        
        // Priority handling experience (20% weight)
        $priority_score = $this->calculate_priority_score( $agent, $ticket_data['priority'] ?? 'medium' );
        $factors['priority'] = $priority_score * 0.2;
        
        // Current workload (20% weight)
        $workload_score = $this->calculate_workload_score( $agent );
        $factors['workload'] = $workload_score * 0.2;
        
        // Agent rating/performance (10% weight)
        $performance_score = $this->calculate_performance_score( $agent );
        $factors['performance'] = $performance_score * 0.1;
        
        // Availability (10% weight)
        $availability_score = $this->calculate_availability_score( $agent );
        $factors['availability'] = $availability_score * 0.1;
        
        $score = array_sum( $factors );
        
        // Store scoring breakdown for debugging
        $agent['score_breakdown'] = $factors;
        
        return max( 0, min( 1, $score ) );
    }
    
    /**
     * Calculate category expertise score
     */
    private function calculate_category_score( $agent, $category, $ai_analysis ) {
        if ( empty( $category ) || empty( $agent['expertise'] ) ) {
            return 0.5; // Neutral score
        }
        
        // Direct category match
        foreach ( $agent['expertise'] as $expertise ) {
            if ( strtolower( $expertise['category'] ) === strtolower( $category ) ) {
                $skill_multiplier = array(
                    'basic' => 0.6,
                    'intermediate' => 0.8,
                    'expert' => 1.0
                );
                return $skill_multiplier[ $expertise['skill_level'] ] * $expertise['weight'];
            }
        }
        
        // Related category match (if AI suggests similarity)
        if ( isset( $ai_analysis['related_categories'] ) ) {
            foreach ( $ai_analysis['related_categories'] as $related_cat => $similarity ) {
                foreach ( $agent['expertise'] as $expertise ) {
                    if ( strtolower( $expertise['category'] ) === strtolower( $related_cat ) ) {
                        $skill_multiplier = array(
                            'basic' => 0.4,
                            'intermediate' => 0.6,
                            'expert' => 0.8
                        );
                        return $skill_multiplier[ $expertise['skill_level'] ] * $expertise['weight'] * $similarity;
                    }
                }
            }
        }
        
        return 0.3; // Low score for no category match
    }
    
    /**
     * Calculate priority handling score
     */
    private function calculate_priority_score( $agent, $priority ) {
        // Get agent's success rate with this priority level
        global $wpdb;
        
        $success_rate = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) 
             FROM {$wpdb->prefix}mets_ticket_ratings r
             JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
             WHERE t.assigned_to = %d AND t.priority = %s
             AND r.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            $agent['user_id'],
            $priority
        ) );
        
        return $success_rate ? floatval( $success_rate ) : 0.7; // Default to 0.7 if no data
    }
    
    /**
     * Calculate workload score (lower workload = higher score)
     */
    private function calculate_workload_score( $agent ) {
        global $wpdb;
        
        $current_tickets = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
             WHERE assigned_to = %d AND status NOT IN ('closed', 'resolved')",
            $agent['user_id']
        ) );
        
        $max_tickets = get_user_meta( $agent['user_id'], 'mets_max_tickets', true ) ?: 20;
        
        // Calculate score based on percentage of capacity used
        $capacity_used = $current_tickets / $max_tickets;
        return max( 0, 1 - $capacity_used );
    }
    
    /**
     * Calculate agent performance score
     */
    private function calculate_performance_score( $agent ) {
        global $wpdb;
        
        $avg_rating = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(rating) FROM {$wpdb->prefix}mets_ticket_ratings r
             JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
             WHERE t.assigned_to = %d 
             AND r.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            $agent['user_id']
        ) );
        
        return $avg_rating ? ( floatval( $avg_rating ) / 5 ) : 0.8; // Default to 0.8 if no ratings
    }
    
    /**
     * Calculate availability score
     */
    private function calculate_availability_score( $agent ) {
        $status = get_user_meta( $agent['user_id'], 'mets_availability_status', true );
        
        switch ( $status ) {
            case 'available':
                return 1.0;
            case 'busy':
                return 0.5;
            case 'away':
                return 0.2;
            default:
                return 0.8; // Default available
        }
    }
    
    /**
     * Build routing reasoning text
     */
    private function build_routing_reasoning( $agent, $ai_analysis, $score ) {
        $reasons = array();
        
        if ( isset( $agent['score_breakdown'] ) ) {
            $breakdown = $agent['score_breakdown'];
            
            if ( $breakdown['category'] > 0.3 ) {
                $reasons[] = did_action( 'init' ) ? __( 'Strong category expertise match', 'multi-entity-ticket-system' ) : 'Strong category expertise match';
            }
            
            if ( $breakdown['workload'] > 0.7 ) {
                $reasons[] = did_action( 'init' ) ? __( 'Low current workload', 'multi-entity-ticket-system' ) : 'Low current workload';
            }
            
            if ( $breakdown['performance'] > 0.8 ) {
                $reasons[] = did_action( 'init' ) ? __( 'High customer satisfaction ratings', 'multi-entity-ticket-system' ) : 'High customer satisfaction ratings';
            }
            
            if ( $breakdown['availability'] === 1.0 ) {
                $reasons[] = did_action( 'init' ) ? __( 'Currently available', 'multi-entity-ticket-system' ) : 'Currently available';
            }
        }
        
        if ( empty( $reasons ) ) {
            $reasons[] = sprintf( 
                did_action( 'init' ) ? __( 'Overall routing score: %.1f%%', 'multi-entity-ticket-system' ) : 'Overall routing score: %.1f%%', 
                $score * 100 
            );
        }
        
        return implode( ', ', $reasons );
    }
    
    /**
     * Get available agents with their expertise
     */
    private function get_available_agents( $entity_id = null ) {
        global $wpdb;
        
        // Get agents (users with ticket management capabilities)
        $agents_query = "
            SELECT DISTINCT u.ID as user_id, u.display_name, u.user_email
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key LIKE '%capabilities%' 
            AND um.meta_value LIKE '%manage_tickets%'
        ";
        
        // Add entity filtering if specified
        if ( $entity_id ) {
            $agents_query .= $wpdb->prepare( "
                AND u.ID IN (
                    SELECT user_id FROM {$wpdb->prefix}mets_user_entities 
                    WHERE entity_id = %d
                )
            ", $entity_id );
        }
        
        $agents = $wpdb->get_results( $agents_query, ARRAY_A );
        
        // Add expertise data for each agent
        foreach ( $agents as &$agent ) {
            $agent['expertise'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT category, skill_level, weight 
                 FROM {$wpdb->prefix}mets_agent_expertise 
                 WHERE user_id = %d",
                $agent['user_id']
            ), ARRAY_A );
        }
        
        return $agents;
    }
    
    /**
     * Check agent availability
     */
    private function check_agent_availability( $agent, $availability_constraints ) {
        if ( empty( $availability_constraints ) ) {
            return true;
        }
        
        // Check max tickets constraint
        if ( isset( $availability_constraints['max_tickets'] ) ) {
            global $wpdb;
            $current_tickets = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
                 WHERE assigned_to = %d AND status NOT IN ('closed', 'resolved')",
                $agent['user_id']
            ) );
            
            if ( $current_tickets >= $availability_constraints['max_tickets'] ) {
                return false;
            }
        }
        
        // Check availability status
        if ( isset( $availability_constraints['require_available'] ) && $availability_constraints['require_available'] ) {
            $status = get_user_meta( $agent['user_id'], 'mets_availability_status', true );
            if ( $status === 'away' ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get next agent using round-robin method (fallback)
     */
    private function get_round_robin_agent() {
        global $wpdb;
        
        // Get the agent who was assigned longest ago
        $agent_id = $wpdb->get_var(
            "SELECT u.ID FROM {$wpdb->users} u
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             LEFT JOIN (
                 SELECT assigned_to, MAX(created_at) as last_assignment
                 FROM {$wpdb->prefix}mets_tickets
                 WHERE assigned_to IS NOT NULL
                 GROUP BY assigned_to
             ) t ON u.ID = t.assigned_to
             WHERE um.meta_key LIKE '%capabilities%' 
             AND um.meta_value LIKE '%manage_tickets%'
             ORDER BY COALESCE(t.last_assignment, '1970-01-01') ASC
             LIMIT 1"
        );
        
        return $agent_id ? intval( $agent_id ) : null;
    }
    
    /**
     * Log routing decision
     */
    public function log_routing_decision( $ticket_id, $agent_id, $routing_data ) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mets_routing_history',
            array(
                'ticket_id' => $ticket_id,
                'assigned_to' => $agent_id,
                'routing_reason' => $routing_data['reasoning'] ?? '',
                'confidence_score' => $routing_data['confidence'] ?? 0,
                'ai_analysis' => maybe_serialize( $routing_data['ai_analysis'] ?? array() ),
                'fallback_used' => $routing_data['fallback_used'] ?? 0,
                'manual_override' => $routing_data['manual_override'] ?? 0,
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%d', '%s', '%f', '%s', '%d', '%d', '%s' )
        );
    }
    
    /**
     * Get routing analytics for admin dashboard
     */
    public function get_routing_analytics( $days = 30 ) {
        global $wpdb;
        
        $date_from = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        return array(
            'total_routings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_routing_history WHERE created_at >= %s",
                $date_from
            ) ),
            'ai_routings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_routing_history 
                 WHERE created_at >= %s AND fallback_used = 0",
                $date_from
            ) ),
            'fallback_routings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_routing_history 
                 WHERE created_at >= %s AND fallback_used = 1",
                $date_from
            ) ),
            'avg_confidence' => $wpdb->get_var( $wpdb->prepare(
                "SELECT AVG(confidence_score) FROM {$wpdb->prefix}mets_routing_history 
                 WHERE created_at >= %s AND confidence_score > 0",
                $date_from
            ) ),
            'agent_distribution' => $wpdb->get_results( $wpdb->prepare(
                "SELECT u.display_name, COUNT(*) as assignments, AVG(confidence_score) as avg_confidence
                 FROM {$wpdb->prefix}mets_routing_history rh
                 JOIN {$wpdb->users} u ON rh.assigned_to = u.ID
                 WHERE rh.created_at >= %s
                 GROUP BY rh.assigned_to
                 ORDER BY assignments DESC",
                $date_from
            ), ARRAY_A )
        );
    }
}