<?php
/**
 * Ticket reply model class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Ticket reply model class.
 *
 * This class handles all database operations for ticket replies
 * including internal notes and customer responses.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Ticket_Reply_Model {

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
		$this->table_name = $wpdb->prefix . 'mets_ticket_replies';
	}

	/**
	 * Create a new reply
	 *
	 * @since    1.0.0
	 * @param    array    $data    Reply data
	 * @return   int|WP_Error      Reply ID on success, WP_Error on failure
	 */
	public function create( $data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $data['ticket_id'] ) ) {
			return new WP_Error( 'missing_ticket_id', __( 'Ticket ID is required.', METS_TEXT_DOMAIN ) );
		}

		if ( empty( $data['content'] ) ) {
			return new WP_Error( 'missing_content', __( 'Reply content is required.', METS_TEXT_DOMAIN ) );
		}

		// Determine user type
		$user_type = 'agent';
		if ( ! empty( $data['user_type'] ) && in_array( $data['user_type'], array( 'customer', 'agent', 'system' ) ) ) {
			$user_type = $data['user_type'];
		} elseif ( empty( $data['user_id'] ) || $data['user_id'] == 0 ) {
			$user_type = 'system';
		}

		// Prepare data for insertion
		$insert_data = array(
			'ticket_id'        => intval( $data['ticket_id'] ),
			'user_id'          => ! empty( $data['user_id'] ) ? intval( $data['user_id'] ) : null,
			'user_type'        => $user_type,
			'content'          => wp_kses_post( $data['content'] ),
			'is_internal_note' => ! empty( $data['is_internal_note'] ) ? 1 : 0,
			'created_at'       => current_time( 'mysql' ),
		);

		$format = array( '%d', '%d', '%s', '%s', '%d', '%s' );

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create reply.', METS_TEXT_DOMAIN ) );
		}

		$reply_id = $wpdb->insert_id;

		// Update ticket's updated_at timestamp
		$wpdb->update(
			$wpdb->prefix . 'mets_tickets',
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $data['ticket_id'] ),
			array( '%s' ),
			array( '%d' )
		);

		// Update first response time if this is the first agent response
		if ( $user_type === 'agent' && empty( $data['is_internal_note'] ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
			$ticket_model = new METS_Ticket_Model();
			$ticket_model->update_first_response_time( $data['ticket_id'] );
		}

		// Trigger action for other components
		do_action( 'mets_ticket_reply_created', $reply_id, $insert_data );

		return $reply_id;
	}

	/**
	 * Get reply by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Reply ID
	 * @return   object|null   Reply object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		$reply = $wpdb->get_row( $wpdb->prepare(
			"SELECT r.*, u.display_name as user_name, u.user_email
			FROM {$this->table_name} r
			LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
			WHERE r.id = %d",
			$id
		) );

		return $reply;
	}

	/**
	 * Get all replies for a ticket
	 *
	 * @since    1.0.0
	 * @param    int          $ticket_id      Ticket ID
	 * @param    bool|array   $args           Include internal notes (bool) or array of arguments
	 * @return   array                        Array of reply objects
	 */
	public function get_by_ticket( $ticket_id, $args = true ) {
		global $wpdb;

		// Support both old boolean format and new array format
		if ( is_bool( $args ) ) {
			$include_internal = $args;
		} elseif ( is_array( $args ) ) {
			$include_internal = ! isset( $args['exclude_internal'] ) || ! $args['exclude_internal'];
		} else {
			$include_internal = true;
		}

		$where_sql = 'WHERE r.ticket_id = %d';
		$where_values = array( $ticket_id );

		if ( ! $include_internal ) {
			$where_sql .= ' AND r.is_internal_note = 0';
		}

		$sql = "SELECT r.*, u.display_name as user_name, u.user_email
		        FROM {$this->table_name} r
		        LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
		        {$where_sql}
		        ORDER BY r.created_at ASC";

		$replies = $wpdb->get_results( $wpdb->prepare( $sql, $where_values ) );

		return $replies;
	}

	/**
	 * Delete reply
	 *
	 * @since    1.0.0
	 * @param    int    $id    Reply ID
	 * @return   bool|WP_Error  True on success, WP_Error on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Check if reply exists
		$reply = $this->get( $id );
		if ( ! $reply ) {
			return new WP_Error( 'reply_not_found', __( 'Reply not found.', METS_TEXT_DOMAIN ) );
		}

		// Delete related attachments
		$wpdb->delete(
			$wpdb->prefix . 'mets_attachments',
			array( 'reply_id' => $id ),
			array( '%d' )
		);

		// Delete the reply
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete reply.', METS_TEXT_DOMAIN ) );
		}

		// Update ticket's updated_at timestamp
		$wpdb->update(
			$wpdb->prefix . 'mets_tickets',
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $reply->ticket_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Trigger action for other components
		do_action( 'mets_ticket_reply_deleted', $id, $reply );

		return true;
	}

	/**
	 * Get reply count for a ticket
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id          Ticket ID
	 * @param    bool    $include_internal   Include internal notes
	 * @return   int                         Number of replies
	 */
	public function get_count_by_ticket( $ticket_id, $include_internal = true ) {
		global $wpdb;

		$where_sql = 'WHERE ticket_id = %d';
		$where_values = array( $ticket_id );

		if ( ! $include_internal ) {
			$where_sql .= ' AND is_internal_note = 0';
		}

		$sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";

		return intval( $wpdb->get_var( $wpdb->prepare( $sql, $where_values ) ) );
	}

	/**
	 * Get last reply for a ticket
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id          Ticket ID
	 * @param    bool    $include_internal   Include internal notes
	 * @return   object|null                 Reply object or null
	 */
	public function get_last_reply( $ticket_id, $include_internal = false ) {
		global $wpdb;

		$where_sql = 'WHERE r.ticket_id = %d';
		$where_values = array( $ticket_id );

		if ( ! $include_internal ) {
			$where_sql .= ' AND r.is_internal_note = 0';
		}

		$sql = "SELECT r.*, u.display_name as user_name, u.user_email
		        FROM {$this->table_name} r
		        LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
		        {$where_sql}
		        ORDER BY r.created_at DESC
		        LIMIT 1";

		$reply = $wpdb->get_row( $wpdb->prepare( $sql, $where_values ) );

		return $reply;
	}
}