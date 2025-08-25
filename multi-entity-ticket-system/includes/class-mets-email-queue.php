<?php
/**
 * Email Queue Processor
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Email Queue Processor class.
 *
 * This class handles queuing, processing, and sending emails with SMTP support.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Email_Queue {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Email_Queue    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Template engine instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Email_Template_Engine    $template_engine    Template engine instance.
	 */
	private $template_engine;

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
	 * @return   METS_Email_Queue    Single instance
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
		$this->template_engine = new METS_Email_Template_Engine();
		$this->smtp_manager = METS_SMTP_Manager::get_instance();
		$this->logger = METS_SMTP_Logger::get_instance();
	}

	/**
	 * Queue an email for sending
	 *
	 * @since    1.0.0
	 * @param    array    $email_data    Email data
	 * @return   int|false               Queue ID or false on failure
	 */
	public function queue_email( $email_data ) {
		global $wpdb;

		$defaults = array(
			'recipient_email' => '',
			'recipient_name' => '',
			'subject' => '',
			'body' => '',
			'template_name' => '',
			'template_data' => array(),
			'entity_id' => null,
			'priority' => 5,
			'scheduled_at' => current_time( 'mysql' ),
			'max_attempts' => 3,
		);

		$email_data = wp_parse_args( $email_data, $defaults );

		// Validate required fields
		if ( empty( $email_data['recipient_email'] ) ) {
			error_log( 'METS Email Queue: Missing recipient email' );
			return false;
		}

		if ( empty( $email_data['subject'] ) && empty( $email_data['template_name'] ) ) {
			error_log( 'METS Email Queue: Missing subject and template' );
			return false;
		}

		// Get SMTP configuration for the entity
		$smtp_config = $this->smtp_manager->get_queue_config( $email_data['entity_id'] );

		// Process template if provided
		if ( ! empty( $email_data['template_name'] ) ) {
			$processed_template = $this->template_engine->load_template( 
				$email_data['template_name'], 
				$email_data['template_data'] 
			);
			
			if ( empty( $email_data['body'] ) ) {
				$email_data['body'] = $processed_template;
			}

			// Extract subject from template data if not provided
			if ( empty( $email_data['subject'] ) && ! empty( $email_data['template_data']['subject'] ) ) {
				$email_data['subject'] = $email_data['template_data']['subject'];
			}
		}

		// Insert into queue
		$result = $wpdb->insert(
			$wpdb->prefix . 'mets_email_queue',
			array(
				'recipient_email' => sanitize_email( $email_data['recipient_email'] ),
				'recipient_name' => sanitize_text_field( $email_data['recipient_name'] ),
				'subject' => sanitize_text_field( $email_data['subject'] ),
				'body' => wp_kses_post( $email_data['body'] ),
				'template_name' => sanitize_text_field( $email_data['template_name'] ),
				'template_data' => json_encode( $email_data['template_data'] ),
				'smtp_config' => json_encode( $smtp_config ),
				'delivery_method' => $smtp_config['method'] ?? 'wordpress',
				'priority' => intval( $email_data['priority'] ),
				'max_attempts' => intval( $email_data['max_attempts'] ),
				'scheduled_at' => $email_data['scheduled_at'],
				'status' => 'pending',
			),
			array(
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'
			)
		);

		if ( $result === false ) {
			error_log( 'METS Email Queue: Failed to queue email - ' . $wpdb->last_error );
			return false;
		}

		$queue_id = $wpdb->insert_id;

		// Log successful queuing
		do_action( 'mets_email_queued', $queue_id, $email_data );

		return $queue_id;
	}

	/**
	 * Process email queue
	 *
	 * @since    1.0.0
	 * @param    int    $batch_size    Number of emails to process
	 * @return   array                 Processing results
	 */
	public function process_queue( $batch_size = 10 ) {
		global $wpdb;

		$results = array(
			'processed' => 0,
			'sent' => 0,
			'failed' => 0,
			'errors' => array(),
		);

		// Get pending emails ordered by priority and scheduled time
		$emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_email_queue 
				WHERE status = 'pending' 
				AND scheduled_at <= %s 
				AND attempts < max_attempts
				ORDER BY priority ASC, scheduled_at ASC 
				LIMIT %d",
				current_time( 'mysql' ),
				$batch_size
			)
		);

		if ( empty( $emails ) ) {
			return $results;
		}

		foreach ( $emails as $email ) {
			$results['processed']++;
			
			// Update attempts count
			$wpdb->update(
				$wpdb->prefix . 'mets_email_queue',
				array( 'attempts' => $email->attempts + 1 ),
				array( 'id' => $email->id ),
				array( '%d' ),
				array( '%d' )
			);

			// Send the email
			$send_result = $this->send_queued_email( $email );

			if ( $send_result['success'] ) {
				$results['sent']++;
				
				// Mark as sent
				$wpdb->update(
					$wpdb->prefix . 'mets_email_queue',
					array(
						'status' => 'sent',
						'sent_at' => current_time( 'mysql' ),
						'error_message' => null,
					),
					array( 'id' => $email->id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);

				do_action( 'mets_email_sent', $email->id, $email );
			} else {
				$results['failed']++;
				$results['errors'][] = "Email {$email->id}: " . $send_result['error'];
				
				// Update error message
				$wpdb->update(
					$wpdb->prefix . 'mets_email_queue',
					array( 'error_message' => $send_result['error'] ),
					array( 'id' => $email->id ),
					array( '%s' ),
					array( '%d' )
				);

				// Mark as failed if max attempts reached
				if ( $email->attempts + 1 >= $email->max_attempts ) {
					$wpdb->update(
						$wpdb->prefix . 'mets_email_queue',
						array( 'status' => 'failed' ),
						array( 'id' => $email->id ),
						array( '%s' ),
						array( '%d' )
					);

					do_action( 'mets_email_failed', $email->id, $email );
				}
			}

			// Small delay between emails to prevent overwhelming SMTP servers
			usleep( 250000 ); // 0.25 seconds
		}

		// Log processing results
		$this->logger->log_queue_processing( $results );

		return $results;
	}

	/**
	 * Send a queued email
	 *
	 * @since    1.0.0
	 * @param    object   $email    Email object from queue
	 * @return   array              Send result
	 */
	private function send_queued_email( $email ) {
		$result = array(
			'success' => false,
			'error' => '',
		);

		try {
			// Decode SMTP config
			$smtp_config = json_decode( $email->smtp_config, true );
			
			// Get current SMTP settings (in case they've been updated)
			if ( ! empty( $smtp_config['entity_id'] ) ) {
				$current_settings = $this->smtp_manager->get_effective_settings( $smtp_config['entity_id'] );
			} else {
				$current_settings = $this->smtp_manager->get_global_settings();
			}

			// Prepare headers
			$headers = array();
			if ( strpos( $email->body, '<html' ) !== false || strpos( $email->body, '<p>' ) !== false ) {
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
			}

			// Add recipient name if available
			$to = $email->recipient_email;
			if ( ! empty( $email->recipient_name ) ) {
				$to = sprintf( '%s <%s>', $email->recipient_name, $email->recipient_email );
			}

			// Send using SMTP if configured
			if ( $current_settings['method'] === 'smtp' && ! empty( $current_settings['enabled'] ) ) {
				require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-mailer.php';
				$mailer = new METS_SMTP_Mailer();
				
				$send_result = $mailer->send_email(
					$email->recipient_email,
					$email->subject,
					$email->body,
					$headers,
					$current_settings
				);

				if ( $send_result['success'] ) {
					$result['success'] = true;
				} else {
					$result['error'] = $send_result['error'];
				}
			} else {
				// Use WordPress default mail
				$sent = wp_mail( $to, $email->subject, $email->body, $headers );
				
				if ( $sent ) {
					$result['success'] = true;
				} else {
					$result['error'] = 'WordPress mail function failed';
				}
			}

		} catch ( Exception $e ) {
			$result['error'] = 'Exception: ' . $e->getMessage();
		}

		return $result;
	}

	/**
	 * Send immediate email (bypass queue)
	 *
	 * @since    1.0.0
	 * @param    array    $email_data    Email data
	 * @return   array                   Send result
	 */
	public function send_immediate( $email_data ) {
		$defaults = array(
			'recipient_email' => '',
			'recipient_name' => '',
			'subject' => '',
			'body' => '',
			'template_name' => '',
			'template_data' => array(),
			'entity_id' => null,
		);

		$email_data = wp_parse_args( $email_data, $defaults );

		// Process template if provided
		if ( ! empty( $email_data['template_name'] ) ) {
			$processed_template = $this->template_engine->load_template( 
				$email_data['template_name'], 
				$email_data['template_data'] 
			);
			
			if ( empty( $email_data['body'] ) ) {
				$email_data['body'] = $processed_template;
			}

			// Extract subject from template data if not provided
			if ( empty( $email_data['subject'] ) && ! empty( $email_data['template_data']['subject'] ) ) {
				$email_data['subject'] = $email_data['template_data']['subject'];
			}
		}

		// Get SMTP settings
		$smtp_settings = $this->smtp_manager->get_effective_settings( $email_data['entity_id'] );

		// Prepare headers
		$headers = array();
		if ( strpos( $email_data['body'], '<html' ) !== false || strpos( $email_data['body'], '<p>' ) !== false ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		// Send using SMTP if configured
		if ( $smtp_settings['method'] === 'smtp' && ! empty( $smtp_settings['enabled'] ) ) {
			require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-mailer.php';
			$mailer = new METS_SMTP_Mailer();
			
			return $mailer->send_email(
				$email_data['recipient_email'],
				$email_data['subject'],
				$email_data['body'],
				$headers,
				$smtp_settings
			);
		} else {
			// Use WordPress default mail
			$to = $email_data['recipient_email'];
			if ( ! empty( $email_data['recipient_name'] ) ) {
				$to = sprintf( '%s <%s>', $email_data['recipient_name'], $email_data['recipient_email'] );
			}

			$sent = wp_mail( $to, $email_data['subject'], $email_data['body'], $headers );
			
			return array(
				'success' => $sent,
				'error' => $sent ? '' : 'WordPress mail function failed',
			);
		}
	}

	/**
	 * Cancel queued email
	 *
	 * @since    1.0.0
	 * @param    int    $queue_id    Queue ID
	 * @return   bool                True on success
	 */
	public function cancel_email( $queue_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'mets_email_queue',
			array( 'status' => 'cancelled' ),
			array( 'id' => $queue_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			do_action( 'mets_email_cancelled', $queue_id );
			return true;
		}

		return false;
	}

	/**
	 * Get queue statistics
	 *
	 * @since    1.0.0
	 * @return   array    Queue statistics
	 */
	public function get_queue_stats() {
		global $wpdb;

		$stats = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
				SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
				SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
				SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
			FROM {$wpdb->prefix}mets_email_queue",
			ARRAY_A
		);

		return $stats ?: array(
			'total' => 0,
			'pending' => 0,
			'sent' => 0,
			'failed' => 0,
			'cancelled' => 0,
		);
	}

	/**
	 * Clean old emails from queue
	 *
	 * @since    1.0.0
	 * @param    int    $days_old    Days to keep emails
	 * @return   int                 Number of emails deleted
	 */
	public function clean_old_emails( $days_old = 30 ) {
		global $wpdb;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}mets_email_queue 
				WHERE status IN ('sent', 'failed', 'cancelled') 
				AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days_old
			)
		);

		if ( $deleted > 0 ) {
			do_action( 'mets_email_queue_cleaned', $deleted );
		}

		return $deleted ?: 0;
	}
}