<?php
/**
 * SLA Monitor
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * SLA Monitor class.
 *
 * This class handles automated SLA monitoring, breach detection, and escalation workflows.
 * It runs on WordPress cron to continuously monitor ticket SLA compliance.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_SLA_Monitor {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SLA_Monitor    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * SLA Calculator instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_SLA_Calculator    $sla_calculator    SLA calculator.
	 */
	private $sla_calculator;

	/**
	 * Email Queue instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Email_Queue    $email_queue    Email queue.
	 */
	private $email_queue;

	/**
	 * Ticket Model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Ticket_Model    $ticket_model    Ticket model.
	 */
	private $ticket_model;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_SLA_Monitor    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-calculator.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-queue.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';

		$this->sla_calculator = new METS_SLA_Calculator();
		$this->email_queue = METS_Email_Queue::get_instance();
		$this->ticket_model = new METS_Ticket_Model();
	}

	/**
	 * Schedule SLA monitoring cron job
	 *
	 * @since    1.0.0
	 */
	public function schedule_monitoring() {
		if ( ! wp_next_scheduled( 'mets_sla_monitoring' ) ) {
			wp_schedule_event( time(), 'mets_sla_check_interval', 'mets_sla_monitoring' );
		}
	}

	/**
	 * Add custom cron schedule intervals
	 *
	 * @since    1.0.0
	 * @param    array    $schedules    WordPress cron schedules
	 * @return   array                  Modified schedules
	 */
	public function add_cron_intervals( $schedules ) {
		// Add 15-minute interval for SLA monitoring - avoid early translation loading
		$schedules['mets_sla_check_interval'] = array(
			'interval' => 900, // 15 minutes
			'display'  => did_action( 'init' ) ? __( 'Every 15 minutes (SLA Monitoring)', METS_TEXT_DOMAIN ) : 'Every 15 minutes (SLA Monitoring)',
		);

		// Add 5-minute interval for urgent SLA monitoring - avoid early translation loading
		$schedules['mets_urgent_sla_check'] = array(
			'interval' => 300, // 5 minutes
			'display'  => did_action( 'init' ) ? __( 'Every 5 minutes (Urgent SLA)', METS_TEXT_DOMAIN ) : 'Every 5 minutes (Urgent SLA)',
		);

		return $schedules;
	}

	/**
	 * Main SLA monitoring function - called by cron
	 *
	 * @since    1.0.0
	 */
	public function monitor_sla_compliance() {
		error_log( '[METS-SLA] Starting SLA monitoring check at ' . current_time( 'mysql' ) );

		// Get all active tickets that need SLA monitoring
		$tickets = $this->get_tickets_for_monitoring();

		$processed = 0;
		$warnings_sent = 0;
		$breaches_detected = 0;
		$escalations_triggered = 0;

		foreach ( $tickets as $ticket ) {
			$result = $this->check_ticket_sla( $ticket );
			
			$processed++;
			
			if ( $result['warning_sent'] ) {
				$warnings_sent++;
			}
			
			if ( $result['breach_detected'] ) {
				$breaches_detected++;
			}
			
			if ( $result['escalation_triggered'] ) {
				$escalations_triggered++;
			}
		}

		// Log monitoring summary
		error_log( sprintf(
			'[METS-SLA] Monitoring complete - Processed: %d, Warnings: %d, Breaches: %d, Escalations: %d',
			$processed,
			$warnings_sent,
			$breaches_detected,
			$escalations_triggered
		) );

		// Update monitoring metrics
		$this->update_monitoring_metrics( array(
			'processed' => $processed,
			'warnings_sent' => $warnings_sent,
			'breaches_detected' => $breaches_detected,
			'escalations_triggered' => $escalations_triggered,
			'last_check' => current_time( 'mysql' ),
		) );

		do_action( 'mets_sla_monitoring_complete', array(
			'processed' => $processed,
			'warnings_sent' => $warnings_sent,
			'breaches_detected' => $breaches_detected,
			'escalations_triggered' => $escalations_triggered,
		) );
	}

	/**
	 * Get tickets that need SLA monitoring
	 *
	 * @since    1.0.0
	 * @return   array    Tickets to monitor
	 */
	private function get_tickets_for_monitoring() {
		global $wpdb;

		// Get active tickets (not closed/resolved) that have SLA rules
		$tickets = $wpdb->get_results( "
			SELECT t.*, 
				   e.name as entity_name,
				   t.sla_response_due,
				   t.sla_resolution_due,
				   t.sla_status
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			WHERE t.status NOT IN ('closed', 'resolved')
			AND (
				t.sla_response_due IS NOT NULL 
				OR t.sla_resolution_due IS NOT NULL
				OR EXISTS (
					SELECT 1 FROM {$wpdb->prefix}mets_sla_rules r 
					WHERE (r.entity_id = t.entity_id OR r.entity_id IS NULL)
					AND r.is_active = 1
					AND (
						r.priority = t.priority 
						OR r.priority IS NULL
					)
				)
			)
			ORDER BY t.priority DESC, t.created_at ASC
		" );

		return $tickets;
	}

	/**
	 * Check SLA compliance for a single ticket
	 *
	 * @since    1.0.0
	 * @param    object   $ticket    Ticket data
	 * @return   array              Check results
	 */
	private function check_ticket_sla( $ticket ) {
		$result = array(
			'warning_sent' => false,
			'breach_detected' => false,
			'escalation_triggered' => false,
		);

		// Get applicable SLA rule for this ticket
		$sla_rule = $this->sla_calculator->get_applicable_sla( (array) $ticket );

		if ( ! $sla_rule ) {
			return $result; // No SLA rule applies
		}

		$current_time = current_time( 'mysql' );
		$ticket_data = (array) $ticket;

		// Calculate SLA due dates if not already set
		if ( empty( $ticket->sla_response_due ) || empty( $ticket->sla_resolution_due ) ) {
			$this->update_ticket_sla_dates( $ticket, $sla_rule );
		}

		// Check response time SLA
		if ( $ticket->sla_response_due && $ticket->sla_status !== 'response_breached' ) {
			$response_check = $this->check_response_sla( $ticket, $sla_rule, $current_time );
			$result = array_merge( $result, $response_check );
		}

		// Check resolution time SLA
		if ( $ticket->sla_resolution_due && $ticket->sla_status !== 'resolution_breached' ) {
			$resolution_check = $this->check_resolution_sla( $ticket, $sla_rule, $current_time );
			$result = array_merge( $result, $resolution_check );
		}

		return $result;
	}

	/**
	 * Check response time SLA for a ticket
	 *
	 * @since    1.0.0
	 * @param    object   $ticket      Ticket data
	 * @param    object   $sla_rule    SLA rule
	 * @param    string   $current_time Current time
	 * @return   array                 Check results
	 */
	private function check_response_sla( $ticket, $sla_rule, $current_time ) {
		$result = array(
			'warning_sent' => false,
			'breach_detected' => false,
			'escalation_triggered' => false,
		);

		$response_due = strtotime( $ticket->sla_response_due );
		$current_timestamp = strtotime( $current_time );

		// Calculate warning time (80% of SLA time)
		$warning_threshold = $response_due - ( $sla_rule->response_time_hours * 3600 * 0.2 );

		// Check if we need to send warning
		if ( $current_timestamp >= $warning_threshold && $ticket->sla_status === 'active' ) {
			$this->send_sla_warning( $ticket, 'response', $sla_rule );
			$this->update_ticket_sla_status( $ticket->id, 'response_warning' );
			$result['warning_sent'] = true;
		}

		// Check if SLA is breached
		if ( $current_timestamp > $response_due ) {
			$this->handle_sla_breach( $ticket, 'response', $sla_rule );
			$this->update_ticket_sla_status( $ticket->id, 'response_breached' );
			$result['breach_detected'] = true;

			// Trigger escalation if configured
			if ( ! empty( $sla_rule->escalation_time ) ) {
				$escalation_result = $this->trigger_escalation( $ticket, 'response', $sla_rule );
				$result['escalation_triggered'] = $escalation_result;
			}
		}

		return $result;
	}

	/**
	 * Check resolution time SLA for a ticket
	 *
	 * @since    1.0.0
	 * @param    object   $ticket      Ticket data
	 * @param    object   $sla_rule    SLA rule
	 * @param    string   $current_time Current time
	 * @return   array                 Check results
	 */
	private function check_resolution_sla( $ticket, $sla_rule, $current_time ) {
		$result = array(
			'warning_sent' => false,
			'breach_detected' => false,
			'escalation_triggered' => false,
		);

		$resolution_due = strtotime( $ticket->sla_resolution_due );
		$current_timestamp = strtotime( $current_time );

		// Calculate warning time (80% of SLA time)
		$warning_threshold = $resolution_due - ( $sla_rule->resolution_time_hours * 3600 * 0.2 );

		// Check if we need to send warning
		if ( $current_timestamp >= $warning_threshold && 
			 ! in_array( $ticket->sla_status, array( 'resolution_warning', 'resolution_breached' ) ) ) {
			$this->send_sla_warning( $ticket, 'resolution', $sla_rule );
			$this->update_ticket_sla_status( $ticket->id, 'resolution_warning' );
			$result['warning_sent'] = true;
		}

		// Check if SLA is breached
		if ( $current_timestamp > $resolution_due ) {
			$this->handle_sla_breach( $ticket, 'resolution', $sla_rule );
			$this->update_ticket_sla_status( $ticket->id, 'resolution_breached' );
			$result['breach_detected'] = true;

			// Trigger escalation if configured
			if ( ! empty( $sla_rule->escalation_time ) ) {
				$escalation_result = $this->trigger_escalation( $ticket, 'resolution', $sla_rule );
				$result['escalation_triggered'] = $escalation_result;
			}
		}

		return $result;
	}

	/**
	 * Update ticket SLA dates
	 *
	 * @since    1.0.0
	 * @param    object   $ticket    Ticket data
	 * @param    object   $sla_rule  SLA rule
	 */
	private function update_ticket_sla_dates( $ticket, $sla_rule ) {
		$response_due = null;
		$resolution_due = null;

		if ( ! empty( $sla_rule->response_time_hours ) ) {
			$response_due = $this->sla_calculator->calculate_due_date(
				$ticket->created_at,
				$sla_rule->response_time_hours,
				isset( $sla_rule->business_hours_only ) ? (bool) $sla_rule->business_hours_only : false,
				$ticket->entity_id
			);
		}

		if ( ! empty( $sla_rule->resolution_time_hours ) ) {
			$resolution_due = $this->sla_calculator->calculate_due_date(
				$ticket->created_at,
				$sla_rule->resolution_time_hours,
				isset( $sla_rule->business_hours_only ) ? (bool) $sla_rule->business_hours_only : false,
				$ticket->entity_id
			);
		}

		// Update ticket with SLA dates
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'mets_tickets',
			array(
				'sla_response_due' => $response_due,
				'sla_resolution_due' => $resolution_due,
				'sla_status' => 'active',
			),
			array( 'id' => $ticket->id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update ticket SLA status
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id   Ticket ID
	 * @param    string   $status      New SLA status
	 */
	private function update_ticket_sla_status( $ticket_id, $status ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'mets_tickets',
			array( 'sla_status' => $status ),
			array( 'id' => $ticket_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Send SLA warning notification
	 *
	 * @since    1.0.0
	 * @param    object   $ticket     Ticket data
	 * @param    string   $type       Warning type (response/resolution)
	 * @param    object   $sla_rule   SLA rule
	 */
	private function send_sla_warning( $ticket, $type, $sla_rule ) {
		$template_data = array(
			'ticket' => $ticket,
			'sla_rule' => $sla_rule,
			'warning_type' => $type,
			'due_date' => $type === 'response' ? $ticket->sla_response_due : $ticket->sla_resolution_due,
		);

		$subject = sprintf(
			__( 'SLA Warning: Ticket #%s - %s SLA Due Soon', METS_TEXT_DOMAIN ),
			$ticket->ticket_number,
			ucfirst( $type )
		);

		// Queue warning email to assigned agent
		if ( ! empty( $ticket->assigned_to ) ) {
			$assigned_user = get_userdata( $ticket->assigned_to );
			if ( $assigned_user ) {
				$this->email_queue->queue_email( array(
					'recipient_email' => $assigned_user->user_email,
					'recipient_name' => $assigned_user->display_name,
					'subject' => $subject,
					'template_name' => 'sla-warning',
					'template_data' => $template_data,
					'priority' => 8,
				) );
			}
		}

		// Queue warning email to managers/admins
		$this->send_manager_notification( $ticket, $subject, 'sla-warning', $template_data );
	}

	/**
	 * Handle SLA breach
	 *
	 * @since    1.0.0
	 * @param    object   $ticket     Ticket data
	 * @param    string   $type       Breach type (response/resolution)
	 * @param    object   $sla_rule   SLA rule
	 */
	private function handle_sla_breach( $ticket, $type, $sla_rule ) {
		$template_data = array(
			'ticket' => $ticket,
			'sla_rule' => $sla_rule,
			'breach_type' => $type,
			'due_date' => $type === 'response' ? $ticket->sla_response_due : $ticket->sla_resolution_due,
		);

		$subject = sprintf(
			__( 'SLA BREACH: Ticket #%s - %s SLA Exceeded', METS_TEXT_DOMAIN ),
			$ticket->ticket_number,
			ucfirst( $type )
		);

		// Log the breach
		error_log( sprintf(
			'[METS-SLA] SLA BREACH - Ticket #%s (%s) - %s SLA exceeded',
			$ticket->ticket_number,
			$ticket->id,
			$type
		) );

		// Queue breach notification to assigned agent
		if ( ! empty( $ticket->assigned_to ) ) {
			$assigned_user = get_userdata( $ticket->assigned_to );
			if ( $assigned_user ) {
				$this->email_queue->queue_email( array(
					'recipient_email' => $assigned_user->user_email,
					'recipient_name' => $assigned_user->display_name,
					'subject' => $subject,
					'template_name' => 'sla-breach',
					'template_data' => $template_data,
					'priority' => 9,
				) );
			}
		}

		// Queue breach notification to managers/admins
		$this->send_manager_notification( $ticket, $subject, 'sla-breach', $template_data, 9 );

		// Add internal note to ticket
		$this->add_sla_breach_note( $ticket, $type );
	}

	/**
	 * Trigger escalation workflow
	 *
	 * @since    1.0.0
	 * @param    object   $ticket     Ticket data
	 * @param    string   $type       Escalation type
	 * @param    object   $sla_rule   SLA rule
	 * @return   bool                 Whether escalation was triggered
	 */
	private function trigger_escalation( $ticket, $type, $sla_rule ) {
		// Implementation depends on escalation configuration
		// This is a placeholder for escalation logic

		error_log( sprintf(
			'[METS-SLA] Escalation triggered - Ticket #%s (%s) - %s SLA',
			$ticket->ticket_number,
			$ticket->id,
			$type
		) );

		do_action( 'mets_sla_escalation_triggered', $ticket, $type, $sla_rule );

		return true;
	}

	/**
	 * Send notification to managers/admins
	 *
	 * @since    1.0.0
	 * @param    object   $ticket        Ticket data
	 * @param    string   $subject       Email subject
	 * @param    string   $template      Email template
	 * @param    array    $template_data Template data
	 * @param    int      $priority      Email priority
	 */
	private function send_manager_notification( $ticket, $subject, $template, $template_data, $priority = 7 ) {
		// Get users with manager/admin capabilities
		$managers = get_users( array(
			'capability' => 'manage_tickets',
			'fields' => array( 'user_email', 'display_name' ),
		) );

		foreach ( $managers as $manager ) {
			$this->email_queue->queue_email( array(
				'recipient_email' => $manager->user_email,
				'recipient_name' => $manager->display_name,
				'subject' => $subject,
				'template_name' => $template,
				'template_data' => $template_data,
				'priority' => $priority,
			) );
		}
	}

	/**
	 * Add SLA breach note to ticket
	 *
	 * @since    1.0.0
	 * @param    object   $ticket    Ticket data
	 * @param    string   $type      Breach type
	 */
	private function add_sla_breach_note( $ticket, $type ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();

		$message = sprintf(
			__( 'SLA BREACH: %s SLA exceeded for this ticket. Due date was: %s', METS_TEXT_DOMAIN ),
			ucfirst( $type ),
			$type === 'response' ? $ticket->sla_response_due : $ticket->sla_resolution_due
		);

		$reply_model->create( array(
			'ticket_id' => $ticket->id,
			'user_id' => 0, // System user
			'message' => $message,
			'is_internal_note' => true,
		) );
	}

	/**
	 * Update monitoring metrics
	 *
	 * @since    1.0.0
	 * @param    array    $metrics    Metrics data
	 */
	private function update_monitoring_metrics( $metrics ) {
		$existing_metrics = get_option( 'mets_sla_monitoring_metrics', array() );
		
		$updated_metrics = array_merge( $existing_metrics, array(
			'last_check' => $metrics['last_check'],
			'total_processed' => ( $existing_metrics['total_processed'] ?? 0 ) + $metrics['processed'],
			'total_warnings' => ( $existing_metrics['total_warnings'] ?? 0 ) + $metrics['warnings_sent'],
			'total_breaches' => ( $existing_metrics['total_breaches'] ?? 0 ) + $metrics['breaches_detected'],
			'total_escalations' => ( $existing_metrics['total_escalations'] ?? 0 ) + $metrics['escalations_triggered'],
		) );

		update_option( 'mets_sla_monitoring_metrics', $updated_metrics );
	}

	/**
	 * Get SLA monitoring metrics
	 *
	 * @since    1.0.0
	 * @return   array    Monitoring metrics
	 */
	public function get_monitoring_metrics() {
		return get_option( 'mets_sla_monitoring_metrics', array(
			'last_check' => '',
			'total_processed' => 0,
			'total_warnings' => 0,
			'total_breaches' => 0,
			'total_escalations' => 0,
		) );
	}

	/**
	 * Manual SLA check for a specific ticket
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @return   array                  Check results
	 */
	public function check_single_ticket( $ticket_id ) {
		$ticket = $this->ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return array( 'error' => 'Ticket not found' );
		}

		return $this->check_ticket_sla( $ticket );
	}

	/**
	 * Reset SLA for a ticket (useful when ticket is reassigned)
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @return   bool                   Success status
	 */
	public function reset_ticket_sla( $ticket_id ) {
		$ticket = $this->ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		// Get applicable SLA rule
		$sla_rule = $this->sla_calculator->get_applicable_sla( (array) $ticket );
		if ( ! $sla_rule ) {
			return false;
		}

		// Recalculate SLA dates from current time
		$this->update_ticket_sla_dates( $ticket, $sla_rule );

		error_log( "[METS-SLA] SLA reset for ticket #{$ticket->ticket_number} ({$ticket_id})" );

		return true;
	}
}