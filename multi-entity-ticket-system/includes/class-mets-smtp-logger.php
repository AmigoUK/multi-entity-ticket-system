<?php
/**
 * SMTP Logger
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * SMTP Logger class.
 *
 * This class handles comprehensive logging for SMTP operations, debugging, and error tracking.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SMTP_Logger {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Logger    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Log levels
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $log_levels    Available log levels.
	 */
	private $log_levels = array(
		'emergency' => 0,
		'alert'     => 1,
		'critical'  => 2,
		'error'     => 3,
		'warning'   => 4,
		'notice'    => 5,
		'info'      => 6,
		'debug'     => 7,
	);

	/**
	 * Current log level
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $current_level    Current logging level.
	 */
	private $current_level;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_SMTP_Logger    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->current_level = $this->get_log_level();
	}

	/**
	 * Get current log level from settings
	 *
	 * @since    1.0.0
	 * @return   string    Log level
	 */
	private function get_log_level() {
		$level = get_option( 'mets_smtp_log_level', 'error' );
		
		// In debug mode, log everything
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return 'debug';
		}
		
		return $level;
	}

	/**
	 * Check if message should be logged
	 *
	 * @since    1.0.0
	 * @param    string   $level    Log level
	 * @return   bool               Whether to log
	 */
	private function should_log( $level ) {
		$current_level_int = $this->log_levels[ $this->current_level ] ?? 3;
		$message_level_int = $this->log_levels[ $level ] ?? 3;
		
		return $message_level_int <= $current_level_int;
	}

	/**
	 * Log SMTP connection attempt
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @param    bool     $success     Connection success
	 * @param    string   $error       Error message if failed
	 */
	public function log_connection( $settings, $success, $error = '' ) {
		$level = $success ? 'info' : 'error';
		
		$message = sprintf(
			'SMTP Connection %s - Host: %s, Port: %s, Encryption: %s',
			$success ? 'SUCCESS' : 'FAILED',
			$settings['host'] ?? 'unknown',
			$settings['port'] ?? 'unknown',
			$settings['encryption'] ?? 'none'
		);

		if ( ! $success && $error ) {
			$message .= ' - Error: ' . $this->sanitize_error_message( $error );
		}

		$this->log( $level, $message, array(
			'type' => 'connection',
			'provider' => $settings['provider'] ?? 'custom',
			'method' => $settings['method'] ?? 'smtp',
		) );
	}

	/**
	 * Log email sending attempt
	 *
	 * @since    1.0.0
	 * @param    array    $email_data    Email data
	 * @param    bool     $success       Send success
	 * @param    string   $error         Error message if failed
	 * @param    array    $debug         Debug information
	 */
	public function log_email_send( $email_data, $success, $error = '', $debug = array() ) {
		$level = $success ? 'info' : 'error';
		
		$message = sprintf(
			'Email %s - To: %s, Subject: %s',
			$success ? 'SENT' : 'FAILED',
			$this->sanitize_email( $email_data['recipient_email'] ?? 'unknown' ),
			$email_data['subject'] ?? 'No Subject'
		);

		if ( ! $success && $error ) {
			$message .= ' - Error: ' . $this->sanitize_error_message( $error );
		}

		$context = array(
			'type' => 'email_send',
			'recipient' => $this->sanitize_email( $email_data['recipient_email'] ?? '' ),
			'template' => $email_data['template_name'] ?? '',
			'method' => $email_data['delivery_method'] ?? 'wordpress',
		);

		if ( ! empty( $debug ) && $this->should_log( 'debug' ) ) {
			$context['debug'] = $this->sanitize_debug_output( $debug );
		}

		$this->log( $level, $message, $context );
	}

	/**
	 * Log queue processing
	 *
	 * @since    1.0.0
	 * @param    array    $results    Processing results
	 */
	public function log_queue_processing( $results ) {
		if ( $results['processed'] === 0 ) {
			return; // Don't log when no emails processed
		}

		$message = sprintf(
			'Email Queue Processed - Total: %d, Sent: %d, Failed: %d',
			$results['processed'],
			$results['sent'],
			$results['failed']
		);

		$level = $results['failed'] > 0 ? 'warning' : 'info';

		$context = array(
			'type' => 'queue_processing',
			'processed' => $results['processed'],
			'sent' => $results['sent'],
			'failed' => $results['failed'],
		);

		if ( ! empty( $results['errors'] ) ) {
			$context['errors'] = array_map( array( $this, 'sanitize_error_message' ), $results['errors'] );
		}

		$this->log( $level, $message, $context );
	}

	/**
	 * Log authentication failure
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @param    string   $error       Error message
	 */
	public function log_auth_failure( $settings, $error ) {
		$message = sprintf(
			'SMTP Authentication FAILED - Host: %s, Username: %s',
			$settings['host'] ?? 'unknown',
			$this->sanitize_username( $settings['username'] ?? 'unknown' )
		);

		$this->log( 'error', $message, array(
			'type' => 'auth_failure',
			'provider' => $settings['provider'] ?? 'custom',
			'error' => $this->sanitize_error_message( $error ),
		) );
	}

	/**
	 * Log configuration errors
	 *
	 * @since    1.0.0
	 * @param    array    $errors    Configuration errors
	 */
	public function log_config_errors( $errors ) {
		if ( empty( $errors ) ) {
			return;
		}

		$message = 'SMTP Configuration Errors: ' . implode( ', ', $errors );

		$this->log( 'error', $message, array(
			'type' => 'config_error',
			'errors' => $errors,
		) );
	}

	/**
	 * Log general message
	 *
	 * @since    1.0.0
	 * @param    string   $level      Log level
	 * @param    string   $message    Log message
	 * @param    array    $context    Additional context
	 */
	public function log( $level, $message, $context = array() ) {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'level' => strtoupper( $level ),
			'message' => $message,
			'context' => $context,
		);

		// Log to WordPress error log
		$this->log_to_error_log( $log_entry );

		// Store in database for admin viewing
		$this->store_log_entry( $log_entry );

		// Fire action for extensibility
		do_action( 'mets_smtp_logged', $log_entry );
	}

	/**
	 * Log to WordPress error log
	 *
	 * @since    1.0.0
	 * @param    array    $log_entry    Log entry
	 */
	private function log_to_error_log( $log_entry ) {
		$formatted_message = sprintf(
			'[METS-SMTP] [%s] %s',
			$log_entry['level'],
			$log_entry['message']
		);

		if ( ! empty( $log_entry['context'] ) ) {
			$formatted_message .= ' - Context: ' . wp_json_encode( $log_entry['context'] );
		}

		error_log( $formatted_message );
	}

	/**
	 * Store log entry in database
	 *
	 * @since    1.0.0
	 * @param    array    $log_entry    Log entry
	 */
	private function store_log_entry( $log_entry ) {
		global $wpdb;

		// Only store important logs in database to prevent bloat
		$important_levels = array( 'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING' );
		
		if ( ! in_array( $log_entry['level'], $important_levels, true ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'mets_smtp_logs';

		// Create table if it doesn't exist
		$this->maybe_create_logs_table();

		$wpdb->insert(
			$table_name,
			array(
				'level' => $log_entry['level'],
				'message' => $log_entry['message'],
				'context' => wp_json_encode( $log_entry['context'] ),
				'created_at' => $log_entry['timestamp'],
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Create logs table if it doesn't exist
	 *
	 * @since    1.0.0
	 */
	private function maybe_create_logs_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_smtp_logs';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
		
		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			level varchar(20) NOT NULL,
			message text NOT NULL,
			context longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY level (level),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Get recent log entries
	 *
	 * @since    1.0.0
	 * @param    int      $limit    Number of entries to retrieve
	 * @param    string   $level    Filter by log level
	 * @return   array              Log entries
	 */
	public function get_recent_logs( $limit = 50, $level = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_smtp_logs';
		
		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return array();
		}

		$where_clause = '';
		$prepare_args = array( $limit );

		if ( ! empty( $level ) ) {
			$where_clause = 'WHERE level = %s';
			array_unshift( $prepare_args, $level );
		}

		$sql = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $prepare_args ) );
	}

	/**
	 * Clear old log entries
	 *
	 * @since    1.0.0
	 * @param    int    $days_old    Days to keep logs
	 * @return   int                 Number of entries deleted
	 */
	public function clear_old_logs( $days_old = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_smtp_logs';
		
		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return 0;
		}

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days_old
			)
		);

		return $deleted ?: 0;
	}

	/**
	 * Sanitize error message for logging
	 *
	 * @since    1.0.0
	 * @param    string   $error    Error message
	 * @return   string             Sanitized error
	 */
	private function sanitize_error_message( $error ) {
		// Remove sensitive information from error messages
		$patterns = array(
			'/password[=:\s]+[^\s\n\r]+/i' => 'password=***',
			'/pass[=:\s]+[^\s\n\r]+/i' => 'pass=***',
			'/auth[=:\s]+[^\s\n\r]+/i' => 'auth=***',
			'/username[=:\s]+[^\s\n\r@]+@/i' => 'username=***@',
		);

		return preg_replace( array_keys( $patterns ), array_values( $patterns ), $error );
	}

	/**
	 * Sanitize email for logging
	 *
	 * @since    1.0.0
	 * @param    string   $email    Email address
	 * @return   string             Sanitized email
	 */
	private function sanitize_email( $email ) {
		if ( ! is_email( $email ) ) {
			return 'invalid-email';
		}

		// Partially obscure email for privacy
		$parts = explode( '@', $email );
		if ( count( $parts ) === 2 ) {
			$username = $parts[0];
			$domain = $parts[1];
			
			if ( strlen( $username ) > 2 ) {
				$username = substr( $username, 0, 2 ) . '***';
			}
			
			return $username . '@' . $domain;
		}

		return $email;
	}

	/**
	 * Sanitize username for logging
	 *
	 * @since    1.0.0
	 * @param    string   $username    Username
	 * @return   string                Sanitized username
	 */
	private function sanitize_username( $username ) {
		if ( strlen( $username ) <= 3 ) {
			return '***';
		}

		return substr( $username, 0, 3 ) . '***';
	}

	/**
	 * Sanitize debug output
	 *
	 * @since    1.0.0
	 * @param    array    $debug    Debug data
	 * @return   array              Sanitized debug data
	 */
	private function sanitize_debug_output( $debug ) {
		if ( ! is_array( $debug ) ) {
			return array();
		}

		$sanitized = array();
		
		foreach ( $debug as $line ) {
			$sanitized[] = $this->sanitize_error_message( $line );
		}

		// Limit debug output size
		return array_slice( $sanitized, 0, 20 );
	}

	/**
	 * Get log statistics
	 *
	 * @since    1.0.0
	 * @return   array    Log statistics
	 */
	public function get_log_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_smtp_logs';
		
		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
			return array();
		}

		$stats = $wpdb->get_results(
			"SELECT 
				level,
				COUNT(*) as count,
				MAX(created_at) as latest
			FROM $table_name 
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY level
			ORDER BY count DESC",
			ARRAY_A
		);

		return $stats ?: array();
	}
}