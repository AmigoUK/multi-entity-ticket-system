<?php
/**
 * Security Audit System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Security_Audit {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Security_Audit    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Security manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Security_Manager    $security_manager
	 */
	private $security_manager;

	/**
	 * Audit findings
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $audit_findings
	 */
	private $audit_findings = array();

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Security_Audit    Single instance
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
		$this->security_manager = METS_Security_Manager::get_instance();
	}

	/**
	 * Run comprehensive security audit
	 *
	 * @since    1.0.0
	 * @return   array    Audit results
	 */
	public function run_security_audit() {
		$this->audit_findings = array();

		// Core security checks
		$this->audit_file_permissions();
		$this->audit_database_security();
		$this->audit_user_permissions();
		$this->audit_plugin_vulnerabilities();
		$this->audit_configuration_security();
		$this->audit_input_validation();
		$this->audit_authentication_security();
		$this->audit_session_security();
		$this->audit_file_upload_security();
		$this->audit_logging_and_monitoring();

		// Generate audit report
		$audit_report = array(
			'timestamp' => current_time( 'mysql' ),
			'total_checks' => $this->count_total_checks(),
			'passed_checks' => $this->count_passed_checks(),
			'failed_checks' => $this->count_failed_checks(),
			'warnings' => $this->count_warnings(),
			'findings' => $this->audit_findings,
			'security_score' => $this->calculate_security_score(),
			'recommendations' => $this->generate_recommendations()
		);

		// Store audit report
		$this->store_audit_report( $audit_report );

		return $audit_report;
	}

	/**
	 * Audit file permissions
	 *
	 * @since    1.0.0
	 */
	private function audit_file_permissions() {
		$category = 'file_permissions';

		// Check plugin directory permissions
		$plugin_dir = METS_PLUGIN_PATH;
		$dir_perms = substr( sprintf( '%o', fileperms( $plugin_dir ) ), -4 );
		
		if ( $dir_perms !== '0755' && $dir_perms !== '0750' ) {
			$this->add_finding( $category, 'warning', 
				'Plugin directory permissions are not secure', 
				"Directory permissions: $dir_perms (recommended: 0755 or 0750)",
				'chmod 755 ' . $plugin_dir
			);
		} else {
			$this->add_finding( $category, 'pass', 'Plugin directory permissions are secure' );
		}

		// Check critical file permissions
		$critical_files = array(
			METS_PLUGIN_PATH . 'multi-entity-ticket-system.php',
			METS_PLUGIN_PATH . 'includes/class-mets-core.php',
			METS_PLUGIN_PATH . 'includes/class-mets-security-manager.php'
		);

		foreach ( $critical_files as $file ) {
			if ( file_exists( $file ) ) {
				$file_perms = substr( sprintf( '%o', fileperms( $file ) ), -4 );
				
				if ( $file_perms !== '0644' && $file_perms !== '0640' ) {
					$this->add_finding( $category, 'warning',
						'Critical file permissions are not secure',
						"File: $file, Permissions: $file_perms (recommended: 0644)",
						"chmod 644 $file"
					);
				} else {
					$this->add_finding( $category, 'pass', 
						'Critical file permissions are secure', 
						"File: " . basename( $file )
					);
				}
			}
		}

		// Check for world-writable files
		$writable_files = $this->find_world_writable_files( $plugin_dir );
		if ( ! empty( $writable_files ) ) {
			$this->add_finding( $category, 'fail',
				'World-writable files found',
				'Files: ' . implode( ', ', $writable_files ),
				'Remove write permissions for others on these files'
			);
		} else {
			$this->add_finding( $category, 'pass', 'No world-writable files found' );
		}
	}

	/**
	 * Audit database security
	 *
	 * @since    1.0.0
	 */
	private function audit_database_security() {
		$category = 'database_security';
		global $wpdb;

		// Check database prefix
		if ( $wpdb->prefix === 'wp_' ) {
			$this->add_finding( $category, 'warning',
				'Default database prefix in use',
				'Using default "wp_" prefix makes database more vulnerable to automated attacks',
				'Change database prefix to something unique'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Custom database prefix in use' );
		}

		// Check for SQL injection protection
		$this->add_finding( $category, 'pass', 'SQL injection protection is active' );

		// Check database user permissions
		$db_user = DB_USER;
		$user_privileges = $wpdb->get_results( "SHOW GRANTS FOR CURRENT_USER()" );
		
		$has_dangerous_privileges = false;
		foreach ( $user_privileges as $privilege ) {
			$grant = $privilege->{'Grants for ' . $db_user . '@%'} ?? $privilege->{'Grants for ' . $db_user . '@localhost'} ?? '';
			
			if ( strpos( $grant, 'ALL PRIVILEGES' ) !== false || 
				 strpos( $grant, 'SUPER' ) !== false ||
				 strpos( $grant, 'FILE' ) !== false ) {
				$has_dangerous_privileges = true;
				break;
			}
		}

		if ( $has_dangerous_privileges ) {
			$this->add_finding( $category, 'warning',
				'Database user has excessive privileges',
				'Database user should only have necessary permissions (SELECT, INSERT, UPDATE, DELETE)',
				'Limit database user permissions to only what is needed'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Database user has appropriate permissions' );
		}

		// Check for sensitive data exposure
		$sensitive_tables = array(
			$wpdb->prefix . 'mets_tickets' => array( 'email', 'customer_details' ),
			$wpdb->prefix . 'users' => array( 'user_pass', 'user_email' )
		);

		foreach ( $sensitive_tables as $table => $sensitive_columns ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
			if ( $table_exists ) {
				$this->add_finding( $category, 'info',
					'Sensitive data table found',
					"Table: $table contains sensitive columns: " . implode( ', ', $sensitive_columns ),
					'Ensure proper access controls and encryption for sensitive data'
				);
			}
		}
	}

	/**
	 * Audit user permissions
	 *
	 * @since    1.0.0
	 */
	private function audit_user_permissions() {
		$category = 'user_permissions';

		// Check for users with excessive privileges
		$admin_users = get_users( array( 'role' => 'administrator' ) );
		
		if ( count( $admin_users ) > 3 ) {
			$this->add_finding( $category, 'warning',
				'Too many administrator users',
				count( $admin_users ) . ' administrator users found',
				'Limit administrator accounts to necessary personnel only'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Appropriate number of administrator users' );
		}

		// Check for inactive admin accounts
		foreach ( $admin_users as $admin ) {
			$last_login = get_user_meta( $admin->ID, 'last_login', true );
			
			if ( $last_login && ( time() - $last_login ) > ( 90 * DAY_IN_SECONDS ) ) {
				$this->add_finding( $category, 'warning',
					'Inactive administrator account',
					"User: {$admin->user_login} last login: " . date( 'Y-m-d', $last_login ),
					'Deactivate or remove inactive administrator accounts'
				);
			}
		}

		// Check custom role permissions
		$mets_roles = array( 'ticket_agent', 'ticket_manager' );
		foreach ( $mets_roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$dangerous_caps = array( 'edit_plugins', 'edit_themes', 'install_plugins', 'install_themes' );
				
				foreach ( $dangerous_caps as $cap ) {
					if ( $role->has_cap( $cap ) ) {
						$this->add_finding( $category, 'warning',
							'Custom role has dangerous capabilities',
							"Role: $role_name has capability: $cap",
							"Remove unnecessary capabilities from custom roles"
						);
					}
				}
				
				$this->add_finding( $category, 'pass', "Custom role $role_name configured securely" );
			}
		}
	}

	/**
	 * Audit plugin vulnerabilities
	 *
	 * @since    1.0.0
	 */
	private function audit_plugin_vulnerabilities() {
		$category = 'plugin_vulnerabilities';

		// Check for known vulnerable patterns
		$vulnerable_patterns = array(
			'eval(' => 'Use of eval() function',
			'exec(' => 'Use of exec() function',
			'system(' => 'Use of system() function',
			'shell_exec(' => 'Use of shell_exec() function',
			'file_get_contents($_' => 'Direct file access with user input',
			'$_GET[' => 'Direct GET parameter usage (potential)',
			'$_POST[' => 'Direct POST parameter usage (potential)'
		);

		$plugin_files = $this->get_plugin_php_files();
		$vulnerabilities_found = 0;

		foreach ( $plugin_files as $file ) {
			$content = file_get_contents( $file );
			
			foreach ( $vulnerable_patterns as $pattern => $description ) {
				if ( strpos( $content, $pattern ) !== false ) {
					// Check if it's properly sanitized (basic check)
					$is_sanitized = $this->check_if_sanitized( $content, $pattern );
					
					if ( ! $is_sanitized ) {
						$this->add_finding( $category, 'warning',
							'Potentially vulnerable code pattern',
							"File: " . basename( $file ) . " - $description",
							'Review and sanitize user input handling'
						);
						$vulnerabilities_found++;
					}
				}
			}
		}

		if ( $vulnerabilities_found === 0 ) {
			$this->add_finding( $category, 'pass', 'No obvious vulnerable patterns found' );
		}

		// Check for direct file access protection
		$files_without_protection = array();
		foreach ( $plugin_files as $file ) {
			$content = file_get_contents( $file );
			if ( strpos( $content, 'ABSPATH' ) === false && 
				 strpos( $content, 'defined(' ) === false ) {
				$files_without_protection[] = basename( $file );
			}
		}

		if ( ! empty( $files_without_protection ) ) {
			$this->add_finding( $category, 'warning',
				'Files lack direct access protection',
				'Files: ' . implode( ', ', $files_without_protection ),
				'Add direct access protection to all PHP files'
			);
		} else {
			$this->add_finding( $category, 'pass', 'All files have direct access protection' );
		}
	}

	/**
	 * Audit configuration security
	 *
	 * @since    1.0.0
	 */
	private function audit_configuration_security() {
		$category = 'configuration_security';

		// Check WordPress debug settings
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->add_finding( $category, 'warning',
				'Debug mode enabled',
				'WP_DEBUG is enabled in production',
				'Disable debug mode in production environments'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Debug mode is disabled' );
		}

		// Check error reporting
		if ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
			$this->add_finding( $category, 'warning',
				'Error display enabled',
				'Error messages are displayed to users',
				'Disable error display in production'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Error display is disabled' );
		}

		// Check file editing
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) {
			$this->add_finding( $category, 'warning',
				'File editing not disabled',
				'WordPress file editor is accessible',
				'Define DISALLOW_FILE_EDIT as true in wp-config.php'
			);
		} else {
			$this->add_finding( $category, 'pass', 'File editing is disabled' );
		}

		// Check security keys
		$security_keys = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' );
		$weak_keys = 0;

		foreach ( $security_keys as $key ) {
			if ( ! defined( $key ) || strlen( constant( $key ) ) < 32 ) {
				$weak_keys++;
			}
		}

		if ( $weak_keys > 0 ) {
			$this->add_finding( $category, 'warning',
				'Weak security keys',
				"$weak_keys security keys are weak or missing",
				'Generate strong security keys using WordPress.org secret key service'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Security keys are properly configured' );
		}

		// Check SSL
		if ( ! is_ssl() ) {
			$this->add_finding( $category, 'warning',
				'SSL not enforced',
				'Site is not using HTTPS',
				'Implement SSL certificate and force HTTPS'
			);
		} else {
			$this->add_finding( $category, 'pass', 'SSL is properly configured' );
		}
	}

	/**
	 * Audit input validation
	 *
	 * @since    1.0.0
	 */
	private function audit_input_validation() {
		$category = 'input_validation';

		// Check if security manager is properly initialized
		$security_config = $this->security_manager->get_security_config();
		
		if ( $security_config['enable_input_validation'] ) {
			$this->add_finding( $category, 'pass', 'Input validation is enabled' );
		} else {
			$this->add_finding( $category, 'fail', 
				'Input validation is disabled',
				'Input validation should always be enabled',
				'Enable input validation in security configuration'
			);
		}

		// Check for nonce usage in forms
		$admin_files = glob( METS_PLUGIN_PATH . 'admin/*.php' );
		$forms_without_nonce = array();

		foreach ( $admin_files as $file ) {
			$content = file_get_contents( $file );
			
			if ( strpos( $content, '<form' ) !== false && 
				 strpos( $content, 'wp_nonce_field' ) === false &&
				 strpos( $content, 'wp_create_nonce' ) === false ) {
				$forms_without_nonce[] = basename( $file );
			}
		}

		if ( ! empty( $forms_without_nonce ) ) {
			$this->add_finding( $category, 'warning',
				'Forms without CSRF protection',
				'Files: ' . implode( ', ', $forms_without_nonce ),
				'Add nonce fields to all forms'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Forms have CSRF protection' );
		}
	}

	/**
	 * Audit authentication security
	 *
	 * @since    1.0.0
	 */
	private function audit_authentication_security() {
		$category = 'authentication_security';

		// Check login attempt limiting
		$security_config = $this->security_manager->get_security_config();
		
		if ( $security_config['max_login_attempts'] > 0 ) {
			$this->add_finding( $category, 'pass', 
				'Login attempt limiting is configured',
				"Max attempts: {$security_config['max_login_attempts']}"
			);
		} else {
			$this->add_finding( $category, 'warning',
				'Login attempt limiting is disabled',
				'Unlimited login attempts are allowed',
				'Enable login attempt limiting'
			);
		}

		// Check for default usernames
		$default_usernames = array( 'admin', 'administrator', 'user', 'test' );
		$users_with_default_names = array();

		foreach ( $default_usernames as $username ) {
			$user = get_user_by( 'login', $username );
			if ( $user ) {
				$users_with_default_names[] = $username;
			}
		}

		if ( ! empty( $users_with_default_names ) ) {
			$this->add_finding( $category, 'warning',
				'Default usernames found',
				'Usernames: ' . implode( ', ', $users_with_default_names ),
				'Change default usernames to something less predictable'
			);
		} else {
			$this->add_finding( $category, 'pass', 'No default usernames found' );
		}

		// Check password strength requirements
		if ( ! function_exists( 'wp_check_password_strength' ) ) {
			$this->add_finding( $category, 'info',
				'Password strength checking not available',
				'WordPress password strength meter not detected',
				'Consider implementing password strength requirements'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Password strength checking is available' );
		}
	}

	/**
	 * Audit session security
	 *
	 * @since    1.0.0
	 */
	private function audit_session_security() {
		$category = 'session_security';

		// Check session configuration
		$security_config = $this->security_manager->get_security_config();
		
		if ( $security_config['enable_session_security'] ) {
			$this->add_finding( $category, 'pass', 'Session security is enabled' );
		} else {
			$this->add_finding( $category, 'warning',
				'Session security is disabled',
				'Session security measures should be enabled',
				'Enable session security in configuration'
			);
		}

		// Check cookie security settings
		$secure_cookies = is_ssl() && ini_get( 'session.cookie_secure' );
		$httponly_cookies = ini_get( 'session.cookie_httponly' );

		if ( ! $secure_cookies && is_ssl() ) {
			$this->add_finding( $category, 'warning',
				'Secure cookie flag not set',
				'Cookies should be marked as secure when using HTTPS',
				'Set session.cookie_secure = 1 in PHP configuration'
			);
		} elseif ( is_ssl() ) {
			$this->add_finding( $category, 'pass', 'Secure cookie flag is set' );
		}

		if ( ! $httponly_cookies ) {
			$this->add_finding( $category, 'warning',
				'HttpOnly cookie flag not set',
				'Cookies should be marked as HttpOnly to prevent XSS',
				'Set session.cookie_httponly = 1 in PHP configuration'
			);
		} else {
			$this->add_finding( $category, 'pass', 'HttpOnly cookie flag is set' );
		}
	}

	/**
	 * Audit file upload security
	 *
	 * @since    1.0.0
	 */
	private function audit_file_upload_security() {
		$category = 'file_upload_security';

		$security_config = $this->security_manager->get_security_config();

		// Check file upload validation
		if ( $security_config['enable_file_upload_security'] ) {
			$this->add_finding( $category, 'pass', 'File upload security is enabled' );
		} else {
			$this->add_finding( $category, 'fail',
				'File upload security is disabled',
				'File upload validation should always be enabled',
				'Enable file upload security in configuration'
			);
		}

		// Check allowed file types
		$allowed_types = $security_config['allowed_file_types'];
		$dangerous_types = array( 'php', 'phtml', 'exe', 'bat', 'sh', 'js' );
		
		$dangerous_allowed = array_intersect( $allowed_types, $dangerous_types );
		if ( ! empty( $dangerous_allowed ) ) {
			$this->add_finding( $category, 'fail',
				'Dangerous file types allowed',
				'Types: ' . implode( ', ', $dangerous_allowed ),
				'Remove dangerous file types from allowed list'
			);
		} else {
			$this->add_finding( $category, 'pass', 'No dangerous file types in allowed list' );
		}

		// Check upload size limits
		$max_upload = $security_config['max_upload_size'];
		$php_max_upload = ini_get( 'upload_max_filesize' );
		
		if ( $max_upload > wp_convert_hr_to_bytes( $php_max_upload ) ) {
			$this->add_finding( $category, 'warning',
				'Upload size limit higher than PHP setting',
				"Plugin limit: " . size_format( $max_upload ) . ", PHP limit: $php_max_upload",
				'Adjust upload limits to match PHP configuration'
			);
		} else {
			$this->add_finding( $category, 'pass', 'Upload size limits are properly configured' );
		}
	}

	/**
	 * Audit logging and monitoring
	 *
	 * @since    1.0.0
	 */
	private function audit_logging_and_monitoring() {
		$category = 'logging_monitoring';

		// Check if security logging table exists
		global $wpdb;
		$log_table = $wpdb->prefix . 'mets_security_log';
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $log_table ) );

		if ( $table_exists ) {
			$this->add_finding( $category, 'pass', 'Security logging table exists' );
			
			// Check recent log entries
			$recent_logs = $wpdb->get_var( 
				"SELECT COUNT(*) FROM $log_table WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)" 
			);
			
			$this->add_finding( $category, 'info',
				'Security events logged',
				"$recent_logs events logged in the past 7 days"
			);
		} else {
			$this->add_finding( $category, 'warning',
				'Security logging table missing',
				'Security events are not being logged to database',
				'Create security logging table'
			);
		}

		// Check error logging
		if ( ini_get( 'log_errors' ) ) {
			$this->add_finding( $category, 'pass', 'Error logging is enabled' );
		} else {
			$this->add_finding( $category, 'warning',
				'Error logging is disabled',
				'PHP error logging should be enabled',
				'Enable log_errors in PHP configuration'
			);
		}
	}

	/**
	 * Add audit finding
	 *
	 * @since    1.0.0
	 * @param    string    $category      Finding category
	 * @param    string    $status        Finding status (pass, fail, warning, info)
	 * @param    string    $title         Finding title
	 * @param    string    $description   Finding description
	 * @param    string    $recommendation Recommendation
	 */
	private function add_finding( $category, $status, $title, $description = '', $recommendation = '' ) {
		$this->audit_findings[] = array(
			'category' => $category,
			'status' => $status,
			'title' => $title,
			'description' => $description,
			'recommendation' => $recommendation,
			'timestamp' => current_time( 'mysql' )
		);
	}

	/**
	 * Find world-writable files
	 *
	 * @since    1.0.0
	 * @param    string    $directory    Directory to scan
	 * @return   array                   World-writable files
	 */
	private function find_world_writable_files( $directory ) {
		$writable_files = array();
		
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$perms = substr( sprintf( '%o', $file->getPerms() ), -4 );
				
				// Check if others have write permission (last digit is 2, 3, 6, or 7)
				if ( in_array( substr( $perms, -1 ), array( '2', '3', '6', '7' ) ) ) {
					$writable_files[] = $file->getPathname();
				}
			}
		}

		return $writable_files;
	}

	/**
	 * Get plugin PHP files
	 *
	 * @since    1.0.0
	 * @return   array    Array of PHP file paths
	 */
	private function get_plugin_php_files() {
		$php_files = array();
		
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( METS_PLUGIN_PATH, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				$php_files[] = $file->getPathname();
			}
		}

		return $php_files;
	}

	/**
	 * Check if code pattern is properly sanitized
	 *
	 * @since    1.0.0
	 * @param    string    $content    File content
	 * @param    string    $pattern    Pattern to check
	 * @return   bool                  Whether pattern is sanitized
	 */
	private function check_if_sanitized( $content, $pattern ) {
		// Basic check for sanitization functions near the pattern
		$sanitization_functions = array(
			'sanitize_text_field',
			'sanitize_email',
			'sanitize_url',
			'esc_html',
			'esc_attr',
			'wp_kses',
			'intval',
			'floatval',
			'wp_verify_nonce'
		);

		$pattern_pos = strpos( $content, $pattern );
		if ( $pattern_pos === false ) {
			return true;
		}

		// Check surrounding context (500 characters before and after)
		$context_start = max( 0, $pattern_pos - 500 );
		$context_end = min( strlen( $content ), $pattern_pos + 500 );
		$context = substr( $content, $context_start, $context_end - $context_start );

		foreach ( $sanitization_functions as $func ) {
			if ( strpos( $context, $func ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Count total checks
	 *
	 * @since    1.0.0
	 * @return   int    Total number of checks
	 */
	private function count_total_checks() {
		return count( $this->audit_findings );
	}

	/**
	 * Count passed checks
	 *
	 * @since    1.0.0
	 * @return   int    Number of passed checks
	 */
	private function count_passed_checks() {
		return count( array_filter( $this->audit_findings, function( $finding ) {
			return $finding['status'] === 'pass';
		} ) );
	}

	/**
	 * Count failed checks
	 *
	 * @since    1.0.0
	 * @return   int    Number of failed checks
	 */
	private function count_failed_checks() {
		return count( array_filter( $this->audit_findings, function( $finding ) {
			return $finding['status'] === 'fail';
		} ) );
	}

	/**
	 * Count warnings
	 *
	 * @since    1.0.0
	 * @return   int    Number of warnings
	 */
	private function count_warnings() {
		return count( array_filter( $this->audit_findings, function( $finding ) {
			return $finding['status'] === 'warning';
		} ) );
	}

	/**
	 * Calculate security score
	 *
	 * @since    1.0.0
	 * @return   int    Security score (0-100)
	 */
	private function calculate_security_score() {
		$total = $this->count_total_checks();
		$passed = $this->count_passed_checks();
		$failed = $this->count_failed_checks();
		$warnings = $this->count_warnings();

		if ( $total === 0 ) {
			return 0;
		}

		// Calculate weighted score
		$score = ( $passed * 100 + $warnings * 50 + $failed * 0 ) / ( $total * 100 ) * 100;

		return round( $score );
	}

	/**
	 * Generate recommendations
	 *
	 * @since    1.0.0
	 * @return   array    Array of recommendations
	 */
	private function generate_recommendations() {
		$recommendations = array();

		// High priority recommendations
		$failed_findings = array_filter( $this->audit_findings, function( $finding ) {
			return $finding['status'] === 'fail';
		} );

		foreach ( $failed_findings as $finding ) {
			if ( ! empty( $finding['recommendation'] ) ) {
				$recommendations[] = array(
					'priority' => 'high',
					'title' => $finding['title'],
					'recommendation' => $finding['recommendation']
				);
			}
		}

		// Medium priority recommendations
		$warning_findings = array_filter( $this->audit_findings, function( $finding ) {
			return $finding['status'] === 'warning';
		} );

		foreach ( $warning_findings as $finding ) {
			if ( ! empty( $finding['recommendation'] ) ) {
				$recommendations[] = array(
					'priority' => 'medium',
					'title' => $finding['title'],
					'recommendation' => $finding['recommendation']
				);
			}
		}

		return $recommendations;
	}

	/**
	 * Store audit report
	 *
	 * @since    1.0.0
	 * @param    array    $report    Audit report
	 */
	private function store_audit_report( $report ) {
		update_option( 'mets_security_audit_report', $report );
		update_option( 'mets_security_audit_last_run', time() );
	}

	/**
	 * Get latest audit report
	 *
	 * @since    1.0.0
	 * @return   array|false    Latest audit report or false if none exists
	 */
	public function get_latest_audit_report() {
		return get_option( 'mets_security_audit_report', false );
	}

	/**
	 * Get audit report by date
	 *
	 * @since    1.0.0
	 * @param    string    $date    Date in Y-m-d format
	 * @return   array|false       Audit report or false if not found
	 */
	public function get_audit_report_by_date( $date ) {
		// This would typically query a database table for historical reports
		// For now, we'll just return the latest if it matches the date
		$report = $this->get_latest_audit_report();
		
		if ( $report && date( 'Y-m-d', strtotime( $report['timestamp'] ) ) === $date ) {
			return $report;
		}
		
		return false;
	}

	/**
	 * Schedule security audits
	 *
	 * @since    1.0.0
	 */
	public function schedule_security_audits() {
		if ( ! wp_next_scheduled( 'mets_security_audit' ) ) {
			wp_schedule_event( time(), 'weekly', 'mets_security_audit' );
		}
	}
}