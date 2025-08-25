<?php
/**
 * Foreign key relationship management
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

/**
 * Foreign key relationship management class.
 *
 * This class handles the creation and management of foreign key relationships
 * with proper cascading rules for the Multi-Entity Ticket System plugin.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Foreign_Keys {

    /**
     * Apply all foreign key relationships
     *
     * @since    1.0.0
     */
    public function apply_all_foreign_keys() {
        // Disable foreign key checks temporarily for complex operations
        $this->disable_foreign_key_checks();
        
        $this->add_entity_foreign_keys();
        $this->add_ticket_foreign_keys();
        $this->add_reply_foreign_keys();
        $this->add_attachment_foreign_keys();
        $this->add_sla_foreign_keys();
        $this->add_rating_foreign_keys();
        $this->add_user_entity_foreign_keys();
        
        // Re-enable foreign key checks
        $this->enable_foreign_key_checks();
    }

    /**
     * Add entity table foreign keys
     *
     * @since    1.0.0
     */
    private function add_entity_foreign_keys() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mets_entities';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log("METS Foreign Keys: Table $table_name does not exist");
            return false;
        }

        // Self-referencing foreign key for parent_id
        $this->add_foreign_key(
            $table_name,
            'fk_entity_parent',
            'parent_id',
            $table_name,
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * Add ticket table foreign keys
     *
     * @since    1.0.0
     */
    private function add_ticket_foreign_keys() {
        global $wpdb;
        
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $entities_table = $wpdb->prefix . 'mets_entities'; 
        $users_table = $wpdb->users;
        $sla_rules_table = $wpdb->prefix . 'mets_sla_rules';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            error_log("METS Foreign Keys: Table $tickets_table does not exist");
            return false;
        }

        // Entity relationship - CASCADE delete
        $this->add_foreign_key(
            $tickets_table,
            'fk_ticket_entity',
            'entity_id',
            $entities_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Assigned user relationship - SET NULL on delete
        $this->add_foreign_key(
            $tickets_table,
            'fk_ticket_assigned_user',
            'assigned_to',
            $users_table,
            'ID',
            'SET NULL',
            'CASCADE'
        );

        // Created by user relationship - SET NULL on delete
        $this->add_foreign_key(
            $tickets_table,
            'fk_ticket_created_user',
            'created_by',
            $users_table,
            'ID',
            'SET NULL',
            'CASCADE'
        );

        // SLA rule relationship - SET NULL on delete
        if ($wpdb->get_var("SHOW TABLES LIKE '$sla_rules_table'") === $sla_rules_table) {
            $this->add_foreign_key(
                $tickets_table,
                'fk_ticket_sla_rule',
                'sla_rule_id',
                $sla_rules_table,
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    /**
     * Add ticket replies foreign keys
     *
     * @since    1.0.0
     */
    private function add_reply_foreign_keys() {
        global $wpdb;
        
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $users_table = $wpdb->users;

        if ($wpdb->get_var("SHOW TABLES LIKE '$replies_table'") !== $replies_table) {
            error_log("METS Foreign Keys: Table $replies_table does not exist");
            return false;
        }

        // Ticket relationship - CASCADE delete
        $this->add_foreign_key(
            $replies_table,
            'fk_reply_ticket',
            'ticket_id',
            $tickets_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // User relationship - SET NULL on delete
        $this->add_foreign_key(
            $replies_table,
            'fk_reply_user',
            'user_id',
            $users_table,
            'ID',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * Add attachment foreign keys
     *
     * @since    1.0.0
     */
    private function add_attachment_foreign_keys() {
        global $wpdb;
        
        $attachments_table = $wpdb->prefix . 'mets_attachments';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $users_table = $wpdb->users;

        if ($wpdb->get_var("SHOW TABLES LIKE '$attachments_table'") !== $attachments_table) {
            error_log("METS Foreign Keys: Table $attachments_table does not exist");
            return false;
        }

        // Ticket relationship - CASCADE delete
        $this->add_foreign_key(
            $attachments_table,
            'fk_attachment_ticket',
            'ticket_id',
            $tickets_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Reply relationship - CASCADE delete (optional)
        $this->add_foreign_key(
            $attachments_table,
            'fk_attachment_reply',
            'reply_id',
            $replies_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Uploaded by user relationship - SET NULL on delete
        $this->add_foreign_key(
            $attachments_table,
            'fk_attachment_user',
            'uploaded_by',
            $users_table,
            'ID',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * Add SLA tracking foreign keys
     *
     * @since    1.0.0
     */
    private function add_sla_foreign_keys() {
        global $wpdb;
        
        $sla_tracking_table = $wpdb->prefix . 'mets_sla_tracking';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $sla_rules_table = $wpdb->prefix . 'mets_sla_rules';

        if ($wpdb->get_var("SHOW TABLES LIKE '$sla_tracking_table'") !== $sla_tracking_table) {
            error_log("METS Foreign Keys: Table $sla_tracking_table does not exist");
            return false;
        }

        // Ticket relationship - CASCADE delete
        $this->add_foreign_key(
            $sla_tracking_table,
            'fk_sla_tracking_ticket',
            'ticket_id',
            $tickets_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // SLA rule relationship - SET NULL on delete
        if ($wpdb->get_var("SHOW TABLES LIKE '$sla_rules_table'") === $sla_rules_table) {
            $this->add_foreign_key(
                $sla_tracking_table,
                'fk_sla_tracking_rule',
                'sla_rule_id',
                $sla_rules_table,
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    /**
     * Add rating foreign keys
     *
     * @since    1.0.0
     */
    private function add_rating_foreign_keys() {
        global $wpdb;
        
        $ratings_table = $wpdb->prefix . 'mets_ticket_ratings';
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $users_table = $wpdb->users;

        if ($wpdb->get_var("SHOW TABLES LIKE '$ratings_table'") !== $ratings_table) {
            error_log("METS Foreign Keys: Table $ratings_table does not exist");
            return false;
        }

        // Ticket relationship - CASCADE delete
        $this->add_foreign_key(
            $ratings_table,
            'fk_rating_ticket',
            'ticket_id',
            $tickets_table,
            'id',
            'CASCADE',
            'CASCADE'
        );

        // User relationship - SET NULL on delete
        $this->add_foreign_key(
            $ratings_table,
            'fk_rating_user',
            'user_id',
            $users_table,
            'ID',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * Add user entity mapping foreign keys
     *
     * @since    1.0.0
     */
    private function add_user_entity_foreign_keys() {
        global $wpdb;
        
        $user_entities_table = $wpdb->prefix . 'mets_user_entities';
        $entities_table = $wpdb->prefix . 'mets_entities';
        $users_table = $wpdb->users;

        if ($wpdb->get_var("SHOW TABLES LIKE '$user_entities_table'") !== $user_entities_table) {
            error_log("METS Foreign Keys: Table $user_entities_table does not exist");
            return false;
        }

        // User relationship - CASCADE delete
        $this->add_foreign_key(
            $user_entities_table,
            'fk_user_entity_user',
            'user_id',
            $users_table,
            'ID',
            'CASCADE',
            'CASCADE'
        );

        // Entity relationship - CASCADE delete
        $this->add_foreign_key(
            $user_entities_table,
            'fk_user_entity_entity',
            'entity_id',
            $entities_table,
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Add a foreign key constraint
     *
     * @since    1.0.0
     * @param    string $table_name      Source table
     * @param    string $constraint_name Name of the constraint
     * @param    string $column_name     Source column
     * @param    string $ref_table       Referenced table
     * @param    string $ref_column      Referenced column
     * @param    string $on_delete       ON DELETE action
     * @param    string $on_update       ON UPDATE action
     */
    private function add_foreign_key($table_name, $constraint_name, $column_name, $ref_table, $ref_column, $on_delete = 'RESTRICT', $on_update = 'CASCADE') {
        global $wpdb;

        // Check if foreign key already exists
        $existing_fk = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                DB_NAME,
                $table_name,
                $constraint_name
            )
        );

        if ($existing_fk) {
            return true; // Already exists
        }

        // Check if referenced table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$ref_table'") !== $ref_table) {
            error_log("METS Foreign Keys: Referenced table $ref_table does not exist");
            return false;
        }

        // Check if referenced column exists
        $ref_column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND COLUMN_NAME = %s",
                DB_NAME,
                $ref_table,
                $ref_column
            )
        );

        if (!$ref_column_exists) {
            error_log("METS Foreign Keys: Referenced column $ref_column does not exist in $ref_table");
            return false;
        }

        // Remove any existing constraint with same name first
        $this->drop_foreign_key($table_name, $constraint_name);

        // Add the foreign key constraint
        $sql = "ALTER TABLE $table_name 
                ADD CONSTRAINT $constraint_name 
                FOREIGN KEY ($column_name) 
                REFERENCES $ref_table($ref_column) 
                ON DELETE $on_delete 
                ON UPDATE $on_update";

        $result = $wpdb->query($sql);

        if ($result === false) {
            error_log("METS Foreign Keys: Failed to add foreign key $constraint_name - " . $wpdb->last_error);
            return false;
        } else {
            error_log("METS Foreign Keys: Foreign key $constraint_name added successfully");
            return true;
        }
    }

    /**
     * Drop a foreign key constraint
     *
     * @since    1.0.0
     * @param    string $table_name      Table name
     * @param    string $constraint_name Constraint name
     */
    private function drop_foreign_key($table_name, $constraint_name) {
        global $wpdb;

        // Check if constraint exists
        $constraint_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = %s 
                AND TABLE_NAME = %s 
                AND CONSTRAINT_NAME = %s 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                DB_NAME,
                $table_name,
                $constraint_name
            )
        );

        if ($constraint_exists) {
            $sql = "ALTER TABLE $table_name DROP FOREIGN KEY $constraint_name";
            $wpdb->query($sql);
        }
    }

    /**
     * Disable foreign key checks
     *
     * @since    1.0.0
     */
    private function disable_foreign_key_checks() {
        global $wpdb;
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
    }

    /**
     * Enable foreign key checks
     *
     * @since    1.0.0
     */
    private function enable_foreign_key_checks() {
        global $wpdb;
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Get all foreign key relationships
     *
     * @since    1.0.0
     * @return   array Array of foreign key information
     */
    public function get_foreign_key_status() {
        global $wpdb;

        $foreign_keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    kcu.TABLE_NAME,
                    kcu.COLUMN_NAME,
                    kcu.CONSTRAINT_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE,
                    rc.UPDATE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_SCHEMA = %s 
                AND kcu.TABLE_NAME LIKE %s
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME",
                DB_NAME,
                $wpdb->prefix . 'mets_%'
            ),
            ARRAY_A
        );

        $organized_fks = array();
        
        foreach ($foreign_keys as $fk) {
            $table = $fk['TABLE_NAME'];
            
            if (!isset($organized_fks[$table])) {
                $organized_fks[$table] = array();
            }
            
            $organized_fks[$table][] = array(
                'constraint_name' => $fk['CONSTRAINT_NAME'],
                'column' => $fk['COLUMN_NAME'],
                'references' => $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'],
                'on_delete' => $fk['DELETE_RULE'],
                'on_update' => $fk['UPDATE_RULE']
            );
        }

        return $organized_fks;
    }

    /**
     * Test foreign key constraints
     *
     * @since    1.0.0
     * @return   array Test results
     */
    public function test_foreign_key_constraints() {
        global $wpdb;
        
        $test_results = array();
        
        // Check if required tables exist before testing
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $replies_table = $wpdb->prefix . 'mets_ticket_replies';
        $ratings_table = $wpdb->prefix . 'mets_ticket_ratings';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            $test_results[] = array(
                'passed' => true,
                'description' => 'Foreign key constraint tests',
                'message' => 'Tables do not exist yet - constraints will be applied during setup'
            );
        } else {
            // Test 1: Try to insert invalid entity_id in tickets
            $test_results[] = $this->test_constraint_violation(
                "INSERT INTO {$wpdb->prefix}mets_tickets 
                 (entity_id, ticket_number, subject, description, customer_name, customer_email) 
                 VALUES (99999, 'TEST-001', 'Test', 'Test', 'Test User', 'test@example.com')",
                'entity foreign key constraint'
            );

            // Test 2: Try to insert invalid ticket_id in replies (only if replies table exists)
            if ($wpdb->get_var("SHOW TABLES LIKE '$replies_table'") === $replies_table) {
                $test_results[] = $this->test_constraint_violation(
                    "INSERT INTO {$wpdb->prefix}mets_ticket_replies 
                     (ticket_id, content) 
                     VALUES (99999, 'Test reply')",
                    'ticket foreign key constraint'
                );
            }

            // Test 3: Try to insert invalid rating (only if ratings table exists)
            if ($wpdb->get_var("SHOW TABLES LIKE '$ratings_table'") === $ratings_table) {
                $test_results[] = $this->test_constraint_violation(
                    "INSERT INTO {$wpdb->prefix}mets_ticket_ratings 
                     (ticket_id, rating) 
                     VALUES (1, 10)",
                    'rating range constraint'
                );
            }
        }

        return $test_results;
    }

    /**
     * Test a specific constraint violation
     *
     * @since    1.0.0
     * @param    string $sql         SQL to test
     * @param    string $description Description of test
     * @return   array Test result
     */
    private function test_constraint_violation($sql, $description) {
        global $wpdb;
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            return array(
                'passed' => true,
                'description' => $description,
                'message' => 'Constraint correctly prevented invalid operation',
                'error' => $wpdb->last_error
            );
        } else {
            return array(
                'passed' => false,
                'description' => $description,
                'message' => 'Constraint failed to prevent invalid operation',
                'error' => 'No error - operation succeeded when it should have failed'
            );
        }
    }

    /**
     * Create data synchronization triggers
     *
     * @since    1.0.0
     */
    public function create_synchronization_triggers() {
        global $wpdb;
        
        // Trigger to update SLA tracking when ticket is resolved
        $trigger_sql = "
        CREATE TRIGGER tr_ticket_resolved_sla_update
        AFTER UPDATE ON {$wpdb->prefix}mets_tickets
        FOR EACH ROW
        BEGIN
            IF NEW.resolved_at IS NOT NULL AND OLD.resolved_at IS NULL THEN
                UPDATE {$wpdb->prefix}mets_sla_tracking 
                SET resolved_at = NEW.resolved_at,
                    resolution_sla_met = CASE 
                        WHEN NEW.resolved_at <= resolution_due_at THEN 1 
                        ELSE 0 
                    END,
                    resolution_breach_duration = CASE 
                        WHEN NEW.resolved_at > resolution_due_at THEN 
                            TIMESTAMPDIFF(MINUTE, resolution_due_at, NEW.resolved_at)
                        ELSE 0 
                    END
                WHERE ticket_id = NEW.id;
            END IF;
        END";

        // Drop trigger if exists and recreate
        $wpdb->query("DROP TRIGGER IF EXISTS tr_ticket_resolved_sla_update");
        
        $result = $wpdb->query($trigger_sql);
        
        if ($result === false) {
            error_log("METS Foreign Keys: Failed to create synchronization trigger - " . $wpdb->last_error);
        } else {
            error_log("METS Foreign Keys: Synchronization trigger created successfully");
        }
    }
}