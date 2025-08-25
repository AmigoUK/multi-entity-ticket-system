<?php
/**
 * Security Database Management
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Security_Database {

	/**
	 * Create security-related database tables
	 *
	 * @since    1.0.0
	 */
	public static function create_security_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Security log table
		$security_log_table = $wpdb->prefix . 'mets_security_log';
		$security_log_sql = "CREATE TABLE $security_log_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			event_type varchar(50) NOT NULL,
			event_data longtext,
			ip_address varchar(45) NOT NULL,
			user_id bigint(20) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_event_type (event_type),
			INDEX idx_ip_address (ip_address),
			INDEX idx_user_id (user_id),
			INDEX idx_created_at (created_at),
			INDEX idx_event_ip (event_type, ip_address),
			INDEX idx_event_date (event_type, created_at)
		) $charset_collate;";

		// Rate limiting table
		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';
		$rate_limit_sql = "CREATE TABLE $rate_limit_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			identifier varchar(255) NOT NULL,
			action_type varchar(50) NOT NULL,
			attempt_count int(11) NOT NULL DEFAULT 1,
			first_attempt datetime DEFAULT CURRENT_TIMESTAMP,
			last_attempt datetime DEFAULT CURRENT_TIMESTAMP,
			blocked_until datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_identifier_action (identifier, action_type),
			INDEX idx_identifier (identifier),
			INDEX idx_action_type (action_type),
			INDEX idx_blocked_until (blocked_until),
			INDEX idx_last_attempt (last_attempt)
		) $charset_collate;";

		// Security configuration table
		$security_config_table = $wpdb->prefix . 'mets_security_config';
		$security_config_sql = "CREATE TABLE $security_config_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			config_key varchar(100) NOT NULL,
			config_value longtext,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_config_key (config_key),
			INDEX idx_updated_at (updated_at)
		) $charset_collate;";

		// Audit trail table
		$audit_trail_table = $wpdb->prefix . 'mets_audit_trail';
		$audit_trail_sql = "CREATE TABLE $audit_trail_table (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT NULL,
			action varchar(100) NOT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) DEFAULT NULL,
			old_values longtext,
			new_values longtext,
			ip_address varchar(45) NOT NULL,
			user_agent text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_user_id (user_id),
			INDEX idx_action (action),
			INDEX idx_object_type (object_type),
			INDEX idx_object_id (object_id),
			INDEX idx_created_at (created_at),
			INDEX idx_user_action (user_id, action),
			INDEX idx_object_composite (object_type, object_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create tables
		$tables_created = array();
		$tables_failed = array();

		$tables = array(
			'security_log' => $security_log_sql,
			'rate_limits' => $rate_limit_sql,
			'security_config' => $security_config_sql,
			'audit_trail' => $audit_trail_sql
		);

		foreach ( $tables as $table_name => $sql ) {
			$result = dbDelta( $sql );
			
			if ( ! empty( $result ) ) {
				$tables_created[] = $table_name;
			} else {
				$tables_failed[] = $table_name;
			}
		}

		// Insert default security configuration
		self::insert_default_security_config();

		// Log table creation results
		if ( ! empty( $tables_failed ) ) {
			error_log( 'METS Security: Failed to create tables: ' . implode( ', ', $tables_failed ) );
		}

		return array(
			'created' => $tables_created,
			'failed' => $tables_failed,
			'success' => empty( $tables_failed )
		);
	}

	/**
	 * Insert default security configuration
	 *
	 * @since    1.0.0
	 */
	private static function insert_default_security_config() {
		global $wpdb;

		$security_config_table = $wpdb->prefix . 'mets_security_config';

		$default_configs = array(
			'enable_rate_limiting' => '1',
			'enable_input_validation' => '1',
			'enable_output_sanitization' => '1',
			'enable_csrf_protection' => '1',
			'enable_sql_injection_protection' => '1',
			'enable_file_upload_security' => '1',
			'enable_session_security' => '1',
			'max_login_attempts' => '5',
			'lockout_duration' => '900',
			'max_upload_size' => '10485760',
			'allowed_file_types' => wp_json_encode( array( 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt' ) ),
			'security_headers_enabled' => '1',
			'audit_trail_enabled' => '1',
			'security_notifications_enabled' => '1'
		);

		foreach ( $default_configs as $key => $value ) {
			// Check if config already exists
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $security_config_table WHERE config_key = %s",
				$key
			) );

			if ( ! $exists ) {
				$wpdb->insert(
					$security_config_table,
					array(
						'config_key' => $key,
						'config_value' => $value
					),
					array( '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Drop security tables (for uninstall)
	 *
	 * @since    1.0.0
	 */
	public static function drop_security_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'mets_security_log',
			$wpdb->prefix . 'mets_rate_limits',
			$wpdb->prefix . 'mets_security_config',
			$wpdb->prefix . 'mets_audit_trail'
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}

		// Clean up options
		$options_to_delete = array(
			'mets_security_config',
			'mets_security_audit_report',
			'mets_security_audit_last_run',
			'mets_security_tables_created'
		);

		foreach ( $options_to_delete as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Update security table structure if needed
	 *
	 * @since    1.0.0
	 */
	public static function update_security_tables() {
		global $wpdb;

		$current_version = get_option( 'mets_security_db_version', '0' );
		$target_version = '1.0';

		if ( version_compare( $current_version, $target_version, '<' ) ) {
			// Check if tables exist and create if not
			$security_log_table = $wpdb->prefix . 'mets_security_log';
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $security_log_table ) );

			if ( ! $table_exists ) {
				self::create_security_tables();
			}

			// Update version
			update_option( 'mets_security_db_version', $target_version );
		}
	}

	/**
	 * Log security event to database
	 *
	 * @since    1.0.0
	 * @param    string    $event_type    Event type
	 * @param    array     $event_data    Event data
	 * @param    string    $ip_address    IP address
	 * @param    int       $user_id       User ID
	 * @return   bool                     Success status
	 */
	public static function log_security_event( $event_type, $event_data = array(), $ip_address = null, $user_id = null ) {
		global $wpdb;

		$security_log_table = $wpdb->prefix . 'mets_security_log';

		if ( ! $ip_address ) {
			$ip_address = self::get_client_ip();
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$result = $wpdb->insert(
			$security_log_table,
			array(
				'event_type' => $event_type,
				'event_data' => wp_json_encode( $event_data ),
				'ip_address' => $ip_address,
				'user_id' => $user_id > 0 ? $user_id : null
			),
			array( '%s', '%s', '%s', '%d' )
		);

		// Clean up old log entries (keep last 10000 entries)
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $security_log_table" );
		if ( $count > 10000 ) {
			$wpdb->query( 
				"DELETE FROM $security_log_table 
				WHERE id NOT IN (
					SELECT id FROM (
						SELECT id FROM $security_log_table 
						ORDER BY created_at DESC 
						LIMIT 10000
					) temp
				)" 
			);
		}

		return $result !== false;
	}

	/**
	 * Log audit trail event
	 *
	 * @since    1.0.0
	 * @param    string    $action        Action performed
	 * @param    string    $object_type   Object type
	 * @param    int       $object_id     Object ID
	 * @param    array     $old_values    Old values
	 * @param    array     $new_values    New values
	 * @return   bool                     Success status
	 */
	public static function log_audit_trail( $action, $object_type, $object_id = null, $old_values = array(), $new_values = array() ) {
		global $wpdb;

		$audit_trail_table = $wpdb->prefix . 'mets_audit_trail';

		$result = $wpdb->insert(
			$audit_trail_table,
			array(
				'user_id' => get_current_user_id() ?: null,
				'action' => $action,
				'object_type' => $object_type,
				'object_id' => $object_id,
				'old_values' => wp_json_encode( $old_values ),
				'new_values' => wp_json_encode( $new_values ),
				'ip_address' => self::get_client_ip(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : ''
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Update rate limit counter
	 *
	 * @since    1.0.0
	 * @param    string    $identifier     Rate limit identifier
	 * @param    string    $action_type    Action type
	 * @param    int       $window_seconds Time window in seconds
	 * @return   int                       Current attempt count
	 */
	public static function update_rate_limit( $identifier, $action_type, $window_seconds = 3600 ) {
		global $wpdb;

		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';
		$now = current_time( 'mysql' );
		$window_start = date( 'Y-m-d H:i:s', time() - $window_seconds );

		// Check existing rate limit record
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $rate_limit_table 
			WHERE identifier = %s AND action_type = %s",
			$identifier, $action_type
		) );

		if ( $existing ) {
			// Check if within time window
			if ( strtotime( $existing->first_attempt ) > strtotime( $window_start ) ) {
				// Update existing record
				$wpdb->update(
					$rate_limit_table,
					array(
						'attempt_count' => $existing->attempt_count + 1,
						'last_attempt' => $now
					),
					array( 'id' => $existing->id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				
				return $existing->attempt_count + 1;
			} else {
				// Reset counter (outside time window)
				$wpdb->update(
					$rate_limit_table,
					array(
						'attempt_count' => 1,
						'first_attempt' => $now,
						'last_attempt' => $now,
						'blocked_until' => null
					),
					array( 'id' => $existing->id ),
					array( '%d', '%s', '%s', '%s' ),
					array( '%d' )
				);
				
				return 1;
			}
		} else {
			// Create new record
			$wpdb->insert(
				$rate_limit_table,
				array(
					'identifier' => $identifier,
					'action_type' => $action_type,
					'attempt_count' => 1,
					'first_attempt' => $now,
					'last_attempt' => $now
				),
				array( '%s', '%s', '%d', '%s', '%s' )
			);
			
			return 1;
		}
	}

	/**
	 * Get rate limit status
	 *
	 * @since    1.0.0
	 * @param    string    $identifier     Rate limit identifier
	 * @param    string    $action_type    Action type
	 * @return   array                     Rate limit status
	 */
	public static function get_rate_limit_status( $identifier, $action_type ) {
		global $wpdb;

		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';

		$record = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $rate_limit_table 
			WHERE identifier = %s AND action_type = %s",
			$identifier, $action_type
		) );

		if ( ! $record ) {
			return array(
				'attempts' => 0,
				'blocked' => false,
				'reset_time' => null
			);
		}

		$blocked = false;
		$reset_time = null;

		if ( $record->blocked_until ) {
			$blocked = strtotime( $record->blocked_until ) > time();
			$reset_time = $record->blocked_until;
		}

		return array(
			'attempts' => $record->attempt_count,
			'blocked' => $blocked,
			'reset_time' => $reset_time,
			'first_attempt' => $record->first_attempt,
			'last_attempt' => $record->last_attempt
		);
	}

	/**
	 * Block identifier for specified duration
	 *
	 * @since    1.0.0
	 * @param    string    $identifier     Rate limit identifier
	 * @param    string    $action_type    Action type
	 * @param    int       $duration       Block duration in seconds
	 * @return   bool                      Success status
	 */
	public static function block_identifier( $identifier, $action_type, $duration ) {
		global $wpdb;

		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';
		$blocked_until = date( 'Y-m-d H:i:s', time() + $duration );

		$result = $wpdb->update(
			$rate_limit_table,
			array( 'blocked_until' => $blocked_until ),
			array( 
				'identifier' => $identifier,
				'action_type' => $action_type
			),
			array( '%s' ),
			array( '%s', '%s' )
		);

		return $result !== false;
	}

	/**
	 * Clean up old rate limit records
	 *
	 * @since    1.0.0
	 * @param    int    $days_old    Delete records older than X days
	 * @return   int                  Number of records deleted
	 */
	public static function cleanup_rate_limits( $days_old = 30 ) {
		global $wpdb;

		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';
		$cutoff_date = date( 'Y-m-d H:i:s', time() - ( $days_old * DAY_IN_SECONDS ) );

		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM $rate_limit_table 
			WHERE last_attempt < %s AND (blocked_until IS NULL OR blocked_until < NOW())",
			$cutoff_date
		) );

		return $deleted;
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address
	 */
	private static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		
		return '0.0.0.0';
	}

	/**
	 * Get security statistics
	 *
	 * @since    1.0.0
	 * @return   array    Security statistics
	 */
	public static function get_security_statistics() {
		global $wpdb;

		$security_log_table = $wpdb->prefix . 'mets_security_log';
		$rate_limit_table = $wpdb->prefix . 'mets_rate_limits';
		$audit_trail_table = $wpdb->prefix . 'mets_audit_trail';

		$stats = array();

		// Total security events
		$stats['total_events'] = $wpdb->get_var( "SELECT COUNT(*) FROM $security_log_table" );

		// Events by type (last 30 days)
		$stats['events_by_type'] = $wpdb->get_results(
			"SELECT event_type, COUNT(*) as count 
			FROM $security_log_table 
			WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY event_type 
			ORDER BY count DESC"
		);

		// Active rate limits
		$stats['active_rate_limits'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $rate_limit_table 
			WHERE blocked_until > NOW()"
		);

		// Top IPs by security events
		$stats['top_ips'] = $wpdb->get_results(
			"SELECT ip_address, COUNT(*) as count 
			FROM $security_log_table 
			WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY ip_address 
			ORDER BY count DESC 
			LIMIT 10"
		);

		// Recent audit trail events
		$stats['recent_audit_events'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $audit_trail_table 
			WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
		);

		return $stats;
	}
}