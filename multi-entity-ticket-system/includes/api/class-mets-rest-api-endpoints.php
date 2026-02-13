<?php
/**
 * REST API Endpoint Implementations
 *
 * Implements all REST API endpoint callbacks
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/api
 * @since      1.0.0
 */

/**
 * The REST API Endpoints implementation class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/api
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_REST_API_Endpoints extends METS_REST_API {

	// Entity Endpoints

	/**
	 * Get entities
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_entities( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		$args = array(
			'status' => $request->get_param( 'status' ),
			'parent_id' => $request->get_param( 'parent_id' ),
			'per_page' => $request->get_param( 'per_page' ),
			'page' => $request->get_param( 'page' ),
		);

		$entities = $entity_model->get_all( $args );
		$total = $entity_model->get_total( $args );

		$data = array();
		foreach ( $entities as $entity ) {
			$data[] = $this->prepare_entity_for_response( $entity );
		}

		return $this->prepare_paginated_response( $data, $total, $args['per_page'], $args['page'] );
	}

	/**
	 * Get single entity
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_entity( $request ) {
		$entity_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get( $entity_id );

		if ( ! $entity ) {
			return $this->prepare_error_response( 'entity_not_found', __( 'Entity not found.', METS_TEXT_DOMAIN ), 404 );
		}

		return rest_ensure_response( $this->prepare_entity_for_response( $entity ) );
	}

	/**
	 * Create entity
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function create_entity( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		$data = array(
			'name' => $request->get_param( 'name' ),
			'type' => $request->get_param( 'type' ),
			'contact_email' => $request->get_param( 'contact_email' ),
			'description' => $request->get_param( 'description' ),
			'parent_id' => $request->get_param( 'parent_id' ),
			'metadata' => $request->get_param( 'metadata' ),
			'status' => 'active',
		);

		$entity_id = $entity_model->create( $data );

		if ( ! $entity_id ) {
			return $this->prepare_error_response( 'entity_creation_failed', __( 'Failed to create entity.', METS_TEXT_DOMAIN ), 500 );
		}

		$entity = $entity_model->get( $entity_id );
		$response = rest_ensure_response( $this->prepare_entity_for_response( $entity ) );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( $this->namespace . '/entities/' . $entity_id ) );

		return $response;
	}

	/**
	 * Update entity
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function update_entity( $request ) {
		$entity_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		
		$entity = $entity_model->get( $entity_id );
		if ( ! $entity ) {
			return $this->prepare_error_response( 'entity_not_found', __( 'Entity not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$data = array();
		$fields = array( 'name', 'type', 'contact_email', 'description', 'parent_id', 'metadata' );
		
		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		$result = $entity_model->update( $entity_id, $data );

		if ( ! $result ) {
			return $this->prepare_error_response( 'entity_update_failed', __( 'Failed to update entity.', METS_TEXT_DOMAIN ), 500 );
		}

		$entity = $entity_model->get( $entity_id );
		return rest_ensure_response( $this->prepare_entity_for_response( $entity ) );
	}

	/**
	 * Delete entity
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function delete_entity( $request ) {
		$entity_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		
		$entity = $entity_model->get( $entity_id );
		if ( ! $entity ) {
			return $this->prepare_error_response( 'entity_not_found', __( 'Entity not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$result = $entity_model->delete( $entity_id );

		if ( ! $result ) {
			return $this->prepare_error_response( 'entity_deletion_failed', __( 'Failed to delete entity.', METS_TEXT_DOMAIN ), 500 );
		}

		return rest_ensure_response( array(
			'deleted' => true,
			'id' => $entity_id,
		) );
	}

	// Ticket Endpoints

	/**
	 * Get tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_tickets( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$args = array(
			'status' => $request->get_param( 'status' ),
			'priority' => $request->get_param( 'priority' ),
			'entity_id' => $request->get_param( 'entity_id' ),
			'customer_id' => $request->get_param( 'customer_id' ),
			'assigned_to' => $request->get_param( 'assigned_to' ),
			'search' => $request->get_param( 'search' ),
			'orderby' => $request->get_param( 'orderby' ),
			'order' => $request->get_param( 'order' ),
			'per_page' => $request->get_param( 'per_page' ),
			'page' => $request->get_param( 'page' ),
		);

		// If not admin, limit to user's own tickets
		if ( ! current_user_can( 'manage_tickets' ) && is_user_logged_in() ) {
			$args['customer_id'] = get_current_user_id();
		}

		$tickets = $ticket_model->get_all( $args );
		$total = $ticket_model->get_total( $args );

		$data = array();
		foreach ( $tickets as $ticket ) {
			$data[] = $this->prepare_ticket_for_response( $ticket );
		}

		return $this->prepare_paginated_response( $data, $total, $args['per_page'], $args['page'] );
	}

	/**
	 * Get single ticket
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_ticket( $request ) {
		$ticket_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get( $ticket_id );

		if ( ! $ticket ) {
			return $this->prepare_error_response( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ), 404 );
		}

		return rest_ensure_response( $this->prepare_ticket_for_response( $ticket, true ) );
	}

	/**
	 * Create ticket
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function create_ticket( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		// Validate entity exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get( $request->get_param( 'entity_id' ) );
		
		if ( ! $entity ) {
			return $this->prepare_error_response( 'invalid_entity', __( 'Invalid entity ID.', METS_TEXT_DOMAIN ), 400 );
		}

		// Handle customer data
		$customer_id = null;
		$customer_name = $request->get_param( 'customer_name' );
		$customer_email = $request->get_param( 'customer_email' );

		if ( is_user_logged_in() ) {
			$customer_id = get_current_user_id();
			$user = wp_get_current_user();
			if ( ! $customer_name ) {
				$customer_name = $user->display_name;
			}
			if ( ! $customer_email ) {
				$customer_email = $user->user_email;
			}
		} elseif ( ! $customer_email ) {
			return $this->prepare_error_response( 'customer_email_required', __( 'Customer email is required for guest tickets.', METS_TEXT_DOMAIN ), 400 );
		}

		$data = array(
			'subject' => $request->get_param( 'subject' ),
			'description' => $request->get_param( 'description' ),
			'entity_id' => $request->get_param( 'entity_id' ),
			'priority' => $request->get_param( 'priority' ),
			'customer_id' => $customer_id,
			'customer_name' => $customer_name,
			'customer_email' => $customer_email,
			'status' => 'open',
			'source' => 'api',
		);

		$ticket_id = $ticket_model->create( $data );

		if ( ! $ticket_id ) {
			return $this->prepare_error_response( 'ticket_creation_failed', __( 'Failed to create ticket.', METS_TEXT_DOMAIN ), 500 );
		}

		// Handle attachments
		$attachments = $request->get_param( 'attachments' );
		if ( ! empty( $attachments ) && is_array( $attachments ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
			$attachment_model = new METS_Attachment_Model();
			
			foreach ( $attachments as $attachment_id ) {
				$attachment_model->link_to_ticket( $attachment_id, $ticket_id );
			}
		}

		// Send notification
		do_action( 'mets_ticket_created', $ticket_id );

		$ticket = $ticket_model->get( $ticket_id );
		$response = rest_ensure_response( $this->prepare_ticket_for_response( $ticket, true ) );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( $this->namespace . '/tickets/' . $ticket_id ) );

		return $response;
	}

	/**
	 * Update ticket
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function update_ticket( $request ) {
		$ticket_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$ticket = $ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return $this->prepare_error_response( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$data = array();
		$fields = array( 'subject', 'status', 'priority', 'assigned_to' );
		
		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		$result = $ticket_model->update( $ticket_id, $data );

		if ( ! $result ) {
			return $this->prepare_error_response( 'ticket_update_failed', __( 'Failed to update ticket.', METS_TEXT_DOMAIN ), 500 );
		}

		// Send notifications for status changes
		if ( isset( $data['status'] ) && $data['status'] !== $ticket->status ) {
			do_action( 'mets_ticket_status_changed', $ticket_id, $ticket->status, $data['status'] );
		}

		$ticket = $ticket_model->get( $ticket_id );
		return rest_ensure_response( $this->prepare_ticket_for_response( $ticket, true ) );
	}

	/**
	 * Add ticket reply
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function add_ticket_reply( $request ) {
		$ticket_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$ticket = $ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return $this->prepare_error_response( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ), 404 );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();

		$is_customer = ! current_user_can( 'manage_tickets' );
		$author_id = is_user_logged_in() ? get_current_user_id() : null;

		$data = array(
			'ticket_id' => $ticket_id,
			'content' => $request->get_param( 'content' ),
			'author_id' => $author_id,
			'author_name' => $is_customer ? $ticket->customer_name : wp_get_current_user()->display_name,
			'author_email' => $is_customer ? $ticket->customer_email : wp_get_current_user()->user_email,
			'is_customer_reply' => $is_customer,
		);

		$reply_id = $reply_model->create( $data );

		if ( ! $reply_id ) {
			return $this->prepare_error_response( 'reply_creation_failed', __( 'Failed to add reply.', METS_TEXT_DOMAIN ), 500 );
		}

		// Handle attachments
		$attachments = $request->get_param( 'attachments' );
		if ( ! empty( $attachments ) && is_array( $attachments ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
			$attachment_model = new METS_Attachment_Model();
			
			foreach ( $attachments as $attachment_id ) {
				$attachment_model->link_to_reply( $attachment_id, $reply_id );
			}
		}

		// Update ticket last activity
		$ticket_model->update( $ticket_id, array( 'updated_at' => current_time( 'mysql' ) ) );

		// Send notification
		do_action( 'mets_ticket_reply_added', $reply_id, $ticket_id );

		$reply = $reply_model->get( $reply_id );
		$response = rest_ensure_response( $this->prepare_reply_for_response( $reply ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Get ticket replies
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_ticket_replies( $request ) {
		$ticket_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();

		$replies = $reply_model->get_by_ticket( $ticket_id );

		$data = array();
		foreach ( $replies as $reply ) {
			$data[] = $this->prepare_reply_for_response( $reply );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Assign ticket
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function assign_ticket( $request ) {
		$ticket_id = $request->get_param( 'id' );
		$agent_id = $request->get_param( 'agent_id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$ticket = $ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return $this->prepare_error_response( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ), 404 );
		}

		// Validate agent
		$agent = get_user_by( 'id', $agent_id );
		if ( ! $agent || ! user_can( $agent, 'manage_tickets' ) ) {
			return $this->prepare_error_response( 'invalid_agent', __( 'Invalid agent ID.', METS_TEXT_DOMAIN ), 400 );
		}

		$result = $ticket_model->update( $ticket_id, array( 
			'assigned_to' => $agent_id,
			'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
		) );

		if ( ! $result ) {
			return $this->prepare_error_response( 'assignment_failed', __( 'Failed to assign ticket.', METS_TEXT_DOMAIN ), 500 );
		}

		// Send notification
		do_action( 'mets_ticket_assigned', $ticket_id, $agent_id );

		return rest_ensure_response( array(
			'success' => true,
			'ticket_id' => $ticket_id,
			'assigned_to' => $agent_id,
		) );
	}

	/**
	 * Update ticket status
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function update_ticket_status( $request ) {
		$ticket_id = $request->get_param( 'id' );
		$status = $request->get_param( 'status' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$ticket = $ticket_model->get( $ticket_id );
		if ( ! $ticket ) {
			return $this->prepare_error_response( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$update_data = array( 'status' => $status );
		
		// Set resolved_at timestamp when resolving
		if ( $status === 'resolved' && $ticket->status !== 'resolved' ) {
			$update_data['resolved_at'] = current_time( 'mysql' );
		}

		$result = $ticket_model->update( $ticket_id, $update_data );

		if ( ! $result ) {
			return $this->prepare_error_response( 'status_update_failed', __( 'Failed to update ticket status.', METS_TEXT_DOMAIN ), 500 );
		}

		// Send notification
		do_action( 'mets_ticket_status_changed', $ticket_id, $ticket->status, $status );

		return rest_ensure_response( array(
			'success' => true,
			'ticket_id' => $ticket_id,
			'status' => $status,
			'previous_status' => $ticket->status,
		) );
	}

	// Customer Endpoints

	/**
	 * Get customers
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_customers( $request ) {
		$args = array(
			'role__in' => array( 'subscriber', 'customer' ),
			'search' => $request->get_param( 'search' ),
			'number' => $request->get_param( 'per_page' ),
			'paged' => $request->get_param( 'page' ),
		);

		if ( $request->get_param( 'search' ) ) {
			$args['search'] = '*' . $request->get_param( 'search' ) . '*';
		}

		$user_query = new WP_User_Query( $args );
		$customers = $user_query->get_results();
		$total = $user_query->get_total();

		$data = array();
		foreach ( $customers as $customer ) {
			$data[] = $this->prepare_customer_for_response( $customer );
		}

		return $this->prepare_paginated_response( $data, $total, $args['number'], $args['paged'] );
	}

	/**
	 * Get customer details
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_customer( $request ) {
		$customer_id = $request->get_param( 'id' );
		$customer = get_user_by( 'id', $customer_id );

		if ( ! $customer ) {
			return $this->prepare_error_response( 'customer_not_found', __( 'Customer not found.', METS_TEXT_DOMAIN ), 404 );
		}

		return rest_ensure_response( $this->prepare_customer_for_response( $customer, true ) );
	}

	/**
	 * Get customer tickets
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_customer_tickets( $request ) {
		$customer_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$tickets = $ticket_model->get_all( array(
			'customer_id' => $customer_id,
			'per_page' => 50,
		) );

		$data = array();
		foreach ( $tickets as $ticket ) {
			$data[] = $this->prepare_ticket_for_response( $ticket );
		}

		return rest_ensure_response( $data );
	}

	// Agent Endpoints

	/**
	 * Get agents
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_agents( $request ) {
		$args = array(
			'role__in' => array( 'mets_agent', 'mets_manager', 'administrator' ),
		);

		$agents = get_users( $args );
		$data = array();

		foreach ( $agents as $agent ) {
			$agent_data = $this->prepare_agent_for_response( $agent );
			
			// Add availability status if requested
			if ( $request->get_param( 'available' ) !== null ) {
				$agent_data['available'] = $this->is_agent_available( $agent->ID );
			}
			
			$data[] = $agent_data;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get agent statistics
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_agent_stats( $request ) {
		$agent_id = $request->get_param( 'id' );
		$period = $request->get_param( 'period' );

		$agent = get_user_by( 'id', $agent_id );
		if ( ! $agent || ! user_can( $agent, 'manage_tickets' ) ) {
			return $this->prepare_error_response( 'agent_not_found', __( 'Agent not found.', METS_TEXT_DOMAIN ), 404 );
		}

		global $wpdb;
		$where_date = $this->get_date_where_clause( $period );

		$stats_sql = "SELECT 
			COUNT(*) as total_tickets,
			COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
			COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
			AVG(CASE WHEN resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_time,
			COUNT(CASE WHEN sla_status = 'met' THEN 1 END) as sla_met,
			COUNT(CASE WHEN sla_status = 'breached' THEN 1 END) as sla_breached
		FROM {$wpdb->prefix}mets_tickets
		WHERE assigned_to = %d {$where_date}";

		$stats = $wpdb->get_row( $wpdb->prepare( $stats_sql, $agent_id ) );

		$response_data = array(
			'agent_id' => $agent_id,
			'period' => $period,
			'stats' => array(
				'total_tickets' => intval( $stats->total_tickets ),
				'resolved_tickets' => intval( $stats->resolved_tickets ),
				'closed_tickets' => intval( $stats->closed_tickets ),
				'avg_resolution_time' => round( floatval( $stats->avg_resolution_time ), 1 ),
				'resolution_rate' => $stats->total_tickets > 0 ? 
					round( ( ( $stats->resolved_tickets + $stats->closed_tickets ) / $stats->total_tickets ) * 100, 1 ) : 0,
				'sla_compliance' => $stats->total_tickets > 0 ?
					round( ( $stats->sla_met / $stats->total_tickets ) * 100, 1 ) : 0,
			),
		);

		return rest_ensure_response( $response_data );
	}

	// SLA Endpoints

	/**
	 * Get SLA rules
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_sla_rules( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		$sla_model = new METS_SLA_Rule_Model();

		$rules = $sla_model->get_all( array( 'status' => 'active' ) );

		$data = array();
		foreach ( $rules as $rule ) {
			$data[] = $this->prepare_sla_rule_for_response( $rule );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get SLA performance metrics
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_sla_performance( $request ) {
		$period = $request->get_param( 'period' );
		$entity_id = $request->get_param( 'entity_id' );

		global $wpdb;
		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
			COUNT(*) as total_tickets,
			COUNT(CASE WHEN sla_due_date IS NOT NULL THEN 1 END) as tickets_with_sla,
			COUNT(CASE WHEN sla_status = 'met' THEN 1 END) as sla_met,
			COUNT(CASE WHEN sla_status = 'warning' THEN 1 END) as sla_warning,
			COUNT(CASE WHEN sla_status = 'breached' THEN 1 END) as sla_breached,
			AVG(CASE WHEN first_response_at IS NOT NULL 
				THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_first_response,
			AVG(CASE WHEN resolved_at IS NOT NULL 
				THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END) as avg_resolution_time
		FROM {$wpdb->prefix}mets_tickets
		WHERE 1=1 {$where_date} {$entity_where}";

		$stats = $wpdb->get_row( $sql );

		$response_data = array(
			'period' => $period,
			'entity_id' => $entity_id,
			'metrics' => array(
				'total_tickets' => intval( $stats->total_tickets ),
				'tickets_with_sla' => intval( $stats->tickets_with_sla ),
				'sla_compliance' => $stats->tickets_with_sla > 0 ?
					round( ( $stats->sla_met / $stats->tickets_with_sla ) * 100, 1 ) : 0,
				'breakdown' => array(
					'met' => intval( $stats->sla_met ),
					'warning' => intval( $stats->sla_warning ),
					'breached' => intval( $stats->sla_breached ),
				),
				'response_times' => array(
					'avg_first_response' => round( floatval( $stats->avg_first_response ), 1 ),
					'avg_resolution' => round( floatval( $stats->avg_resolution_time ), 1 ),
				),
			),
		);

		return rest_ensure_response( $response_data );
	}

	// Knowledge Base Endpoints

	/**
	 * Get KB articles
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_kb_articles( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$args = array(
			'search' => $request->get_param( 'search' ),
			'category_id' => $request->get_param( 'category_id' ),
			'entity_id' => $request->get_param( 'entity_id' ),
			'featured' => $request->get_param( 'featured' ),
			'status' => array( 'published' ),
			'visibility' => $this->get_user_kb_visibility_levels(),
			'per_page' => $request->get_param( 'per_page' ),
			'page' => $request->get_param( 'page' ),
		);

		$result = $article_model->get_articles_with_inheritance( $args );

		$data = array();
		foreach ( $result['articles'] as $article ) {
			$data[] = $this->prepare_kb_article_for_response( $article );
		}

		return $this->prepare_paginated_response( 
			$data, 
			$result['total'], 
			$args['per_page'], 
			$args['page'] 
		);
	}

	/**
	 * Get single KB article
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_kb_article( $request ) {
		$article_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get( $article_id );

		if ( ! $article || $article->status !== 'published' ) {
			return $this->prepare_error_response( 'article_not_found', __( 'Article not found.', METS_TEXT_DOMAIN ), 404 );
		}

		// Check visibility
		$user_levels = $this->get_user_kb_visibility_levels();
		if ( ! in_array( $article->visibility, $user_levels ) ) {
			return $this->prepare_error_response( 'access_denied', __( 'You do not have permission to view this article.', METS_TEXT_DOMAIN ), 403 );
		}

		// Record view
		$article_model->increment_view_count( $article_id );
		
		// Record analytics
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();
		$analytics_model->record_event( array(
			'article_id' => $article_id,
			'action' => 'view',
		) );

		return rest_ensure_response( $this->prepare_kb_article_for_response( $article, true ) );
	}

	/**
	 * Create KB article
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function create_kb_article( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$data = array(
			'title' => $request->get_param( 'title' ),
			'content' => $request->get_param( 'content' ),
			'entity_id' => $request->get_param( 'entity_id' ),
			'visibility' => $request->get_param( 'visibility' ),
			'featured' => $request->get_param( 'featured' ),
			'status' => $request->get_param( 'status' ),
			'author_id' => get_current_user_id(),
		);

		// Generate slug
		$data['slug'] = sanitize_title( $data['title'] );

		$article_id = $article_model->create( $data );

		if ( ! $article_id ) {
			return $this->prepare_error_response( 'article_creation_failed', __( 'Failed to create article.', METS_TEXT_DOMAIN ), 500 );
		}

		// Handle category
		if ( $request->has_param( 'category_id' ) ) {
			$article_model->set_categories( $article_id, array( $request->get_param( 'category_id' ) ) );
		}

		$article = $article_model->get( $article_id );
		$response = rest_ensure_response( $this->prepare_kb_article_for_response( $article, true ) );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( $this->namespace . '/kb/articles/' . $article_id ) );

		return $response;
	}

	/**
	 * Update KB article
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function update_kb_article( $request ) {
		$article_id = $request->get_param( 'id' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		$article = $article_model->get( $article_id );
		if ( ! $article ) {
			return $this->prepare_error_response( 'article_not_found', __( 'Article not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$data = array();
		$fields = array( 'title', 'content', 'entity_id', 'visibility', 'featured', 'status' );
		
		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$data[ $field ] = $request->get_param( $field );
			}
		}

		// Update slug if title changed
		if ( isset( $data['title'] ) && $data['title'] !== $article->title ) {
			$data['slug'] = sanitize_title( $data['title'] );
		}

		$result = $article_model->update( $article_id, $data );

		if ( ! $result ) {
			return $this->prepare_error_response( 'article_update_failed', __( 'Failed to update article.', METS_TEXT_DOMAIN ), 500 );
		}

		// Handle category update
		if ( $request->has_param( 'category_id' ) ) {
			$article_model->set_categories( $article_id, array( $request->get_param( 'category_id' ) ) );
		}

		$article = $article_model->get( $article_id );
		return rest_ensure_response( $this->prepare_kb_article_for_response( $article, true ) );
	}

	/**
	 * Submit article feedback
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function submit_article_feedback( $request ) {
		$article_id = $request->get_param( 'id' );
		$helpful = $request->get_param( 'helpful' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		$article = $article_model->get( $article_id );
		if ( ! $article || $article->status !== 'published' ) {
			return $this->prepare_error_response( 'article_not_found', __( 'Article not found.', METS_TEXT_DOMAIN ), 404 );
		}

		$vote = $helpful ? 'yes' : 'no';
		$result = $article_model->add_helpfulness_vote( $article_id, $vote );

		if ( ! $result ) {
			return $this->prepare_error_response( 'feedback_failed', __( 'Failed to submit feedback.', METS_TEXT_DOMAIN ), 500 );
		}

		// Record analytics
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();
		$analytics_model->record_event( array(
			'article_id' => $article_id,
			'action' => $helpful ? 'helpful' : 'not_helpful',
		) );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Thank you for your feedback!', METS_TEXT_DOMAIN ),
		) );
	}

	/**
	 * Get KB categories
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_kb_categories( $request ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		$category_model = new METS_KB_Category_Model();

		$entity_id = $request->get_param( 'entity_id' );
		$categories = $category_model->get_categories_for_entity( $entity_id );

		$data = array();
		foreach ( $categories as $category ) {
			$data[] = $this->prepare_kb_category_for_response( $category );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Search KB articles
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function search_kb_articles( $request ) {
		$query = $request->get_param( 'query' );
		$entity_id = $request->get_param( 'entity_id' );
		$limit = $request->get_param( 'limit' );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$results = $article_model->search( $query, array(
			'entity_id' => $entity_id,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_kb_visibility_levels(),
			'limit' => $limit,
		) );

		// Record search analytics
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();
		$analytics_model->log_search( array(
			'query' => $query,
			'results_count' => count( $results ),
			'entity_id' => $entity_id,
		) );

		$data = array();
		foreach ( $results as $article ) {
			$data[] = $this->prepare_kb_article_for_response( $article );
		}

		return rest_ensure_response( array(
			'query' => $query,
			'results' => $data,
			'total' => count( $data ),
		) );
	}

	// Reporting Endpoints

	/**
	 * Get dashboard metrics
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_dashboard_metrics( $request ) {
		$period = $request->get_param( 'period' );
		$entity_id = $request->get_param( 'entity_id' );

		global $wpdb;
		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND entity_id = %d", $entity_id ) : "";

		// Ticket metrics
		$ticket_sql = "SELECT 
			COUNT(*) as total_tickets,
			COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
			COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
			COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
			COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
			COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_tickets,
			COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_tickets
		FROM {$wpdb->prefix}mets_tickets
		WHERE 1=1 {$where_date} {$entity_where}";

		$ticket_stats = $wpdb->get_row( $ticket_sql );

		// SLA metrics
		$sla_sql = "SELECT 
			COUNT(CASE WHEN sla_status = 'met' THEN 1 END) as sla_met,
			COUNT(CASE WHEN sla_status = 'breached' THEN 1 END) as sla_breached,
			COUNT(CASE WHEN sla_due_date IS NOT NULL THEN 1 END) as total_with_sla
		FROM {$wpdb->prefix}mets_tickets
		WHERE 1=1 {$where_date} {$entity_where}";

		$sla_stats = $wpdb->get_row( $sla_sql );

		// KB metrics (if user has access)
		$kb_stats = null;
		if ( current_user_can( 'read_kb_articles' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
			$analytics_model = new METS_KB_Analytics_Model();
			$search_data = $analytics_model->get_search_analytics( $period, $entity_id );
			
			$kb_stats = array(
				'total_searches' => $search_data['total_searches'],
				'search_ctr' => $search_data['overall_ctr'],
				'top_articles' => count( $analytics_model->get_top_articles( 5, $period, $entity_id ) ),
			);
		}

		$response_data = array(
			'period' => $period,
			'entity_id' => $entity_id,
			'metrics' => array(
				'tickets' => array(
					'total' => intval( $ticket_stats->total_tickets ),
					'open' => intval( $ticket_stats->open_tickets ),
					'in_progress' => intval( $ticket_stats->in_progress_tickets ),
					'resolved' => intval( $ticket_stats->resolved_tickets ),
					'closed' => intval( $ticket_stats->closed_tickets ),
					'critical' => intval( $ticket_stats->critical_tickets ),
					'high' => intval( $ticket_stats->high_tickets ),
				),
				'sla' => array(
					'compliance' => $sla_stats->total_with_sla > 0 ?
						round( ( $sla_stats->sla_met / $sla_stats->total_with_sla ) * 100, 1 ) : 0,
					'met' => intval( $sla_stats->sla_met ),
					'breached' => intval( $sla_stats->sla_breached ),
				),
			),
		);

		if ( $kb_stats ) {
			$response_data['metrics']['knowledge_base'] = $kb_stats;
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * Generate custom report
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function generate_custom_report( $request ) {
		$report_type = $request->get_param( 'report_type' );
		$filters = $request->get_param( 'filters' ) ?: array();
		$date_range = $request->get_param( 'date_range' ) ?: array();
		$format = $request->get_param( 'format' );

		// Merge filters and date range into config
		$config = array_merge( $filters, $date_range, array(
			'report_type' => $report_type,
			'limit' => 'all',
		) );

		require_once METS_PLUGIN_PATH . 'admin/class-mets-custom-report-builder.php';
		$report_builder = new METS_Custom_Report_Builder();
		
		// Use reflection to access private method
		$reflection = new ReflectionMethod( $report_builder, 'generate_custom_report' );
		$reflection->setAccessible( true );
		$report_data = $reflection->invoke( $report_builder, $config );

		if ( $format === 'csv' ) {
			// Generate CSV and return download URL
			$csv_data = $this->generate_csv_from_report( $report_data, $config );
			$upload_dir = wp_upload_dir();
			$filename = 'report_' . uniqid() . '.csv';
			$filepath = $upload_dir['path'] . '/' . $filename;
			
			file_put_contents( $filepath, $csv_data );
			
			return rest_ensure_response( array(
				'format' => 'csv',
				'download_url' => $upload_dir['url'] . '/' . $filename,
				'filename' => $filename,
			) );
		}

		// Return JSON format
		return rest_ensure_response( $report_data );
	}

	/**
	 * Export report
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function export_report( $request ) {
		// This would typically handle stored reports
		// For now, return error
		return $this->prepare_error_response( 'not_implemented', __( 'Report export not implemented yet.', METS_TEXT_DOMAIN ), 501 );
	}

	// System Endpoints

	/**
	 * Get system information
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_system_info( $request ) {
		global $wpdb;

		// Get ticket counts
		$ticket_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets" );
		$entity_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_entities" );
		$kb_article_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_articles WHERE status = 'published'" );

		$system_info = array(
			'version' => METS_VERSION,
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version' => phpversion(),
			'mysql_version' => $wpdb->db_version(),
			'statistics' => array(
				'total_tickets' => intval( $ticket_count ),
				'total_entities' => intval( $entity_count ),
				'total_kb_articles' => intval( $kb_article_count ),
				'total_agents' => count( get_users( array( 'role__in' => array( 'mets_agent', 'mets_manager' ) ) ) ),
			),
			'settings' => array(
				'smtp_configured' => get_option( 'mets_smtp_settings' ) ? true : false,
				'sla_enabled' => get_option( 'mets_sla_rules' ) ? true : false,
				'kb_enabled' => true,
			),
		);

		return rest_ensure_response( $system_info );
	}

	/**
	 * Get system status
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function get_system_status( $request ) {
		global $wpdb;

		// Simple health check
		$status = array(
			'status' => 'ok',
			'timestamp' => current_time( 'mysql' ),
			'checks' => array(
				'database' => true,
				'filesystem' => is_writable( wp_upload_dir()['basedir'] ),
				'api' => true,
			),
		);

		// Check database connectivity
		try {
			$wpdb->query( "SELECT 1" );
		} catch ( Exception $e ) {
			$status['status'] = 'error';
			$status['checks']['database'] = false;
		}

		return rest_ensure_response( $status );
	}

	/**
	 * Test email configuration
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request   $request   The request object
	 * @return   WP_REST_Response             The response
	 */
	public function test_email( $request ) {
		$to = $request->get_param( 'to' );

		$subject = __( 'Test Email from Multi-Entity Ticket System', METS_TEXT_DOMAIN );
		$message = sprintf(
			__( 'This is a test email from your Multi-Entity Ticket System installation on %s.', METS_TEXT_DOMAIN ),
			get_bloginfo( 'name' )
		);

		$result = wp_mail( $to, $subject, $message );

		if ( $result ) {
			return rest_ensure_response( array(
				'success' => true,
				'message' => sprintf( __( 'Test email sent successfully to %s.', METS_TEXT_DOMAIN ), $to ),
			) );
		} else {
			return $this->prepare_error_response( 
				'email_failed', 
				__( 'Failed to send test email. Please check your email configuration.', METS_TEXT_DOMAIN ),
				500
			);
		}
	}

	// Helper Methods

	/**
	 * Prepare entity for API response
	 *
	 * @since    1.0.0
	 * @param    object   $entity    Entity object
	 * @return   array               Prepared entity data
	 */
	private function prepare_entity_for_response( $entity ) {
		return array(
			'id' => intval( $entity->id ),
			'name' => $entity->name,
			'type' => $entity->type,
			'contact_email' => $entity->contact_email,
			'description' => $entity->description,
			'parent_id' => $entity->parent_id ? intval( $entity->parent_id ) : null,
			'metadata' => $entity->metadata ? json_decode( $entity->metadata, true ) : null,
			'status' => $entity->status,
			'created_at' => mysql_to_rfc3339( $entity->created_at ),
			'updated_at' => mysql_to_rfc3339( $entity->updated_at ),
		);
	}

	/**
	 * Prepare ticket for API response
	 *
	 * @since    1.0.0
	 * @param    object   $ticket      Ticket object
	 * @param    bool     $detailed    Include detailed information
	 * @return   array                 Prepared ticket data
	 */
	private function prepare_ticket_for_response( $ticket, $detailed = false ) {
		$data = array(
			'id' => intval( $ticket->id ),
			'subject' => $ticket->subject,
			'status' => $ticket->status,
			'priority' => $ticket->priority,
			'entity_id' => intval( $ticket->entity_id ),
			'entity_name' => $ticket->entity_name,
			'customer_id' => $ticket->customer_id ? intval( $ticket->customer_id ) : null,
			'customer_name' => $ticket->customer_name,
			'customer_email' => $ticket->customer_email,
			'assigned_to' => $ticket->assigned_to ? intval( $ticket->assigned_to ) : null,
			'assigned_agent_name' => $ticket->assigned_agent_name,
			'created_at' => mysql_to_rfc3339( $ticket->created_at ),
			'updated_at' => mysql_to_rfc3339( $ticket->updated_at ),
			'resolved_at' => $ticket->resolved_at ? mysql_to_rfc3339( $ticket->resolved_at ) : null,
		);

		if ( $detailed ) {
			$data['description'] = $ticket->description;
			$data['internal_notes'] = current_user_can( 'manage_tickets' ) ? $ticket->internal_notes : null;
			$data['sla_due_date'] = $ticket->sla_due_date ? mysql_to_rfc3339( $ticket->sla_due_date ) : null;
			$data['sla_status'] = $ticket->sla_status;
			$data['first_response_at'] = $ticket->first_response_at ? mysql_to_rfc3339( $ticket->first_response_at ) : null;
			$data['source'] = $ticket->source;
			
			// Include reply count
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
			$reply_model = new METS_Ticket_Reply_Model();
			$data['reply_count'] = $reply_model->get_count_by_ticket( $ticket->id );
		}

		return $data;
	}

	/**
	 * Prepare reply for API response
	 *
	 * @since    1.0.0
	 * @param    object   $reply    Reply object
	 * @return   array              Prepared reply data
	 */
	private function prepare_reply_for_response( $reply ) {
		return array(
			'id' => intval( $reply->id ),
			'ticket_id' => intval( $reply->ticket_id ),
			'content' => $reply->content,
			'author_id' => $reply->author_id ? intval( $reply->author_id ) : null,
			'author_name' => $reply->author_name,
			'author_email' => $reply->author_email,
			'is_customer_reply' => (bool) $reply->is_customer_reply,
			'created_at' => mysql_to_rfc3339( $reply->created_at ),
		);
	}

	/**
	 * Prepare customer for API response
	 *
	 * @since    1.0.0
	 * @param    WP_User  $customer    Customer user object
	 * @param    bool     $detailed    Include detailed information
	 * @return   array                 Prepared customer data
	 */
	private function prepare_customer_for_response( $customer, $detailed = false ) {
		$data = array(
			'id' => $customer->ID,
			'display_name' => $customer->display_name,
			'email' => $customer->user_email,
			'registered' => mysql_to_rfc3339( $customer->user_registered ),
		);

		if ( $detailed ) {
			// Add ticket statistics
			global $wpdb;
			$ticket_stats = $wpdb->get_row( $wpdb->prepare(
				"SELECT 
					COUNT(*) as total_tickets,
					COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
					COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets
				FROM {$wpdb->prefix}mets_tickets
				WHERE customer_id = %d",
				$customer->ID
			) );

			$data['statistics'] = array(
				'total_tickets' => intval( $ticket_stats->total_tickets ),
				'open_tickets' => intval( $ticket_stats->open_tickets ),
				'resolved_tickets' => intval( $ticket_stats->resolved_tickets ),
			);
		}

		return $data;
	}

	/**
	 * Prepare agent for API response
	 *
	 * @since    1.0.0
	 * @param    WP_User  $agent    Agent user object
	 * @return   array              Prepared agent data
	 */
	private function prepare_agent_for_response( $agent ) {
		return array(
			'id' => $agent->ID,
			'display_name' => $agent->display_name,
			'email' => $agent->user_email,
			'role' => array_shift( $agent->roles ),
		);
	}

	/**
	 * Prepare SLA rule for API response
	 *
	 * @since    1.0.0
	 * @param    object   $rule    SLA rule object
	 * @return   array             Prepared rule data
	 */
	private function prepare_sla_rule_for_response( $rule ) {
		return array(
			'id' => intval( $rule->id ),
			'name' => $rule->name,
			'priority' => intval( $rule->priority ),
			'conditions' => json_decode( $rule->conditions, true ),
			'response_time' => intval( $rule->response_time ),
			'resolution_time' => intval( $rule->resolution_time ),
			'business_hours' => (bool) $rule->business_hours,
			'status' => $rule->status,
		);
	}

	/**
	 * Prepare KB article for API response
	 *
	 * @since    1.0.0
	 * @param    object   $article     Article object
	 * @param    bool     $detailed    Include detailed information
	 * @return   array                 Prepared article data
	 */
	private function prepare_kb_article_for_response( $article, $detailed = false ) {
		$data = array(
			'id' => intval( $article->id ),
			'title' => $article->title,
			'slug' => $article->slug,
			'excerpt' => $article->excerpt,
			'entity_id' => $article->entity_id ? intval( $article->entity_id ) : null,
			'entity_name' => $article->entity_name,
			'featured' => (bool) $article->featured,
			'view_count' => intval( $article->view_count ),
			'helpful_count' => intval( $article->helpful_count ),
			'not_helpful_count' => intval( $article->not_helpful_count ),
			'created_at' => mysql_to_rfc3339( $article->created_at ),
			'updated_at' => mysql_to_rfc3339( $article->updated_at ),
		);

		if ( $detailed ) {
			$data['content'] = $article->content;
			$data['visibility'] = $article->visibility;
			$data['author_id'] = intval( $article->author_id );
			$author = get_user_by( 'id', $article->author_id );
			$data['author_name'] = $author ? $author->display_name : __( 'Unknown User', METS_TEXT_DOMAIN );
			
			// Include categories
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
			$article_model = new METS_KB_Article_Model();
			$categories = $article_model->get_categories( $article->id );
			$data['categories'] = array_map( function( $cat ) {
				return $this->prepare_kb_category_for_response( $cat );
			}, $categories );
		}

		return $data;
	}

	/**
	 * Prepare KB category for API response
	 *
	 * @since    1.0.0
	 * @param    object   $category    Category object
	 * @return   array                 Prepared category data
	 */
	private function prepare_kb_category_for_response( $category ) {
		return array(
			'id' => intval( $category->id ),
			'name' => $category->name,
			'slug' => $category->slug,
			'description' => $category->description,
			'parent_id' => $category->parent_id ? intval( $category->parent_id ) : null,
			'entity_id' => $category->entity_id ? intval( $category->entity_id ) : null,
			'icon' => $category->icon,
			'color' => $category->color,
			'article_count' => intval( $category->article_count ),
		);
	}

	/**
	 * Get user KB visibility levels
	 *
	 * @since    1.0.0
	 * @return   array    Visibility levels user can access
	 */
	private function get_user_kb_visibility_levels() {
		if ( current_user_can( 'manage_tickets' ) ) {
			return array( 'internal', 'staff', 'customer' );
		} elseif ( is_user_logged_in() ) {
			return array( 'staff', 'customer' );
		} else {
			return array( 'customer' );
		}
	}

	/**
	 * Check if agent is available
	 *
	 * @since    1.0.0
	 * @param    int      $agent_id    Agent ID
	 * @return   bool                  Whether agent is available
	 */
	private function is_agent_available( $agent_id ) {
		// This could check business hours, agent status, workload, etc.
		// For now, simple implementation
		global $wpdb;
		
		$open_tickets = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE assigned_to = %d AND status IN ('open', 'in_progress')",
			$agent_id
		) );

		// Consider agent available if they have less than 10 open tickets
		return $open_tickets < 10;
	}

	/**
	 * Get date WHERE clause for SQL queries
	 *
	 * @since    1.0.0
	 * @param    string   $period    Period string
	 * @return   string              WHERE clause
	 */
	private function get_date_where_clause( $period ) {
		switch ( $period ) {
			case '24hours':
				return " AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
			case '7days':
				return " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			case '30days':
				return " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
			case '90days':
				return " AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
			case 'all':
			default:
				return "";
		}
	}

	/**
	 * Generate CSV from report data
	 *
	 * @since    1.0.0
	 * @param    array    $report_data    Report data
	 * @param    array    $config         Report configuration
	 * @return   string                   CSV content
	 */
	private function generate_csv_from_report( $report_data, $config ) {
		$csv = '';
		
		// Add headers based on report type
		switch ( $report_data['type'] ) {
			case 'tickets':
				$headers = array( 'ID', 'Subject', 'Status', 'Priority', 'Entity', 'Customer', 'Agent', 'Created', 'Updated' );
				break;
			case 'agent':
				$headers = array( 'Agent', 'Tickets Assigned', 'Tickets Resolved', 'Resolution Rate', 'Avg Resolution Time' );
				break;
			default:
				$headers = array();
		}

		// Build CSV content
		$csv .= implode( ',', $headers ) . "\n";

		// Add data rows
		// This is a simplified version - full implementation would handle all report types
		if ( $report_data['type'] === 'tickets' && isset( $report_data['tickets'] ) ) {
			foreach ( $report_data['tickets'] as $ticket ) {
				$row = array(
					$ticket->id,
					'"' . str_replace( '"', '""', $ticket->subject ) . '"',
					$ticket->status,
					$ticket->priority,
					$ticket->entity_name,
					$ticket->customer_name,
					$ticket->agent_name ?: 'Unassigned',
					$ticket->created_at,
					$ticket->updated_at,
				);
				$csv .= implode( ',', $row ) . "\n";
			}
		}

		return $csv;
	}
}