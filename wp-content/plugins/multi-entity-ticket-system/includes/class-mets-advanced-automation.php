<?php
/**
 * Advanced Automation System
 *
 * Handles advanced automation features like rules, triggers, and workflows
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * The Advanced Automation class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Advanced_Automation {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Advanced_Automation    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Registered automation rules
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $rules    Automation rules
	 */
	private $rules = array();

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Advanced_Automation    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the automation system
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
		$this->load_automation_rules();
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Ticket lifecycle hooks
		add_action( 'mets_ticket_created', array( $this, 'process_ticket_created' ), 10, 2 );
		add_action( 'mets_ticket_updated', array( $this, 'process_ticket_updated' ), 10, 3 );
		add_action( 'mets_ticket_status_changed', array( $this, 'process_status_changed' ), 10, 4 );
		add_action( 'mets_ticket_assigned', array( $this, 'process_ticket_assigned' ), 10, 2 );
		add_action( 'mets_ticket_replied', array( $this, 'process_ticket_replied' ), 10, 2 );

		// Scheduled automation
		add_action( 'mets_automation_check', array( $this, 'run_scheduled_automation' ) );
		add_action( 'mets_escalation_check', array( $this, 'check_escalations' ) );
		
		// Time-based triggers
		add_action( 'init', array( $this, 'schedule_automation_checks' ) );

		// Admin hooks
		add_action( 'wp_ajax_mets_test_automation_rule', array( $this, 'ajax_test_automation_rule' ) );
		add_action( 'wp_ajax_mets_save_automation_rule', array( $this, 'ajax_save_automation_rule' ) );
	}

	/**
	 * Load automation rules from database
	 *
	 * @since    1.0.0
	 */
	private function load_automation_rules() {
		$saved_rules = get_option( 'mets_automation_rules', array() );
		
		foreach ( $saved_rules as $rule ) {
			if ( ! empty( $rule['active'] ) ) {
				$this->rules[] = $rule;
			}
		}

		// Load default system rules
		$this->load_default_rules();
	}

	/**
	 * Load default system automation rules
	 *
	 * @since    1.0.0
	 */
	private function load_default_rules() {
		$default_rules = array(
			array(
				'id' => 'auto_assign_high_priority',
				'name' => 'Auto-assign high priority tickets',
				'trigger' => 'ticket_created',
				'conditions' => array(
					array( 'field' => 'priority', 'operator' => 'equals', 'value' => 'high' )
				),
				'actions' => array(
					array( 'action' => 'assign_agent', 'value' => 'round_robin' )
				),
				'active' => true,
				'system' => true
			),
			array(
				'id' => 'escalate_overdue_tickets',
				'name' => 'Escalate overdue tickets',
				'trigger' => 'scheduled',
				'schedule' => 'hourly',
				'conditions' => array(
					array( 'field' => 'sla_status', 'operator' => 'equals', 'value' => 'breached' ),
					array( 'field' => 'status', 'operator' => 'not_equals', 'value' => 'closed' )
				),
				'actions' => array(
					array( 'action' => 'update_priority', 'value' => 'critical' ),
					array( 'action' => 'notify_manager', 'value' => 'sla_breach' )
				),
				'active' => true,
				'system' => true
			),
			array(
				'id' => 'auto_close_resolved',
				'name' => 'Auto-close resolved tickets after 7 days',
				'trigger' => 'scheduled',
				'schedule' => 'daily',
				'conditions' => array(
					array( 'field' => 'status', 'operator' => 'equals', 'value' => 'resolved' ),
					array( 'field' => 'resolved_at', 'operator' => 'older_than', 'value' => '7 days' )
				),
				'actions' => array(
					array( 'action' => 'update_status', 'value' => 'closed' ),
					array( 'action' => 'add_note', 'value' => 'Automatically closed after 7 days of resolution' )
				),
				'active' => true,
				'system' => true
			),
			array(
				'id' => 'first_response_reminder',
				'name' => 'First response reminder',
				'trigger' => 'scheduled',
				'schedule' => 'every_15_minutes',
				'conditions' => array(
					array( 'field' => 'status', 'operator' => 'equals', 'value' => 'open' ),
					array( 'field' => 'first_response_at', 'operator' => 'is_null', 'value' => '' ),
					array( 'field' => 'created_at', 'operator' => 'older_than', 'value' => '2 hours' )
				),
				'actions' => array(
					array( 'action' => 'notify_agent', 'value' => 'first_response_reminder' )
				),
				'active' => true,
				'system' => true
			),
			array(
				'id' => 'vip_customer_priority',
				'name' => 'VIP customer high priority',
				'trigger' => 'ticket_created',
				'conditions' => array(
					array( 'field' => 'customer_meta', 'operator' => 'contains', 'value' => 'vip_customer' )
				),
				'actions' => array(
					array( 'action' => 'update_priority', 'value' => 'high' ),
					array( 'action' => 'add_tag', 'value' => 'VIP' )
				),
				'active' => true,
				'system' => true
			)
		);

		foreach ( $default_rules as $rule ) {
			$this->rules[] = $rule;
		}
	}

	/**
	 * Process ticket created event
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    array    $ticket_data   Ticket data
	 */
	public function process_ticket_created( $ticket_id, $ticket_data ) {
		$this->process_trigger( 'ticket_created', $ticket_id, array(
			'ticket_data' => $ticket_data
		) );
	}

	/**
	 * Process ticket updated event
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    array    $old_data      Old ticket data
	 * @param    array    $new_data      New ticket data
	 */
	public function process_ticket_updated( $ticket_id, $old_data, $new_data ) {
		$this->process_trigger( 'ticket_updated', $ticket_id, array(
			'old_data' => $old_data,
			'new_data' => $new_data
		) );
	}

	/**
	 * Process status changed event
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    string   $old_status    Old status
	 * @param    string   $new_status    New status
	 * @param    int      $user_id       User who changed status
	 */
	public function process_status_changed( $ticket_id, $old_status, $new_status, $user_id ) {
		$this->process_trigger( 'status_changed', $ticket_id, array(
			'old_status' => $old_status,
			'new_status' => $new_status,
			'user_id' => $user_id
		) );
	}

	/**
	 * Process ticket assigned event
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @param    int    $agent_id     Agent ID
	 */
	public function process_ticket_assigned( $ticket_id, $agent_id ) {
		$this->process_trigger( 'ticket_assigned', $ticket_id, array(
			'agent_id' => $agent_id
		) );
	}

	/**
	 * Process ticket replied event
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    array    $reply_data    Reply data
	 */
	public function process_ticket_replied( $ticket_id, $reply_data ) {
		$this->process_trigger( 'ticket_replied', $ticket_id, array(
			'reply_data' => $reply_data
		) );
	}

	/**
	 * Process automation trigger
	 *
	 * @since    1.0.0
	 * @param    string    $trigger      Trigger type
	 * @param    int       $ticket_id    Ticket ID
	 * @param    array     $context      Additional context
	 */
	private function process_trigger( $trigger, $ticket_id, $context = array() ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get( $ticket_id );

		if ( ! $ticket ) {
			return;
		}

		foreach ( $this->rules as $rule ) {
			if ( $rule['trigger'] !== $trigger || empty( $rule['active'] ) ) {
				continue;
			}

			if ( $this->evaluate_conditions( $rule['conditions'], $ticket, $context ) ) {
				$this->execute_actions( $rule['actions'], $ticket, $context );
				
				// Log automation execution
				$this->log_automation_execution( $rule['id'], $ticket_id, $trigger );
			}
		}
	}

	/**
	 * Evaluate rule conditions
	 *
	 * @since    1.0.0
	 * @param    array     $conditions    Rule conditions
	 * @param    object    $ticket        Ticket object
	 * @param    array     $context       Additional context
	 * @return   bool                     Whether conditions are met
	 */
	private function evaluate_conditions( $conditions, $ticket, $context = array() ) {
		foreach ( $conditions as $condition ) {
			if ( ! $this->evaluate_single_condition( $condition, $ticket, $context ) ) {
				return false; // All conditions must be true (AND logic)
			}
		}
		return true;
	}

	/**
	 * Evaluate single condition
	 *
	 * @since    1.0.0
	 * @param    array     $condition    Condition to evaluate
	 * @param    object    $ticket       Ticket object
	 * @param    array     $context      Additional context
	 * @return   bool                    Whether condition is met
	 */
	private function evaluate_single_condition( $condition, $ticket, $context = array() ) {
		$field = $condition['field'];
		$operator = $condition['operator'];
		$expected_value = $condition['value'];

		// Get actual value from ticket
		$actual_value = $this->get_field_value( $field, $ticket, $context );

		// Evaluate based on operator
		switch ( $operator ) {
			case 'equals':
				return $actual_value == $expected_value;

			case 'not_equals':
				return $actual_value != $expected_value;

			case 'contains':
				return stripos( $actual_value, $expected_value ) !== false;

			case 'not_contains':
				return stripos( $actual_value, $expected_value ) === false;

			case 'greater_than':
				return $actual_value > $expected_value;

			case 'less_than':
				return $actual_value < $expected_value;

			case 'is_null':
				return is_null( $actual_value ) || empty( $actual_value );

			case 'is_not_null':
				return ! is_null( $actual_value ) && ! empty( $actual_value );

			case 'older_than':
				if ( ! $actual_value ) return false;
				$timestamp = strtotime( $actual_value );
				$compare_timestamp = strtotime( '-' . $expected_value );
				return $timestamp < $compare_timestamp;

			case 'newer_than':
				if ( ! $actual_value ) return false;
				$timestamp = strtotime( $actual_value );
				$compare_timestamp = strtotime( '-' . $expected_value );
				return $timestamp > $compare_timestamp;

			case 'in_array':
				$expected_array = is_array( $expected_value ) ? $expected_value : explode( ',', $expected_value );
				return in_array( $actual_value, $expected_array );

			case 'not_in_array':
				$expected_array = is_array( $expected_value ) ? $expected_value : explode( ',', $expected_value );
				return ! in_array( $actual_value, $expected_array );

			default:
				return false;
		}
	}

	/**
	 * Get field value from ticket or context
	 *
	 * @since    1.0.0
	 * @param    string    $field      Field name
	 * @param    object    $ticket     Ticket object
	 * @param    array     $context    Additional context
	 * @return   mixed                 Field value
	 */
	private function get_field_value( $field, $ticket, $context = array() ) {
		// Direct ticket fields
		if ( property_exists( $ticket, $field ) ) {
			return $ticket->$field;
		}

		// Special field handlers
		switch ( $field ) {
			case 'customer_meta':
				if ( ! empty( $ticket->created_by ) ) {
					return get_user_meta( $ticket->created_by, 'mets_customer_type', true );
				}
				return '';

			case 'entity_name':
				if ( ! empty( $ticket->entity_id ) ) {
					require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
					$entity_model = new METS_Entity_Model();
					$entity = $entity_model->get( $ticket->entity_id );
					return $entity ? $entity->name : '';
				}
				return '';

			case 'reply_count':
				global $wpdb;
				return $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mets_ticket_replies WHERE ticket_id = %d",
					$ticket->id
				) );

			case 'last_reply_time':
				global $wpdb;
				return $wpdb->get_var( $wpdb->prepare(
					"SELECT created_at FROM {$wpdb->prefix}mets_ticket_replies 
					 WHERE ticket_id = %d ORDER BY created_at DESC LIMIT 1",
					$ticket->id
				) );

			case 'time_since_created':
				return time() - strtotime( $ticket->created_at );

			case 'time_since_updated':
				return time() - strtotime( $ticket->updated_at );

			case 'business_hours_elapsed':
				// Calculate business hours elapsed since creation
				return $this->calculate_business_hours_elapsed( $ticket->created_at );

			default:
				// Check context for additional values
				return $context[ $field ] ?? null;
		}
	}

	/**
	 * Execute automation actions
	 *
	 * @since    1.0.0
	 * @param    array     $actions    Actions to execute
	 * @param    object    $ticket     Ticket object
	 * @param    array     $context    Additional context
	 */
	private function execute_actions( $actions, $ticket, $context = array() ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		foreach ( $actions as $action ) {
			$this->execute_single_action( $action, $ticket, $context );
		}
	}

	/**
	 * Execute single automation action
	 *
	 * @since    1.0.0
	 * @param    array     $action     Action to execute
	 * @param    object    $ticket     Ticket object
	 * @param    array     $context    Additional context
	 */
	private function execute_single_action( $action, $ticket, $context = array() ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		switch ( $action['action'] ) {
			case 'update_status':
				$ticket_model->update( $ticket->id, array( 'status' => $action['value'] ) );
				do_action( 'mets_ticket_status_changed', $ticket->id, $ticket->status, $action['value'], 0 );
				break;

			case 'update_priority':
				$ticket_model->update( $ticket->id, array( 'priority' => $action['value'] ) );
				break;

			case 'assign_agent':
				$agent_id = $this->get_agent_for_assignment( $action['value'], $ticket );
				if ( $agent_id ) {
					$ticket_model->update( $ticket->id, array( 'assigned_to' => $agent_id ) );
					do_action( 'mets_ticket_assigned', $ticket->id, $agent_id );
				}
				break;

			case 'add_note':
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
				$reply_model = new METS_Ticket_Reply_Model();
				$reply_model->create( array(
					'ticket_id' => $ticket->id,
					'user_id' => 0, // System note
					'content' => $action['value'],
					'is_internal_note' => 1
				) );
				break;

			case 'add_tag':
				$this->add_ticket_tag( $ticket->id, $action['value'] );
				break;

			case 'remove_tag':
				$this->remove_ticket_tag( $ticket->id, $action['value'] );
				break;

			case 'send_email':
				$this->send_automation_email( $action['value'], $ticket, $context );
				break;

			case 'notify_agent':
				$this->notify_assigned_agent( $ticket, $action['value'] );
				break;

			case 'notify_manager':
				$this->notify_managers( $ticket, $action['value'] );
				break;

			case 'escalate_to_manager':
				$this->escalate_to_manager( $ticket );
				break;

			case 'create_task':
				$this->create_follow_up_task( $ticket, $action['value'] );
				break;

			case 'webhook':
				$this->trigger_webhook( $action['value'], $ticket, $context );
				break;

			case 'delay_action':
				$this->schedule_delayed_action( $action['value'], $ticket->id );
				break;

			default:
				do_action( 'mets_automation_custom_action', $action, $ticket, $context );
				break;
		}
	}

	/**
	 * Get agent ID for assignment
	 *
	 * @since    1.0.0
	 * @param    string    $assignment_method    Assignment method
	 * @param    object    $ticket               Ticket object
	 * @return   int|null                        Agent ID or null
	 */
	private function get_agent_for_assignment( $assignment_method, $ticket ) {
		global $wpdb;

		switch ( $assignment_method ) {
			case 'round_robin':
				// Get agents assigned to ticket's entity
				$agents = $wpdb->get_results( $wpdb->prepare(
					"SELECT u.ID, COUNT(t.id) as ticket_count
					 FROM {$wpdb->users} u
					 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
					 LEFT JOIN {$wpdb->prefix}mets_tickets t ON u.ID = t.assigned_to AND t.status NOT IN ('closed', 'resolved')
					 WHERE um.meta_key = 'wp_capabilities' 
					 AND (um.meta_value LIKE '%manage_tickets%' OR um.meta_value LIKE '%reply_to_tickets%')
					 GROUP BY u.ID
					 ORDER BY ticket_count ASC, RAND()
					 LIMIT 1",
				) );
				return ! empty( $agents ) ? $agents[0]->ID : null;

			case 'least_loaded':
				$agent = $wpdb->get_row(
					"SELECT u.ID
					 FROM {$wpdb->users} u
					 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
					 LEFT JOIN {$wpdb->prefix}mets_tickets t ON u.ID = t.assigned_to AND t.status NOT IN ('closed', 'resolved')
					 WHERE um.meta_key = 'wp_capabilities' 
					 AND (um.meta_value LIKE '%manage_tickets%' OR um.meta_value LIKE '%reply_to_tickets%')
					 GROUP BY u.ID
					 ORDER BY COUNT(t.id) ASC
					 LIMIT 1"
				);
				return $agent ? $agent->ID : null;

			case 'random':
				$agent = $wpdb->get_row(
					"SELECT u.ID
					 FROM {$wpdb->users} u
					 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
					 WHERE um.meta_key = 'wp_capabilities' 
					 AND (um.meta_value LIKE '%manage_tickets%' OR um.meta_value LIKE '%reply_to_tickets%')
					 ORDER BY RAND()
					 LIMIT 1"
				);
				return $agent ? $agent->ID : null;

			case 'entity_based':
				// Assign based on entity-specific agent assignments
				$entity_agents = get_option( 'mets_entity_agents_' . $ticket->entity_id, array() );
				if ( ! empty( $entity_agents ) ) {
					return $entity_agents[ array_rand( $entity_agents ) ];
				}
				return null;

			default:
				// Specific agent ID
				if ( is_numeric( $assignment_method ) ) {
					return intval( $assignment_method );
				}
				return null;
		}
	}

	/**
	 * Add tag to ticket
	 *
	 * @since    1.0.0
	 * @param    int       $ticket_id    Ticket ID
	 * @param    string    $tag          Tag to add
	 */
	private function add_ticket_tag( $ticket_id, $tag ) {
		$existing_tags = get_post_meta( $ticket_id, 'mets_ticket_tags', true );
		$existing_tags = $existing_tags ? explode( ',', $existing_tags ) : array();
		
		if ( ! in_array( $tag, $existing_tags ) ) {
			$existing_tags[] = $tag;
			update_post_meta( $ticket_id, 'mets_ticket_tags', implode( ',', $existing_tags ) );
		}
	}

	/**
	 * Remove tag from ticket
	 *
	 * @since    1.0.0
	 * @param    int       $ticket_id    Ticket ID
	 * @param    string    $tag          Tag to remove
	 */
	private function remove_ticket_tag( $ticket_id, $tag ) {
		$existing_tags = get_post_meta( $ticket_id, 'mets_ticket_tags', true );
		$existing_tags = $existing_tags ? explode( ',', $existing_tags ) : array();
		
		$existing_tags = array_filter( $existing_tags, function( $existing_tag ) use ( $tag ) {
			return $existing_tag !== $tag;
		} );
		
		update_post_meta( $ticket_id, 'mets_ticket_tags', implode( ',', $existing_tags ) );
	}

	/**
	 * Send automation email
	 *
	 * @since    1.0.0
	 * @param    string    $email_template    Email template
	 * @param    object    $ticket            Ticket object
	 * @param    array     $context           Additional context
	 */
	private function send_automation_email( $email_template, $ticket, $context = array() ) {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-notifications.php';
		$email_notifications = METS_Email_Notifications::get_instance();
		
		// Send based on template type
		switch ( $email_template ) {
			case 'escalation_notice':
				$email_notifications->send_escalation_email( $ticket->id );
				break;
			case 'sla_breach_warning':
				$email_notifications->send_sla_breach_warning( $ticket->id );
				break;
			case 'manager_notification':
				$email_notifications->send_manager_notification( $ticket->id );
				break;
			default:
				// Custom email template
				do_action( 'mets_send_custom_email', $email_template, $ticket, $context );
				break;
		}
	}

	/**
	 * Notify assigned agent
	 *
	 * @since    1.0.0
	 * @param    object    $ticket           Ticket object
	 * @param    string    $notification_type    Notification type
	 */
	private function notify_assigned_agent( $ticket, $notification_type ) {
		if ( empty( $ticket->assigned_to ) ) {
			return;
		}

		$agent = get_userdata( $ticket->assigned_to );
		if ( ! $agent ) {
			return;
		}

		$subject = '';
		$message = '';

		switch ( $notification_type ) {
			case 'first_response_reminder':
				$subject = sprintf( __( 'First Response Required: Ticket #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'Ticket #%d "%s" requires first response. Created %s ago.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject,
					human_time_diff( strtotime( $ticket->created_at ) )
				);
				break;

			case 'sla_warning':
				$subject = sprintf( __( 'SLA Warning: Ticket #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'Ticket #%d "%s" is approaching SLA deadline.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject
				);
				break;

			case 'overdue_ticket':
				$subject = sprintf( __( 'Overdue Ticket: #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'Ticket #%d "%s" is overdue and requires immediate attention.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject
				);
				break;
		}

		if ( ! empty( $subject ) && ! empty( $message ) ) {
			wp_mail( $agent->user_email, $subject, $message );
		}
	}

	/**
	 * Notify managers
	 *
	 * @since    1.0.0
	 * @param    object    $ticket           Ticket object
	 * @param    string    $notification_type    Notification type
	 */
	private function notify_managers( $ticket, $notification_type ) {
		// Get users with manager capabilities
		$managers = get_users( array(
			'capability' => 'manage_ticket_system',
			'fields' => array( 'user_email' )
		) );

		if ( empty( $managers ) ) {
			return;
		}

		$subject = '';
		$message = '';

		switch ( $notification_type ) {
			case 'sla_breach':
				$subject = sprintf( __( 'SLA Breach Alert: Ticket #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'Ticket #%d "%s" has breached its SLA and requires management attention.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject
				);
				break;

			case 'critical_priority':
				$subject = sprintf( __( 'Critical Priority Ticket: #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'A critical priority ticket #%d "%s" has been created and requires immediate attention.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject
				);
				break;

			case 'escalation':
				$subject = sprintf( __( 'Ticket Escalated: #%d', METS_TEXT_DOMAIN ), $ticket->id );
				$message = sprintf( 
					__( 'Ticket #%d "%s" has been escalated to management level.', METS_TEXT_DOMAIN ),
					$ticket->id,
					$ticket->subject
				);
				break;
		}

		if ( ! empty( $subject ) && ! empty( $message ) ) {
			$emails = array_column( $managers, 'user_email' );
			wp_mail( $emails, $subject, $message );
		}
	}

	/**
	 * Escalate ticket to manager
	 *
	 * @since    1.0.0
	 * @param    object    $ticket    Ticket object
	 */
	private function escalate_to_manager( $ticket ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		// Update priority and add escalation flag
		$ticket_model->update( $ticket->id, array(
			'priority' => 'critical',
			'escalated_at' => current_time( 'mysql' )
		) );

		// Add internal note
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();
		$reply_model->create( array(
			'ticket_id' => $ticket->id,
			'user_id' => 0,
			'content' => __( 'Ticket automatically escalated to management due to automation rule.', METS_TEXT_DOMAIN ),
			'is_internal_note' => 1
		) );

		// Notify managers
		$this->notify_managers( $ticket, 'escalation' );
	}

	/**
	 * Create follow-up task
	 *
	 * @since    1.0.0
	 * @param    object    $ticket      Ticket object
	 * @param    string    $task_data   Task data
	 */
	private function create_follow_up_task( $ticket, $task_data ) {
		// Parse task data (could be JSON or simple string)
		$task_info = json_decode( $task_data, true );
		if ( ! $task_info ) {
			$task_info = array( 'description' => $task_data );
		}

		// Add task as internal note with special formatting
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();
		
		$task_content = sprintf( 
			__( '📋 TASK: %s', METS_TEXT_DOMAIN ),
			$task_info['description']
		);

		if ( ! empty( $task_info['due_date'] ) ) {
			$task_content .= sprintf( __( ' (Due: %s)', METS_TEXT_DOMAIN ), $task_info['due_date'] );
		}

		$reply_model->create( array(
			'ticket_id' => $ticket->id,
			'user_id' => 0,
			'content' => $task_content,
			'is_internal_note' => 1
		) );
	}

	/**
	 * Trigger webhook
	 *
	 * @since    1.0.0
	 * @param    string    $webhook_url    Webhook URL
	 * @param    object    $ticket         Ticket object
	 * @param    array     $context        Additional context
	 */
	private function trigger_webhook( $webhook_url, $ticket, $context = array() ) {
		$payload = array(
			'ticket' => $ticket,
			'context' => $context,
			'timestamp' => current_time( 'timestamp' )
		);

		wp_remote_post( $webhook_url, array(
			'body' => wp_json_encode( $payload ),
			'headers' => array(
				'Content-Type' => 'application/json'
			),
			'timeout' => 30
		) );
	}

	/**
	 * Schedule delayed action
	 *
	 * @since    1.0.0
	 * @param    string    $delay_config    Delay configuration
	 * @param    int       $ticket_id       Ticket ID
	 */
	private function schedule_delayed_action( $delay_config, $ticket_id ) {
		$delay_data = json_decode( $delay_config, true );
		if ( ! $delay_data || empty( $delay_data['delay'] ) || empty( $delay_data['action'] ) ) {
			return;
		}

		$delay_seconds = $this->parse_delay_string( $delay_data['delay'] );
		if ( $delay_seconds > 0 ) {
			wp_schedule_single_event(
				time() + $delay_seconds,
				'mets_delayed_automation_action',
				array( $ticket_id, $delay_data['action'] )
			);
		}
	}

	/**
	 * Parse delay string to seconds
	 *
	 * @since    1.0.0
	 * @param    string    $delay_string    Delay string (e.g., "2 hours", "1 day")
	 * @return   int                        Delay in seconds
	 */
	private function parse_delay_string( $delay_string ) {
		$delay_string = strtolower( trim( $delay_string ) );
		
		if ( preg_match( '/(\d+)\s*(minute|hour|day|week)s?/', $delay_string, $matches ) ) {
			$number = intval( $matches[1] );
			$unit = $matches[2];
			
			switch ( $unit ) {
				case 'minute':
					return $number * 60;
				case 'hour':
					return $number * 3600;
				case 'day':
					return $number * 86400;
				case 'week':
					return $number * 604800;
			}
		}
		
		return 0;
	}

	/**
	 * Calculate business hours elapsed
	 *
	 * @since    1.0.0
	 * @param    string    $start_time    Start time
	 * @return   float                    Business hours elapsed
	 */
	private function calculate_business_hours_elapsed( $start_time ) {
		// Get business hours settings
		$business_hours = get_option( 'mets_business_hours', array() );
		
		if ( empty( $business_hours ) ) {
			// No business hours configured, return regular hours
			return ( time() - strtotime( $start_time ) ) / 3600;
		}

		// Complex calculation for business hours only
		// This is a simplified version - full implementation would consider
		// holidays, weekends, and specific entity business hours
		$start_timestamp = strtotime( $start_time );
		$current_timestamp = time();
		$business_hours_elapsed = 0;

		// Calculate day by day
		$current_day = $start_timestamp;
		while ( $current_day < $current_timestamp ) {
			$day_of_week = strtolower( date( 'l', $current_day ) );
			
			if ( isset( $business_hours[ $day_of_week ] ) && $business_hours[ $day_of_week ]['enabled'] ) {
				$day_start = strtotime( $business_hours[ $day_of_week ]['start'], $current_day );
				$day_end = strtotime( $business_hours[ $day_of_week ]['end'], $current_day );
				
				$work_start = max( $current_day, $day_start );
				$work_end = min( $current_timestamp, $day_end );
				
				if ( $work_end > $work_start ) {
					$business_hours_elapsed += ( $work_end - $work_start ) / 3600;
				}
			}
			
			$current_day = strtotime( '+1 day', $current_day );
			$current_day = strtotime( 'midnight', $current_day );
		}

		return $business_hours_elapsed;
	}

	/**
	 * Run scheduled automation
	 *
	 * @since    1.0.0
	 */
	public function run_scheduled_automation() {
		global $wpdb;

		// Get all active tickets for processing
		$tickets = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}mets_tickets 
			 WHERE status NOT IN ('closed', 'archived')
			 ORDER BY created_at ASC"
		);

		foreach ( $tickets as $ticket ) {
			$this->process_trigger( 'scheduled', $ticket->id );
		}
	}

	/**
	 * Check escalations
	 *
	 * @since    1.0.0
	 */
	public function check_escalations() {
		global $wpdb;

		// Check for tickets that need escalation
		$escalation_rules = get_option( 'mets_escalation_rules', array() );
		
		foreach ( $escalation_rules as $rule ) {
			if ( empty( $rule['active'] ) ) {
				continue;
			}

			$tickets = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mets_tickets 
				 WHERE status = %s 
				 AND priority = %s 
				 AND created_at <= %s
				 AND escalated_at IS NULL",
				$rule['status'],
				$rule['from_priority'],
				date( 'Y-m-d H:i:s', strtotime( '-' . $rule['after_hours'] . ' hours' ) )
			) );

			foreach ( $tickets as $ticket ) {
				$this->escalate_ticket( $ticket, $rule );
			}
		}
	}

	/**
	 * Escalate ticket based on rule
	 *
	 * @since    1.0.0
	 * @param    object    $ticket    Ticket object
	 * @param    array     $rule      Escalation rule
	 */
	private function escalate_ticket( $ticket, $rule ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		// Update ticket
		$ticket_model->update( $ticket->id, array(
			'priority' => $rule['to_priority'],
			'escalated_at' => current_time( 'mysql' )
		) );

		// Add escalation note
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();
		$reply_model->create( array(
			'ticket_id' => $ticket->id,
			'user_id' => 0,
			'content' => sprintf( 
				__( 'Ticket escalated from %s to %s priority after %d hours.', METS_TEXT_DOMAIN ),
				$rule['from_priority'],
				$rule['to_priority'],
				$rule['after_hours']
			),
			'is_internal_note' => 1
		) );

		// Trigger notifications
		do_action( 'mets_ticket_escalated', $ticket->id, $rule );
	}

	/**
	 * Schedule automation checks
	 *
	 * @since    1.0.0
	 */
	public function schedule_automation_checks() {
		if ( ! wp_next_scheduled( 'mets_automation_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'mets_automation_check' );
		}

		if ( ! wp_next_scheduled( 'mets_escalation_check' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'mets_escalation_check' );
		}
	}

	/**
	 * Log automation execution
	 *
	 * @since    1.0.0
	 * @param    string    $rule_id     Rule ID
	 * @param    int       $ticket_id   Ticket ID
	 * @param    string    $trigger     Trigger type
	 */
	private function log_automation_execution( $rule_id, $ticket_id, $trigger ) {
		global $wpdb;

		// Create automation log table if it doesn't exist
		$table_name = $wpdb->prefix . 'mets_automation_log';
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				rule_id varchar(255) NOT NULL,
				ticket_id bigint(20) NOT NULL,
				trigger_type varchar(50) NOT NULL,
				executed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY ticket_id (ticket_id),
				KEY rule_id (rule_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);

		// Log the execution
		$wpdb->insert(
			$table_name,
			array(
				'rule_id' => $rule_id,
				'ticket_id' => $ticket_id,
				'trigger_type' => $trigger,
				'executed_at' => current_time( 'mysql' )
			),
			array( '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * AJAX test automation rule
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_automation_rule() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$rule = json_decode( stripslashes( $_POST['rule'] ), true );
		$test_ticket_id = intval( $_POST['test_ticket_id'] ?? 0 );

		if ( ! $rule || ! $test_ticket_id ) {
			wp_send_json_error( __( 'Invalid rule or ticket ID.', METS_TEXT_DOMAIN ) );
		}

		// Get test ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get( $test_ticket_id );

		if ( ! $ticket ) {
			wp_send_json_error( __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
		}

		// Test conditions
		$conditions_met = $this->evaluate_conditions( $rule['conditions'], $ticket );
		
		$result = array(
			'conditions_met' => $conditions_met,
			'ticket_data' => $ticket,
			'rule_summary' => array(
				'trigger' => $rule['trigger'],
				'conditions_count' => count( $rule['conditions'] ),
				'actions_count' => count( $rule['actions'] )
			)
		);

		if ( $conditions_met ) {
			$result['message'] = __( 'Rule conditions are satisfied for this ticket.', METS_TEXT_DOMAIN );
		} else {
			$result['message'] = __( 'Rule conditions are not met for this ticket.', METS_TEXT_DOMAIN );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX save automation rule
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_automation_rule() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$rule = json_decode( stripslashes( $_POST['rule'] ), true );

		if ( ! $rule || empty( $rule['name'] ) || empty( $rule['trigger'] ) ) {
			wp_send_json_error( __( 'Invalid rule data.', METS_TEXT_DOMAIN ) );
		}

		// Get existing rules
		$existing_rules = get_option( 'mets_automation_rules', array() );
		
		// Add or update rule
		$rule['id'] = $rule['id'] ?? uniqid( 'rule_' );
		$rule['created_at'] = $rule['created_at'] ?? current_time( 'mysql' );
		$rule['updated_at'] = current_time( 'mysql' );

		$found = false;
		foreach ( $existing_rules as $key => $existing_rule ) {
			if ( $existing_rule['id'] === $rule['id'] ) {
				$existing_rules[ $key ] = $rule;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$existing_rules[] = $rule;
		}

		// Save rules
		$saved = update_option( 'mets_automation_rules', $existing_rules );

		if ( $saved ) {
			wp_send_json_success( array(
				'message' => __( 'Automation rule saved successfully.', METS_TEXT_DOMAIN ),
				'rule_id' => $rule['id']
			) );
		} else {
			wp_send_json_error( __( 'Failed to save automation rule.', METS_TEXT_DOMAIN ) );
		}
	}
}