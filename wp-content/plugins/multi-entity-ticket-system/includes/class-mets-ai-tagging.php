<?php
/**
 * METS AI Tag Generation Engine
 *
 * Handles automatic tag generation using AI analysis
 *
 * @package METS
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class METS_AI_Tagging {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * AI Service instance
     */
    private $ai_service;
    
    /**
     * Confidence threshold for auto-applying tags
     */
    const AUTO_APPLY_THRESHOLD = 0.8;
    
    /**
     * Maximum tags to generate per ticket
     */
    const MAX_TAGS_PER_TICKET = 8;
    
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
     * Generate tags for ticket content
     *
     * @param string $title Ticket title
     * @param string $content Ticket content
     * @param string $category Ticket category
     * @param array $existing_tags Existing tags to avoid duplicates
     * @return array|WP_Error Generated tags with confidence scores
     */
    public function generate_tags( $title, $content, $category = '', $existing_tags = array() ) {
        if ( ! $this->ai_service->is_configured() ) {
            return new WP_Error( 'ai_not_configured', __( 'AI service not configured', 'multi-entity-ticket-system' ) );
        }
        
        // Extract keywords and themes using AI
        $ai_tags = $this->ai_service->extract_tags( $title, $content, $category );
        
        if ( is_wp_error( $ai_tags ) ) {
            return $ai_tags;
        }
        
        // Process and validate generated tags
        $processed_tags = $this->process_generated_tags( $ai_tags, $existing_tags );
        
        // Calculate relevance scores
        $scored_tags = $this->calculate_tag_relevance( $processed_tags, $title, $content );
        
        // Sort by relevance and limit count
        usort( $scored_tags, function( $a, $b ) {
            return $b['confidence'] <=> $a['confidence'];
        } );
        
        return array_slice( $scored_tags, 0, self::MAX_TAGS_PER_TICKET );
    }
    
    /**
     * Apply generated tags to a ticket
     *
     * @param int $ticket_id Ticket ID
     * @param array $generated_tags Generated tags with confidence scores
     * @param bool $auto_apply Whether to auto-apply high-confidence tags
     * @return array Results of tag application
     */
    public function apply_tags( $ticket_id, $generated_tags, $auto_apply = true ) {
        global $wpdb;
        
        $results = array(
            'applied' => array(),
            'suggested' => array(),
            'errors' => array()
        );
        
        foreach ( $generated_tags as $tag_data ) {
            $should_auto_apply = $auto_apply && $tag_data['confidence'] >= self::AUTO_APPLY_THRESHOLD;
            
            // Insert tag record
            $insert_result = $wpdb->insert(
                $wpdb->prefix . 'mets_auto_tags',
                array(
                    'ticket_id' => $ticket_id,
                    'tag_name' => $tag_data['tag'],
                    'confidence_score' => $tag_data['confidence'],
                    'auto_applied' => $should_auto_apply ? 1 : 0,
                    'ai_reasoning' => $tag_data['reasoning'] ?? '',
                    'created_at' => current_time( 'mysql' )
                ),
                array( '%d', '%s', '%f', '%d', '%s', '%s' )
            );
            
            if ( $insert_result === false ) {
                $results['errors'][] = sprintf( 
                    __( 'Failed to save tag: %s', 'multi-entity-ticket-system' ), 
                    $tag_data['tag'] 
                );
                continue;
            }
            
            if ( $should_auto_apply ) {
                // Apply tag to ticket immediately
                $this->apply_tag_to_ticket( $ticket_id, $tag_data['tag'] );
                $results['applied'][] = $tag_data['tag'];
            } else {
                $results['suggested'][] = $tag_data['tag'];
            }
        }
        
        return $results;
    }
    
    /**
     * Get tag suggestions for ticket editing
     *
     * @param int $ticket_id Ticket ID
     * @param bool $include_applied Include already applied tags
     * @return array Tag suggestions
     */
    public function get_tag_suggestions( $ticket_id, $include_applied = false ) {
        global $wpdb;
        
        $where_clause = $include_applied ? '' : 'AND auto_applied = 0 AND approved_by IS NULL';
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT tag_name, confidence_score, ai_reasoning, auto_applied, approved_by
             FROM {$wpdb->prefix}mets_auto_tags
             WHERE ticket_id = %d {$where_clause}
             ORDER BY confidence_score DESC",
            $ticket_id
        ), ARRAY_A );
    }
    
    /**
     * Approve suggested tag
     *
     * @param int $ticket_id Ticket ID
     * @param string $tag_name Tag to approve
     * @param int $user_id User approving the tag
     * @return bool Success status
     */
    public function approve_tag( $ticket_id, $tag_name, $user_id ) {
        global $wpdb;
        
        // Update approval status
        $update_result = $wpdb->update(
            $wpdb->prefix . 'mets_auto_tags',
            array(
                'approved_by' => $user_id,
                'approved_at' => current_time( 'mysql' )
            ),
            array(
                'ticket_id' => $ticket_id,
                'tag_name' => $tag_name
            ),
            array( '%d', '%s' ),
            array( '%d', '%s' )
        );
        
        if ( $update_result !== false ) {
            // Apply tag to ticket
            $this->apply_tag_to_ticket( $ticket_id, $tag_name );
            return true;
        }
        
        return false;
    }
    
    /**
     * Reject suggested tag
     *
     * @param int $ticket_id Ticket ID
     * @param string $tag_name Tag to reject
     * @param int $user_id User rejecting the tag
     * @return bool Success status
     */
    public function reject_tag( $ticket_id, $tag_name, $user_id ) {
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'mets_auto_tags',
            array( 'rejected_by' => $user_id ),
            array(
                'ticket_id' => $ticket_id,
                'tag_name' => $tag_name
            ),
            array( '%d' ),
            array( '%d', '%s' )
        ) !== false;
    }
    
    /**
     * Process and validate generated tags
     *
     * @param array $ai_tags Raw tags from AI
     * @param array $existing_tags Existing tags to avoid duplicates
     * @return array Processed tags
     */
    private function process_generated_tags( $ai_tags, $existing_tags = array() ) {
        $processed = array();
        $existing_lower = array_map( 'strtolower', $existing_tags );
        
        foreach ( $ai_tags as $tag ) {
            // Clean and normalize tag
            $clean_tag = $this->clean_tag_name( $tag );
            
            if ( empty( $clean_tag ) ) {
                continue;
            }
            
            // Skip if duplicate
            if ( in_array( strtolower( $clean_tag ), $existing_lower ) ) {
                continue;
            }
            
            // Skip if invalid
            if ( ! $this->validate_tag( $clean_tag ) ) {
                continue;
            }
            
            $processed[] = $clean_tag;
            $existing_lower[] = strtolower( $clean_tag );
        }
        
        return $processed;
    }
    
    /**
     * Clean and normalize tag name
     *
     * @param string $tag Raw tag name
     * @return string Cleaned tag name
     */
    private function clean_tag_name( $tag ) {
        // Remove extra whitespace and special characters
        $tag = trim( preg_replace( '/[^\w\s-]/', '', $tag ) );
        
        // Convert to lowercase and replace spaces with hyphens
        $tag = strtolower( str_replace( ' ', '-', $tag ) );
        
        // Remove multiple consecutive hyphens
        $tag = preg_replace( '/-+/', '-', $tag );
        
        // Remove leading/trailing hyphens
        $tag = trim( $tag, '-' );
        
        return $tag;
    }
    
    /**
     * Validate tag name
     *
     * @param string $tag Tag to validate
     * @return bool Is valid
     */
    private function validate_tag( $tag ) {
        // Check length
        if ( strlen( $tag ) < 2 || strlen( $tag ) > 50 ) {
            return false;
        }
        
        // Check for blacklisted words
        $blacklist = array( 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
        if ( in_array( $tag, $blacklist ) ) {
            return false;
        }
        
        // Check if it's just a number
        if ( is_numeric( $tag ) ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate relevance scores for tags
     *
     * @param array $tags Processed tags
     * @param string $title Ticket title
     * @param string $content Ticket content
     * @return array Tags with confidence scores
     */
    private function calculate_tag_relevance( $tags, $title, $content ) {
        $scored_tags = array();
        $combined_text = strtolower( $title . ' ' . $content );
        
        foreach ( $tags as $tag ) {
            $confidence = $this->calculate_single_tag_relevance( $tag, $combined_text );
            $reasoning = $this->build_tag_reasoning( $tag, $confidence, $title, $content );
            
            $scored_tags[] = array(
                'tag' => $tag,
                'confidence' => $confidence,
                'reasoning' => $reasoning
            );
        }
        
        return $scored_tags;
    }
    
    /**
     * Calculate relevance score for a single tag
     *
     * @param string $tag Tag name
     * @param string $text Combined ticket text
     * @return float Relevance score (0-1)
     */
    private function calculate_single_tag_relevance( $tag, $text ) {
        $score = 0.0;
        
        // Exact match bonus
        if ( strpos( $text, $tag ) !== false ) {
            $score += 0.4;
        }
        
        // Word stem matching
        $tag_words = explode( '-', $tag );
        foreach ( $tag_words as $word ) {
            if ( strlen( $word ) > 3 ) {
                $stem = substr( $word, 0, -1 ); // Simple stemming
                if ( strpos( $text, $stem ) !== false ) {
                    $score += 0.2;
                }
            }
        }
        
        // Frequency bonus
        $frequency = substr_count( $text, $tag );
        if ( $frequency > 1 ) {
            $score += min( 0.3, $frequency * 0.1 );
        }
        
        // Position bonus (tags found in title get higher score)
        if ( strpos( strtolower( $tag ), $tag ) !== false ) {
            $score += 0.2;
        }
        
        // Popular tag bonus (based on historical usage)
        $popularity_score = $this->get_tag_popularity_score( $tag );
        $score += $popularity_score * 0.1;
        
        return min( 1.0, $score );
    }
    
    /**
     * Get tag popularity score based on historical usage
     *
     * @param string $tag Tag name
     * @return float Popularity score (0-1)
     */
    private function get_tag_popularity_score( $tag ) {
        global $wpdb;
        
        $usage_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_auto_tags 
             WHERE tag_name = %s AND (auto_applied = 1 OR approved_by IS NOT NULL)
             AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
            $tag
        ) );
        
        // Normalize based on usage (cap at 10 uses = max score)
        return min( 1.0, intval( $usage_count ) / 10 );
    }
    
    /**
     * Build reasoning text for tag
     *
     * @param string $tag Tag name
     * @param float $confidence Confidence score
     * @param string $title Ticket title
     * @param string $content Ticket content
     * @return string Reasoning text
     */
    private function build_tag_reasoning( $tag, $confidence, $title, $content ) {
        $reasons = array();
        
        if ( stripos( $title, $tag ) !== false ) {
            $reasons[] = __( 'Found in title', 'multi-entity-ticket-system' );
        }
        
        if ( stripos( $content, $tag ) !== false ) {
            $reasons[] = __( 'Found in content', 'multi-entity-ticket-system' );
        }
        
        $frequency = substr_count( strtolower( $content ), $tag );
        if ( $frequency > 1 ) {
            $reasons[] = sprintf( 
                __( 'Mentioned %d times', 'multi-entity-ticket-system' ), 
                $frequency 
            );
        }
        
        if ( $confidence >= 0.8 ) {
            $reasons[] = __( 'High relevance', 'multi-entity-ticket-system' );
        } elseif ( $confidence >= 0.6 ) {
            $reasons[] = __( 'Medium relevance', 'multi-entity-ticket-system' );
        }
        
        return implode( ', ', $reasons );
    }
    
    /**
     * Apply tag to ticket (update ticket meta or custom field)
     *
     * @param int $ticket_id Ticket ID
     * @param string $tag_name Tag to apply
     * @return bool Success status
     */
    private function apply_tag_to_ticket( $ticket_id, $tag_name ) {
        global $wpdb;
        
        // Get current ticket data
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT meta_data FROM {$wpdb->prefix}mets_tickets WHERE id = %d",
            $ticket_id
        ) );
        
        if ( ! $ticket ) {
            return false;
        }
        
        $meta_data = maybe_unserialize( $ticket->meta_data ) ?: array();
        
        // Add tag to meta data
        if ( ! isset( $meta_data['tags'] ) ) {
            $meta_data['tags'] = array();
        }
        
        if ( ! in_array( $tag_name, $meta_data['tags'] ) ) {
            $meta_data['tags'][] = $tag_name;
        }
        
        // Update ticket
        return $wpdb->update(
            $wpdb->prefix . 'mets_tickets',
            array( 'meta_data' => maybe_serialize( $meta_data ) ),
            array( 'id' => $ticket_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
    }
    
    /**
     * Get tagging analytics
     *
     * @param int $days Number of days to analyze
     * @return array Analytics data
     */
    public function get_tagging_analytics( $days = 30 ) {
        global $wpdb;
        
        $date_from = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        
        return array(
            'total_tags_generated' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_auto_tags WHERE created_at >= %s",
                $date_from
            ) ),
            'auto_applied_tags' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_auto_tags 
                 WHERE created_at >= %s AND auto_applied = 1",
                $date_from
            ) ),
            'approved_tags' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_auto_tags 
                 WHERE created_at >= %s AND approved_by IS NOT NULL",
                $date_from
            ) ),
            'rejected_tags' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_auto_tags 
                 WHERE created_at >= %s AND rejected_by IS NOT NULL",
                $date_from
            ) ),
            'avg_confidence' => $wpdb->get_var( $wpdb->prepare(
                "SELECT AVG(confidence_score) FROM {$wpdb->prefix}mets_auto_tags 
                 WHERE created_at >= %s",
                $date_from
            ) ),
            'popular_tags' => $wpdb->get_results( $wpdb->prepare(
                "SELECT tag_name, COUNT(*) as usage_count, AVG(confidence_score) as avg_confidence
                 FROM {$wpdb->prefix}mets_auto_tags
                 WHERE created_at >= %s 
                 AND (auto_applied = 1 OR approved_by IS NOT NULL)
                 GROUP BY tag_name
                 ORDER BY usage_count DESC
                 LIMIT 20",
                $date_from
            ), ARRAY_A )
        );
    }
    
    /**
     * Bulk approve suggested tags
     *
     * @param array $tag_approvals Array of ticket_id => [tag_names]
     * @param int $user_id User approving tags
     * @return array Results
     */
    public function bulk_approve_tags( $tag_approvals, $user_id ) {
        $results = array( 'success' => 0, 'errors' => 0 );
        
        foreach ( $tag_approvals as $ticket_id => $tag_names ) {
            foreach ( $tag_names as $tag_name ) {
                if ( $this->approve_tag( $ticket_id, $tag_name, $user_id ) ) {
                    $results['success']++;
                } else {
                    $results['errors']++;
                }
            }
        }
        
        return $results;
    }
}