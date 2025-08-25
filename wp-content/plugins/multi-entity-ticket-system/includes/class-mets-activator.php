<?php
/**
 * Fired during plugin activation
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Activator {

	/**
	 * Get translated text safely during activation
	 * Falls back to English if translations aren't loaded yet
	 *
	 * @since    1.0.0
	 * @param    string    $text        Text to translate
	 * @param    string    $fallback    Fallback English text
	 * @return   string                 Translated or fallback text
	 */
	private static function safe_translate( $text, $fallback = null ) {
		// Use fallback if provided, otherwise use the original text
		$fallback = $fallback !== null ? $fallback : $text;
		
		// Only translate if init has fired
		return did_action( 'init' ) ? __( $text, METS_TEXT_DOMAIN ) : $fallback;
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check for database upgrade
		self::check_database_upgrade();
		
		// Create database tables
		self::create_tables();
		
		// Apply schema updates for existing installations
		self::apply_schema_updates();
		
		// Create custom user roles
		self::create_user_roles();
		
		// Set default options
		self::set_default_options();
		
		// Create default entities if none exist
		self::create_default_entities();
		
		// Create default SLA rules
		self::create_default_sla_rules();
		
		// Schedule SLA monitoring
		self::schedule_sla_monitoring();
		
		// Add KB rewrite rules before flushing
		self::add_kb_rewrite_rules();
		
		// Migrate existing email templates
		self::migrate_email_templates();
		
		// Update plugin version
		update_option( 'mets_plugin_version', METS_VERSION );
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Create database tables
	 *
	 * @since    1.0.0
	 */
	private static function create_tables() {
		require_once METS_PLUGIN_PATH . 'database/class-mets-tables.php';
		$tables = new METS_Tables();
		$tables->create_all_tables();
		
		// Create knowledgebase tables
		require_once METS_PLUGIN_PATH . 'includes/class-mets-kb-activator.php';
		METS_KB_Activator::activate();
	}

	/**
	 * Apply schema updates for existing installations
	 *
	 * @since    1.0.0
	 */
	private static function apply_schema_updates() {
		require_once METS_PLUGIN_PATH . 'database/class-mets-schema-manager.php';
		$schema_manager = new METS_Schema_Manager();
		$results = $schema_manager->update_schema_for_missing_columns();
		
		if (!$results['success']) {
			error_log('[METS] Schema update failed: ' . implode(', ', $results['errors']));
		} else if (!empty($results['columns_added'])) {
			error_log('[METS] Schema updated successfully. Added columns: ' . implode(', ', $results['columns_added']));
		}
	}

	/**
	 * Create custom user roles and capabilities
	 *
	 * @since    1.0.0
	 */
	private static function create_user_roles() {
		// Ticket Admin - Full access to all ticket system features
		add_role( 'ticket_admin', self::safe_translate( 'Ticket Admin' ), array(
			'read'                    => true,
			'edit_posts'              => false,
			'delete_posts'            => false,
			'publish_posts'           => false,
			'upload_files'            => true,
			'manage_ticket_system'    => true,
			'manage_entities'         => true,
			'manage_tickets'          => true,
			'view_all_tickets'        => true,
			'assign_tickets'          => true,
			'edit_tickets'            => true,
			'delete_tickets'          => true,
			'manage_sla_rules'        => true,
			'view_reports'            => true,
			'export_data'             => true,
			'manage_users'            => true,
		) );

		// Ticket Manager - Multi-entity access, can manage agents
		add_role( 'ticket_manager', self::safe_translate( 'Ticket Manager' ), array(
			'read'                 => true,
			'upload_files'         => true,
			'manage_tickets'       => true,
			'view_assigned_tickets' => true,
			'assign_tickets'       => true,
			'edit_tickets'         => true,
			'view_reports'         => true,
			'manage_agents'        => true,
		) );

		// Ticket Agent - Limited entity access
		add_role( 'ticket_agent', self::safe_translate( 'Ticket Agent' ), array(
			'read'                 => true,
			'upload_files'         => true,
			'view_assigned_tickets' => true,
			'edit_assigned_tickets' => true,
			'reply_to_tickets'     => true,
		) );

		// Ticket Customer - Can view own tickets and submit new ones
		add_role( 'ticket_customer', self::safe_translate( 'Ticket Customer' ), array(
			'read'              => true,
			'submit_tickets'    => true,
			'view_own_tickets'  => true,
			'reply_to_tickets'  => true,
		) );

		// Add capabilities to administrator
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_ticket_system' );
			$admin_role->add_cap( 'manage_entities' );
			$admin_role->add_cap( 'manage_tickets' );
			$admin_role->add_cap( 'view_all_tickets' );
			$admin_role->add_cap( 'assign_tickets' );
			$admin_role->add_cap( 'edit_tickets' );
			$admin_role->add_cap( 'delete_tickets' );
			$admin_role->add_cap( 'manage_sla_rules' );
			$admin_role->add_cap( 'view_reports' );
			$admin_role->add_cap( 'export_data' );
			$admin_role->add_cap( 'manage_agents' );
			
			// KB capabilities
			$admin_role->add_cap( 'manage_kb_articles' );
			$admin_role->add_cap( 'edit_kb_articles' );
			$admin_role->add_cap( 'publish_kb_articles' );
			$admin_role->add_cap( 'delete_kb_articles' );
			$admin_role->add_cap( 'review_kb_articles' );
			$admin_role->add_cap( 'edit_entity_kb_articles' );
		}
	}

	/**
	 * Set default plugin options
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'ticket_statuses' => array(
				'new'         => array( 'label' => self::safe_translate( 'New' ), 'color' => '#007cba' ),
				'open'        => array( 'label' => self::safe_translate( 'Open' ), 'color' => '#00a32a' ),
				'in_progress' => array( 'label' => self::safe_translate( 'In Progress' ), 'color' => '#f0b849' ),
				'pending'     => array( 'label' => self::safe_translate( 'Pending' ), 'color' => '#f56e28' ),
				'resolved'    => array( 'label' => self::safe_translate( 'Resolved' ), 'color' => '#00a32a' ),
				'closed'      => array( 'label' => self::safe_translate( 'Closed' ), 'color' => '#787c82' ),
			),
			'ticket_priorities' => array(
				'low'      => array( 'label' => self::safe_translate( 'Low' ), 'color' => '#00a32a', 'order' => 1 ),
				'normal'   => array( 'label' => self::safe_translate( 'Normal' ), 'color' => '#007cba', 'order' => 2 ),
				'high'     => array( 'label' => self::safe_translate( 'High' ), 'color' => '#f0b849', 'order' => 3 ),
				'urgent'   => array( 'label' => self::safe_translate( 'Urgent' ), 'color' => '#d63638', 'order' => 4 ),
			),
			'ticket_categories' => array(
				'general'   => self::safe_translate( 'General' ),
				'technical' => self::safe_translate( 'Technical' ),
				'billing'   => self::safe_translate( 'Billing' ),
				'support'   => self::safe_translate( 'Support' ),
			),
			'file_upload_settings' => array(
				'max_files'     => 10,
				'max_file_size' => 20971520, // 20MB in bytes
				'allowed_types' => array( 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip' ),
			),
			'email_settings' => array(
				'notifications_enabled' => true,
				'notify_on_new_ticket'  => true,
				'notify_on_assignment'  => true,
				'notify_on_reply'       => true,
				'notify_on_status_change' => true,
			),
			'general_settings' => array(
				'require_registration' => false,
				'tickets_per_page'     => 20,
				'auto_assign_tickets'  => false,
			),
			'sla_settings' => array(
				'sla_enabled' => true,
				'business_hours_enabled' => true,
				'default_response_time' => 24, // hours
				'default_resolution_time' => 72, // hours
				'sla_warning_hours' => 2,
			),
			'smtp_settings' => array(
				'enabled' => false,
				'method' => 'wordpress',
				'provider' => '',
				'host' => '',
				'port' => 587,
				'encryption' => 'tls',
				'auth_required' => true,
				'username' => '',
				'password' => '',
				'from_email' => get_option( 'admin_email' ),
				'from_name' => get_bloginfo( 'name' ),
				'reply_to' => '',
				'test_email' => get_option( 'admin_email' ),
			),
		);

		foreach ( $default_options as $option_name => $option_value ) {
			$full_option_name = 'mets_' . $option_name;
			if ( ! get_option( $full_option_name ) ) {
				add_option( $full_option_name, $option_value );
			}
		}
	}

	/**
	 * Create default entities
	 *
	 * @since    1.0.0
	 */
	private static function create_default_entities() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mets_entities';
		
		// Check if any entities exist
		$entity_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		
		if ( $entity_count == 0 ) {
			// Create a default entity
			$wpdb->insert(
				$table_name,
				array(
					'name'        => self::safe_translate( 'Default Entity' ),
					'slug'        => 'default-entity',
					'description' => self::safe_translate( 'Default entity for ticket system' ),
					'status'      => 'active',
					'created_at'  => current_time( 'mysql' ),
					'updated_at'  => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Create default SLA rules
	 *
	 * @since    1.0.0
	 */
	private static function create_default_sla_rules() {
		global $wpdb;
		
		// Check if any SLA rules exist
		$sla_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_sla_rules" );
		
		if ( $sla_count == 0 ) {
			// Get default entity
			$entity = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}mets_entities ORDER BY id ASC LIMIT 1" );
			
			if ( $entity ) {
				// Create default SLA rules for different priorities
				$default_rules = array(
					array(
						'entity_id' => $entity->id,
						'name' => self::safe_translate( 'Low Priority SLA' ),
						'priority' => 'low',
						'response_time_hours' => 48,
						'resolution_time_hours' => 120,
						'escalation_time_hours' => 96,
						'business_hours_only' => 1,
						'is_active' => 1,
					),
					array(
						'entity_id' => $entity->id,
						'name' => self::safe_translate( 'Normal Priority SLA' ),
						'priority' => 'normal',
						'response_time_hours' => 24,
						'resolution_time_hours' => 72,
						'escalation_time_hours' => 48,
						'business_hours_only' => 1,
						'is_active' => 1,
					),
					array(
						'entity_id' => $entity->id,
						'name' => self::safe_translate( 'High Priority SLA' ),
						'priority' => 'high',
						'response_time_hours' => 8,
						'resolution_time_hours' => 24,
						'escalation_time_hours' => 16,
						'business_hours_only' => 1,
						'is_active' => 1,
					),
					array(
						'entity_id' => $entity->id,
						'name' => self::safe_translate( 'Urgent Priority SLA' ),
						'priority' => 'urgent',
						'response_time_hours' => 2,
						'resolution_time_hours' => 8,
						'escalation_time_hours' => 4,
						'business_hours_only' => 0,
						'is_active' => 1,
					),
				);

				foreach ( $default_rules as $rule ) {
					$wpdb->insert(
						$wpdb->prefix . 'mets_sla_rules',
						$rule,
						array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d' )
					);
				}

				// Create default business hours (Monday-Friday, 9-5)
				$business_hours = array(
					array( 'entity_id' => $entity->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => 1 ),
					array( 'entity_id' => $entity->id, 'day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => 1 ),
					array( 'entity_id' => $entity->id, 'day_of_week' => 3, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => 1 ),
					array( 'entity_id' => $entity->id, 'day_of_week' => 4, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => 1 ),
					array( 'entity_id' => $entity->id, 'day_of_week' => 5, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => 1 ),
				);

				foreach ( $business_hours as $hours ) {
					$wpdb->insert(
						$wpdb->prefix . 'mets_business_hours',
						$hours,
						array( '%d', '%d', '%s', '%s', '%d' )
					);
				}
			}
		}
	}

	/**
	 * Schedule SLA monitoring
	 *
	 * @since    1.0.0
	 */
	private static function schedule_sla_monitoring() {
		// Schedule SLA monitoring cron job
		if ( ! wp_next_scheduled( 'mets_sla_monitoring' ) ) {
			wp_schedule_event( time(), 'hourly', 'mets_sla_monitoring' );
		}

		// Schedule breach detection
		if ( ! wp_next_scheduled( 'mets_sla_breach_check' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'mets_sla_breach_check' );
		}

		// Schedule email queue processing
		if ( ! wp_next_scheduled( 'mets_process_email_queue' ) ) {
			wp_schedule_event( time(), 'five_minutes', 'mets_process_email_queue' );
		}
	}

	/**
	 * Add KB rewrite rules during activation
	 *
	 * @since    1.0.0
	 */
	private static function add_kb_rewrite_rules() {
		// Initialize KB rewrite rules by creating an instance of the KB public class
		require_once METS_PLUGIN_PATH . 'public/class-mets-knowledgebase-public.php';
		$kb_public = new METS_Knowledgebase_Public( 'multi-entity-ticket-system', '1.0.0' );
		$kb_public->add_rewrite_rules();
	}

	/**
	 * Check if database upgrade is needed
	 *
	 * @since    1.0.0
	 */
	private static function check_database_upgrade() {
		$current_version = get_option( 'mets_plugin_version', '0.0.0' );
		
		// If this is a fresh installation, skip upgrade checks
		if ( $current_version === '0.0.0' ) {
			return;
		}
		
		// Check if we need to upgrade database schema
		if ( version_compare( $current_version, METS_VERSION, '<' ) ) {
			// Perform upgrade tasks based on version differences
			self::perform_database_upgrade( $current_version );
		}
	}

	/**
	 * Perform database upgrade tasks
	 *
	 * @since    1.0.0
	 * @param    string    $from_version    Previous version
	 */
	private static function perform_database_upgrade( $from_version ) {
		global $wpdb;
		
		// Add upgrade logic for different versions
		// For now, ensure email template tables exist
		$email_templates_table = $wpdb->prefix . 'mets_email_templates';
		$email_history_table = $wpdb->prefix . 'mets_email_template_history';
		
		// Check if email template tables exist
		$templates_exists = $wpdb->get_var( "SHOW TABLES LIKE '$email_templates_table'" ) === $email_templates_table;
		$history_exists = $wpdb->get_var( "SHOW TABLES LIKE '$email_history_table'" ) === $email_history_table;
		
		if ( ! $templates_exists || ! $history_exists ) {
			require_once METS_PLUGIN_PATH . 'database/class-mets-tables.php';
			$tables = new METS_Tables();
			
			if ( ! $templates_exists ) {
				$tables->create_email_templates_table();
			}
			
			if ( ! $history_exists ) {
				$tables->create_email_template_history_table();
			}
		}
	}

	/**
	 * Migrate existing email templates to database
	 *
	 * @since    1.0.0
	 */
	private static function migrate_email_templates() {
		// Only migrate if email template tables exist and migration hasn't been done
		$migration_done = get_option( 'mets_email_templates_migrated', false );
		
		if ( ! $migration_done ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-email-template-manager.php';
			$template_manager = new METS_Email_Template_Manager();
			
			// Perform migration
			$results = $template_manager->migrate_existing_templates();
			
			// Log results for debugging
			if ( ! empty( $results['success'] ) ) {
				error_log( 'METS: Successfully migrated templates: ' . implode( ', ', array_keys( $results['success'] ) ) );
			}
			
			if ( ! empty( $results['errors'] ) ) {
				error_log( 'METS: Migration errors: ' . implode( ', ', $results['errors'] ) );
			}
			
			// Mark migration as complete regardless of results
			// This prevents infinite migration attempts
			update_option( 'mets_email_templates_migrated', true );
		}
	}
}