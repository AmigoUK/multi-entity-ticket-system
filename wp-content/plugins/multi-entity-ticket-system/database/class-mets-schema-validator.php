<?php
/**
 * Database schema validation and constraint management
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

/**
 * Database schema validation and constraint management class.
 *
 * This class handles database schema validation, constraint enforcement,
 * and data integrity improvements for the Multi-Entity Ticket System plugin.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Schema_Validator {

    /**
     * Apply all database constraints and validations
     *
     * @since    1.0.0
     */
    public function apply_all_constraints() {
        $this->add_rating_constraints();
        $this->add_status_constraints();
        $this->add_priority_constraints();
        $this->add_sla_constraints();
        $this->add_response_time_constraints();
        $this->add_email_validation_constraints();
        $this->add_date_consistency_constraints();
        $this->optimize_indexes();
    }

    /**
     * Add rating constraints (1-5 stars)
     *
     * @since    1.0.0
     */
    private function add_rating_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_ticket_ratings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("METS Schema Validator: Table $table_name does not exist");
            return false;
        }

        // Add check constraint for rating (1-5)
        $constraint_name = 'chk_rating_range';
        
        // First, check if constraint already exists
        $existing_constraint = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s 
                AND CONSTRAINT_TYPE = 'CHECK'",
                DB_NAME,
                $table_name,
                $constraint_name
            )
        );

        if (!$existing_constraint) {
            $sql = "ALTER TABLE $table_name ADD CONSTRAINT $constraint_name CHECK (rating >= 1 AND rating <= 5)";
            
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("METS Schema Validator: Failed to add rating constraint - " . $wpdb->last_error);
            } else {
                error_log("METS Schema Validator: Rating constraint added successfully");
            }
        }

        // Ensure rating column is properly typed
        $alter_sql = "ALTER TABLE $table_name MODIFY COLUMN rating TINYINT(1) UNSIGNED NOT NULL DEFAULT 5 
                      COMMENT 'Rating from 1 (poor) to 5 (excellent)'";
        
        $wpdb->query($alter_sql);
    }

    /**
     * Add status constraints
     *
     * @since    1.0.0
     */
    private function add_status_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_tickets';
        
        // Valid status values
        $valid_statuses = array(
            'new', 'open', 'pending', 'in_progress', 'waiting_customer', 
            'escalated', 'resolved', 'closed', 'cancelled'
        );
        
        $status_list = "'" . implode("','", $valid_statuses) . "'";
        
        // Modify status column to use ENUM
        $sql = "ALTER TABLE $table_name MODIFY COLUMN status 
                ENUM($status_list) NOT NULL DEFAULT 'new' 
                COMMENT 'Ticket status with predefined values'";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log("METS Schema Validator: Failed to add status constraint - " . $wpdb->last_error);
        } else {
            error_log("METS Schema Validator: Status constraint added successfully");
        }
    }

    /**
     * Add priority constraints
     *
     * @since    1.0.0
     */
    private function add_priority_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_tickets';
        
        // Valid priority values
        $valid_priorities = array('low', 'normal', 'high', 'urgent', 'critical');
        $priority_list = "'" . implode("','", $valid_priorities) . "'";
        
        // Modify priority column to use ENUM
        $sql = "ALTER TABLE $table_name MODIFY COLUMN priority 
                ENUM($priority_list) NOT NULL DEFAULT 'normal' 
                COMMENT 'Ticket priority level'";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log("METS Schema Validator: Failed to add priority constraint - " . $wpdb->last_error);
        } else {
            error_log("METS Schema Validator: Priority constraint added successfully");
        }
    }

    /**
     * Add SLA constraints
     *
     * @since    1.0.0
     */
    private function add_sla_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_sla_tracking';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("METS Schema Validator: Table $table_name does not exist");
            return false;
        }

        // Add constraints to ensure breach durations are non-negative
        $constraints = array(
            'chk_response_breach_duration' => 'response_breach_duration >= 0',
            'chk_resolution_breach_duration' => 'resolution_breach_duration >= 0'
        );

        foreach ($constraints as $constraint_name => $condition) {
            // Check if constraint already exists
            $existing_constraint = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = %s 
                    AND TABLE_NAME = %s 
                    AND CONSTRAINT_NAME = %s 
                    AND CONSTRAINT_TYPE = 'CHECK'",
                    DB_NAME,
                    $table_name,
                    $constraint_name
                )
            );

            if (!$existing_constraint) {
                $sql = "ALTER TABLE $table_name ADD CONSTRAINT $constraint_name CHECK ($condition)";
                
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    error_log("METS Schema Validator: Failed to add SLA constraint $constraint_name - " . $wpdb->last_error);
                } else {
                    error_log("METS Schema Validator: SLA constraint $constraint_name added successfully");
                }
            }
        }
    }

    /**
     * Add response time constraints
     *
     * @since    1.0.0
     */
    private function add_response_time_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_response_metrics';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("METS Schema Validator: Table $table_name does not exist");
            return false;
        }

        // Add check constraint for positive response times
        $constraint_name = 'chk_positive_response_time';
        
        $existing_constraint = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s 
                AND CONSTRAINT_TYPE = 'CHECK'",
                DB_NAME,
                $table_name,
                $constraint_name
            )
        );

        if (!$existing_constraint) {
            $sql = "ALTER TABLE $table_name ADD CONSTRAINT $constraint_name 
                    CHECK (response_time > 0 AND response_time IS NOT NULL)";
            
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("METS Schema Validator: Failed to add response time constraint - " . $wpdb->last_error);
            } else {
                error_log("METS Schema Validator: Response time constraint added successfully");
            }
        }
    }

    /**
     * Add email validation constraints
     *
     * @since    1.0.0
     */
    private function add_email_validation_constraints() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_tickets';
        
        // Add check constraint for email format validation
        $constraint_name = 'chk_valid_email_format';
        
        $existing_constraint = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s 
                AND CONSTRAINT_TYPE = 'CHECK'",
                DB_NAME,
                $table_name,
                $constraint_name
            )
        );

        if (!$existing_constraint) {
            // Basic email format validation using MySQL REGEXP
            $sql = "ALTER TABLE $table_name ADD CONSTRAINT $constraint_name 
                    CHECK (customer_email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$')";
            
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("METS Schema Validator: Failed to add email constraint - " . $wpdb->last_error);
            } else {
                error_log("METS Schema Validator: Email validation constraint added successfully");         
            }
        }
    }

    /**
     * Add date consistency constraints
     *
     * @since    1.0.0
     */
    private function add_date_consistency_constraints() {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $sla_table = $wpdb->prefix . 'mets_sla_tracking';
        
        // Tickets table date constraints
        $ticket_constraints = array(
            'chk_first_response_after_creation' => 'first_response_at IS NULL OR first_response_at >= created_at',
            'chk_resolved_after_creation' => 'resolved_at IS NULL OR resolved_at >= created_at',
            'chk_closed_after_resolved' => 'closed_at IS NULL OR resolved_at IS NULL OR closed_at >= resolved_at'
        );

        foreach ($ticket_constraints as $constraint_name => $condition) {
            $existing_constraint = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = %s 
                    AND TABLE_NAME = %s 
                    AND CONSTRAINT_NAME = %s 
                    AND CONSTRAINT_TYPE = 'CHECK'",
                    DB_NAME,
                    $tickets_table,
                    $constraint_name
                )
            );

            if (!$existing_constraint) {
                $sql = "ALTER TABLE $tickets_table ADD CONSTRAINT $constraint_name CHECK ($condition)";
                
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    error_log("METS Schema Validator: Failed to add date constraint $constraint_name - " . $wpdb->last_error);
                } else {
                    error_log("METS Schema Validator: Date constraint $constraint_name added successfully");
                }
            }
        }

        // SLA tracking date constraints
        if ($wpdb->get_var("SHOW TABLES LIKE '$sla_table'") === $sla_table) {
            $sla_constraints = array(
                'chk_first_response_before_due' => 'first_response_at IS NULL OR response_due_at IS NULL OR first_response_at <= response_due_at OR response_sla_met = 0',
                'chk_resolved_before_due' => 'resolved_at IS NULL OR resolution_due_at IS NULL OR resolved_at <= resolution_due_at OR resolution_sla_met = 0'
            );

            foreach ($sla_constraints as $constraint_name => $condition) {
                $existing_constraint = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                        WHERE TABLE_SCHEMA = %s 
                        AND TABLE_NAME = %s 
                        AND CONSTRAINT_NAME = %s 
                        AND CONSTRAINT_TYPE = 'CHECK'",
                        DB_NAME,
                        $sla_table,
                        $constraint_name
                    )
                );

                if (!$existing_constraint) {
                    $sql = "ALTER TABLE $sla_table ADD CONSTRAINT $constraint_name CHECK ($condition)";
                    
                    $result = $wpdb->query($sql);
                    
                    if ($result === false) {
                        error_log("METS Schema Validator: Failed to add SLA date constraint $constraint_name - " . $wpdb->last_error);
                    } else {
                        error_log("METS Schema Validator: SLA date constraint $constraint_name added successfully");
                    }
                }
            }
        }
    }

    /**
     * Optimize database indexes for better performance
     *
     * @since    1.0.0
     */
    private function optimize_indexes() {
        global $wpdb;
        
        $optimizations = array(
            // Tickets table optimizations
            $wpdb->prefix . 'mets_tickets' => array(
                'idx_status_priority_created' => 'status, priority, created_at',
                'idx_assigned_status' => 'assigned_to, status',
                'idx_entity_customer_email' => 'entity_id, customer_email',
                'idx_sla_due_dates' => 'sla_response_due, sla_resolution_due'
            ),
            
            // Ticket replies optimizations
            $wpdb->prefix . 'mets_ticket_replies' => array(
                'idx_ticket_created_type' => 'ticket_id, created_at, user_type',
                'idx_user_created' => 'user_id, created_at'
            ),
            
            // SLA tracking optimizations
            $wpdb->prefix . 'mets_sla_tracking' => array(
                'idx_sla_performance' => 'response_sla_met, resolution_sla_met, created_at',
                'idx_breach_analysis' => 'response_breach_duration, resolution_breach_duration'
            ),
            
            // Ratings optimizations
            $wpdb->prefix . 'mets_ticket_ratings' => array(
                'idx_rating_created' => 'rating, created_at',
                'idx_user_rating' => 'user_id, rating'
            )
        );

        foreach ($optimizations as $table_name => $indexes) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                continue;
            }

            foreach ($indexes as $index_name => $columns) {
                // Check if index already exists
                $existing_index = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT INDEX_NAME 
                        FROM INFORMATION_SCHEMA.STATISTICS 
                        WHERE TABLE_SCHEMA = %s 
                        AND TABLE_NAME = %s 
                        AND INDEX_NAME = %s",
                        DB_NAME,
                        $table_name,
                        $index_name
                    )
                );

                if (!$existing_index) {
                    $sql = "CREATE INDEX $index_name ON $table_name ($columns)";
                    
                    $result = $wpdb->query($sql);
                    
                    if ($result === false) {
                        error_log("METS Schema Validator: Failed to create index $index_name on $table_name - " . $wpdb->last_error);
                    } else {
                        error_log("METS Schema Validator: Index $index_name created successfully on $table_name");
                    }
                }
            }
        }
    }

    /**
     * Validate existing data against new constraints
     *
     * @since    1.0.0
     * @return   array Array of validation results
     */
    public function validate_existing_data() {
        global $wpdb;
        
        $validation_results = array();
        
        // Check if ratings table exists first
        $ratings_table = $wpdb->prefix . 'mets_ticket_ratings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$ratings_table'") !== $ratings_table) {
            $validation_results['ratings'] = array(
                'valid' => true,
                'invalid_count' => 0,
                'message' => 'Ratings table does not exist - will be created during setup'
            );
        } else {
            // Validate ratings (1-5)
            $invalid_ratings = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_ticket_ratings 
                 WHERE rating < 1 OR rating > 5"
            );
            
            $validation_results['ratings'] = array(
                'valid' => ($invalid_ratings == 0),
                'invalid_count' => $invalid_ratings,
                'message' => $invalid_ratings > 0 ? 
                    "Found $invalid_ratings ratings outside 1-5 range" : 
                    'All ratings are valid'
            );
        }

        // Check if tickets table exists first
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            $validation_results['emails'] = array(
                'valid' => true,
                'invalid_count' => 0,
                'message' => 'Tickets table does not exist - will be created during setup'
            );
        } else {
            // Validate email formats
            $invalid_emails = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
                 WHERE customer_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'"
            );
            
            $validation_results['emails'] = array(
                'valid' => ($invalid_emails == 0),
                'invalid_count' => $invalid_emails,
                'message' => $invalid_emails > 0 ? 
                    "Found $invalid_emails invalid email formats" : 
                    'All email formats are valid'
            );
        }

        // Validate date consistency - reuse tickets table check
        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            $validation_results['dates'] = array(
                'valid' => true,
                'invalid_count' => 0,
                'message' => 'Tickets table does not exist - will be created during setup'
            );
        } else {
            $invalid_dates = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
                 WHERE (first_response_at IS NOT NULL AND first_response_at < created_at)
                    OR (resolved_at IS NOT NULL AND resolved_at < created_at)
                    OR (closed_at IS NOT NULL AND resolved_at IS NOT NULL AND closed_at < resolved_at)"
            );
            
            $validation_results['dates'] = array(
                'valid' => ($invalid_dates == 0),
                'invalid_count' => $invalid_dates,
                'message' => $invalid_dates > 0 ? 
                    "Found $invalid_dates tickets with inconsistent dates" : 
                    'All date sequences are valid'
            );
        }

        // Validate response times
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mets_response_metrics'") === $wpdb->prefix . 'mets_response_metrics') {
            $invalid_response_times = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mets_response_metrics 
                 WHERE response_time <= 0 OR response_time IS NULL"
            );
            
            $validation_results['response_times'] = array(
                'valid' => ($invalid_response_times == 0),
                'invalid_count' => $invalid_response_times,
                'message' => $invalid_response_times > 0 ? 
                    "Found $invalid_response_times invalid response times" : 
                    'All response times are valid'
            );
        }

        return $validation_results;
    }

    /**
     * Clean up invalid data based on validation results
     *
     * @since    1.0.0
     * @param    array $validation_results Results from validate_existing_data()
     * @return   array Cleanup results
     */
    public function cleanup_invalid_data($validation_results) {
        global $wpdb;
        
        $cleanup_results = array();
        
        // Fix invalid ratings by setting them to 3 (neutral)
        if (!$validation_results['ratings']['valid']) {
            $updated = $wpdb->query(
                "UPDATE {$wpdb->prefix}mets_ticket_ratings 
                 SET rating = 3 
                 WHERE rating < 1 OR rating > 5"
            );
            
            $cleanup_results['ratings'] = array(
                'cleaned' => true,
                'updated_count' => $updated,
                'message' => "Updated $updated invalid ratings to neutral (3)"
            );
        }

        // Log invalid emails for manual review (don't auto-fix)
        if (!$validation_results['emails']['valid']) {
            $invalid_emails = $wpdb->get_results(
                "SELECT id, customer_email FROM {$wpdb->prefix}mets_tickets 
                 WHERE customer_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'",
                ARRAY_A
            );
            
            error_log("METS Schema Validator: Invalid emails found: " . json_encode($invalid_emails));
            
            $cleanup_results['emails'] = array(
                'cleaned' => false,
                'invalid_count' => count($invalid_emails),
                'message' => "Invalid emails logged for manual review"
            );
        }

        return $cleanup_results;
    }

    /**
     * Get constraint status report
     *
     * @since    1.0.0
     * @return   array Status of all constraints
     */
    public function get_constraint_status() {
        global $wpdb;
        
        $constraints = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME LIKE %s
                ORDER BY TABLE_NAME, CONSTRAINT_TYPE",
                DB_NAME,
                $wpdb->prefix . 'mets_%'
            ),
            ARRAY_A
        );

        $status_report = array();
        
        foreach ($constraints as $constraint) {
            $table = $constraint['TABLE_NAME'];
            $type = $constraint['CONSTRAINT_TYPE'];
            
            if (!isset($status_report[$table])) {
                $status_report[$table] = array();
            }
            
            if (!isset($status_report[$table][$type])) {
                $status_report[$table][$type] = array();
            }
            
            $status_report[$table][$type][] = $constraint['CONSTRAINT_NAME'];
        }

        return $status_report;
    }
}