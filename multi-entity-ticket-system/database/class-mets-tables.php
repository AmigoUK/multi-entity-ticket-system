<?php
/**
 * Database table creation and management
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

/**
 * Database table creation and management class.
 *
 * This class handles all database table creation, updates, and schema management
 * for the Multi-Entity Ticket System plugin.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/database
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Tables {

	/**
	 * Create all database tables
	 *
	 * @since    1.0.0
	 */
	public function create_all_tables() {
		$this->create_entities_table();
		$this->create_tickets_table();
		$this->create_ticket_replies_table();
		$this->create_attachments_table();
		$this->create_user_entities_table();
		$this->create_sla_rules_table();
		$this->create_business_hours_table();
		$this->create_email_queue_table();
		$this->create_automation_rules_table();
		$this->create_response_metrics_table();
		$this->create_workflow_rules_table();
	}

	/**
	 * Create entities table
	 *
	 * @since    1.0.0
	 */
	private function create_entities_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			parent_id bigint(20) unsigned DEFAULT NULL,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			logo_url varchar(255),
			primary_color varchar(7),
			secondary_color varchar(7),
			email_template text,
			settings longtext,
			smtp_settings longtext,
			status enum('active','inactive') DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY entity_slug (slug),
			KEY parent_id (parent_id),
			KEY status (status)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create tickets table
	 *
	 * @since    1.0.0
	 */
	private function create_tickets_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_tickets';
		$entities_table = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned NOT NULL,
			ticket_number varchar(50) NOT NULL,
			subject varchar(255) NOT NULL,
			description longtext NOT NULL,
			status varchar(50) DEFAULT 'new',
			priority varchar(50) DEFAULT 'normal',
			category varchar(100),
			customer_name varchar(255) NOT NULL,
			customer_email varchar(100) NOT NULL,
			customer_phone varchar(50),
			assigned_to bigint(20) unsigned DEFAULT NULL,
			created_by bigint(20) unsigned,
			sla_rule_id bigint(20) unsigned DEFAULT NULL,
			sla_response_due datetime,
			sla_resolution_due datetime,
			sla_escalation_due datetime,
			sla_response_breached tinyint(1) DEFAULT 0,
			sla_resolution_breached tinyint(1) DEFAULT 0,
			first_response_at datetime,
			resolved_at datetime,
			closed_at datetime,
			woo_order_id bigint(20) unsigned DEFAULT NULL,
			meta_data longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY ticket_number (ticket_number),
			KEY entity_status_date (entity_id, status, created_at),
			KEY assigned_priority (assigned_to, priority),
			KEY customer_email (customer_email),
			KEY woo_order_id (woo_order_id),
			KEY status (status),
			KEY priority (priority),
			KEY created_at (created_at),
			KEY sla_response_due (sla_response_due),
			KEY sla_resolution_due (sla_resolution_due),
			KEY sla_escalation_due (sla_escalation_due),
			FOREIGN KEY (entity_id) REFERENCES $entities_table(id) ON DELETE CASCADE,
			FOREIGN KEY (assigned_to) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
			FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create ticket replies table
	 *
	 * @since    1.0.0
	 */
	private function create_ticket_replies_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_ticket_replies';
		$tickets_table = $wpdb->prefix . 'mets_tickets';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned,
			user_type enum('customer','agent','system') DEFAULT 'agent',
			content longtext NOT NULL,
			is_internal_note tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ticket_id (ticket_id),
			KEY user_id (user_id),
			KEY created_at (created_at),
			KEY is_internal_note (is_internal_note),
			FOREIGN KEY (ticket_id) REFERENCES $tickets_table(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create attachments table
	 *
	 * @since    1.0.0
	 */
	private function create_attachments_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_attachments';
		$tickets_table = $wpdb->prefix . 'mets_tickets';
		$replies_table = $wpdb->prefix . 'mets_ticket_replies';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			reply_id bigint(20) unsigned DEFAULT NULL,
			file_name varchar(255) NOT NULL,
			file_type varchar(100),
			file_size bigint(20),
			file_url varchar(500),
			uploaded_by bigint(20) unsigned,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ticket_id (ticket_id),
			KEY reply_id (reply_id),
			KEY uploaded_by (uploaded_by),
			KEY created_at (created_at),
			FOREIGN KEY (ticket_id) REFERENCES $tickets_table(id) ON DELETE CASCADE,
			FOREIGN KEY (reply_id) REFERENCES $replies_table(id) ON DELETE CASCADE,
			FOREIGN KEY (uploaded_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create user entities mapping table
	 *
	 * @since    1.0.0
	 */
	private function create_user_entities_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_user_entities';
		$entities_table = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			entity_id bigint(20) unsigned NOT NULL,
			role varchar(50) NOT NULL,
			permissions longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_entity (user_id, entity_id),
			KEY entity_id (entity_id),
			KEY role (role),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (entity_id) REFERENCES $entities_table(id) ON DELETE CASCADE
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create SLA rules table
	 *
	 * @since    1.0.0
	 */
	private function create_sla_rules_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_sla_rules';
		$entities_table = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			priority varchar(50) NOT NULL,
			response_time_hours int(11) DEFAULT NULL,
			resolution_time_hours int(11) DEFAULT NULL,
			escalation_time_hours int(11) DEFAULT NULL,
			business_hours_only tinyint(1) DEFAULT 0,
			conditions longtext,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity_priority (entity_id, priority),
			KEY is_active (is_active),
			FOREIGN KEY (entity_id) REFERENCES $entities_table(id) ON DELETE CASCADE
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create business hours table
	 *
	 * @since    1.0.0
	 */
	private function create_business_hours_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_business_hours';
		$entities_table = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entity_id bigint(20) unsigned,
			day_of_week tinyint(1) NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			is_active tinyint(1) DEFAULT 1,
			PRIMARY KEY (id),
			KEY entity_day (entity_id, day_of_week),
			FOREIGN KEY (entity_id) REFERENCES $entities_table(id) ON DELETE CASCADE
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create email queue table
	 *
	 * @since    1.0.0
	 */
	private function create_email_queue_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_email_queue';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipient_email varchar(255) NOT NULL,
			recipient_name varchar(255),
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			template_name varchar(100),
			template_data longtext,
			smtp_config longtext,
			delivery_method varchar(50) DEFAULT 'wordpress',
			priority tinyint(1) DEFAULT 5,
			attempts tinyint(1) DEFAULT 0,
			max_attempts tinyint(1) DEFAULT 3,
			status enum('pending','sent','failed','cancelled') DEFAULT 'pending',
			scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
			sent_at datetime,
			error_message text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status_priority (status, priority),
			KEY scheduled_at (scheduled_at)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create automation rules table
	 *
	 * @since    1.0.0
	 */
	private function create_automation_rules_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_automation_rules';
		$entities_table = $wpdb->prefix . 'mets_entities';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			entity_id bigint(20) unsigned,
			trigger_event varchar(100) NOT NULL,
			conditions longtext,
			actions longtext,
			is_active tinyint(1) DEFAULT 1,
			execution_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity_trigger (entity_id, trigger_event),
			KEY active_order (is_active, execution_order),
			FOREIGN KEY (entity_id) REFERENCES $entities_table(id) ON DELETE CASCADE
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Create response metrics table
	 *
	 * @since    1.0.0
	 */
	private function create_response_metrics_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_response_metrics';
		$tickets_table = $wpdb->prefix . 'mets_tickets';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			metric_type varchar(50) NOT NULL,
			start_time datetime NOT NULL,
			end_time datetime,
			duration_minutes int(11),
			business_duration_minutes int(11),
			sla_target_minutes int(11),
			within_sla tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ticket_metric (ticket_id, metric_type),
			FOREIGN KEY (ticket_id) REFERENCES $tickets_table(id) ON DELETE CASCADE
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Check if tables exist
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function tables_exist() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'mets_entities',
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_ticket_replies',
			$wpdb->prefix . 'mets_attachments',
			$wpdb->prefix . 'mets_user_entities',
			$wpdb->prefix . 'mets_sla_rules',
			$wpdb->prefix . 'mets_business_hours',
			$wpdb->prefix . 'mets_email_queue',
			$wpdb->prefix . 'mets_automation_rules',
			$wpdb->prefix . 'mets_response_metrics',
			$wpdb->prefix . 'mets_workflow_rules',
		);

		foreach ( $tables as $table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create workflow rules table
	 *
	 * @since    1.0.0
	 */
	private function create_workflow_rules_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_workflow_rules';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			from_status varchar(50) NOT NULL,
			to_status varchar(50) NOT NULL,
			allowed_roles text NOT NULL,
			priority_id int(11) DEFAULT NULL,
			category varchar(100) DEFAULT NULL,
			auto_assign tinyint(1) DEFAULT 0,
			requires_note tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY from_to_status (from_status, to_status),
			KEY priority_category (priority_id, category)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Drop all tables (used in uninstall)
	 *
	 * @since    1.0.0
	 */
	public function drop_all_tables() {
		global $wpdb;

		// Drop in reverse order due to foreign key constraints
		$tables = array(
			$wpdb->prefix . 'mets_response_metrics',
			$wpdb->prefix . 'mets_email_queue',
			$wpdb->prefix . 'mets_automation_rules',
			$wpdb->prefix . 'mets_attachments',
			$wpdb->prefix . 'mets_ticket_replies',
			$wpdb->prefix . 'mets_sla_rules',
			$wpdb->prefix . 'mets_business_hours',
			$wpdb->prefix . 'mets_workflow_rules',
			$wpdb->prefix . 'mets_user_entities',
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_entities',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
	}
}