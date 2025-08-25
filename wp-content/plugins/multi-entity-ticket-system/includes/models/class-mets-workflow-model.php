<?php
/**
 * Workflow model class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Workflow model class.
 *
 * This class handles workflow rules for ticket status transitions,
 * defining which status changes are allowed and by which user roles.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Workflow_Model {

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
		$this->table_name = $wpdb->prefix . 'mets_workflow_rules';
	}

	/**
	 * Create workflow rule
	 *
	 * @since    1.0.0
	 * @param    array    $data    Rule data
	 * @return   int|WP_Error      Rule ID on success, WP_Error on failure
	 */
	public function create( $data ) {
		global $wpdb;

		// Validate required fields
		$required_fields = array( 'from_status', 'to_status', 'allowed_roles' );
		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Field %s is required.', METS_TEXT_DOMAIN ), $field ) );
			}
		}

		// Prepare data for insertion
		$insert_data = array(
			'from_status'    => sanitize_text_field( $data['from_status'] ),
			'to_status'      => sanitize_text_field( $data['to_status'] ),
			'allowed_roles'  => is_array( $data['allowed_roles'] ) ? serialize( $data['allowed_roles'] ) : serialize( array( $data['allowed_roles'] ) ),
			'priority_id'    => ! empty( $data['priority_id'] ) ? intval( $data['priority_id'] ) : null,
			'category'       => ! empty( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '',
			'auto_assign'    => ! empty( $data['auto_assign'] ) ? 1 : 0,
			'requires_note'  => ! empty( $data['requires_note'] ) ? 1 : 0,
			'created_at'     => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s' );

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create workflow rule.', METS_TEXT_DOMAIN ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get workflow rule by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Rule ID
	 * @return   object|null   Rule object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		$rule = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		) );

		if ( $rule && ! empty( $rule->allowed_roles ) ) {
			$rule->allowed_roles = maybe_unserialize( $rule->allowed_roles );
		}

		return $rule;
	}

	/**
	 * Update workflow rule
	 *
	 * @since    1.0.0
	 * @param    int      $id      Rule ID
	 * @param    array    $data    Rule data
	 * @return   bool|WP_Error     True on success, WP_Error on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		// Check if rule exists
		if ( ! $this->get( $id ) ) {
			return new WP_Error( 'rule_not_found', __( 'Workflow rule not found.', METS_TEXT_DOMAIN ) );
		}

		// Prepare data for update
		$update_data = array();
		$format = array();

		if ( isset( $data['from_status'] ) ) {
			$update_data['from_status'] = sanitize_text_field( $data['from_status'] );
			$format[] = '%s';
		}

		if ( isset( $data['to_status'] ) ) {
			$update_data['to_status'] = sanitize_text_field( $data['to_status'] );
			$format[] = '%s';
		}

		if ( isset( $data['allowed_roles'] ) ) {
			$update_data['allowed_roles'] = is_array( $data['allowed_roles'] ) ? serialize( $data['allowed_roles'] ) : serialize( array( $data['allowed_roles'] ) );
			$format[] = '%s';
		}

		if ( isset( $data['priority_id'] ) ) {
			$update_data['priority_id'] = ! empty( $data['priority_id'] ) ? intval( $data['priority_id'] ) : null;
			$format[] = '%d';
		}

		if ( isset( $data['category'] ) ) {
			$update_data['category'] = sanitize_text_field( $data['category'] );
			$format[] = '%s';
		}

		if ( isset( $data['auto_assign'] ) ) {
			$update_data['auto_assign'] = ! empty( $data['auto_assign'] ) ? 1 : 0;
			$format[] = '%d';
		}

		if ( isset( $data['requires_note'] ) ) {
			$update_data['requires_note'] = ! empty( $data['requires_note'] ) ? 1 : 0;
			$format[] = '%d';
		}

		if ( empty( $update_data ) ) {
			return new WP_Error( 'no_data', __( 'No data to update.', METS_TEXT_DOMAIN ) );
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update workflow rule.', METS_TEXT_DOMAIN ) );
		}

		return true;
	}

	/**
	 * Delete workflow rule
	 *
	 * @since    1.0.0
	 * @param    int    $id    Rule ID
	 * @return   bool|WP_Error  True on success, WP_Error on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Check if rule exists
		if ( ! $this->get( $id ) ) {
			return new WP_Error( 'rule_not_found', __( 'Workflow rule not found.', METS_TEXT_DOMAIN ) );
		}

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete workflow rule.', METS_TEXT_DOMAIN ) );
		}

		return true;
	}

	/**
	 * Get all workflow rules
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of rule objects
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'from_status' => null,
			'to_status'   => null,
			'orderby'     => 'from_status',
			'order'       => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		if ( ! empty( $args['from_status'] ) ) {
			$where_clauses[] = 'from_status = %s';
			$where_values[] = $args['from_status'];
		}

		if ( ! empty( $args['to_status'] ) ) {
			$where_clauses[] = 'to_status = %s';
			$where_values[] = $args['to_status'];
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		$allowed_orderby = array( 'from_status', 'to_status', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'from_status';
		$order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		$sql = "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY {$orderby} {$order}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		$rules = $wpdb->get_results( $sql );

		// Unserialize allowed_roles for each rule
		foreach ( $rules as $rule ) {
			if ( ! empty( $rule->allowed_roles ) ) {
				$rule->allowed_roles = maybe_unserialize( $rule->allowed_roles );
			}
		}

		return $rules;
	}

	/**
	 * Check if status transition is allowed
	 *
	 * @since    1.0.0
	 * @param    string    $from_status    Current status
	 * @param    string    $to_status      Target status
	 * @param    int       $user_id        User ID (optional, defaults to current user)
	 * @param    array     $ticket_data    Additional ticket data for context
	 * @return   bool|WP_Error             True if allowed, WP_Error if not
	 */
	public function is_transition_allowed( $from_status, $to_status, $user_id = null, $ticket_data = array() ) {
		// Allow staying in the same status
		if ( $from_status === $to_status ) {
			return true;
		}

		// Default to current user if not specified
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Admins can always change status
		if ( user_can( $user_id, 'manage_ticket_system' ) ) {
			return true;
		}

		// Get workflow rules for this transition
		$rules = $this->get_all( array(
			'from_status' => $from_status,
			'to_status'   => $to_status,
		) );

		if ( empty( $rules ) ) {
			return new WP_Error( 'transition_not_allowed', __( 'This status transition is not allowed.', METS_TEXT_DOMAIN ) );
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user.', METS_TEXT_DOMAIN ) );
		}

		// Check if user role is allowed for any matching rule
		foreach ( $rules as $rule ) {
			$allowed_roles = is_array( $rule->allowed_roles ) ? $rule->allowed_roles : array( $rule->allowed_roles );
			
			// Check if user has any of the allowed roles
			foreach ( $allowed_roles as $role ) {
				if ( user_can( $user_id, $role ) || in_array( $role, $user->roles ) ) {
					// Additional checks based on rule conditions
					if ( ! empty( $rule->priority_id ) && ! empty( $ticket_data['priority'] ) ) {
						// Check if ticket priority matches rule requirement
						$priorities = get_option( 'mets_ticket_priorities', array() );
						$priority_keys = array_keys( $priorities );
						if ( isset( $priority_keys[ $rule->priority_id - 1 ] ) ) {
							$required_priority = $priority_keys[ $rule->priority_id - 1 ];
							if ( $ticket_data['priority'] !== $required_priority ) {
								continue; // This rule doesn't apply
							}
						}
					}

					if ( ! empty( $rule->category ) && ! empty( $ticket_data['category'] ) ) {
						if ( $ticket_data['category'] !== $rule->category ) {
							continue; // This rule doesn't apply
						}
					}

					// If we get here, the rule applies and the user is allowed
					return true;
				}
			}
		}

		return new WP_Error( 'role_not_allowed', __( 'You do not have permission to make this status change.', METS_TEXT_DOMAIN ) );
	}

	/**
	 * Get allowed transitions for a status
	 *
	 * @since    1.0.0
	 * @param    string    $from_status    Current status
	 * @param    int       $user_id        User ID (optional, defaults to current user)
	 * @param    array     $ticket_data    Additional ticket data for context
	 * @return   array                     Array of allowed target statuses
	 */
	public function get_allowed_transitions( $from_status, $user_id = null, $ticket_data = array() ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Admins can transition to any status
		if ( user_can( $user_id, 'manage_ticket_system' ) ) {
			$statuses = get_option( 'mets_ticket_statuses', array() );
			return array_keys( $statuses );
		}

		$rules = $this->get_all( array( 'from_status' => $from_status ) );
		$allowed_statuses = array( $from_status ); // Always allow staying in same status

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return $allowed_statuses;
		}

		foreach ( $rules as $rule ) {
			$allowed_roles = is_array( $rule->allowed_roles ) ? $rule->allowed_roles : array( $rule->allowed_roles );
			
			// Check if user has any of the allowed roles
			$has_role = false;
			foreach ( $allowed_roles as $role ) {
				if ( user_can( $user_id, $role ) || in_array( $role, $user->roles ) ) {
					$has_role = true;
					break;
				}
			}

			if ( ! $has_role ) {
				continue;
			}

			// Additional checks based on rule conditions
			if ( ! empty( $rule->priority_id ) && ! empty( $ticket_data['priority'] ) ) {
				$priorities = get_option( 'mets_ticket_priorities', array() );
				$priority_keys = array_keys( $priorities );
				if ( isset( $priority_keys[ $rule->priority_id - 1 ] ) ) {
					$required_priority = $priority_keys[ $rule->priority_id - 1 ];
					if ( $ticket_data['priority'] !== $required_priority ) {
						continue;
					}
				}
			}

			if ( ! empty( $rule->category ) && ! empty( $ticket_data['category'] ) ) {
				if ( $ticket_data['category'] !== $rule->category ) {
					continue;
				}
			}

			// Add allowed status if not already in array
			if ( ! in_array( $rule->to_status, $allowed_statuses ) ) {
				$allowed_statuses[] = $rule->to_status;
			}
		}

		return $allowed_statuses;
	}

	/**
	 * Check if transition requires a note
	 *
	 * @since    1.0.0
	 * @param    string    $from_status    Current status
	 * @param    string    $to_status      Target status
	 * @return   bool                      True if note is required
	 */
	public function requires_note( $from_status, $to_status ) {
		$rules = $this->get_all( array(
			'from_status' => $from_status,
			'to_status'   => $to_status,
		) );

		foreach ( $rules as $rule ) {
			if ( $rule->requires_note ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get default workflow rules
	 *
	 * @since    1.0.0
	 * @return   array    Array of default rules
	 */
	public function get_default_rules() {
		return array(
			array(
				'from_status'    => 'new',
				'to_status'      => 'open',
				'allowed_roles'  => array( 'ticket_agent', 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 1,
				'requires_note'  => 0,
			),
			array(
				'from_status'    => 'new',
				'to_status'      => 'in_progress',
				'allowed_roles'  => array( 'ticket_agent', 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 1,
				'requires_note'  => 0,
			),
			array(
				'from_status'    => 'open',
				'to_status'      => 'in_progress',
				'allowed_roles'  => array( 'ticket_agent', 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 0,
				'requires_note'  => 0,
			),
			array(
				'from_status'    => 'in_progress',
				'to_status'      => 'resolved',
				'allowed_roles'  => array( 'ticket_agent', 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 0,
				'requires_note'  => 1,
			),
			array(
				'from_status'    => 'resolved',
				'to_status'      => 'closed',
				'allowed_roles'  => array( 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 0,
				'requires_note'  => 0,
			),
			array(
				'from_status'    => 'resolved',
				'to_status'      => 'open',
				'allowed_roles'  => array( 'ticket_agent', 'ticket_manager', 'ticket_admin' ),
				'auto_assign'    => 0,
				'requires_note'  => 1,
			),
		);
	}
}