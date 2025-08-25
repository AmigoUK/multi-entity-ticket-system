<?php
/**
 * Data cleanup and repair utilities
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

/**
 * Data cleanup and repair utilities class.
 *
 * This class provides safe methods to clean up invalid data
 * and repair database inconsistencies before applying constraints.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Data_Cleanup {

    /**
     * Run comprehensive data cleanup
     *
     * @since    1.0.0
     * @param    bool    $dry_run    Whether to simulate changes without applying them
     * @return   array   Cleanup results
     */
    public function run_comprehensive_cleanup($dry_run = false) {
        $results = array(
            'timestamp' => current_time('mysql'),
            'dry_run' => $dry_run,
            'operations' => array()
        );

        try {
            // Step 1: Fix invalid email formats
            $results['operations']['email_cleanup'] = $this->fix_invalid_emails($dry_run);

            // Step 2: Remove orphaned records
            $results['operations']['orphaned_cleanup'] = $this->remove_orphaned_records($dry_run);

            // Step 3: Fix data relationships
            $results['operations']['relationship_repair'] = $this->repair_data_relationships($dry_run);

            // Step 4: Validate data consistency
            $results['operations']['consistency_check'] = $this->validate_data_consistency();

            $results['success'] = true;
            $results['summary'] = $this->generate_cleanup_summary($results['operations']);

        } catch (Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            error_log("METS Data Cleanup: Error during cleanup - " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Fix invalid email formats
     *
     * @since    1.0.0
     * @param    bool    $dry_run    Whether to simulate changes
     * @return   array   Email cleanup results
     */
    public function fix_invalid_emails($dry_run = false) {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        
        // Find invalid emails
        $invalid_emails = $wpdb->get_results(
            "SELECT id, customer_email, customer_name, ticket_number 
             FROM $tickets_table 
             WHERE customer_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'
             AND customer_email IS NOT NULL 
             AND customer_email != ''",
            ARRAY_A
        );

        $results = array(
            'found_invalid' => count($invalid_emails),
            'invalid_emails' => $invalid_emails,
            'fixes_applied' => array(),
            'manual_review_needed' => array()
        );

        if (!$dry_run && count($invalid_emails) > 0) {
            foreach ($invalid_emails as $ticket) {
                $original_email = $ticket['customer_email'];
                $fixed_email = $this->attempt_email_fix($original_email);
                
                if ($fixed_email && $fixed_email !== $original_email) {
                    // Apply automatic fix
                    $updated = $wpdb->update(
                        $tickets_table,
                        array('customer_email' => $fixed_email),
                        array('id' => $ticket['id']),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($updated) {
                        $results['fixes_applied'][] = array(
                            'ticket_id' => $ticket['id'],
                            'ticket_number' => $ticket['ticket_number'],
                            'original' => $original_email,
                            'fixed' => $fixed_email
                        );
                    }
                } else {
                    // Mark for manual review
                    $results['manual_review_needed'][] = array(
                        'ticket_id' => $ticket['id'],
                        'ticket_number' => $ticket['ticket_number'],
                        'customer_name' => $ticket['customer_name'],
                        'invalid_email' => $original_email,
                        'suggested_action' => 'Contact customer to verify correct email address'
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Attempt to automatically fix common email format issues
     *
     * @since    1.0.0
     * @param    string $email Original email
     * @return   string|false Fixed email or false if cannot be fixed
     */
    private function attempt_email_fix($email) {
        if (empty($email)) {
            return false;
        }

        $email = trim(strtolower($email));

        // Common fixes
        $fixes = array(
            // Remove extra spaces
            '/\s+/' => '',
            // Fix common domain typos
            '/\.co$/' => '.com',
            '/\.cm$/' => '.com',
            '/gmail\.co$/' => 'gmail.com',
            '/yahoo\.co$/' => 'yahoo.com',
            // Fix missing @ symbol patterns
            '/([a-z0-9._%+-]+)gmail\.com/' => '$1@gmail.com',
            '/([a-z0-9._%+-]+)yahoo\.com/' => '$1@yahoo.com',
            '/([a-z0-9._%+-]+)hotmail\.com/' => '$1@hotmail.com',
            // Fix double @ symbols
            '/@@/' => '@',
            // Fix common character mistakes
            '/\[at\]/' => '@',
            '/\(at\)/' => '@',
        );

        $fixed_email = $email;
        foreach ($fixes as $pattern => $replacement) {
            $fixed_email = preg_replace($pattern, $replacement, $fixed_email);
        }

        // Validate the fixed email
        if (filter_var($fixed_email, FILTER_VALIDATE_EMAIL)) {
            return $fixed_email;
        }

        return false;
    }

    /**
     * Remove orphaned records
     *
     * @since    1.0.0
     * @param    bool    $dry_run    Whether to simulate changes
     * @return   array   Orphaned records cleanup results
     */
    public function remove_orphaned_records($dry_run = false) {
        global $wpdb;
        
        $results = array(
            'orphaned_replies' => 0,
            'orphaned_attachments' => 0,
            'orphaned_ratings' => 0,
            'orphaned_sla_tracking' => 0,
            'orphaned_response_metrics' => 0
        );

        // 1. Find orphaned ticket replies
        $orphaned_replies = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_ticket_replies r
             LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
             WHERE t.id IS NULL"
        );

        if ($orphaned_replies > 0) {
            $results['orphaned_replies'] = $orphaned_replies;
            if (!$dry_run) {
                $wpdb->query(
                    "DELETE r FROM {$wpdb->prefix}mets_ticket_replies r
                     LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                     WHERE t.id IS NULL"
                );
            }
        }

        // 2. Find orphaned attachments
        $orphaned_attachments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_attachments a
             LEFT JOIN {$wpdb->prefix}mets_tickets t ON a.ticket_id = t.id
             WHERE t.id IS NULL"
        );

        if ($orphaned_attachments > 0) {
            $results['orphaned_attachments'] = $orphaned_attachments;
            if (!$dry_run) {
                $wpdb->query(
                    "DELETE a FROM {$wpdb->prefix}mets_attachments a
                     LEFT JOIN {$wpdb->prefix}mets_tickets t ON a.ticket_id = t.id
                     WHERE t.id IS NULL"
                );
            }
        }

        // 3. Find orphaned ratings
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_ticket_ratings'") === $wpdb->prefix . 'mets_ticket_ratings') {
            $orphaned_ratings = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_ticket_ratings r
                 LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                 WHERE t.id IS NULL"
            );

            if ($orphaned_ratings > 0) {
                $results['orphaned_ratings'] = $orphaned_ratings;
                if (!$dry_run) {
                    $wpdb->query(
                        "DELETE r FROM {$wpdb->prefix}mets_ticket_ratings r
                         LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
                         WHERE t.id IS NULL"
                    );
                }
            }
        }

        // 4. Find orphaned SLA tracking
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_sla_tracking'") === $wpdb->prefix . 'mets_sla_tracking') {
            $orphaned_sla = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_sla_tracking s
                 LEFT JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
                 WHERE t.id IS NULL"
            );

            if ($orphaned_sla > 0) {
                $results['orphaned_sla_tracking'] = $orphaned_sla;
                if (!$dry_run) {
                    $wpdb->query(
                        "DELETE s FROM {$wpdb->prefix}mets_sla_tracking s
                         LEFT JOIN {$wpdb->prefix}mets_tickets t ON s.ticket_id = t.id
                         WHERE t.id IS NULL"
                    );
                }
            }
        }

        // 5. Find orphaned response metrics
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_response_metrics'") === $wpdb->prefix . 'mets_response_metrics') {
            $orphaned_metrics = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_response_metrics m
                 LEFT JOIN {$wpdb->prefix}mets_tickets t ON m.ticket_id = t.id
                 WHERE t.id IS NULL"
            );

            if ($orphaned_metrics > 0) {
                $results['orphaned_response_metrics'] = $orphaned_metrics;
                if (!$dry_run) {
                    $wpdb->query(
                        "DELETE m FROM {$wpdb->prefix}mets_response_metrics m
                         LEFT JOIN {$wpdb->prefix}mets_tickets t ON m.ticket_id = t.id
                         WHERE t.id IS NULL"
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Repair data relationships
     *
     * @since    1.0.0
     * @param    bool    $dry_run    Whether to simulate changes
     * @return   array   Relationship repair results
     */
    public function repair_data_relationships($dry_run = false) {
        global $wpdb;
        
        $results = array(
            'invalid_user_assignments' => 0,
            'invalid_entity_references' => 0,
            'missing_sla_tracking' => 0,
            'inconsistent_dates' => 0
        );

        // 1. Fix tickets assigned to non-existent users
        $invalid_assignments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets t
             LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
             WHERE t.assigned_to IS NOT NULL AND u.ID IS NULL"
        );

        if ($invalid_assignments > 0) {
            $results['invalid_user_assignments'] = $invalid_assignments;
            if (!$dry_run) {
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}mets_tickets t
                     LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
                     SET t.assigned_to = NULL
                     WHERE t.assigned_to IS NOT NULL AND u.ID IS NULL"
                );
            }
        }

        // 2. Fix tickets with invalid entity references
        $invalid_entities = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets t
             LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
             WHERE e.id IS NULL"
        );

        if ($invalid_entities > 0) {
            $results['invalid_entity_references'] = $invalid_entities;
            // Note: We don't auto-delete these as they might contain important data
            // Instead, we'll report them for manual review
        }

        // 3. Create missing SLA tracking records for active tickets
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_sla_tracking'") === $wpdb->prefix . 'mets_sla_tracking') {
            $missing_sla = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets t
                 LEFT JOIN {$wpdb->prefix}mets_sla_tracking s ON t.id = s.ticket_id
                 WHERE s.id IS NULL AND t.status NOT IN ('closed', 'cancelled')"
            );

            if ($missing_sla > 0) {
                $results['missing_sla_tracking'] = $missing_sla;
                if (!$dry_run) {
                    // Create basic SLA tracking records for tickets missing them
                    $wpdb->query(
                        "INSERT INTO {$wpdb->prefix}mets_sla_tracking (ticket_id, created_at)
                         SELECT t.id, t.created_at
                         FROM {$wpdb->prefix}mets_tickets t
                         LEFT JOIN {$wpdb->prefix}mets_sla_tracking s ON t.id = s.ticket_id
                         WHERE s.id IS NULL AND t.status NOT IN ('closed', 'cancelled')"
                    );
                }
            }
        }

        // 4. Fix inconsistent date sequences
        $inconsistent_dates = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets
             WHERE (first_response_at IS NOT NULL AND first_response_at < created_at)
                OR (resolved_at IS NOT NULL AND resolved_at < created_at)
                OR (closed_at IS NOT NULL AND resolved_at IS NOT NULL AND closed_at < resolved_at)"
        );

        if ($inconsistent_dates > 0) {
            $results['inconsistent_dates'] = $inconsistent_dates;
            if (!$dry_run) {
                // Fix obviously wrong dates by setting them to NULL for manual review
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}mets_tickets 
                     SET first_response_at = NULL 
                     WHERE first_response_at IS NOT NULL AND first_response_at < created_at"
                );
                
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}mets_tickets 
                     SET resolved_at = NULL 
                     WHERE resolved_at IS NOT NULL AND resolved_at < created_at"
                );
                
                $wpdb->query(
                    "UPDATE {$wpdb->prefix}mets_tickets 
                     SET closed_at = NULL 
                     WHERE closed_at IS NOT NULL AND resolved_at IS NOT NULL AND closed_at < resolved_at"
                );
            }
        }

        return $results;
    }

    /**
     * Validate data consistency after cleanup
     *
     * @since    1.0.0
     * @return   array   Validation results
     */
    public function validate_data_consistency() {
        global $wpdb;
        
        $validation = array(
            'email_formats' => array(),
            'orphaned_records' => array(),
            'data_relationships' => array(),
            'overall_status' => 'unknown'
        );

        // Check email formats
        $invalid_emails = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
             WHERE customer_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'
             AND customer_email IS NOT NULL 
             AND customer_email != ''"
        );

        $validation['email_formats'] = array(
            'valid' => ($invalid_emails == 0),
            'invalid_count' => $invalid_emails,
            'status' => ($invalid_emails == 0) ? 'pass' : 'fail'
        );

        // Check for orphaned records
        $orphaned_count = 0;
        $tables_to_check = array(
            'mets_ticket_replies' => 'ticket_id',
            'mets_attachments' => 'ticket_id',
            'mets_ticket_ratings' => 'ticket_id',
            'mets_sla_tracking' => 'ticket_id'
        );

        foreach ($tables_to_check as $table => $foreign_key) {
            $full_table = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table) {
                $count = $wpdb->get_var(
                    "SELECT COUNT(*) FROM $full_table r
                     LEFT JOIN {$wpdb->prefix}mets_tickets t ON r.$foreign_key = t.id
                     WHERE t.id IS NULL"
                );
                $orphaned_count += $count;
            }
        }

        $validation['orphaned_records'] = array(
            'valid' => ($orphaned_count == 0),
            'orphaned_count' => $orphaned_count,
            'status' => ($orphaned_count == 0) ? 'pass' : 'fail'
        );

        // Check data relationships
        $invalid_relationships = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets t
             LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
             WHERE e.id IS NULL"
        );

        $validation['data_relationships'] = array(
            'valid' => ($invalid_relationships == 0),
            'invalid_count' => $invalid_relationships,
            'status' => ($invalid_relationships == 0) ? 'pass' : 'fail'
        );

        // Overall status
        $all_passed = $validation['email_formats']['valid'] && 
                     $validation['orphaned_records']['valid'] && 
                     $validation['data_relationships']['valid'];

        $validation['overall_status'] = $all_passed ? 'pass' : 'fail';

        return $validation;
    }

    /**
     * Generate cleanup summary
     *
     * @since    1.0.0
     * @param    array $operations All cleanup operations
     * @return   array Summary information
     */
    private function generate_cleanup_summary($operations) {
        $summary = array(
            'emails_fixed' => 0,
            'orphaned_removed' => 0,
            'relationships_repaired' => 0,
            'manual_review_needed' => 0,
            'recommendations' => array()
        );

        // Count email fixes
        if (isset($operations['email_cleanup'])) {
            $summary['emails_fixed'] = count($operations['email_cleanup']['fixes_applied']);
            $summary['manual_review_needed'] = count($operations['email_cleanup']['manual_review_needed']);
        }

        // Count orphaned records removed
        if (isset($operations['orphaned_cleanup'])) {
            $orphaned = $operations['orphaned_cleanup'];
            $summary['orphaned_removed'] = $orphaned['orphaned_replies'] + 
                                          $orphaned['orphaned_attachments'] + 
                                          $orphaned['orphaned_ratings'] + 
                                          $orphaned['orphaned_sla_tracking'] + 
                                          $orphaned['orphaned_response_metrics'];
        }

        // Count relationship repairs
        if (isset($operations['relationship_repair'])) {
            $repair = $operations['relationship_repair'];
            $summary['relationships_repaired'] = $repair['invalid_user_assignments'] + 
                                                $repair['missing_sla_tracking'] + 
                                                $repair['inconsistent_dates'];
        }

        // Generate recommendations
        if ($summary['manual_review_needed'] > 0) {
            $summary['recommendations'][] = "Review {$summary['manual_review_needed']} email addresses that need manual correction";
        }

        if (isset($operations['relationship_repair']['invalid_entity_references']) && 
            $operations['relationship_repair']['invalid_entity_references'] > 0) {
            $summary['recommendations'][] = "Review tickets with invalid entity references - these may need to be reassigned or archived";
        }

        return $summary;
    }

    /**
     * Get cleanup recommendations
     *
     * @since    1.0.0
     * @return   array Recommended cleanup actions
     */
    public function get_cleanup_recommendations() {
        $dry_run_results = $this->run_comprehensive_cleanup(true);
        
        $recommendations = array(
            'priority' => 'medium',
            'actions' => array(),
            'estimated_time' => '5-10 minutes',
            'safety_level' => 'safe'
        );

        if ($dry_run_results['success']) {
            $ops = $dry_run_results['operations'];
            
            // High priority recommendations
            if (isset($ops['email_cleanup']['found_invalid']) && $ops['email_cleanup']['found_invalid'] > 0) {
                $recommendations['priority'] = 'high';
                $recommendations['actions'][] = "Fix {$ops['email_cleanup']['found_invalid']} invalid email format(s)";
            }

            // Medium priority recommendations
            $orphaned_total = 0;
            if (isset($ops['orphaned_cleanup'])) {
                foreach ($ops['orphaned_cleanup'] as $count) {
                    $orphaned_total += is_numeric($count) ? $count : 0;
                }
            }
            
            if ($orphaned_total > 0) {
                $recommendations['actions'][] = "Clean up $orphaned_total orphaned record(s)";
            }

            // Relationship repairs
            if (isset($ops['relationship_repair'])) {
                $repair_total = 0;
                foreach ($ops['relationship_repair'] as $count) {
                    $repair_total += is_numeric($count) ? $count : 0;
                }
                
                if ($repair_total > 0) {
                    $recommendations['actions'][] = "Repair $repair_total data relationship issue(s)";
                }
            }
        }

        if (empty($recommendations['actions'])) {
            $recommendations['actions'][] = "No cleanup needed - database is clean";
            $recommendations['priority'] = 'low';
        }

        return $recommendations;
    }
}