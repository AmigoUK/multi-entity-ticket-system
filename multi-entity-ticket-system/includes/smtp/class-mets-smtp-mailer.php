<?php
/**
 * SMTP Mailer
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @since      1.0.0
 */

/**
 * SMTP Mailer class.
 *
 * This class handles email sending using PHPMailer with SMTP configuration.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/smtp
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SMTP_Mailer {

	/**
	 * PHPMailer instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      PHPMailer    $phpmailer    PHPMailer instance.
	 */
	private $phpmailer;

	/**
	 * Debug output
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $debug_output    Debug output messages.
	 */
	private $debug_output = array();

	/**
	 * Logger instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SMTP_Logger    $logger    Logger instance.
	 */
	private $logger;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// PHPMailer is included in WordPress core
		if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		// Initialize logger
		$this->logger = METS_SMTP_Logger::get_instance();
	}

	/**
	 * Send email using SMTP
	 *
	 * @since    1.0.0
	 * @param    string   $to           Recipient email
	 * @param    string   $subject      Email subject
	 * @param    string   $message      Email message
	 * @param    array    $headers      Email headers
	 * @param    array    $settings     SMTP settings to use
	 * @return   array                  Send result
	 */
	public function send_email( $to, $subject, $message, $headers = array(), $settings = array() ) {
		$result = array(
			'success' => false,
			'error' => '',
			'debug' => array(),
		);

		// Use WordPress mail if specified
		if ( empty( $settings ) || $settings['method'] === 'wordpress' ) {
			$sent = wp_mail( $to, $subject, $message, $headers );
			$result['success'] = $sent;
			if ( ! $sent ) {
				$result['error'] = __( 'Failed to send email using WordPress mail function', METS_TEXT_DOMAIN );
			}
			return $result;
		}

		try {
			// Initialize PHPMailer
			$this->phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
			
			// Configure debug output
			$this->phpmailer->Debugoutput = array( $this, 'debug_output_callback' );
			$this->phpmailer->SMTPDebug = 2; // Enable verbose debug output
			
			// Configure SMTP
			$this->configure_smtp( $settings );
			
			// Set email parameters
			$this->set_email_parameters( $to, $subject, $message, $headers, $settings );
			
			// Send email
			$this->phpmailer->send();
			
			$result['success'] = true;
			$result['debug'] = $this->debug_output;
			
		} catch ( PHPMailer\PHPMailer\Exception $e ) {
			$result['error'] = sprintf( __( 'PHPMailer Error: %s', METS_TEXT_DOMAIN ), $e->getMessage() );
			$result['debug'] = $this->debug_output;
		} catch ( Exception $e ) {
			$result['error'] = sprintf( __( 'General Error: %s', METS_TEXT_DOMAIN ), $e->getMessage() );
			$result['debug'] = $this->debug_output;
		}
		
		// Log the email send attempt
		$email_data = array(
			'recipient_email' => $to,
			'subject' => $subject,
			'delivery_method' => $settings['method'] ?? 'smtp',
		);
		
		$this->logger->log_email_send( 
			$email_data, 
			$result['success'], 
			$result['error'], 
			$result['debug'] 
		);
		
		// Clear PHPMailer instance
		$this->phpmailer = null;
		$this->debug_output = array();
		
		return $result;
	}

	/**
	 * Configure SMTP settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 */
	private function configure_smtp( $settings ) {
		// Use SMTP
		$this->phpmailer->isSMTP();
		
		// Set host
		if ( ! empty( $settings['provider'] ) && $settings['provider'] !== 'custom' ) {
			// Use provider settings
			require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-providers.php';
			$provider_config = METS_SMTP_Providers::get_provider_config( $settings['provider'] );
			
			if ( $provider_config ) {
				$this->phpmailer->Host = $provider_config['host'];
				$this->phpmailer->Port = $provider_config['port'];
				$this->phpmailer->SMTPSecure = $provider_config['encryption'];
			}
		} else {
			// Use custom settings
			$this->phpmailer->Host = $settings['host'];
			$this->phpmailer->Port = absint( $settings['port'] );
			
			// Set encryption
			switch ( $settings['encryption'] ) {
				case 'ssl':
					$this->phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
					break;
				case 'tls':
					$this->phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
					break;
				default:
					$this->phpmailer->SMTPSecure = false;
					$this->phpmailer->SMTPAutoTLS = false;
			}
		}
		
		// Authentication
		if ( ! empty( $settings['auth_required'] ) ) {
			$this->phpmailer->SMTPAuth = true;
			$this->phpmailer->Username = $settings['username'];
			$this->phpmailer->Password = $settings['password'];
			
			// Enhanced debugging for Gmail
			if ( strpos( $settings['host'], 'gmail.com' ) !== false ) {
				error_log( '[METS-SMTP-DEBUG] Gmail detected - Username: ' . 
					substr( $settings['username'], 0, 3 ) . '***' . 
					' | Password length: ' . strlen( $settings['password'] ) );
			}
		} else {
			$this->phpmailer->SMTPAuth = false;
		}
		
		// Additional options
		$this->phpmailer->Timeout = 30;
		$this->phpmailer->SMTPKeepAlive = true;
		
		// Allow insecure connections for testing (not recommended for production)
		if ( defined( 'METS_SMTP_ALLOW_INSECURE' ) && METS_SMTP_ALLOW_INSECURE ) {
			$this->phpmailer->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				),
			);
		}
	}

	/**
	 * Set email parameters
	 *
	 * @since    1.0.0
	 * @param    string   $to          Recipient
	 * @param    string   $subject     Subject
	 * @param    string   $message     Message
	 * @param    array    $headers     Headers
	 * @param    array    $settings    SMTP settings
	 */
	private function set_email_parameters( $to, $subject, $message, $headers, $settings ) {
		// Set charset
		$this->phpmailer->CharSet = get_bloginfo( 'charset' );
		
		// Set From
		$from_email = ! empty( $settings['from_email'] ) ? $settings['from_email'] : get_option( 'admin_email' );
		$from_name = ! empty( $settings['from_name'] ) ? $settings['from_name'] : get_bloginfo( 'name' );
		$this->phpmailer->setFrom( $from_email, $from_name );
		
		// Set Reply-To
		if ( ! empty( $settings['reply_to'] ) ) {
			$this->phpmailer->addReplyTo( $settings['reply_to'] );
		}
		
		// Set recipient(s)
		$recipients = explode( ',', $to );
		foreach ( $recipients as $recipient ) {
			$recipient = trim( $recipient );
			if ( is_email( $recipient ) ) {
				$this->phpmailer->addAddress( $recipient );
			}
		}
		
		// Set subject
		$this->phpmailer->Subject = $subject;
		
		// Process headers
		$this->process_headers( $headers );
		
		// Set message body
		if ( $this->phpmailer->ContentType === 'text/html' ) {
			$this->phpmailer->msgHTML( $message );
			
			// Create text version from HTML
			if ( ! $this->phpmailer->AltBody ) {
				$this->phpmailer->AltBody = wp_strip_all_tags( $message );
			}
		} else {
			$this->phpmailer->Body = $message;
		}
	}

	/**
	 * Process email headers
	 *
	 * @since    1.0.0
	 * @param    array|string    $headers    Email headers
	 */
	private function process_headers( $headers ) {
		if ( empty( $headers ) ) {
			// Default to HTML email
			$this->phpmailer->isHTML( true );
			return;
		}
		
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}
		
		foreach ( $headers as $header ) {
			if ( strpos( $header, ':' ) === false ) {
				continue;
			}
			
			list( $name, $content ) = explode( ':', trim( $header ), 2 );
			$name = trim( $name );
			$content = trim( $content );
			
			switch ( strtolower( $name ) ) {
				case 'from':
					// Parse from header
					if ( preg_match( '/(.*)<(.+)>/', $content, $matches ) ) {
						$this->phpmailer->setFrom( trim( $matches[2] ), trim( $matches[1] ) );
					} else {
						$this->phpmailer->setFrom( $content );
					}
					break;
					
				case 'content-type':
					if ( strpos( $content, 'text/html' ) !== false ) {
						$this->phpmailer->isHTML( true );
					} else {
						$this->phpmailer->isHTML( false );
					}
					break;
					
				case 'cc':
					$this->phpmailer->addCC( $content );
					break;
					
				case 'bcc':
					$this->phpmailer->addBCC( $content );
					break;
					
				case 'reply-to':
					$this->phpmailer->addReplyTo( $content );
					break;
					
				default:
					// Add as custom header
					$this->phpmailer->addCustomHeader( $name, $content );
			}
		}
		
		// Default to HTML if not set
		if ( ! isset( $this->phpmailer->ContentType ) ) {
			$this->phpmailer->isHTML( true );
		}
	}

	/**
	 * Test SMTP connection
	 *
	 * @since    1.0.0
	 * @param    array    $settings    SMTP settings
	 * @return   array                 Test result
	 */
	public function test_connection( $settings ) {
		$result = array(
			'success' => false,
			'message' => '',
			'debug' => array(),
		);
		
		try {
			// Initialize PHPMailer
			$this->phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
			
			// Configure debug output
			$this->phpmailer->Debugoutput = array( $this, 'debug_output_callback' );
			$this->phpmailer->SMTPDebug = 3; // Enable full debug output
			
			// Configure SMTP
			$this->configure_smtp( $settings );
			
			// Test connection
			if ( $this->phpmailer->smtpConnect() ) {
				$result['success'] = true;
				$result['message'] = __( 'SMTP connection successful!', METS_TEXT_DOMAIN );
				$this->phpmailer->smtpClose();
			} else {
				$result['message'] = __( 'Failed to connect to SMTP server', METS_TEXT_DOMAIN );
			}
			
			$result['debug'] = $this->debug_output;
			
		} catch ( PHPMailer\PHPMailer\Exception $e ) {
			$error_message = $e->getMessage();
			$result['message'] = sprintf( __( 'PHPMailer Error: %s', METS_TEXT_DOMAIN ), $error_message );
			$result['debug'] = $this->debug_output;
			
			// Add Gmail-specific troubleshooting hints
			if ( strpos( $settings['host'], 'gmail.com' ) !== false ) {
				if ( strpos( $error_message, 'Could not authenticate' ) !== false ) {
					$result['message'] .= "\n\n" . __( 'Gmail Authentication Tips:', METS_TEXT_DOMAIN ) . "\n";
					$result['message'] .= "• " . __( 'Use an App Password instead of your regular Gmail password', METS_TEXT_DOMAIN ) . "\n";
					$result['message'] .= "• " . __( 'Enable 2-Factor Authentication in your Google Account', METS_TEXT_DOMAIN ) . "\n";
					$result['message'] .= "• " . __( 'Generate an App Password: Google Account > Security > App passwords', METS_TEXT_DOMAIN ) . "\n";
					$result['message'] .= "• " . __( 'Use your full Gmail address as username (e.g., you@gmail.com)', METS_TEXT_DOMAIN );
				}
			}
		} catch ( Exception $e ) {
			$result['message'] = sprintf( __( 'General Error: %s', METS_TEXT_DOMAIN ), $e->getMessage() );
			$result['debug'] = $this->debug_output;
		}
		
		// Log the connection attempt
		$this->logger->log_connection( 
			$settings, 
			$result['success'], 
			$result['success'] ? '' : $result['message']
		);
		
		// Clear PHPMailer instance
		$this->phpmailer = null;
		$this->debug_output = array();
		
		return $result;
	}

	/**
	 * Debug output callback
	 *
	 * @since    1.0.0
	 * @param    string   $str     Debug string
	 * @param    int      $level   Debug level
	 */
	public function debug_output_callback( $str, $level = 0 ) {
		// Clean up debug output
		$str = trim( $str );
		if ( ! empty( $str ) ) {
			// Sanitize sensitive information
			$str = $this->sanitize_debug_output( $str );
			$this->debug_output[] = $str;
		}
	}

	/**
	 * Sanitize debug output
	 *
	 * @since    1.0.0
	 * @param    string   $str    Debug string
	 * @return   string           Sanitized string
	 */
	private function sanitize_debug_output( $str ) {
		// Remove passwords from debug output
		$str = preg_replace( '/PASS(WORD)?\s*[=:]\s*[^\s]+/i', 'PASS=***', $str );
		$str = preg_replace( '/AUTH\s+PLAIN\s+[^\s]+/i', 'AUTH PLAIN ***', $str );
		$str = preg_replace( '/AUTH\s+LOGIN\s+[^\s]+/i', 'AUTH LOGIN ***', $str );
		
		return $str;
	}

	/**
	 * Hook into WordPress mail function
	 *
	 * @since    1.0.0
	 * @param    array    $args    Mail arguments
	 * @return   array             Modified arguments
	 */
	public function phpmailer_init( $phpmailer ) {
		// Get SMTP settings
		$smtp_manager = METS_SMTP_Manager::get_instance();
		$settings = $smtp_manager->get_global_settings();
		
		// Skip if SMTP is not enabled
		if ( empty( $settings['enabled'] ) || $settings['method'] === 'wordpress' ) {
			return;
		}
		
		// Configure PHPMailer with our SMTP settings
		try {
			$this->phpmailer = $phpmailer;
			$this->configure_smtp( $settings );
			
			// Set From if configured
			if ( ! empty( $settings['from_email'] ) ) {
				$from_name = ! empty( $settings['from_name'] ) ? $settings['from_name'] : get_bloginfo( 'name' );
				$phpmailer->setFrom( $settings['from_email'], $from_name );
			}
			
			// Set Reply-To if configured
			if ( ! empty( $settings['reply_to'] ) ) {
				$phpmailer->clearReplyTos();
				$phpmailer->addReplyTo( $settings['reply_to'] );
			}
			
		} catch ( Exception $e ) {
			// Log error but don't break email sending
			error_log( 'METS SMTP Error: ' . $e->getMessage() );
		}
	}
}