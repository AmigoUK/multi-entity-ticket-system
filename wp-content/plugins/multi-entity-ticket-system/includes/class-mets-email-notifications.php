<?php
/**
 * Email Notifications Handler
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Email Notifications Handler class.
 *
 * This class handles sending email notifications for various ticket events.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Email_Notifications {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Email_Notifications    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Email queue instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Email_Queue    $email_queue    Email queue instance.
	 */
	private $email_queue;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Email_Notifications    Single instance
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
		$this->email_queue = METS_Email_Queue::get_instance();
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks
	 *
	 * @since    1.0.0
	 */
	private function setup_hooks() {
		// Ticket lifecycle hooks
		add_action( 'mets_ticket_created', array( $this, 'on_ticket_created' ), 10, 2 );
		add_action( 'mets_ticket_replied', array( $this, 'on_ticket_replied' ), 10, 3 );
		add_action( 'mets_ticket_assigned', array( $this, 'on_ticket_assigned' ), 10, 3 );
		add_action( 'mets_ticket_status_changed', array( $this, 'on_status_changed' ), 10, 4 );
		
		// SLA hooks
		add_action( 'mets_sla_approaching_breach', array( $this, 'on_sla_warning' ), 10, 1 );
		add_action( 'mets_sla_breached', array( $this, 'on_sla_breach' ), 10, 1 );
	}

	/**
	 * Handle ticket created event
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    array    $ticket_data   Ticket data
	 */
	public function on_ticket_created( $ticket_id, $ticket_data ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		
		if ( ! $ticket ) {
			return;
		}

		// Check if notifications are enabled
		$email_settings = get_option( 'mets_email_settings', array() );
		if ( empty( $email_settings['notify_on_new_ticket'] ) ) {
			return;
		}

		// Send confirmation to customer
		$this->send_customer_notification( $ticket, 'ticket-created-customer' );
		
		// Send notification to agents (if configured)
		$this->send_agent_notification( $ticket, 'ticket-created-agent' );
	}

	/**
	 * Handle ticket reply event
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @param    int    $reply_id     Reply ID
	 * @param    array  $reply_data   Reply data
	 */
	public function on_ticket_replied( $ticket_id, $reply_id, $reply_data ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		$reply = $this->get_reply_data( $reply_id );
		
		if ( ! $ticket || ! $reply ) {
			return;
		}

		// Check if notifications are enabled
		$email_settings = get_option( 'mets_email_settings', array() );
		if ( empty( $email_settings['notify_on_reply'] ) ) {
			return;
		}

		// Prepare reply data
		$ticket['reply_content'] = $reply->content;
		$ticket['reply_date'] = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->created_at ) );
		
		if ( $reply->user_type === 'agent' ) {
			// Agent replied - notify customer
			$ticket['agent_name'] = $this->get_user_display_name( $reply->user_id );
			$this->send_customer_notification( $ticket, 'ticket-reply-customer' );
		} else {
			// Customer replied - notify agents
			$this->send_agent_notification( $ticket, 'ticket-reply-agent' );
		}
	}

	/**
	 * Handle ticket assigned event
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @param    int    $old_agent    Old agent ID
	 * @param    int    $new_agent    New agent ID
	 */
	public function on_ticket_assigned( $ticket_id, $old_agent, $new_agent ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		
		if ( ! $ticket || ! $new_agent ) {
			return;
		}

		// Check if notifications are enabled
		$email_settings = get_option( 'mets_email_settings', array() );
		if ( empty( $email_settings['notify_on_assignment'] ) ) {
			return;
		}

		// Send notification to assigned agent
		$agent = get_userdata( $new_agent );
		if ( $agent ) {
			$ticket['agent_name'] = $agent->display_name;
			$ticket['agent_email'] = $agent->user_email;
			
			$this->email_queue->queue_email( array(
				'recipient_email' => $agent->user_email,
				'recipient_name' => $agent->display_name,
				'template_name' => 'ticket-assigned',
				'template_data' => $ticket,
				'entity_id' => $ticket['entity_id'],
				'priority' => 3,
			) );
		}
	}

	/**
	 * Handle status change event
	 *
	 * @since    1.0.0
	 * @param    int       $ticket_id     Ticket ID
	 * @param    string    $old_status    Old status
	 * @param    string    $new_status    New status
	 * @param    int       $user_id       User who changed status
	 */
	public function on_status_changed( $ticket_id, $old_status, $new_status, $user_id ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		
		if ( ! $ticket ) {
			return;
		}

		// Check if notifications are enabled
		$email_settings = get_option( 'mets_email_settings', array() );
		if ( empty( $email_settings['notify_on_status_change'] ) ) {
			return;
		}

		// Add status change info
		$ticket['old_status'] = $old_status;
		$ticket['new_status'] = $new_status;
		$ticket['status_changed_by'] = $this->get_user_display_name( $user_id );
		
		// Send notification to customer
		$this->send_customer_notification( $ticket, 'ticket-status-changed' );
	}

	/**
	 * Handle SLA warning event
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 */
	public function on_sla_warning( $ticket_id ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		
		if ( ! $ticket ) {
			return;
		}

		// Send warning to agents
		$this->send_agent_notification( $ticket, 'sla-breach-warning', 1 ); // High priority
	}

	/**
	 * Handle SLA breach event
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 */
	public function on_sla_breach( $ticket_id ) {
		$ticket = $this->get_ticket_data( $ticket_id );
		
		if ( ! $ticket ) {
			return;
		}

		// Send breach notification to agents and managers
		$this->send_agent_notification( $ticket, 'sla-breach-notification', 1 ); // High priority
	}

	/**
	 * Send notification to customer
	 *
	 * @since    1.0.0
	 * @param    array    $ticket          Ticket data
	 * @param    string   $template_name   Template name
	 * @param    int      $priority        Email priority
	 */
	private function send_customer_notification( $ticket, $template_name, $priority = 5 ) {
		if ( empty( $ticket['customer_email'] ) ) {
			return;
		}

		$this->email_queue->queue_email( array(
			'recipient_email' => $ticket['customer_email'],
			'recipient_name' => $ticket['customer_name'],
			'template_name' => $template_name,
			'template_data' => $ticket,
			'entity_id' => $ticket['entity_id'],
			'priority' => $priority,
		) );
	}

	/**
	 * Send notification to agents
	 *
	 * @since    1.0.0
	 * @param    array    $ticket          Ticket data
	 * @param    string   $template_name   Template name
	 * @param    int      $priority        Email priority
	 */
	private function send_agent_notification( $ticket, $template_name, $priority = 5 ) {
		// Get agents for the entity
		$agents = $this->get_entity_agents( $ticket['entity_id'] );
		
		foreach ( $agents as $agent ) {
			$ticket['agent_name'] = $agent->display_name;
			$ticket['agent_email'] = $agent->user_email;
			
			$this->email_queue->queue_email( array(
				'recipient_email' => $agent->user_email,
				'recipient_name' => $agent->display_name,
				'template_name' => $template_name,
				'template_data' => $ticket,
				'entity_id' => $ticket['entity_id'],
				'priority' => $priority,
			) );
		}
	}

	/**
	 * Get ticket data for email templates
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @return   array|false          Ticket data or false
	 */
	private function get_ticket_data( $ticket_id ) {
		global $wpdb;
		
		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT t.*, e.name as entity_name, e.id as entity_id
				FROM {$wpdb->prefix}mets_tickets t
				LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
				WHERE t.id = %d",
				$ticket_id
			)
		);
		
		if ( ! $ticket ) {
			return false;
		}

		// Build portal URL
		$portal_url = METS_Core::get_ticket_portal_url();
		$ticket_url = $portal_url ? add_query_arg( array( 'ticket' => $ticket->ticket_number ), $portal_url ) : '';
		$admin_ticket_url = admin_url( "admin.php?page=mets-all-tickets&action=edit&ticket_id={$ticket->id}" );
		
		// Generate guest access token
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-guest-access-token-model.php';
		$token_model = new METS_Guest_Access_Token_Model();
		$guest_token = $token_model->generate_token( $ticket->id, $ticket->customer_email );
		
		// Build guest access URL if token was generated
		$guest_access_url = '';
		if ( $guest_token ) {
			$guest_access_url = add_query_arg( 
				array(
					'mets_view' => 'ticket',
					'mets_ticket' => $ticket->id,
					'access_token' => $guest_token,
				),
				home_url( '/guest-ticket-access' ) // This should be configurable
			);
		}

		return array(
			'ticket_id' => $ticket->id,
			'ticket_number' => $ticket->ticket_number,
			'ticket_subject' => $ticket->subject,
			'ticket_content' => $ticket->description,
			'ticket_url' => $ticket_url,
			'admin_ticket_url' => $admin_ticket_url,
			'guest_access_url' => $guest_access_url,
			'customer_name' => $ticket->customer_name,
			'customer_email' => $ticket->customer_email,
			'customer_phone' => $ticket->customer_phone ?: '',
			'entity_id' => $ticket->entity_id,
			'entity_name' => $ticket->entity_name,
			'status' => $ticket->status,
			'priority' => $ticket->priority,
			'category' => $ticket->category,
			'created_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ),
			'updated_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) ),
			'site_name' => get_bloginfo( 'name' ),
			'site_url' => home_url(),
			'portal_url' => $portal_url ?: home_url(),
		);
	}

	/**
	 * Get reply data
	 *
	 * @since    1.0.0
	 * @param    int    $reply_id    Reply ID
	 * @return   object|false        Reply data or false
	 */
	private function get_reply_data( $reply_id ) {
		global $wpdb;
		
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_ticket_replies WHERE id = %d",
				$reply_id
			)
		);
	}

	/**
	 * Get entity agents
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Array of agent user objects
	 */
	private function get_entity_agents( $entity_id ) {
		global $wpdb;
		
		$agent_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}mets_user_entities 
				WHERE entity_id = %d AND role IN ('agent', 'manager')",
				$entity_id
			)
		);
		
		$agents = array();
		foreach ( $agent_ids as $agent_id ) {
			$user = get_userdata( $agent_id );
			if ( $user ) {
				$agents[] = $user;
			}
		}
		
		return $agents;
	}

	/**
	 * Get user display name
	 *
	 * @since    1.0.0
	 * @param    int    $user_id    User ID
	 * @return   string             Display name
	 */
	private function get_user_display_name( $user_id ) {
		if ( ! $user_id ) {
			return __( 'System', METS_TEXT_DOMAIN );
		}
		
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : __( 'Unknown User', METS_TEXT_DOMAIN );
	}

	/**
	 * Send immediate email (bypass queue)
	 *
	 * @since    1.0.0
	 * @param    array    $email_data    Email data
	 * @return   array                   Send result
	 */
	public function send_immediate_email( $email_data ) {
		return $this->email_queue->send_immediate( $email_data );
	}

	/**
	 * Test email sending
	 *
	 * @since    1.0.0
	 * @param    string   $recipient    Recipient email
	 * @param    int      $entity_id    Entity ID (optional)
	 * @return   array                  Send result
	 */
	public function send_test_email( $recipient, $entity_id = null ) {
		$sample_ticket = array(
			'ticket_number' => 'TEST-' . time(),
			'ticket_subject' => 'Test Email from Multi-Entity Ticket System',
			'ticket_content' => 'This is a test email to verify that your email configuration is working correctly.',
			'customer_name' => 'Test Customer',
			'entity_name' => 'Test Department',
			'created_date' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'site_name' => get_bloginfo( 'name' ),
			'site_url' => home_url(),
			'portal_url' => home_url(),
			'ticket_url' => home_url(),
		);

		return $this->email_queue->send_immediate( array(
			'recipient_email' => $recipient,
			'template_name' => 'ticket-created-customer',
			'template_data' => $sample_ticket,
			'entity_id' => $entity_id,
		) );
	}
}