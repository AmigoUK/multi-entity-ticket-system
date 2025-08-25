<?php
/**
 * SMTP Manager
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @since      1.0.0
 */

/**
 * SMTP Manager class.
 *
 * This class handles SMTP configuration management, credential encryption,
 * and provides the main interface for SMTP functionality.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SMTP_Manager {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Manager    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Encryption key for credentials
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $encryption_key    Encryption key.
	 */
	private $encryption_key;

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
	 * @return   METS_SMTP_Manager    Single instance
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
		$this->encryption_key = $this->get_encryption_key();
		$this->logger = METS_SMTP_Logger::get_instance();
	}

	/**
	 * Get or generate encryption key
	 *
	 * @since    1.0.0
	 * @return   string    Encryption key
	 */
	private function get_encryption_key() {
		$key_file = WP_CONTENT_DIR . '/mets-secure-key.php';
		
		if ( ! file_exists( $key_file ) ) {
			$this->generate_secure_key( $key_file );
		}
		
		$key_data = include $key_file;
		return base64_decode( $key_data['key'] );
	}

	/**
	 * Generate secure encryption key file
	 *
	 * @since    1.0.0
	 * @param    string    $key_file    Path to key file
	 */
	private function generate_secure_key( $key_file ) {
		// Generate cryptographically secure random key
		$key = base64_encode( random_bytes( 32 ) );
		
		// Create secure PHP file with ABSPATH check
		$content = "<?php\n";
		$content .= "// METS Secure Key - DO NOT EDIT OR SHARE\n";
		$content .= "if ( ! defined( 'ABSPATH' ) ) { exit; }\n";
		$content .= "return array( 'key' => '{$key}', 'created' => '" . date( 'Y-m-d H:i:s' ) . "' );\n";
		
		// Write file with exclusive lock
		if ( file_put_contents( $key_file, $content, LOCK_EX ) === false ) {
			error_log( 'METS Security Error: Unable to create encryption key file' );
			// Fallback to database storage (less secure but functional)
			$key = wp_generate_password( 64, true, true );
			update_option( 'mets_smtp_encryption_key_fallback', $key );
			return;
		}
		
		// Set restrictive file permissions
		chmod( $key_file, 0600 );
		
		// Log key creation for security audit
		error_log( 'METS Security: New encryption key generated at ' . date( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Encrypt sensitive data
	 *
	 * @since    1.0.0
	 * @param    string    $data    Data to encrypt
	 * @return   string             Encrypted data
	 */
	public function encrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		// Generate random IV for each encryption
		$iv = random_bytes( 16 );
		
		// Use the key directly (already 32 bytes)
		$key = $this->encryption_key;
		
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
		
		if ( $encrypted === false ) {
			error_log( 'METS Security Error: Encryption failed' );
			return '';
		}
		
		// Prepend IV to encrypted data and encode
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt sensitive data
	 *
	 * @since    1.0.0
	 * @param    string    $encrypted_data    Encrypted data
	 * @return   string                       Decrypted data
	 */
	public function decrypt( $encrypted_data ) {
		if ( empty( $encrypted_data ) ) {
			return '';
		}

		$data = base64_decode( $encrypted_data );
		if ( $data === false || strlen( $data ) < 16 ) {
			error_log( 'METS Security Error: Invalid encrypted data format' );
			return '';
		}
		
		// Extract IV and encrypted content
		$iv = substr( $data, 0, 16 );
		$encrypted = substr( $data, 16 );
		
		// Use the key directly
		$key = $this->encryption_key;
		
		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
		
		if ( $decrypted === false ) {
			error_log( 'METS Security Error: Decryption failed' );
			return '';
		}
		
		return $decrypted;
	}

	/**
	 * Get global SMTP settings
	 *
	 * @since    1.0.0
	 * @return   array    SMTP settings
	 */
	public function get_global_settings() {
		$settings = get_option( 'mets_smtp_settings', array() );
		
		$defaults = array(
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
		);
		
		$settings = wp_parse_args( $settings, $defaults );
		
		// Decrypt password if exists
		if ( ! empty( $settings['password'] ) ) {
			$settings['password'] = $this->decrypt( $settings['password'] );
		}
		
		return $settings;
	}

	/**
	 * Save global SMTP settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @return   bool                  True on success
	 */
	public function save_global_settings( $settings ) {
		// Validate settings
		$validation = $this->validate_settings( $settings );
		if ( ! $validation['valid'] ) {
			return false;
		}
		
		// Encrypt password before saving
		if ( ! empty( $settings['password'] ) ) {
			$settings['password'] = $this->encrypt( $settings['password'] );
		}
		
		// Sanitize settings
		$settings = $this->sanitize_settings( $settings );
		
		return update_option( 'mets_smtp_settings', $settings );
	}

	/**
	 * Get entity SMTP settings
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array|false          SMTP settings or false if not configured
	 */
	public function get_entity_settings( $entity_id ) {
		global $wpdb;
		
		$smtp_settings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT smtp_settings FROM {$wpdb->prefix}mets_entities WHERE id = %d",
				$entity_id
			)
		);
		
		if ( empty( $smtp_settings ) ) {
			return false;
		}
		
		$settings = json_decode( $smtp_settings, true );
		
		if ( ! is_array( $settings ) ) {
			return false;
		}
		
		// Decrypt password if exists
		if ( ! empty( $settings['password'] ) ) {
			$settings['password'] = $this->decrypt( $settings['password'] );
		}
		
		return $settings;
	}

	/**
	 * Save entity SMTP settings
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @param    array    $settings     SMTP settings
	 * @return   bool                   True on success
	 */
	public function save_entity_settings( $entity_id, $settings ) {
		global $wpdb;
		
		// If settings are empty or disabled, clear entity settings
		if ( empty( $settings ) || empty( $settings['enabled'] ) ) {
			return $wpdb->update(
				$wpdb->prefix . 'mets_entities',
				array( 'smtp_settings' => null ),
				array( 'id' => $entity_id ),
				array( '%s' ),
				array( '%d' )
			) !== false;
		}
		
		// Validate settings
		$validation = $this->validate_settings( $settings );
		if ( ! $validation['valid'] ) {
			return false;
		}
		
		// Encrypt password before saving
		if ( ! empty( $settings['password'] ) ) {
			$settings['password'] = $this->encrypt( $settings['password'] );
		}
		
		// Sanitize settings
		$settings = $this->sanitize_settings( $settings );
		
		// Save to database
		return $wpdb->update(
			$wpdb->prefix . 'mets_entities',
			array( 'smtp_settings' => json_encode( $settings ) ),
			array( 'id' => $entity_id ),
			array( '%s' ),
			array( '%d' )
		) !== false;
	}

	/**
	 * Get effective SMTP settings for an entity
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                SMTP settings (entity or global)
	 */
	public function get_effective_settings( $entity_id = null ) {
		// First check entity-specific settings
		if ( $entity_id ) {
			$entity_settings = $this->get_entity_settings( $entity_id );
			if ( $entity_settings && ! empty( $entity_settings['enabled'] ) ) {
				return $entity_settings;
			}
		}
		
		// Fall back to global settings
		return $this->get_global_settings();
	}

	/**
	 * Validate SMTP settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @return   array                 Validation result
	 */
	public function validate_settings( $settings ) {
		$result = array(
			'valid' => true,
			'errors' => array(),
		);
		
		// Skip validation if SMTP is disabled
		if ( empty( $settings['enabled'] ) || $settings['method'] === 'wordpress' ) {
			return $result;
		}
		
		// Validate provider if selected
		if ( ! empty( $settings['provider'] ) && $settings['provider'] !== 'custom' ) {
			require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-providers.php';
			return METS_SMTP_Providers::validate_provider_credentials( $settings['provider'], $settings );
		}
		
		// Validate custom SMTP settings
		if ( empty( $settings['host'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'SMTP host is required', METS_TEXT_DOMAIN );
		}
		
		if ( empty( $settings['port'] ) || ! is_numeric( $settings['port'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Valid SMTP port is required', METS_TEXT_DOMAIN );
		}
		
		if ( $settings['auth_required'] ) {
			if ( empty( $settings['username'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Username is required when authentication is enabled', METS_TEXT_DOMAIN );
			}
			
			if ( empty( $settings['password'] ) ) {
				$result['valid'] = false;
				$result['errors'][] = __( 'Password is required when authentication is enabled', METS_TEXT_DOMAIN );
			}
		}
		
		// Validate email addresses
		if ( ! empty( $settings['from_email'] ) && ! is_email( $settings['from_email'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Invalid From Email address', METS_TEXT_DOMAIN );
		}
		
		if ( ! empty( $settings['reply_to'] ) && ! is_email( $settings['reply_to'] ) ) {
			$result['valid'] = false;
			$result['errors'][] = __( 'Invalid Reply-To Email address', METS_TEXT_DOMAIN );
		}
		
		return $result;
	}

	/**
	 * Sanitize SMTP settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @return   array                 Sanitized settings
	 */
	private function sanitize_settings( $settings ) {
		$sanitized = array();
		
		// Boolean fields
		$sanitized['enabled'] = ! empty( $settings['enabled'] );
		$sanitized['auth_required'] = ! empty( $settings['auth_required'] );
		
		// String fields
		$string_fields = array( 'method', 'provider', 'host', 'encryption', 'username', 'password', 'from_name' );
		foreach ( $string_fields as $field ) {
			$sanitized[ $field ] = isset( $settings[ $field ] ) ? sanitize_text_field( $settings[ $field ] ) : '';
		}
		
		// Email fields
		$email_fields = array( 'from_email', 'reply_to', 'test_email' );
		foreach ( $email_fields as $field ) {
			$sanitized[ $field ] = isset( $settings[ $field ] ) ? sanitize_email( $settings[ $field ] ) : '';
		}
		
		// Numeric fields
		$sanitized['port'] = isset( $settings['port'] ) ? absint( $settings['port'] ) : 587;
		
		return $sanitized;
	}

	/**
	 * Test SMTP connection
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings to test
	 * @return   array                 Test result
	 */
	public function test_connection( $settings ) {
		$result = array(
			'success' => false,
			'message' => '',
			'debug' => array(),
		);
		
		// Validate settings first
		$validation = $this->validate_settings( $settings );
		if ( ! $validation['valid'] ) {
			$result['message'] = implode( ', ', $validation['errors'] );
			return $result;
		}
		
		// Use WordPress mail for testing if selected
		if ( $settings['method'] === 'wordpress' ) {
			$result['success'] = true;
			$result['message'] = __( 'WordPress default mail is selected. Connection test not required.', METS_TEXT_DOMAIN );
			return $result;
		}
		
		// Test SMTP connection using PHPMailer
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-mailer.php';
		$mailer = new METS_SMTP_Mailer();
		
		$connection_result = $mailer->test_connection( $settings );
		
		// Log the connection attempt
		$this->logger->log_connection( $settings, $connection_result['success'], $connection_result['message'] ?? '' );
		
		return $connection_result;
	}

	/**
	 * Send test email
	 *
	 * @since    1.0.0
	 * @param    array    $settings        SMTP settings to use
	 * @param    string   $test_email      Email address to send test to
	 * @return   array                     Test result
	 */
	public function send_test_email( $settings, $test_email = '' ) {
		$result = array(
			'success' => false,
			'message' => '',
		);
		
		if ( empty( $test_email ) ) {
			$test_email = ! empty( $settings['test_email'] ) ? $settings['test_email'] : get_option( 'admin_email' );
		}
		
		if ( ! is_email( $test_email ) ) {
			$result['message'] = __( 'Invalid test email address', METS_TEXT_DOMAIN );
			return $result;
		}
		
		// Prepare test email content
		$subject = sprintf( __( 'SMTP Test Email - %s', METS_TEXT_DOMAIN ), get_bloginfo( 'name' ) );
		$message = sprintf(
			__( "This is a test email from the Multi-Entity Ticket System SMTP configuration.\n\nIf you received this email, your SMTP settings are working correctly!\n\nConfiguration Details:\n- Method: %s\n- Provider: %s\n- Host: %s\n- Port: %s\n- Encryption: %s\n\nSent on: %s", METS_TEXT_DOMAIN ),
			$settings['method'],
			$settings['provider'] ?: 'Custom',
			$settings['host'] ?: 'N/A',
			$settings['port'] ?: 'N/A',
			$settings['encryption'] ?: 'N/A',
			current_time( 'mysql' )
		);
		
		// Send email using configured settings
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-mailer.php';
		$mailer = new METS_SMTP_Mailer();
		
		$send_result = $mailer->send_email(
			$test_email,
			$subject,
			$message,
			array(),
			$settings
		);
		
		if ( $send_result['success'] ) {
			$result['success'] = true;
			$result['message'] = sprintf( __( 'Test email sent successfully to %s', METS_TEXT_DOMAIN ), $test_email );
		} else {
			$result['message'] = $send_result['error'];
		}
		
		return $result;
	}

	/**
	 * Get SMTP configuration for email queue
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID (optional)
	 * @return   array                SMTP configuration for queue
	 */
	public function get_queue_config( $entity_id = null ) {
		$settings = $this->get_effective_settings( $entity_id );
		
		// Don't include sensitive data in queue
		$config = array(
			'method' => $settings['method'],
			'entity_id' => $entity_id,
		);
		
		// Add non-sensitive settings for debugging
		if ( $settings['method'] !== 'wordpress' ) {
			$config['provider'] = $settings['provider'];
			$config['host'] = $settings['host'];
			$config['port'] = $settings['port'];
			$config['encryption'] = $settings['encryption'];
			$config['from_email'] = $settings['from_email'];
			$config['from_name'] = $settings['from_name'];
		}
		
		return $config;
	}

	/**
	 * Clear SMTP cache
	 *
	 * @since    1.0.0
	 */
	public function clear_cache() {
		delete_transient( 'mets_smtp_test_cache' );
		wp_cache_delete( 'mets_smtp_settings', 'options' );
	}

	/**
	 * Rotate encryption key and re-encrypt all passwords
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function rotate_encryption_key() {
		global $wpdb;
		
		// Get all entities with SMTP passwords
		$entities = $wpdb->get_results(
			"SELECT id, smtp_settings FROM {$wpdb->prefix}mets_entities 
			WHERE smtp_settings IS NOT NULL AND smtp_settings != ''"
		);
		
		// Get global settings
		$global_settings = get_option( 'mets_smtp_settings', array() );
		
		// Decrypt all passwords with current key
		$decrypted_passwords = array();
		
		foreach ( $entities as $entity ) {
			$settings = json_decode( $entity->smtp_settings, true );
			if ( ! empty( $settings['password'] ) ) {
				$decrypted_passwords[ $entity->id ] = $this->decrypt( $settings['password'] );
			}
		}
		
		$global_password = '';
		if ( ! empty( $global_settings['password'] ) ) {
			$global_password = $this->decrypt( $global_settings['password'] );
		}
		
		// Generate new key
		$key_file = WP_CONTENT_DIR . '/mets-secure-key.php';
		if ( file_exists( $key_file ) ) {
			unlink( $key_file );
		}
		$this->generate_secure_key( $key_file );
		
		// Update encryption key in instance
		$this->encryption_key = $this->get_encryption_key();
		
		// Re-encrypt all passwords with new key
		foreach ( $decrypted_passwords as $entity_id => $password ) {
			$settings = json_decode( $wpdb->get_var( $wpdb->prepare(
				"SELECT smtp_settings FROM {$wpdb->prefix}mets_entities WHERE id = %d",
				$entity_id
			) ), true );
			
			$settings['password'] = $this->encrypt( $password );
			
			$wpdb->update(
				$wpdb->prefix . 'mets_entities',
				array( 'smtp_settings' => json_encode( $settings ) ),
				array( 'id' => $entity_id ),
				array( '%s' ),
				array( '%d' )
			);
		}
		
		// Re-encrypt global password
		if ( ! empty( $global_password ) ) {
			$global_settings['password'] = $this->encrypt( $global_password );
			update_option( 'mets_smtp_settings', $global_settings );
		}
		
		// Log rotation for security audit
		error_log( 'METS Security: Encryption key rotated successfully at ' . date( 'Y-m-d H:i:s' ) );
		
		return true;
	}

	/**
	 * Test encryption functionality
	 *
	 * @since    1.0.0
	 * @return   array    Test results
	 */
	public function test_encryption() {
		$test_password = 'TestPassword123!@#$%^&*()';
		
		$encrypted = $this->encrypt( $test_password );
		$decrypted = $this->decrypt( $encrypted );
		
		return array(
			'original' => $test_password,
			'encrypted' => $encrypted,
			'decrypted' => $decrypted,
			'success' => ( $test_password === $decrypted ),
			'encrypted_length' => strlen( $encrypted ),
			'key_exists' => file_exists( WP_CONTENT_DIR . '/mets-secure-key.php' )
		);
	}
}