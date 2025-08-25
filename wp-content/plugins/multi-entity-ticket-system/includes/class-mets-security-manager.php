<?php
/**
 * Security Management System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Security_Manager {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Security_Manager    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Security configuration
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $security_config
	 */
	private $security_config = array(
		'enable_rate_limiting' => true,
		'enable_input_validation' => true,
		'enable_output_sanitization' => true,
		'enable_csrf_protection' => true,
		'enable_sql_injection_protection' => true, // Temporarily disabled to prevent blocking legitimate queries
		'enable_file_upload_security' => true,
		'enable_session_security' => true,
		'max_login_attempts' => 5,
		'lockout_duration' => 900, // 15 minutes
		'max_upload_size' => 10485760, // 10MB
		'allowed_file_types' => array( 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt' ),
		'sensitive_data_fields' => array( 'password', 'token', 'key', 'secret', 'api_key' )
	);

	/**
	 * Rate limiting data
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $rate_limits
	 */
	private $rate_limits = array();

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Security_Manager    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->load_security_config();
		$this->init_security_measures();
	}

	/**
	 * Load security configuration from database
	 *
	 * @since    1.0.0
	 */
	private function load_security_config() {
		global $wpdb;
		
		$security_config_table = $wpdb->prefix . 'mets_security_config';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $security_config_table ) );
		
		if ( $table_exists ) {
			$configs = $wpdb->get_results( "SELECT config_key, config_value FROM $security_config_table" );
			
			foreach ( $configs as $config ) {
				$value = $config->config_value;
				
				// Handle special data types
				if ( $config->config_key === 'allowed_file_types' ) {
					$value = json_decode( $value, true );
				} elseif ( in_array( $config->config_key, array( 'enable_rate_limiting', 'enable_input_validation', 'enable_output_sanitization', 'enable_csrf_protection', 'enable_sql_injection_protection', 'enable_file_upload_security', 'enable_session_security', 'security_headers_enabled', 'audit_trail_enabled', 'security_notifications_enabled' ) ) ) {
					$value = (bool) $value;
				} elseif ( in_array( $config->config_key, array( 'max_login_attempts', 'lockout_duration', 'max_upload_size' ) ) ) {
					$value = (int) $value;
				}
				
				$this->security_config[ $config->config_key ] = $value;
			}
		}
	}

	/**
	 * Initialize security measures
	 *
	 * @since    1.0.0
	 */
	private function init_security_measures() {
		// Input validation and sanitization
		add_filter( 'mets_sanitize_input', array( $this, 'sanitize_input' ), 10, 2 );
		add_filter( 'mets_validate_input', array( $this, 'validate_input' ), 10, 3 );
		
		// Output sanitization
		add_filter( 'mets_sanitize_output', array( $this, 'sanitize_output' ), 10, 2 );
		
		// File upload security
		add_filter( 'mets_validate_file_upload', array( $this, 'validate_file_upload' ), 10, 2 );
		
		// Rate limiting
		add_action( 'wp_ajax_mets_check_rate_limit', array( $this, 'check_rate_limit' ) );
		add_action( 'wp_ajax_nopriv_mets_check_rate_limit', array( $this, 'check_rate_limit' ) );
		
		// Security headers
		add_action( 'send_headers', array( $this, 'add_security_headers' ) );
		
		// Login attempt monitoring
		add_action( 'wp_login_failed', array( $this, 'handle_failed_login' ) );
		add_filter( 'authenticate', array( $this, 'check_login_attempts' ), 30, 3 );
		
		// SQL injection protection
		add_filter( 'query', array( $this, 'detect_sql_injection' ) );
		
		// Session security
		add_action( 'init', array( $this, 'secure_session_config' ) );
		
		// Content Security Policy
		add_action( 'wp_head', array( $this, 'add_csp_meta' ) );
		add_action( 'admin_head', array( $this, 'add_csp_meta' ) );
	}

	/**
	 * Sanitize input data
	 *
	 * @since    1.0.0
	 * @param    mixed     $input    Input data
	 * @param    string    $type     Input type
	 * @return   mixed               Sanitized input
	 */
	public function sanitize_input( $input, $type = 'text' ) {
		if ( is_array( $input ) ) {
			return array_map( function( $item ) use ( $type ) {
				return $this->sanitize_input( $item, $type );
			}, $input );
		}

		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );
			
			case 'url':
				return esc_url_raw( $input );
			
			case 'int':
			case 'integer':
				return intval( $input );
			
			case 'float':
				return floatval( $input );
			
			case 'bool':
			case 'boolean':
				return (bool) $input;
			
			case 'html':
				return wp_kses_post( $input );
			
			case 'textarea':
				return sanitize_textarea_field( $input );
			
			case 'key':
				return sanitize_key( $input );
			
			case 'slug':
				return sanitize_title( $input );
			
			case 'filename':
				return sanitize_file_name( $input );
			
			case 'sql':
				return $this->sanitize_sql_input( $input );
			
			case 'json':
				return $this->sanitize_json_input( $input );
			
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Validate input data
	 *
	 * @since    1.0.0
	 * @param    mixed     $input      Input data
	 * @param    string    $type       Validation type
	 * @param    array     $options    Validation options
	 * @return   bool                  Validation result
	 */
	public function validate_input( $input, $type, $options = array() ) {
		$input = $this->sanitize_input( $input, $type );

		switch ( $type ) {
			case 'email':
				return is_email( $input );
			
			case 'url':
				return filter_var( $input, FILTER_VALIDATE_URL ) !== false;
			
			case 'int':
			case 'integer':
				$min = isset( $options['min'] ) ? $options['min'] : null;
				$max = isset( $options['max'] ) ? $options['max'] : null;
				
				if ( ! is_numeric( $input ) ) {
					return false;
				}
				
				if ( $min !== null && $input < $min ) {
					return false;
				}
				
				if ( $max !== null && $input > $max ) {
					return false;
				}
				
				return true;
			
			case 'string':
			case 'text':
				$min_length = isset( $options['min_length'] ) ? $options['min_length'] : 0;
				$max_length = isset( $options['max_length'] ) ? $options['max_length'] : 1000;
				
				$length = strlen( $input );
				return $length >= $min_length && $length <= $max_length;
			
			case 'array':
				return is_array( $input );
			
			case 'json':
				return $this->validate_json( $input );
			
			case 'sql_safe':
				return $this->validate_sql_safe( $input );
			
			case 'filename':
				return $this->validate_filename( $input );
			
			case 'nonce':
				$action = isset( $options['action'] ) ? $options['action'] : -1;
				return wp_verify_nonce( $input, $action );
			
			default:
				return true;
		}
	}

	/**
	 * Sanitize output data
	 *
	 * @since    1.0.0
	 * @param    mixed     $output    Output data
	 * @param    string    $context   Output context
	 * @return   mixed                Sanitized output
	 */
	public function sanitize_output( $output, $context = 'html' ) {
		if ( is_array( $output ) ) {
			return array_map( function( $item ) use ( $context ) {
				return $this->sanitize_output( $item, $context );
			}, $output );
		}

		switch ( $context ) {
			case 'html':
				return esc_html( $output );
			
			case 'attr':
			case 'attribute':
				return esc_attr( $output );
			
			case 'url':
				return esc_url( $output );
			
			case 'js':
			case 'javascript':
				return esc_js( $output );
			
			case 'textarea':
				return esc_textarea( $output );
			
			case 'sql':
				return esc_sql( $output );
			
			case 'json':
				return wp_json_encode( $output );
			
			case 'raw':
				return $output;
			
			default:
				return esc_html( $output );
		}
	}

	/**
	 * Validate file upload
	 *
	 * @since    1.0.0
	 * @param    array    $file      File data
	 * @param    array    $options   Upload options
	 * @return   array               Validation result
	 */
	public function validate_file_upload( $file, $options = array() ) {
		$result = array(
			'valid' => false,
			'errors' => array()
		);

		// Check if file was uploaded
		if ( ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			$result['errors'][] = __( 'No file uploaded.', METS_TEXT_DOMAIN );
			return $result;
		}

		// Check for upload errors
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			$result['errors'][] = $this->get_upload_error_message( $file['error'] );
			return $result;
		}

		// Validate file size
		$max_size = isset( $options['max_size'] ) ? $options['max_size'] : $this->security_config['max_upload_size'];
		if ( $file['size'] > $max_size ) {
			$result['errors'][] = sprintf( 
				__( 'File size exceeds maximum allowed size of %s.', METS_TEXT_DOMAIN ), 
				size_format( $max_size ) 
			);
			return $result;
		}

		// Validate file type
		$allowed_types = isset( $options['allowed_types'] ) 
			? $options['allowed_types'] 
			: $this->security_config['allowed_file_types'];
		
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_extension, $allowed_types ) ) {
			$result['errors'][] = sprintf( 
				__( 'File type "%s" is not allowed. Allowed types: %s', METS_TEXT_DOMAIN ), 
				$file_extension, 
				implode( ', ', $allowed_types ) 
			);
			return $result;
		}

		// Validate MIME type
		$file_info = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $file_info, $file['tmp_name'] );
		finfo_close( $file_info );
		
		if ( ! $this->is_allowed_mime_type( $mime_type, $file_extension ) ) {
			$result['errors'][] = __( 'File content does not match file extension.', METS_TEXT_DOMAIN );
			return $result;
		}

		// Scan for malicious content
		if ( $this->scan_file_for_malware( $file['tmp_name'] ) ) {
			$result['errors'][] = __( 'File contains potentially malicious content.', METS_TEXT_DOMAIN );
			return $result;
		}

		$result['valid'] = true;
		return $result;
	}

	/**
	 * Check rate limiting
	 *
	 * @since    1.0.0
	 * @param    string    $action    Action being performed
	 * @param    string    $identifier Rate limit identifier
	 * @return   bool                 Whether rate limit is exceeded
	 */
	public function check_rate_limit( $action = 'general', $identifier = null ) {
		if ( ! $this->security_config['enable_rate_limiting'] ) {
			return false;
		}

		if ( ! $identifier ) {
			$identifier = $this->get_client_identifier();
		}

		$cache_key = "mets_rate_limit_{$action}_{$identifier}";
		$attempts = get_transient( $cache_key );

		$limits = $this->get_rate_limits_for_action( $action );
		
		if ( $attempts >= $limits['max_attempts'] ) {
			// Rate limit exceeded
			$this->log_security_event( 'rate_limit_exceeded', array(
				'action' => $action,
				'identifier' => $identifier,
				'attempts' => $attempts
			) );
			
			return true;
		}

		// Increment attempt counter
		$attempts = $attempts ? $attempts + 1 : 1;
		set_transient( $cache_key, $attempts, $limits['time_window'] );

		return false;
	}

	/**
	 * Add security headers
	 *
	 * @since    1.0.0
	 */
	public function add_security_headers() {
		// Prevent clickjacking
		header( 'X-Frame-Options: SAMEORIGIN' );
		
		// Prevent MIME type sniffing
		header( 'X-Content-Type-Options: nosniff' );
		
		// Enable XSS protection
		header( 'X-XSS-Protection: 1; mode=block' );
		
		// Strict transport security (if HTTPS)
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
		}
		
		// Referrer policy
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		
		// Feature policy
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
		
		// Content Security Policy (only for METS-specific pages to avoid conflicts)
		global $pagenow;
		if ( $this->should_apply_csp() ) {
			$csp = $this->generate_csp_header();
			if ( $csp ) {
				header( 'Content-Security-Policy: ' . $csp );
			}
		}
	}

	/**
	 * Generate Content Security Policy header
	 *
	 * @since    1.0.0
	 * @return   string    CSP header content
	 */
	private function generate_csp_header() {
		// Only apply CSP on METS admin pages and frontend forms
		if ( ! $this->is_mets_page() ) {
			return '';
		}
		
		$nonce = wp_create_nonce( 'mets_csp_nonce' );
		
		$csp_directives = array(
			"default-src 'self'",
			"script-src 'self' 'unsafe-inline' 'unsafe-eval'",
			"style-src 'self' 'unsafe-inline' fonts.googleapis.com",
			"img-src 'self' data: https:",
			"font-src 'self' data: fonts.gstatic.com https:",
			"connect-src 'self'",
			"form-action 'self'",
			"frame-ancestors 'none'",
			"object-src 'none'",
			"base-uri 'self'"
		);
		
		return implode( '; ', $csp_directives );
	}

	/**
	 * Check if current page is METS related
	 *
	 * @since    1.0.0
	 * @return   bool    Whether current page is METS related
	 */
	private function is_mets_page() {
		global $pagenow;
		
		// Admin pages
		if ( is_admin() ) {
			$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			return strpos( $page, 'mets' ) === 0;
		}
		
		// Frontend pages with METS shortcodes or REST API
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$uri = $_SERVER['REQUEST_URI'] ?? '';
			return strpos( $uri, '/wp-json/mets/' ) !== false;
		}
		
		// Pages with METS content
		global $post;
		if ( $post && ( has_shortcode( $post->post_content, 'ticket_form' ) || has_shortcode( $post->post_content, 'ticket_portal' ) ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if CSP should be applied to avoid conflicts with page builders
	 *
	 * @since    1.0.0
	 * @return   bool    Whether CSP should be applied
	 */
	private function should_apply_csp() {
		global $pagenow;
		
		// Never apply CSP on page/post editors to avoid breaking page builders
		$excluded_pages = array( 'post.php', 'post-new.php', 'edit.php', 'customize.php' );
		if ( in_array( $pagenow, $excluded_pages ) ) {
			return false;
		}
		
		// Don't apply CSP if popular page builders are detected
		if ( $this->is_page_builder_active() ) {
			return false;
		}
		
		// Only apply on METS-specific pages
		return $this->is_mets_page();
	}

	/**
	 * Check if page builders are active to avoid CSP conflicts
	 *
	 * @since    1.0.0
	 * @return   bool    Whether page builders are detected
	 */
	private function is_page_builder_active() {
		// Check for common page builder indicators
		$page_builder_indicators = array(
			'elementor', 'divi', 'beaver-builder', 'visual-composer', 
			'gutenberg', 'block-editor', 'oxygen', 'bricks'
		);
		
		// Check URL parameters
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		foreach ( $page_builder_indicators as $indicator ) {
			if ( strpos( $request_uri, $indicator ) !== false ) {
				return true;
			}
		}
		
		// Check $_GET parameters
		foreach ( $page_builder_indicators as $indicator ) {
			if ( isset( $_GET[ $indicator ] ) ) {
				return true;
			}
		}
		
		// Check for Elementor preview mode
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Handle failed login attempts
	 *
	 * @since    1.0.0
	 * @param    string    $username    Username
	 */
	public function handle_failed_login( $username ) {
		$ip = $this->get_client_ip();
		$cache_key = "mets_login_attempts_{$ip}";
		
		$attempts = get_transient( $cache_key );
		$attempts = $attempts ? $attempts + 1 : 1;
		
		set_transient( $cache_key, $attempts, $this->security_config['lockout_duration'] );
		
		// Log security event
		$this->log_security_event( 'failed_login', array(
			'username' => $username,
			'ip' => $ip,
			'attempts' => $attempts
		) );
		
		// If max attempts reached, trigger lockout
		if ( $attempts >= $this->security_config['max_login_attempts'] ) {
			$this->log_security_event( 'account_locked', array(
				'username' => $username,
				'ip' => $ip,
				'lockout_duration' => $this->security_config['lockout_duration']
			) );
		}
	}

	/**
	 * Check login attempts before authentication
	 *
	 * @since    1.0.0
	 * @param    WP_User|WP_Error|null    $user      User object or error
	 * @param    string                   $username  Username
	 * @param    string                   $password  Password
	 * @return   WP_User|WP_Error                    User object or error
	 */
	public function check_login_attempts( $user, $username, $password ) {
		$ip = $this->get_client_ip();
		$cache_key = "mets_login_attempts_{$ip}";
		
		$attempts = get_transient( $cache_key );
		
		if ( $attempts >= $this->security_config['max_login_attempts'] ) {
			return new WP_Error( 
				'too_many_attempts', 
				sprintf( 
					__( 'Too many failed login attempts. Please try again in %d minutes.', METS_TEXT_DOMAIN ),
					ceil( $this->security_config['lockout_duration'] / 60 )
				) 
			);
		}
		
		return $user;
	}

	/**
	 * Detect SQL injection attempts
	 *
	 * @since    1.0.0
	 * @param    string    $query    SQL query
	 * @return   string              Original query
	 */
	public function detect_sql_injection( $query ) {
		// Allow disabling via constant for debugging
		if ( defined( 'METS_DISABLE_SQL_INJECTION_PROTECTION' ) && METS_DISABLE_SQL_INJECTION_PROTECTION ) {
			return $query;
		}
		
		// Only check METS-related queries and only if SQL injection protection is enabled
		if ( ! $this->security_config['enable_sql_injection_protection'] || strpos( $query, 'mets_' ) === false ) {
			return $query;
		}

		// Only check for very specific dangerous patterns that indicate actual SQL injection
		$suspicious_patterns = array(
			// Multiple statements with dangerous commands
			'/;\s*(drop|delete|truncate|alter)\s+/i',
			// Union-based injection attempts
			'/union\s+select.*from\s+/i',
			// Obvious injection attempts
			'/\'\s*(or|and)\s*\'\w*\'\s*=\s*\'\w*\'/i',
			// Boolean-based blind injection
			'/\'\s*(or|and)\s*1\s*=\s*1\s*--/i',
			'/\'\s*(or|and)\s*1\s*=\s*0\s*--/i',
			// Comment-based injection
			'/\/\*.*\*\/.*(?:union|select|drop|delete|insert|update)/i',
			// Benchmark/sleep attacks
			'/benchmark\s*\(/i',
			'/sleep\s*\(/i'
		);

		// Check for patterns that are likely injection attempts
		foreach ( $suspicious_patterns as $pattern ) {
			if ( preg_match( $pattern, $query ) ) {
				// Log the attempt but don't block unless it's very dangerous
				$this->log_security_event( 'sql_injection_attempt', array(
					'query' => substr( $query, 0, 500 ), // Limit query length in log
					'pattern' => $pattern,
					'ip' => $this->get_client_ip()
				) );
				
				// Only block very dangerous patterns
				if ( preg_match( '/;\s*(drop|delete|truncate|alter)\s+/i', $query ) || 
					 preg_match( '/benchmark\s*\(/i', $query ) || 
					 preg_match( '/sleep\s*\(/i', $query ) ) {
					wp_die( __( 'Security violation detected.', METS_TEXT_DOMAIN ) );
				}
			}
		}

		return $query;
	}

	/**
	 * Secure session configuration
	 *
	 * @since    1.0.0
	 */
	public function secure_session_config() {
		if ( ! $this->security_config['enable_session_security'] ) {
			return;
		}

		// Regenerate session ID periodically
		if ( ! isset( $_SESSION['mets_last_regeneration'] ) ) {
			$_SESSION['mets_last_regeneration'] = time();
		} elseif ( time() - $_SESSION['mets_last_regeneration'] > 1800 ) { // 30 minutes
			session_regenerate_id( true );
			$_SESSION['mets_last_regeneration'] = time();
		}

		// Set secure session cookies
		if ( is_ssl() ) {
			ini_set( 'session.cookie_secure', 1 );
		}
		
		ini_set( 'session.cookie_httponly', 1 );
		ini_set( 'session.use_only_cookies', 1 );
		ini_set( 'session.cookie_samesite', 'Strict' );
	}

	/**
	 * Add Content Security Policy meta tag
	 *
	 * @since    1.0.0
	 */
	public function add_csp_meta() {
		// Disabled to prevent conflicts with HTTP header CSP
		// The CSP handler class now manages all CSP directives
		return;
		
		global $pagenow;
		if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
			return;
		}
		
		$csp = "default-src 'self'; " .
			   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
			   "style-src 'self' 'unsafe-inline'; " .
			   "img-src 'self' data: https:; " .
			   "font-src 'self' https:; " .
			   "connect-src 'self'; " .
			   "frame-ancestors 'self';";
		
		echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr( $csp ) . '">' . "\n";
	}

	/**
	 * Sanitize SQL input
	 *
	 * @since    1.0.0
	 * @param    string    $input    SQL input
	 * @return   string              Sanitized input
	 */
	private function sanitize_sql_input( $input ) {
		global $wpdb;
		return $wpdb->prepare( '%s', $input );
	}

	/**
	 * Sanitize JSON input
	 *
	 * @since    1.0.0
	 * @param    string    $input    JSON input
	 * @return   string              Sanitized input
	 */
	private function sanitize_json_input( $input ) {
		$decoded = json_decode( $input, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return '';
		}
		
		return wp_json_encode( $decoded );
	}

	/**
	 * Validate JSON input
	 *
	 * @since    1.0.0
	 * @param    string    $input    JSON input
	 * @return   bool                Validation result
	 */
	private function validate_json( $input ) {
		json_decode( $input );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Validate SQL-safe input
	 *
	 * @since    1.0.0
	 * @param    string    $input    Input to validate
	 * @return   bool                Whether input is SQL-safe
	 */
	private function validate_sql_safe( $input ) {
		$dangerous_keywords = array(
			'union', 'select', 'insert', 'update', 'delete', 'drop', 
			'create', 'alter', 'exec', 'execute', 'script', 'javascript'
		);
		
		$input_lower = strtolower( $input );
		foreach ( $dangerous_keywords as $keyword ) {
			if ( strpos( $input_lower, $keyword ) !== false ) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Validate filename
	 *
	 * @since    1.0.0
	 * @param    string    $filename    Filename to validate
	 * @return   bool                   Whether filename is valid
	 */
	private function validate_filename( $filename ) {
		// Check for dangerous characters
		$dangerous_chars = array( '..', '/', '\\', ':', '*', '?', '"', '<', '>', '|' );
		foreach ( $dangerous_chars as $char ) {
			if ( strpos( $filename, $char ) !== false ) {
				return false;
			}
		}
		
		// Check for executable extensions
		$dangerous_extensions = array( 'php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi' );
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		
		return ! in_array( $extension, $dangerous_extensions );
	}

	/**
	 * Get upload error message
	 *
	 * @since    1.0.0
	 * @param    int    $error_code    Upload error code
	 * @return   string                Error message
	 */
	private function get_upload_error_message( $error_code ) {
		$error_messages = array(
			UPLOAD_ERR_INI_SIZE => __( 'File exceeds upload_max_filesize directive.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_FORM_SIZE => __( 'File exceeds MAX_FILE_SIZE directive.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_PARTIAL => __( 'File was only partially uploaded.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_NO_FILE => __( 'No file was uploaded.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Missing temporary folder.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', METS_TEXT_DOMAIN ),
			UPLOAD_ERR_EXTENSION => __( 'File upload stopped by extension.', METS_TEXT_DOMAIN )
		);
		
		return isset( $error_messages[ $error_code ] ) 
			? $error_messages[ $error_code ] 
			: __( 'Unknown upload error.', METS_TEXT_DOMAIN );
	}

	/**
	 * Check if MIME type is allowed for file extension
	 *
	 * @since    1.0.0
	 * @param    string    $mime_type     MIME type
	 * @param    string    $extension     File extension
	 * @return   bool                     Whether MIME type is allowed
	 */
	private function is_allowed_mime_type( $mime_type, $extension ) {
		$allowed_mime_types = array(
			'jpg' => array( 'image/jpeg', 'image/jpg' ),
			'jpeg' => array( 'image/jpeg', 'image/jpg' ),
			'png' => array( 'image/png' ),
			'gif' => array( 'image/gif' ),
			'pdf' => array( 'application/pdf' ),
			'doc' => array( 'application/msword' ),
			'docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
			'txt' => array( 'text/plain' )
		);
		
		if ( ! isset( $allowed_mime_types[ $extension ] ) ) {
			return false;
		}
		
		return in_array( $mime_type, $allowed_mime_types[ $extension ] );
	}

	/**
	 * Scan file for malware patterns
	 *
	 * @since    1.0.0
	 * @param    string    $file_path    Path to file
	 * @return   bool                    Whether malware was detected
	 */
	private function scan_file_for_malware( $file_path ) {
		$content = file_get_contents( $file_path );
		
		$malware_patterns = array(
			'/<\?php/i',
			'/<script/i',
			'/eval\s*\(/i',
			'/exec\s*\(/i',
			'/system\s*\(/i',
			'/shell_exec\s*\(/i',
			'/base64_decode\s*\(/i',
			'/file_get_contents\s*\(/i',
			'/fwrite\s*\(/i',
			'/fopen\s*\(/i'
		);
		
		foreach ( $malware_patterns as $pattern ) {
			if ( preg_match( $pattern, $content ) ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Get rate limits for specific action
	 *
	 * @since    1.0.0
	 * @param    string    $action    Action name
	 * @return   array               Rate limit configuration
	 */
	private function get_rate_limits_for_action( $action ) {
		$default_limits = array(
			'max_attempts' => 100,
			'time_window' => 3600 // 1 hour
		);
		
		$action_limits = array(
			'login' => array( 'max_attempts' => 5, 'time_window' => 900 ),
			'ticket_create' => array( 'max_attempts' => 10, 'time_window' => 600 ),
			'file_upload' => array( 'max_attempts' => 20, 'time_window' => 3600 ),
			'search' => array( 'max_attempts' => 100, 'time_window' => 600 ),
			'api_request' => array( 'max_attempts' => 1000, 'time_window' => 3600 )
		);
		
		return isset( $action_limits[ $action ] ) ? $action_limits[ $action ] : $default_limits;
	}

	/**
	 * Get client identifier
	 *
	 * @since    1.0.0
	 * @return   string    Client identifier
	 */
	private function get_client_identifier() {
		$ip = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		
		return md5( $ip . $user_agent );
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare
			'HTTP_CLIENT_IP',            // Proxy
			'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
			'HTTP_X_FORWARDED',          // Proxy
			'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
			'HTTP_FORWARDED_FOR',        // Proxy
			'HTTP_FORWARDED',            // Proxy
			'REMOTE_ADDR'                // Standard
		);
		
		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				
				// Handle comma-separated IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		
		return '0.0.0.0';
	}

	/**
	 * Log security event
	 *
	 * @since    1.0.0
	 * @param    string    $event_type    Event type
	 * @param    array     $data          Event data
	 */
	private function log_security_event( $event_type, $data = array() ) {
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'event_type' => $event_type,
			'ip' => $this->get_client_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'user_id' => get_current_user_id(),
			'data' => $data
		);
		
		// Store in database
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mets_security_log',
			array(
				'event_type' => $event_type,
				'event_data' => wp_json_encode( $log_entry ),
				'ip_address' => $log_entry['ip'],
				'user_id' => $log_entry['user_id'],
				'created_at' => $log_entry['timestamp']
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);
		
		// Also log to WordPress error log if critical
		$critical_events = array( 'sql_injection_attempt', 'malware_detected', 'account_locked' );
		if ( in_array( $event_type, $critical_events ) ) {
			error_log( 'METS Security Event: ' . $event_type . ' - ' . wp_json_encode( $data ) );
		}
	}

	/**
	 * Get security configuration
	 *
	 * @since    1.0.0
	 * @return   array    Security configuration
	 */
	public function get_security_config() {
		return $this->security_config;
	}

	/**
	 * Update security configuration
	 *
	 * @since    1.0.0
	 * @param    array    $config    New configuration
	 * @return   bool               Success status
	 */
	public function update_security_config( $config ) {
		global $wpdb;
		
		$security_config_table = $wpdb->prefix . 'mets_security_config';
		$success = true;
		
		foreach ( $config as $key => $value ) {
			// Prepare value for database
			$db_value = $value;
			if ( is_array( $value ) ) {
				$db_value = wp_json_encode( $value );
			} elseif ( is_bool( $value ) ) {
				$db_value = $value ? '1' : '0';
			}
			
			// Update or insert configuration
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $security_config_table WHERE config_key = %s",
				$key
			) );
			
			if ( $existing ) {
				$result = $wpdb->update(
					$security_config_table,
					array( 'config_value' => $db_value ),
					array( 'config_key' => $key ),
					array( '%s' ),
					array( '%s' )
				);
			} else {
				$result = $wpdb->insert(
					$security_config_table,
					array(
						'config_key' => $key,
						'config_value' => $db_value
					),
					array( '%s', '%s' )
				);
			}
			
			if ( $result === false ) {
				$success = false;
			} else {
				$this->security_config[ $key ] = $value;
			}
		}
		
		return $success;
	}

	/**
	 * Get security log entries
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Log entries
	 */
	public function get_security_log( $args = array() ) {
		global $wpdb;
		
		$defaults = array(
			'limit' => 100,
			'offset' => 0,
			'event_type' => '',
			'date_from' => '',
			'date_to' => '',
			'ip_address' => ''
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$where_clauses = array( '1=1' );
		$where_values = array();
		
		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = 'event_type = %s';
			$where_values[] = $args['event_type'];
		}
		
		if ( ! empty( $args['date_from'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$where_values[] = $args['date_from'];
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$where_values[] = $args['date_to'];
		}
		
		if ( ! empty( $args['ip_address'] ) ) {
			$where_clauses[] = 'ip_address = %s';
			$where_values[] = $args['ip_address'];
		}
		
		$where_sql = implode( ' AND ', $where_clauses );
		
		$sql = "SELECT * FROM {$wpdb->prefix}mets_security_log 
				WHERE $where_sql 
				ORDER BY created_at DESC 
				LIMIT %d OFFSET %d";
		
		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];
		
		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}
		
		return $wpdb->get_results( $sql );
	}
}
