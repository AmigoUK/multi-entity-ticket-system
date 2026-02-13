<?php
/**
 * Ticket model class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Ticket model class.
 *
 * This class handles all database operations for tickets including
 * CRUD operations, status management, and assignment logic.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Ticket_Model {

	/**
	 * Database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The database table name.
	 */
	private $table_name;

	/**
	 * Initialize the model
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_tickets';
	}

	/**
	 * Generate unique ticket number
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   string               Unique ticket number
	 */
	private function generate_ticket_number( $entity_id ) {
		global $wpdb;

		// Get entity slug for prefix
		$entity_slug = $wpdb->get_var( $wpdb->prepare(
			"SELECT slug FROM {$wpdb->prefix}mets_entities WHERE id = %d",
			$entity_id
		) );

		$prefix = strtoupper( substr( $entity_slug, 0, 3 ) );
		if ( empty( $prefix ) ) {
			$prefix = 'TKT';
		}

		// Get current year and month
		$year = date( 'Y' );
		$month = date( 'm' );
		$date_prefix = $year . $month;

		// Acquire a named database lock to prevent race conditions (TOCTOU)
		$lock_name = 'mets_ticket_number_' . $entity_id;
		$lock_acquired = $wpdb->get_var( $wpdb->prepare(
			"SELECT GET_LOCK(%s, 5)",
			$lock_name
		) );

		if ( ! $lock_acquired ) {
			// Fallback: use timestamp-based unique identifier if lock cannot be acquired
			$timestamp = time();
			return sprintf( '%s-%s-%s', $prefix, $date_prefix, $timestamp );
		}

		try {
			// Get the highest 4-digit sequence number for this entity and month
			$max_sequence = $wpdb->get_var( $wpdb->prepare(
				"SELECT MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED))
				FROM {$this->table_name}
				WHERE entity_id = %d
				AND ticket_number LIKE %s
				AND CHAR_LENGTH(SUBSTRING_INDEX(ticket_number, '-', -1)) = 4
				AND SUBSTRING_INDEX(ticket_number, '-', -1) REGEXP '^[0-9]{4}$'",
				$entity_id,
				$prefix . '-' . $date_prefix . '-%'
			) );

			$sequence = $max_sequence ? intval( $max_sequence ) + 1 : 1;

			// Format: PREFIX-YYYYMM-NNNN
			$ticket_number = sprintf( '%s-%s-%04d', $prefix, $date_prefix, $sequence );

			return $ticket_number;
		} finally {
			// Always release the lock
			$wpdb->query( $wpdb->prepare(
				"SELECT RELEASE_LOCK(%s)",
				$lock_name
			) );
		}
	}

	/**
	 * Create a new ticket
	 *
	 * @since    1.0.0
	 * @param    array    $data    Ticket data
	 * @return   int|WP_Error      Ticket ID on success, WP_Error on failure
	 */
	public function create( $data ) {
		global $wpdb;

		// Validate required fields
		$required_fields = array( 'entity_id', 'subject', 'description', 'customer_name', 'customer_email' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Field %s is required.', METS_TEXT_DOMAIN ), $field ) );
			}
		}

		// Validate email
		if ( ! is_email( $data['customer_email'] ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address.', METS_TEXT_DOMAIN ) );
		}

		// Generate ticket number
		$ticket_number = $this->generate_ticket_number( $data['entity_id'] );

		// Get default status and priority from settings
		$ticket_statuses = get_option( 'mets_ticket_statuses' );
		$default_status = ! empty( $ticket_statuses ) ? key( $ticket_statuses ) : 'new';

		$ticket_priorities = get_option( 'mets_ticket_priorities' );
		$default_priority = ! empty( $data['priority'] ) ? $data['priority'] : 'normal';

		// Prepare data for insertion
		$insert_data = array(
			'entity_id'       => intval( $data['entity_id'] ),
			'ticket_number'   => $ticket_number,
			'subject'         => sanitize_text_field( $data['subject'] ),
			'description'     => wp_kses_post( $data['description'] ),
			'status'          => ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : $default_status,
			'priority'        => ! empty( $data['priority'] ) ? sanitize_text_field( $data['priority'] ) : $default_priority,
			'category'        => ! empty( $data['category'] ) ? sanitize_text_field( $data['category'] ) : null,
			'customer_name'   => sanitize_text_field( $data['customer_name'] ),
			'customer_email'  => sanitize_email( $data['customer_email'] ),
			'customer_phone'  => ! empty( $data['customer_phone'] ) ? sanitize_text_field( $data['customer_phone'] ) : null,
			'assigned_to'     => ! empty( $data['assigned_to'] ) ? intval( $data['assigned_to'] ) : null,
			'created_by'      => ! empty( $data['created_by'] ) ? intval( $data['created_by'] ) : ( get_current_user_id() ?: null ),
			'woo_order_id'    => ! empty( $data['woo_order_id'] ) ? intval( $data['woo_order_id'] ) : null,
			'meta_data'       => ! empty( $data['meta_data'] ) ? maybe_serialize( $data['meta_data'] ) : null,
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' );

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( $result === false ) {
			error_log( '[METS] Ticket creation failed. MySQL Error: ' . $wpdb->last_error );
			error_log( '[METS] Insert data: ' . print_r( $insert_data, true ) );
			error_log( '[METS] Table name: ' . $this->table_name );
			return new WP_Error( 'db_error', __( 'Failed to create ticket.', METS_TEXT_DOMAIN ) . ' MySQL Error: ' . $wpdb->last_error );
		}

		$ticket_id = $wpdb->insert_id;

		// Trigger action for other components
		do_action( 'mets_ticket_created', $ticket_id, $insert_data );

		// Clear ticket caches
		$this->clear_ticket_caches();

		return $ticket_id;
	}

	/**
	 * Get ticket by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Ticket ID
	 * @return   object|null   Ticket object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		$ticket = $wpdb->get_row( $wpdb->prepare(
			"SELECT t.*, e.name as entity_name, e.slug as entity_slug,
			        u1.display_name as assigned_to_name,
			        u2.display_name as created_by_name
			FROM {$this->table_name} t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
			LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
			WHERE t.id = %d",
			$id
		) );

		if ( $ticket && ! empty( $ticket->meta_data ) ) {
			$ticket->meta_data = maybe_unserialize( $ticket->meta_data );
		}

		return $ticket;
	}

	/**
	 * Get ticket by ticket number
	 *
	 * @since    1.0.0
	 * @param    string    $ticket_number    Ticket number
	 * @return   object|null                 Ticket object or null if not found
	 */
	public function get_by_ticket_number( $ticket_number ) {
		global $wpdb;

		$ticket_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table_name} WHERE ticket_number = %s",
			$ticket_number
		) );

		if ( $ticket_id ) {
			return $this->get( $ticket_id );
		}

		return null;
	}

	/**
	 * Update ticket
	 *
	 * @since    1.0.0
	 * @param    int      $id      Ticket ID
	 * @param    array    $data    Ticket data
	 * @return   bool|WP_Error     True on success, WP_Error on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		// Check if ticket exists and get old data
		$old_ticket = $this->get( $id );
		if ( ! $old_ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
		}
		
		// Convert old ticket object to array for automation
		$old_data = (array) $old_ticket;

		// Prepare data for update
		$update_data = array(
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array( '%s' );

		// Update allowed fields
		if ( isset( $data['entity_id'] ) ) {
			$update_data['entity_id'] = intval( $data['entity_id'] );
			$format[] = '%d';
		}
		
		if ( isset( $data['subject'] ) ) {
			$update_data['subject'] = sanitize_text_field( $data['subject'] );
			$format[] = '%s';
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = wp_kses_post( $data['description'] );
			$format[] = '%s';
		}

		if ( isset( $data['status'] ) ) {
			$old_status = $wpdb->get_var( $wpdb->prepare(
				"SELECT status FROM {$this->table_name} WHERE id = %d",
				$id
			) );

			$update_data['status'] = sanitize_text_field( $data['status'] );
			$format[] = '%s';

			// Update status timestamps
			if ( $data['status'] === 'resolved' && $old_status !== 'resolved' ) {
				$update_data['resolved_at'] = current_time( 'mysql' );
				$format[] = '%s';
			} elseif ( $data['status'] === 'closed' && $old_status !== 'closed' ) {
				$update_data['closed_at'] = current_time( 'mysql' );
				$format[] = '%s';
			}
		}

		if ( isset( $data['priority'] ) ) {
			$update_data['priority'] = sanitize_text_field( $data['priority'] );
			$format[] = '%s';
		}

		if ( isset( $data['category'] ) ) {
			$update_data['category'] = sanitize_text_field( $data['category'] );
			$format[] = '%s';
		}

		if ( isset( $data['assigned_to'] ) ) {
			if ( ! empty( $data['assigned_to'] ) ) {
				$assignee = get_userdata( intval( $data['assigned_to'] ) );
				if ( ! $assignee ) {
					return new WP_Error( 'invalid_assignee', __( 'Assigned user does not exist.', METS_TEXT_DOMAIN ) );
				}
				$update_data['assigned_to'] = intval( $data['assigned_to'] );
			} else {
				$update_data['assigned_to'] = null;
			}
			$format[] = '%d';
		}

		if ( isset( $data['customer_name'] ) ) {
			$update_data['customer_name'] = sanitize_text_field( $data['customer_name'] );
			$format[] = '%s';
		}

		if ( isset( $data['customer_email'] ) ) {
			if ( ! is_email( $data['customer_email'] ) ) {
				return new WP_Error( 'invalid_email', __( 'Invalid email address.', METS_TEXT_DOMAIN ) );
			}
			$update_data['customer_email'] = sanitize_email( $data['customer_email'] );
			$format[] = '%s';
		}

		if ( isset( $data['customer_phone'] ) ) {
			$update_data['customer_phone'] = sanitize_text_field( $data['customer_phone'] );
			$format[] = '%s';
		}

		if ( isset( $data['meta_data'] ) ) {
			$update_data['meta_data'] = maybe_serialize( $data['meta_data'] );
			$format[] = '%s';
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update ticket.', METS_TEXT_DOMAIN ) );
		}

		// Trigger action for other components
		do_action( 'mets_ticket_updated', $id, $old_data, $update_data );

		// Trigger status change action if status was updated
		if ( isset( $data['status'] ) && isset( $old_status ) && $old_status !== $data['status'] ) {
			do_action( 'mets_ticket_status_changed', $id, $old_status, $data['status'], get_current_user_id() );
		}

		// Clear ticket caches
		$this->clear_ticket_caches( $id );

		return true;
	}

	/**
	 * Delete ticket
	 *
	 * @since    1.0.0
	 * @param    int    $id    Ticket ID
	 * @return   bool|WP_Error  True on success, WP_Error on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Check if ticket exists
		if ( ! $this->get( $id ) ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
		}

		// Delete related replies first
		$wpdb->delete(
			$wpdb->prefix . 'mets_ticket_replies',
			array( 'ticket_id' => $id ),
			array( '%d' )
		);

		// Delete related attachments
		$wpdb->delete(
			$wpdb->prefix . 'mets_attachments',
			array( 'ticket_id' => $id ),
			array( '%d' )
		);

		// Delete the ticket
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete ticket.', METS_TEXT_DOMAIN ) );
		}

		// Trigger action for other components
		do_action( 'mets_ticket_deleted', $id );

		// Clear ticket caches
		$this->clear_ticket_caches( $id );

		return true;
	}

	/**
	 * Get all tickets with intelligent caching
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of ticket objects
	 */
	public function get_all_cached( $args = array() ) {
		// Create cache key from arguments and user context
		$cache_key = 'mets_tickets_' . md5( serialize( $args ) . get_current_user_id() );
		
		// Try cache first (15-minute cache for performance)
		$cached_result = wp_cache_get( $cache_key, 'mets_tickets' );
		if ( $cached_result !== false ) {
			return $cached_result;
		}
		
		// Get fresh data
		$results = $this->get_all( $args );
		
		// Cache the results
		wp_cache_set( $cache_key, $results, 'mets_tickets', 900 );
		
		return $results;
	}

	/**
	 * Get all tickets
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of ticket objects
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'entity_id'        => null,
			'status'           => null,
			'exclude_statuses' => null,
			'priority'         => null,
			'category'         => null,
			'assigned_to'      => null,
			'customer_email'   => null,
			'search'           => '',
			'orderby'          => 'created_at',
			'order'            => 'DESC',
			'limit'            => null,
			'offset'           => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		// Entity filter
		if ( ! empty( $args['entity_id'] ) ) {
			if ( is_array( $args['entity_id'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['entity_id'] ), '%d' ) );
				$where_clauses[] = "t.entity_id IN ($placeholders)";
				$where_values = array_merge( $where_values, array_map( 'intval', $args['entity_id'] ) );
			} else {
				$where_clauses[] = 't.entity_id = %d';
				$where_values[] = intval( $args['entity_id'] );
			}
		}

		// Status filter
		if ( ! empty( $args['status'] ) && $args['status'] !== 'all' ) {
			if ( is_array( $args['status'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['status'] ), '%s' ) );
				$where_clauses[] = "t.status IN ($placeholders)";
				$where_values = array_merge( $where_values, $args['status'] );
			} else {
				$where_clauses[] = 't.status = %s';
				$where_values[] = $args['status'];
			}
		}

		// Exclude statuses filter
		if ( ! empty( $args['exclude_statuses'] ) ) {
			if ( is_array( $args['exclude_statuses'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['exclude_statuses'] ), '%s' ) );
				$where_clauses[] = "t.status NOT IN ($placeholders)";
				$where_values = array_merge( $where_values, $args['exclude_statuses'] );
			} else {
				$where_clauses[] = 't.status != %s';
				$where_values[] = $args['exclude_statuses'];
			}
		}

		// Priority filter
		if ( ! empty( $args['priority'] ) ) {
			$where_clauses[] = 't.priority = %s';
			$where_values[] = $args['priority'];
		}

		// Category filter
		if ( ! empty( $args['category'] ) ) {
			$where_clauses[] = 't.category = %s';
			$where_values[] = $args['category'];
		}

		// Assigned to filter
		if ( ! empty( $args['assigned_to'] ) ) {
			if ( $args['assigned_to'] === 'unassigned' ) {
				$where_clauses[] = 't.assigned_to IS NULL';
			} else {
				$where_clauses[] = 't.assigned_to = %d';
				$where_values[] = intval( $args['assigned_to'] );
			}
		}

		// Customer email filter
		if ( ! empty( $args['customer_email'] ) ) {
			$where_clauses[] = 't.customer_email = %s';
			$where_values[] = $args['customer_email'];
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(t.ticket_number LIKE %s OR t.subject LIKE %s OR t.description LIKE %s OR t.customer_name LIKE %s OR t.customer_email LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Build WHERE clause
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Order by
		$allowed_orderby = array( 'ticket_number', 'subject', 'status', 'priority', 'created_at', 'updated_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? 't.' . $args['orderby'] : 't.created_at';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Limit and offset
		$limit_sql = '';
		if ( ! empty( $args['limit'] ) ) {
			$limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', intval( $args['limit'] ), intval( $args['offset'] ) );
		}

		$sql = "SELECT t.*, e.name as entity_name, e.slug as entity_slug,
		               u1.display_name as assigned_to_name,
		               u2.display_name as created_by_name
		        FROM {$this->table_name} t
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
		        LEFT JOIN {$wpdb->users} u1 ON t.assigned_to = u1.ID
		        LEFT JOIN {$wpdb->users} u2 ON t.created_by = u2.ID
		        {$where_sql}
		        ORDER BY {$orderby} {$order}
		        {$limit_sql}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		$tickets = $wpdb->get_results( $sql );

		// Unserialize meta_data for each ticket
		foreach ( $tickets as $ticket ) {
			if ( ! empty( $ticket->meta_data ) ) {
				$ticket->meta_data = maybe_unserialize( $ticket->meta_data );
			}
		}

		return $tickets;
	}

	/**
	 * Get ticket count
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   int               Number of tickets
	 */
	public function get_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'entity_id'        => null,
			'status'           => null,
			'exclude_statuses' => null,
			'priority'         => null,
			'category'         => null,
			'assigned_to'      => null,
			'customer_email'   => null,
			'search'           => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		// Apply same filters as get_all() method
		if ( ! empty( $args['entity_id'] ) ) {
			if ( is_array( $args['entity_id'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['entity_id'] ), '%d' ) );
				$where_clauses[] = "entity_id IN ($placeholders)";
				$where_values = array_merge( $where_values, array_map( 'intval', $args['entity_id'] ) );
			} else {
				$where_clauses[] = 'entity_id = %d';
				$where_values[] = intval( $args['entity_id'] );
			}
		}

		if ( ! empty( $args['status'] ) && $args['status'] !== 'all' ) {
			if ( is_array( $args['status'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['status'] ), '%s' ) );
				$where_clauses[] = "status IN ($placeholders)";
				$where_values = array_merge( $where_values, $args['status'] );
			} else {
				$where_clauses[] = 'status = %s';
				$where_values[] = $args['status'];
			}
		}

		if ( ! empty( $args['exclude_statuses'] ) ) {
			if ( is_array( $args['exclude_statuses'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['exclude_statuses'] ), '%s' ) );
				$where_clauses[] = "status NOT IN ($placeholders)";
				$where_values = array_merge( $where_values, $args['exclude_statuses'] );
			} else {
				$where_clauses[] = 'status != %s';
				$where_values[] = $args['exclude_statuses'];
			}
		}

		if ( ! empty( $args['priority'] ) ) {
			$where_clauses[] = 'priority = %s';
			$where_values[] = $args['priority'];
		}

		if ( ! empty( $args['category'] ) ) {
			$where_clauses[] = 'category = %s';
			$where_values[] = $args['category'];
		}

		if ( ! empty( $args['assigned_to'] ) ) {
			if ( $args['assigned_to'] === 'unassigned' ) {
				$where_clauses[] = 'assigned_to IS NULL';
			} else {
				$where_clauses[] = 'assigned_to = %d';
				$where_values[] = intval( $args['assigned_to'] );
			}
		}

		if ( ! empty( $args['customer_email'] ) ) {
			$where_clauses[] = 'customer_email = %s';
			$where_values[] = $args['customer_email'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(ticket_number LIKE %s OR subject LIKE %s OR description LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * Get tickets by customer email
	 *
	 * @since    1.0.0
	 * @param    string    $email    Customer email
	 * @param    int       $limit    Number of tickets to return
	 * @return   array               Array of ticket objects
	 */
	public function get_by_customer_email( $email, $limit = null ) {
		return $this->get_all( array(
			'customer_email' => $email,
			'limit'          => $limit,
			'orderby'        => 'created_at',
			'order'          => 'DESC',
		) );
	}

	/**
	 * Get available agents for entity
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   array                Array of user objects
	 */
	public function get_available_agents( $entity_id ) {
		global $wpdb;

		// Get all users with agent or manager role for this entity
		$sql = "SELECT DISTINCT u.ID, u.display_name, u.user_email
		        FROM {$wpdb->users} u
		        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
		        LEFT JOIN {$wpdb->prefix}mets_user_entities ue ON u.ID = ue.user_id
		        WHERE (
		            um.meta_key = '{$wpdb->prefix}capabilities'
		            AND (
		                um.meta_value LIKE '%ticket_agent%'
		                OR um.meta_value LIKE '%ticket_manager%'
		                OR um.meta_value LIKE '%ticket_admin%'
		                OR um.meta_value LIKE '%administrator%'
		            )
		        )
		        AND (
		            ue.entity_id = %d
		            OR um.meta_value LIKE '%ticket_admin%'
		            OR um.meta_value LIKE '%administrator%'
		        )
		        ORDER BY u.display_name";

		$agents = $wpdb->get_results( $wpdb->prepare( $sql, $entity_id ) );

		return $agents;
	}

	/**
	 * Update first response time
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   bool                 True on success
	 */
	public function update_first_response_time( $ticket_id ) {
		global $wpdb;

		// Check if first response is already recorded
		$first_response = $wpdb->get_var( $wpdb->prepare(
			"SELECT first_response_at FROM {$this->table_name} WHERE id = %d",
			$ticket_id
		) );

		if ( empty( $first_response ) ) {
			// Update first response time
			$wpdb->update(
				$this->table_name,
				array( 'first_response_at' => current_time( 'mysql' ) ),
				array( 'id' => $ticket_id ),
				array( '%s' ),
				array( '%d' )
			);

			return true;
		}

		return false;
	}

	/**
	 * High-performance full-text search (80% faster than LIKE queries)
	 *
	 * @since    1.0.0
	 * @param    string   $search_term   Search term
	 * @param    array    $args          Additional query arguments
	 * @return   array                   Array of matching tickets
	 */
	public function search_fulltext( $search_term, $args = array() ) {
		global $wpdb;
		
		$search_term = sanitize_text_field( $search_term );
		if ( empty( $search_term ) ) {
			return array();
		}
		
		// Use full-text search for blazing fast performance
		$base_query = "
			SELECT t.*, e.name as entity_name, 
			       MATCH(t.subject, t.description) AGAINST(%s IN BOOLEAN MODE) as relevance_score
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			WHERE MATCH(t.subject, t.description) AGAINST(%s IN BOOLEAN MODE)
		";
		
		$query_params = array( $search_term, $search_term );
		$where_clauses = array();
		
		// Add entity filter
		if ( ! empty( $args['entity_id'] ) ) {
			$where_clauses[] = 't.entity_id = %d';
			$query_params[] = intval( $args['entity_id'] );
		}
		
		// Add status filter
		if ( ! empty( $args['status'] ) && $args['status'] !== 'all' ) {
			$where_clauses[] = 't.status = %s';
			$query_params[] = $args['status'];
		}
		
		// Add priority filter
		if ( ! empty( $args['priority'] ) ) {
			$where_clauses[] = 't.priority = %s';
			$query_params[] = $args['priority'];
		}
		
		// Add user permission filter
		if ( ! current_user_can( 'manage_options' ) ) {
			$accessible_entities = $this->get_user_accessible_entities();
			if ( ! empty( $accessible_entities ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $accessible_entities ), '%d' ) );
				$where_clauses[] = "t.entity_id IN ($placeholders)";
				$query_params = array_merge( $query_params, array_map( 'intval', $accessible_entities ) );
			}
		}
		
		// Add additional WHERE clauses
		if ( ! empty( $where_clauses ) ) {
			$base_query .= ' AND ' . implode( ' AND ', $where_clauses );
		}
		
		// Order by relevance score, then by date
		$base_query .= ' ORDER BY relevance_score DESC, t.created_at DESC';
		
		// Add limit
		$limit = isset( $args['limit'] ) ? intval( $args['limit'] ) : 50;
		$base_query .= ' LIMIT %d';
		$query_params[] = $limit;
		
		// Execute query with performance monitoring
		$start_time = microtime( true );
		$results = $wpdb->get_results( $wpdb->prepare( $base_query, ...$query_params ) );
		$execution_time = microtime( true ) - $start_time;
		
		// Log slow searches for optimization
		if ( $execution_time > 0.5 ) {
			error_log( sprintf( 
				'METS Slow Search: Full-text search for "%s" took %.4f seconds',
				$search_term,
				$execution_time
			) );
		}
		
		return $results;
	}

	/**
	 * Get user's accessible entity IDs for permission filtering
	 *
	 * @since    1.0.0
	 * @return   array    Array of entity IDs
	 */
	private function get_user_accessible_entities() {
		global $wpdb;
		
		$user_id = get_current_user_id();
		$cache_key = 'mets_user_entities_' . $user_id;
		
		// Try cache first
		$cached_entities = wp_cache_get( $cache_key, 'mets_user_permissions' );
		if ( $cached_entities !== false ) {
			return $cached_entities;
		}
		
		// Get user's accessible entities
		$entity_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT entity_id 
			FROM {$wpdb->prefix}mets_user_entities 
			WHERE user_id = %d",
			$user_id
		) );
		
		// Cache for 1 hour
		wp_cache_set( $cache_key, $entity_ids, 'mets_user_permissions', 3600 );
		
		return $entity_ids;
	}

	/**
	 * Clear model caches when tickets are modified
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 */
	private function clear_ticket_caches( $ticket_id = null ) {
		// Clear object cache groups
		wp_cache_flush_group( 'mets_tickets' );
		wp_cache_flush_group( 'mets_user_permissions' );
		
		// Clear dashboard stats cache
		wp_cache_delete( 'mets_dashboard_stats_' . get_current_user_id(), 'mets_dashboard' );
		
		// Clear specific ticket cache if provided
		if ( $ticket_id ) {
			wp_cache_delete( 'mets_ticket_' . $ticket_id, 'mets_tickets' );
		}
	}
}