<?php
/**
 * SMTP Diagnostics Tool
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * SMTP Diagnostics Tool class.
 *
 * This class provides comprehensive diagnostics for SMTP configuration and connectivity issues.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SMTP_Diagnostics {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Diagnostics    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * SMTP manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Manager    $smtp_manager    SMTP manager instance.
	 */
	private $smtp_manager;

	/**
	 * Logger instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Logger    $logger    Logger instance.
	 */
	private $logger;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_SMTP_Diagnostics    Single instance
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
		$this->smtp_manager = METS_SMTP_Manager::get_instance();
		$this->logger = METS_SMTP_Logger::get_instance();
	}

	/**
	 * Run comprehensive SMTP diagnostics
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID (optional)
	 * @return   array                Diagnostics results
	 */
	public function run_diagnostics( $entity_id = null ) {
		$results = array(
			'overall_status' => 'unknown',
			'tests' => array(),
			'recommendations' => array(),
			'system_info' => $this->get_system_info(),
		);

		// Test 1: Configuration validation
		$config_test = $this->test_configuration( $entity_id );
		$results['tests']['configuration'] = $config_test;

		// Test 2: Network connectivity
		$network_test = $this->test_network_connectivity( $entity_id );
		$results['tests']['network'] = $network_test;

		// Test 3: SMTP authentication
		$auth_test = $this->test_smtp_authentication( $entity_id );
		$results['tests']['authentication'] = $auth_test;

		// Test 4: Email sending capability
		$send_test = $this->test_email_sending( $entity_id );
		$results['tests']['email_sending'] = $send_test;

		// Test 5: Queue processing
		$queue_test = $this->test_queue_processing();
		$results['tests']['queue_processing'] = $queue_test;

		// Determine overall status
		$results['overall_status'] = $this->determine_overall_status( $results['tests'] );

		// Generate recommendations
		$results['recommendations'] = $this->generate_recommendations( $results['tests'] );

		return $results;
	}

	/**
	 * Test SMTP configuration
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Test results
	 */
	private function test_configuration( $entity_id = null ) {
		$test = array(
			'name' => __( 'Configuration Validation', METS_TEXT_DOMAIN ),
			'status' => 'pass',
			'message' => '',
			'details' => array(),
			'issues' => array(),
		);

		$settings = $this->smtp_manager->get_effective_settings( $entity_id );

		// Check if SMTP is enabled
		if ( empty( $settings['enabled'] ) || $settings['method'] === 'wordpress' ) {
			$test['status'] = 'info';
			$test['message'] = __( 'SMTP is disabled, using WordPress default mail', METS_TEXT_DOMAIN );
			$test['details'][] = __( 'Method: WordPress Default', METS_TEXT_DOMAIN );
			return $test;
		}

		// Validate SMTP settings
		$validation = $this->smtp_manager->validate_settings( $settings );
		
		if ( ! $validation['valid'] ) {
			$test['status'] = 'fail';
			$test['message'] = __( 'Configuration errors found', METS_TEXT_DOMAIN );
			$test['issues'] = $validation['errors'];
			return $test;
		}

		// Configuration looks good
		$test['message'] = __( 'SMTP configuration is valid', METS_TEXT_DOMAIN );
		$test['details'] = array(
			sprintf( __( 'Method: %s', METS_TEXT_DOMAIN ), ucfirst( $settings['method'] ) ),
			sprintf( __( 'Provider: %s', METS_TEXT_DOMAIN ), $settings['provider'] ?: 'Custom' ),
			sprintf( __( 'Host: %s', METS_TEXT_DOMAIN ), $settings['host'] ),
			sprintf( __( 'Port: %s', METS_TEXT_DOMAIN ), $settings['port'] ),
			sprintf( __( 'Encryption: %s', METS_TEXT_DOMAIN ), strtoupper( $settings['encryption'] ) ),
			sprintf( __( 'Authentication: %s', METS_TEXT_DOMAIN ), $settings['auth_required'] ? 'Required' : 'None' ),
		);

		return $test;
	}

	/**
	 * Test network connectivity
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Test results
	 */
	private function test_network_connectivity( $entity_id = null ) {
		$test = array(
			'name' => __( 'Network Connectivity', METS_TEXT_DOMAIN ),
			'status' => 'unknown',
			'message' => '',
			'details' => array(),
			'issues' => array(),
		);

		$settings = $this->smtp_manager->get_effective_settings( $entity_id );

		if ( $settings['method'] === 'wordpress' ) {
			$test['status'] = 'skip';
			$test['message'] = __( 'Network test skipped for WordPress mail', METS_TEXT_DOMAIN );
			return $test;
		}

		$host = $settings['host'];
		$port = $settings['port'];

		// Test basic TCP connection
		$connection = @fsockopen( $host, $port, $errno, $errstr, 10 );
		
		if ( ! $connection ) {
			$test['status'] = 'fail';
			$test['message'] = sprintf( 
				__( 'Cannot connect to %s:%s', METS_TEXT_DOMAIN ), 
				$host, 
				$port 
			);
			$test['issues'][] = sprintf( 
				__( 'Error %d: %s', METS_TEXT_DOMAIN ), 
				$errno, 
				$errstr 
			);
			
			// Add common solutions
			$test['issues'][] = __( 'Possible causes: Firewall blocking connection, incorrect host/port, server down', METS_TEXT_DOMAIN );
			
			return $test;
		}

		fclose( $connection );

		$test['status'] = 'pass';
		$test['message'] = sprintf( 
			__( 'Successfully connected to %s:%s', METS_TEXT_DOMAIN ), 
			$host, 
			$port 
		);

		// Additional network info
		$test['details'][] = sprintf( __( 'Connection time: < 10 seconds', METS_TEXT_DOMAIN ) );
		
		// Check for SSL/TLS if required
		if ( in_array( $settings['encryption'], array( 'tls', 'ssl' ), true ) ) {
			$ssl_context = stream_context_create( array(
				'ssl' => array(
					'verify_peer' => true,
					'verify_peer_name' => true,
				)
			) );
			
			$ssl_connection = @stream_socket_client(
				"ssl://{$host}:{$port}",
				$errno,
				$errstr,
				10,
				STREAM_CLIENT_CONNECT,
				$ssl_context
			);
			
			if ( $ssl_connection ) {
				$test['details'][] = sprintf( __( 'SSL/TLS connection: Available', METS_TEXT_DOMAIN ) );
				fclose( $ssl_connection );
			} else {
				$test['details'][] = sprintf( __( 'SSL/TLS connection: Issues detected', METS_TEXT_DOMAIN ) );
				$test['issues'][] = sprintf( __( 'SSL Error: %s', METS_TEXT_DOMAIN ), $errstr );
			}
		}

		return $test;
	}

	/**
	 * Test SMTP authentication
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Test results
	 */
	private function test_smtp_authentication( $entity_id = null ) {
		$test = array(
			'name' => __( 'SMTP Authentication', METS_TEXT_DOMAIN ),
			'status' => 'unknown',
			'message' => '',
			'details' => array(),
			'issues' => array(),
		);

		$settings = $this->smtp_manager->get_effective_settings( $entity_id );

		if ( $settings['method'] === 'wordpress' ) {
			$test['status'] = 'skip';
			$test['message'] = __( 'Authentication test skipped for WordPress mail', METS_TEXT_DOMAIN );
			return $test;
		}

		// Test SMTP connection with authentication
		$connection_result = $this->smtp_manager->test_connection( $settings );
		
		if ( $connection_result['success'] ) {
			$test['status'] = 'pass';
			$test['message'] = __( 'SMTP authentication successful', METS_TEXT_DOMAIN );
			$test['details'][] = $connection_result['message'];
		} else {
			$test['status'] = 'fail';
			$test['message'] = __( 'SMTP authentication failed', METS_TEXT_DOMAIN );
			$test['issues'][] = $connection_result['message'];
			
			// Analyze common authentication errors
			$error_msg = strtolower( $connection_result['message'] );
			
			if ( strpos( $error_msg, 'authentication' ) !== false ) {
				$test['issues'][] = __( 'Possible causes: Incorrect username/password, 2FA enabled, app passwords required', METS_TEXT_DOMAIN );
			}
			
			if ( strpos( $error_msg, 'certificate' ) !== false || strpos( $error_msg, 'ssl' ) !== false ) {
				$test['issues'][] = __( 'SSL/TLS certificate issues detected', METS_TEXT_DOMAIN );
			}
		}

		return $test;
	}

	/**
	 * Test email sending capability
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Test results
	 */
	private function test_email_sending( $entity_id = null ) {
		$test = array(
			'name' => __( 'Email Sending Test', METS_TEXT_DOMAIN ),
			'status' => 'unknown',
			'message' => '',
			'details' => array(),
			'issues' => array(),
		);

		$settings = $this->smtp_manager->get_effective_settings( $entity_id );
		$test_email = get_option( 'admin_email' );

		// Send a test email
		$email_result = $this->smtp_manager->send_test_email( $settings, $test_email );
		
		if ( $email_result['success'] ) {
			$test['status'] = 'pass';
			$test['message'] = $email_result['message'];
			$test['details'][] = sprintf( 
				__( 'Test email sent to: %s', METS_TEXT_DOMAIN ), 
				$test_email 
			);
		} else {
			$test['status'] = 'fail';
			$test['message'] = __( 'Failed to send test email', METS_TEXT_DOMAIN );
			$test['issues'][] = $email_result['message'];
		}

		return $test;
	}

	/**
	 * Test email queue processing
	 *
	 * @since    1.0.0
	 * @return   array    Test results
	 */
	private function test_queue_processing() {
		$test = array(
			'name' => __( 'Email Queue Processing', METS_TEXT_DOMAIN ),
			'status' => 'unknown',
			'message' => '',
			'details' => array(),
			'issues' => array(),
		);

		$email_queue = METS_Email_Queue::get_instance();
		$stats = $email_queue->get_queue_stats();

		if ( empty( $stats ) ) {
			$test['status'] = 'info';
			$test['message'] = __( 'No email queue data available', METS_TEXT_DOMAIN );
			return $test;
		}

		$test['status'] = 'pass';
		$test['message'] = __( 'Email queue is operational', METS_TEXT_DOMAIN );
		
		$test['details'] = array(
			sprintf( __( 'Total emails: %d', METS_TEXT_DOMAIN ), $stats['total'] ),
			sprintf( __( 'Pending: %d', METS_TEXT_DOMAIN ), $stats['pending'] ),
			sprintf( __( 'Sent: %d', METS_TEXT_DOMAIN ), $stats['sent'] ),
			sprintf( __( 'Failed: %d', METS_TEXT_DOMAIN ), $stats['failed'] ),
		);

		if ( $stats['failed'] > 0 ) {
			$failure_rate = ( $stats['failed'] / max( $stats['total'], 1 ) ) * 100;
			if ( $failure_rate > 10 ) {
				$test['status'] = 'warning';
				$test['issues'][] = sprintf( 
					__( 'High failure rate: %.1f%% of emails failed', METS_TEXT_DOMAIN ), 
					$failure_rate 
				);
			}
		}

		// Check for stuck emails
		if ( $stats['pending'] > 50 ) {
			$test['issues'][] = __( 'Large number of pending emails - queue processing may be slow', METS_TEXT_DOMAIN );
		}

		return $test;
	}

	/**
	 * Get system information
	 *
	 * @since    1.0.0
	 * @return   array    System information
	 */
	private function get_system_info() {
		return array(
			'php_version' => PHP_VERSION,
			'wp_version' => get_bloginfo( 'version' ),
			'openssl_enabled' => extension_loaded( 'openssl' ),
			'curl_enabled' => extension_loaded( 'curl' ),
			'mail_function' => function_exists( 'mail' ),
			'wp_mail_available' => function_exists( 'wp_mail' ),
			'phpmailer_version' => class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ? 'Available' : 'Not Available',
			'server_time' => current_time( 'mysql' ),
			'timezone' => wp_timezone_string(),
		);
	}

	/**
	 * Determine overall status from individual tests
	 *
	 * @since    1.0.0
	 * @param    array    $tests    Test results
	 * @return   string             Overall status
	 */
	private function determine_overall_status( $tests ) {
		$statuses = array_column( $tests, 'status' );

		if ( in_array( 'fail', $statuses, true ) ) {
			return 'fail';
		}

		if ( in_array( 'warning', $statuses, true ) ) {
			return 'warning';
		}

		if ( in_array( 'pass', $statuses, true ) ) {
			return 'pass';
		}

		return 'unknown';
	}

	/**
	 * Generate recommendations based on test results
	 *
	 * @since    1.0.0
	 * @param    array    $tests    Test results
	 * @return   array              Recommendations
	 */
	private function generate_recommendations( $tests ) {
		$recommendations = array();

		foreach ( $tests as $test ) {
			if ( $test['status'] === 'fail' && ! empty( $test['issues'] ) ) {
				$recommendations[] = array(
					'type' => 'error',
					'title' => sprintf( __( 'Fix %s Issues', METS_TEXT_DOMAIN ), $test['name'] ),
					'description' => implode( ' ', $test['issues'] ),
				);
			}

			if ( $test['status'] === 'warning' && ! empty( $test['issues'] ) ) {
				$recommendations[] = array(
					'type' => 'warning',
					'title' => sprintf( __( 'Review %s', METS_TEXT_DOMAIN ), $test['name'] ),
					'description' => implode( ' ', $test['issues'] ),
				);
			}
		}

		// General recommendations
		if ( empty( $recommendations ) ) {
			$recommendations[] = array(
				'type' => 'success',
				'title' => __( 'SMTP Configuration Looks Good', METS_TEXT_DOMAIN ),
				'description' => __( 'All tests passed successfully. Your SMTP configuration appears to be working correctly.', METS_TEXT_DOMAIN ),
			);
		}

		return $recommendations;
	}

	/**
	 * Get recent error logs
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Number of entries to retrieve
	 * @return   array            Recent error logs
	 */
	public function get_recent_errors( $limit = 10 ) {
		return $this->logger->get_recent_logs( $limit, 'ERROR' );
	}

	/**
	 * Get system status for dashboard widget
	 *
	 * @since    1.0.0
	 * @return   array    System status summary
	 */
	public function get_system_status() {
		$settings = $this->smtp_manager->get_global_settings();
		$email_queue = METS_Email_Queue::get_instance();
		$queue_stats = $email_queue->get_queue_stats();
		$recent_errors = $this->get_recent_errors( 5 );

		return array(
			'smtp_enabled' => ! empty( $settings['enabled'] ) && $settings['method'] !== 'wordpress',
			'smtp_method' => $settings['method'] ?? 'wordpress',
			'smtp_provider' => $settings['provider'] ?? '',
			'queue_pending' => $queue_stats['pending'] ?? 0,
			'queue_failed' => $queue_stats['failed'] ?? 0,
			'recent_errors' => count( $recent_errors ),
			'last_error' => ! empty( $recent_errors ) ? $recent_errors[0]->created_at : null,
		);
	}
}