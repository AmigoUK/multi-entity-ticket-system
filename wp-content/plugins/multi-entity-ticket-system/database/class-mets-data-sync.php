<?php
/**
 * Data synchronization between related tables
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

/**
 * Data synchronization management class.
 *
 * This class handles data synchronization between related tables
 * to ensure consistency and integrity across the system.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Data_Sync {

    /**
     * Synchronize all related data
     *
     * @since    1.0.0
     * @return   array Synchronization results
     */
    public function synchronize_all_data() {
        $results = array();
        
        $results['sla_tracking'] = $this->sync_sla_tracking_data();
        $results['response_metrics'] = $this->sync_response_metrics();
        $results['ticket_counters'] = $this->sync_ticket_counters();
        $results['rating_aggregates'] = $this->sync_rating_aggregates();
        $results['escalation_flags'] = $this->sync_escalation_flags();
        
        return $results;
    }

    /**
     * Synchronize SLA tracking data with ticket updates
     *
     * @since    1.0.0
     * @return   array Sync results
     */
    public function sync_sla_tracking_data() {
        global $wpdb;
        
        $sla_table = $wpdb->prefix . 'mets_sla_tracking';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        
        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$sla_table'") !== $sla_table ||
            $wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            return array(
                'success' => true, // Don't fail when tables don't exist - this is expected
                'message' => 'Tables do not exist yet - synchronization will be available after setup',
                'total_operations' => 0
            );
        }

        $sync_results = array();

        // 1. Update first_response_at from tickets table
        $response_updates = $wpdb->query("
            UPDATE $sla_table sla
            JOIN $tickets_table t ON sla.ticket_id = t.id
            SET sla.first_response_at = t.first_response_at
            WHERE t.first_response_at IS NOT NULL 
            AND (sla.first_response_at IS NULL OR sla.first_response_at != t.first_response_at)
        ");

        $sync_results['response_updates'] = $response_updates;

        // 2. Update resolved_at from tickets table
        $resolution_updates = $wpdb->query("
            UPDATE $sla_table sla
            JOIN $tickets_table t ON sla.ticket_id = t.id
            SET sla.resolved_at = t.resolved_at
            WHERE t.resolved_at IS NOT NULL 
            AND (sla.resolved_at IS NULL OR sla.resolved_at != t.resolved_at)
        ");

        $sync_results['resolution_updates'] = $resolution_updates;

        // 3. Update SLA compliance flags
        $compliance_updates = $wpdb->query("
            UPDATE $sla_table 
            SET response_sla_met = CASE 
                WHEN first_response_at IS NOT NULL AND response_due_at IS NOT NULL THEN
                    CASE WHEN first_response_at <= response_due_at THEN 1 ELSE 0 END
                ELSE response_sla_met
            END,
            resolution_sla_met = CASE 
                WHEN resolved_at IS NOT NULL AND resolution_due_at IS NOT NULL THEN
                    CASE WHEN resolved_at <= resolution_due_at THEN 1 ELSE 0 END
                ELSE resolution_sla_met
            END
        ");

        $sync_results['compliance_updates'] = $compliance_updates;

        // 4. Calculate breach durations
        $breach_updates = $wpdb->query("
            UPDATE $sla_table 
            SET response_breach_duration = CASE 
                WHEN first_response_at IS NOT NULL AND response_due_at IS NOT NULL 
                AND first_response_at > response_due_at THEN
                    TIMESTAMPDIFF(MINUTE, response_due_at, first_response_at)
                ELSE 0
            END,
            resolution_breach_duration = CASE 
                WHEN resolved_at IS NOT NULL AND resolution_due_at IS NOT NULL 
                AND resolved_at > resolution_due_at THEN
                    TIMESTAMPDIFF(MINUTE, resolution_due_at, resolved_at)
                ELSE 0
            END
        ");

        $sync_results['breach_updates'] = $breach_updates;

        $sync_results['success'] = true;
        $sync_results['total_operations'] = $response_updates + $resolution_updates + $compliance_updates + $breach_updates;

        return $sync_results;
    }

    /**
     * Synchronize response metrics
     *
     * @since    1.0.0
     * @return   array Sync results
     */
    public function sync_response_metrics() {
        global $wpdb;
        
        $metrics_table = $wpdb->prefix . 'mets_response_metrics';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        
        // Check if tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$metrics_table'") !== $metrics_table) {
            return array('success' => false, 'message' => 'Response metrics table does not exist');
        }

        $sync_results = array();

        // Calculate actual response times from ticket data
        $response_time_updates = $wpdb->query("
            INSERT INTO $metrics_table (ticket_id, response_time, agent_id, created_at)
            SELECT 
                t.id as ticket_id,
                TIMESTAMPDIFF(MINUTE, t.created_at, t.first_response_at) as response_time,
                t.assigned_to as agent_id,
                t.first_response_at as created_at
            FROM $tickets_table t
            LEFT JOIN $metrics_table m ON t.id = m.ticket_id
            WHERE t.first_response_at IS NOT NULL 
            AND m.id IS NULL
            AND TIMESTAMPDIFF(MINUTE, t.created_at, t.first_response_at) > 0
            ON DUPLICATE KEY UPDATE
                response_time = VALUES(response_time),
                agent_id = VALUES(agent_id)
        ");

        $sync_results['response_time_updates'] = $response_time_updates;

        // Clean up invalid response times (negative or zero)
        $cleanup_invalid = $wpdb->query("
            DELETE FROM $metrics_table 
            WHERE response_time <= 0 OR response_time IS NULL
        ");

        $sync_results['invalid_cleanup'] = $cleanup_invalid;
        $sync_results['success'] = true;

        return $sync_results;
    }

    /**
     * Synchronize ticket counters and statistics
     *
     * @since    1.0.0
     * @return   array Sync results
     */
    public function sync_ticket_counters() {
        global $wpdb;
        
        $sync_results = array();

        // Update entity ticket counts (if entity stats table exists)
        $entity_stats_table = $wpdb->prefix . 'mets_entity_stats';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$entity_stats_table'") === $entity_stats_table) {
            $entity_count_updates = $wpdb->query("
                INSERT INTO $entity_stats_table (entity_id, total_tickets, open_tickets, resolved_tickets, last_updated)
                SELECT 
                    entity_id,
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status IN ('new', 'open', 'in_progress', 'pending') THEN 1 ELSE 0 END) as open_tickets,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                    NOW() as last_updated
                FROM {$wpdb->prefix}mets_tickets
                GROUP BY entity_id
                ON DUPLICATE KEY UPDATE
                    total_tickets = VALUES(total_tickets),
                    open_tickets = VALUES(open_tickets),
                    resolved_tickets = VALUES(resolved_tickets),
                    last_updated = VALUES(last_updated)
            ");
            
            $sync_results['entity_stats_updates'] = $entity_count_updates;
        }

        // Update user workload statistics
        $user_workload = $wpdb->get_results("
            SELECT 
                assigned_to as user_id,
                COUNT(*) as active_tickets,
                AVG(CASE 
                    WHEN priority = 'low' THEN 1
                    WHEN priority = 'normal' THEN 2
                    WHEN priority = 'high' THEN 3
                    WHEN priority = 'urgent' THEN 4
                    WHEN priority = 'critical' THEN 5
                    ELSE 2
                END) as avg_priority_score
            FROM {$wpdb->prefix}mets_tickets
            WHERE assigned_to IS NOT NULL 
            AND status IN ('new', 'open', 'in_progress', 'pending')
            GROUP BY assigned_to
        ");

        // Store workload data in user meta
        $workload_updates = 0;
        foreach ($user_workload as $workload) {
            update_user_meta($workload->user_id, 'mets_active_tickets', $workload->active_tickets);
            update_user_meta($workload->user_id, 'mets_avg_priority_score', round($workload->avg_priority_score, 2));
            $workload_updates++;
        }

        $sync_results['workload_updates'] = $workload_updates;
        $sync_results['success'] = true;

        return $sync_results;
    }

    /**
     * Synchronize rating aggregates
     *
     * @since    1.0.0
     * @return   array Sync results
     */
    public function sync_rating_aggregates() {
        global $wpdb;
        
        $ratings_table = $wpdb->prefix . 'mets_ticket_ratings';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$ratings_table'") !== $ratings_table) {
            return array('success' => false, 'message' => 'Ratings table does not exist');
        }

        $sync_results = array();

        // Calculate agent rating averages
        $agent_ratings = $wpdb->get_results("
            SELECT 
                t.assigned_to as agent_id,
                AVG(r.rating) as avg_rating,
                COUNT(r.rating) as total_ratings,
                SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) as positive_ratings
            FROM $ratings_table r
            JOIN $tickets_table t ON r.ticket_id = t.id
            WHERE t.assigned_to IS NOT NULL
            GROUP BY t.assigned_to
        ");

        // Store rating data in user meta
        $rating_updates = 0;
        foreach ($agent_ratings as $rating) {
            $satisfaction_rate = ($rating->total_ratings > 0) ? 
                round(($rating->positive_ratings / $rating->total_ratings) * 100, 1) : 0;
                
            update_user_meta($rating->agent_id, 'mets_avg_rating', round($rating->avg_rating, 2));
            update_user_meta($rating->agent_id, 'mets_total_ratings', $rating->total_ratings);
            update_user_meta($rating->agent_id, 'mets_satisfaction_rate', $satisfaction_rate);
            $rating_updates++;
        }

        // Calculate entity rating averages
        $entity_ratings = $wpdb->get_results("
            SELECT 
                t.entity_id,
                AVG(r.rating) as avg_rating,
                COUNT(r.rating) as total_ratings
            FROM $ratings_table r
            JOIN $tickets_table t ON r.ticket_id = t.id
            GROUP BY t.entity_id
        ");

        $entity_rating_updates = 0;
        foreach ($entity_ratings as $rating) {
            // Store in options table with entity prefix
            update_option("mets_entity_{$rating->entity_id}_avg_rating", round($rating->avg_rating, 2));
            update_option("mets_entity_{$rating->entity_id}_total_ratings", $rating->total_ratings);
            $entity_rating_updates++;
        }

        $sync_results['agent_rating_updates'] = $rating_updates;
        $sync_results['entity_rating_updates'] = $entity_rating_updates;
        $sync_results['success'] = true;

        return $sync_results;
    }

    /**
     * Synchronize escalation flags
     *
     * @since    1.0.0
     * @return   array Sync results
     */
    public function sync_escalation_flags() {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $sla_table = $wpdb->prefix . 'mets_sla_tracking';
        
        $sync_results = array();

        // Mark tickets for escalation based on SLA breach
        $escalation_updates = $wpdb->query("
            UPDATE $tickets_table t
            LEFT JOIN $sla_table sla ON t.id = sla.ticket_id
            SET t.status = 'escalated'
            WHERE t.status NOT IN ('resolved', 'closed', 'cancelled')
            AND (
                (sla.response_due_at IS NOT NULL AND NOW() > sla.response_due_at AND t.first_response_at IS NULL)
                OR (sla.resolution_due_at IS NOT NULL AND NOW() > sla.resolution_due_at AND t.resolved_at IS NULL)
            )
        ");

        // Update escalation triggered flag in SLA tracking
        $sla_escalation_updates = $wpdb->query("
            UPDATE $sla_table sla
            JOIN $tickets_table t ON sla.ticket_id = t.id
            SET sla.escalation_triggered = 1
            WHERE t.status = 'escalated'
            AND sla.escalation_triggered = 0
        ");

        // Log escalated tickets for notification
        $escalated_tickets = $wpdb->get_results("
            SELECT t.id, t.ticket_number, t.subject, t.assigned_to, t.entity_id
            FROM $tickets_table t
            WHERE t.status = 'escalated'
            AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        foreach ($escalated_tickets as $ticket) {
            error_log("METS Data Sync: Ticket {$ticket->ticket_number} escalated due to SLA breach");
            
            // Trigger escalation notification (hook for other systems)
            do_action('mets_ticket_escalated', $ticket);
        }

        $sync_results['escalation_updates'] = $escalation_updates;
        $sync_results['sla_escalation_updates'] = $sla_escalation_updates;
        $sync_results['escalated_count'] = count($escalated_tickets);
        $sync_results['success'] = true;

        return $sync_results;
    }

    /**
     * Repair data inconsistencies
     *
     * @since    1.0.0
     * @return   array Repair results
     */
    public function repair_data_inconsistencies() {
        global $wpdb;
        
        $repair_results = array();

        // 1. Fix orphaned ticket replies (tickets that don't exist)
        $orphaned_replies = $wpdb->query("
            DELETE r FROM {$wpdb->prefix}mets_ticket_replies r
            LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
            WHERE t.id IS NULL
        ");

        $repair_results['orphaned_replies_cleaned'] = $orphaned_replies;

        // 2. Fix orphaned attachments
        $orphaned_attachments = $wpdb->query("
            DELETE a FROM {$wpdb->prefix}mets_attachments a
            LEFT JOIN {$wpdb->prefix}mets_tickets t ON a.ticket_id = t.id
            WHERE t.id IS NULL
        ");

        $repair_results['orphaned_attachments_cleaned'] = $orphaned_attachments;

        // 3. Fix orphaned SLA tracking records
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_sla_tracking'") === $wpdb->prefix . 'mets_sla_tracking') {
            $orphaned_sla = $wpdb->query("
                DELETE sla FROM {$wpdb->prefix}mets_sla_tracking sla
                LEFT JOIN {$wpdb->prefix}mets_tickets t ON sla.ticket_id = t.id
                WHERE t.id IS NULL
            ");

            $repair_results['orphaned_sla_cleaned'] = $orphaned_sla;
        }

        // 4. Fix orphaned ratings
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_ticket_ratings'") === $wpdb->prefix . 'mets_ticket_ratings') {
            $orphaned_ratings = $wpdb->query("
                DELETE r FROM {$wpdb->prefix}mets_ticket_ratings r
                LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                WHERE t.id IS NULL
            ");

            $repair_results['orphaned_ratings_cleaned'] = $orphaned_ratings;
        }

        // 5. Fix tickets assigned to non-existent users
        $invalid_assignments = $wpdb->query("
            UPDATE {$wpdb->prefix}mets_tickets t
            LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
            SET t.assigned_to = NULL
            WHERE t.assigned_to IS NOT NULL AND u.ID IS NULL
        ");

        $repair_results['invalid_assignments_fixed'] = $invalid_assignments;

        // 6. Fix tickets with invalid entity references
        $invalid_entities = $wpdb->query("
            DELETE t FROM {$wpdb->prefix}mets_tickets t
            LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
            WHERE e.id IS NULL
        ");

        $repair_results['invalid_entity_tickets_cleaned'] = $invalid_entities;

        $repair_results['success'] = true;
        $repair_results['total_repairs'] = array_sum(array_filter($repair_results, 'is_numeric'));

        return $repair_results;
    }

    /**
     * Schedule automatic synchronization
     *
     * @since    1.0.0
     */
    public function schedule_auto_sync() {
        // Schedule hourly sync for critical data
        if (!wp_next_scheduled('mets_hourly_data_sync')) {
            wp_schedule_event(time(), 'hourly', 'mets_hourly_data_sync');
        }

        // Schedule daily comprehensive sync
        if (!wp_next_scheduled('mets_daily_data_sync')) {
            wp_schedule_event(time(), 'daily', 'mets_daily_data_sync');
        }

        // Schedule weekly data repair
        if (!wp_next_scheduled('mets_weekly_data_repair')) {
            wp_schedule_event(time(), 'weekly', 'mets_weekly_data_repair');
        }
    }

    /**
     * Get data synchronization status
     *
     * @since    1.0.0
     * @return   array Status information
     */
    public function get_sync_status() {
        global $wpdb;
        
        $status = array();

        // Check last sync times
        $status['last_hourly_sync'] = get_option('mets_last_hourly_sync', 'Never');
        $status['last_daily_sync'] = get_option('mets_last_daily_sync', 'Never');
        $status['last_repair'] = get_option('mets_last_data_repair', 'Never');

        // Check if required tables exist before checking for inconsistencies
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $sla_table = $wpdb->prefix . 'mets_sla_tracking';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table ||
            $wpdb->get_var("SHOW TABLES LIKE '$replies_table'") !== $replies_table) {
            $status['orphaned_replies'] = 0;
            $status['missing_sla_tracking'] = 0;
            $status['needs_repair'] = false;
            $status['message'] = 'Tables do not exist yet - setup required';
        } else {
            // Check for potential inconsistencies
            $orphaned_replies = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}mets_ticket_replies r
                LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                WHERE t.id IS NULL
            ");

            $status['orphaned_replies'] = $orphaned_replies;

            if ($wpdb->get_var("SHOW TABLES LIKE '$sla_table'") === $sla_table) {
                $missing_sla_tracking = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets t
                    LEFT JOIN {$wpdb->prefix}mets_sla_tracking sla ON t.id = sla.ticket_id
                    WHERE sla.id IS NULL AND t.status NOT IN ('closed', 'cancelled')
                ");
                $status['missing_sla_tracking'] = $missing_sla_tracking;
            } else {
                $status['missing_sla_tracking'] = 0;
            }

            $status['needs_repair'] = ($orphaned_replies > 0 || $status['missing_sla_tracking'] > 0);
        }
        
        return $status;
    }
}