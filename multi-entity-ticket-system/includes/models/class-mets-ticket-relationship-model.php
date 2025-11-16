<?php
/**
 * Ticket Relationship Model
 *
 * Handles all database operations for ticket relationships (merge, split, link)
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.1
 */

/**
 * Ticket Relationship Model Class
 *
 * This class handles all database operations for ticket relationships
 * including merge, split, and linking operations.
 *
 * @since      1.0.1
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Ticket_Relationship_Model {

	/**
	 * Database table name
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $table_name    The database table name.
	 */
	private $table_name;

	/**
	 * Initialize the model
	 *
	 * @since    1.0.1
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_ticket_relationships';
	}

	/**
	 * Create a relationship between tickets
	 *
	 * @since    1.0.1
	 * @param    int       $parent_ticket_id      Parent ticket ID
	 * @param    int       $child_ticket_id       Child ticket ID
	 * @param    string    $relationship_type     Type: merged, split, related, duplicate
	 * @param    string    $notes                 Optional notes
	 * @return   int|WP_Error                    Relationship ID on success, WP_Error on failure
	 */
	public function create( $parent_ticket_id, $child_ticket_id, $relationship_type, $notes = '' ) {
		global $wpdb;

		// Validate relationship type
		$valid_types = array( 'merged', 'split', 'related', 'duplicate' );
		if ( ! in_array( $relationship_type, $valid_types ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid relationship type.', METS_TEXT_DOMAIN ) );
		}

		// Prevent self-relationships
		if ( $parent_ticket_id === $child_ticket_id ) {
			return new WP_Error( 'self_relationship', __( 'Cannot create relationship to itself.', METS_TEXT_DOMAIN ) );
		}

		// Check if relationship already exists
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->table_name}
			WHERE parent_ticket_id = %d AND child_ticket_id = %d AND relationship_type = %s",
			$parent_ticket_id,
			$child_ticket_id,
			$relationship_type
		) );

		if ( $existing ) {
			return new WP_Error( 'relationship_exists', __( 'Relationship already exists.', METS_TEXT_DOMAIN ) );
		}

		// Insert relationship
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'parent_ticket_id'  => $parent_ticket_id,
				'child_ticket_id'   => $child_ticket_id,
				'relationship_type' => $relationship_type,
				'created_by'        => get_current_user_id(),
				'notes'             => sanitize_textarea_field( $notes ),
			),
			array( '%d', '%d', '%s', '%d', '%s' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create relationship.', METS_TEXT_DOMAIN ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get related tickets for a given ticket
	 *
	 * @since    1.0.1
	 * @param    int       $ticket_id             Ticket ID
	 * @param    string    $relationship_type     Optional filter by type
	 * @return   array                            Array of related tickets
	 */
	public function get_related_tickets( $ticket_id, $relationship_type = null ) {
		global $wpdb;

		$where_type = '';
		$params = array( $ticket_id, $ticket_id );

		if ( $relationship_type ) {
			$where_type = 'AND tr.relationship_type = %s';
			$params[] = $relationship_type;
		}

		$sql = "SELECT tr.*, t.ticket_number, t.subject, t.status, t.priority,
				CASE
					WHEN tr.parent_ticket_id = %d THEN 'parent'
					ELSE 'child'
				END as direction
				FROM {$this->table_name} tr
				LEFT JOIN {$wpdb->prefix}mets_tickets t ON
					(CASE
						WHEN tr.parent_ticket_id = %d THEN tr.child_ticket_id
						ELSE tr.parent_ticket_id
					END) = t.id
				WHERE (tr.parent_ticket_id = %d OR tr.child_ticket_id = %d)
				{$where_type}
				ORDER BY tr.created_at DESC";

		$params = array_merge( array( $ticket_id, $ticket_id, $ticket_id, $ticket_id ), $relationship_type ? array( $relationship_type ) : array() );

		$relationships = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

		return $relationships;
	}

	/**
	 * Get a relationship by ID
	 *
	 * @since    1.0.1
	 * @param    int    $relationship_id    Relationship ID
	 * @return   object|null                Relationship object or null if not found
	 */
	public function get( $relationship_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$relationship_id
		) );
	}

	/**
	 * Delete a relationship
	 *
	 * @since    1.0.1
	 * @param    int    $relationship_id    Relationship ID
	 * @return   bool|WP_Error             True on success, WP_Error on failure
	 */
	public function delete( $relationship_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $relationship_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete relationship.', METS_TEXT_DOMAIN ) );
		}

		return true;
	}

	/**
	 * Merge two tickets
	 *
	 * @since    1.0.1
	 * @param    int       $primary_id      Primary ticket ID (keeps this one)
	 * @param    int       $secondary_id    Secondary ticket ID (merges into primary)
	 * @param    string    $notes           Optional notes
	 * @return   bool|WP_Error             True on success, WP_Error on failure
	 */
	public function merge_tickets( $primary_id, $secondary_id, $notes = '' ) {
		global $wpdb;

		// Verify both tickets exist
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$primary_ticket = $ticket_model->get( $primary_id );
		$secondary_ticket = $ticket_model->get( $secondary_id );

		if ( ! $primary_ticket || ! $secondary_ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'One or both tickets not found.', METS_TEXT_DOMAIN ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Move all replies from secondary to primary
			$wpdb->update(
				$wpdb->prefix . 'mets_ticket_replies',
				array( 'ticket_id' => $primary_id ),
				array( 'ticket_id' => $secondary_id ),
				array( '%d' ),
				array( '%d' )
			);

			// Move all attachments from secondary to primary
			$wpdb->update(
				$wpdb->prefix . 'mets_attachments',
				array( 'ticket_id' => $primary_id ),
				array( 'ticket_id' => $secondary_id ),
				array( '%d' ),
				array( '%d' )
			);

			// Update secondary ticket status to 'merged'
			$ticket_model->update( $secondary_id, array(
				'status' => 'merged',
			) );

			// Create relationship record
			$this->create( $primary_id, $secondary_id, 'merged', $notes );

			// Add system note to primary ticket
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
			$reply_model = new METS_Ticket_Reply_Model();

			$merge_note = sprintf(
				__( 'Ticket #%s was merged into this ticket by %s. %s', METS_TEXT_DOMAIN ),
				$secondary_ticket->ticket_number,
				wp_get_current_user()->display_name,
				$notes ? "\n\nNotes: " . $notes : ''
			);

			$reply_model->create( array(
				'ticket_id'        => $primary_id,
				'user_id'          => get_current_user_id(),
				'user_type'        => 'system',
				'content'          => $merge_note,
				'is_internal_note' => true,
			) );

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			// Trigger action
			do_action( 'mets_tickets_merged', $primary_id, $secondary_id, get_current_user_id() );

			return true;

		} catch ( Exception $e ) {
			// Rollback on error
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'merge_failed', $e->getMessage() );
		}
	}

	/**
	 * Split ticket (create child ticket from selected content)
	 *
	 * @since    1.0.1
	 * @param    int       $parent_id          Parent ticket ID
	 * @param    string    $new_subject        Subject for new ticket
	 * @param    array     $reply_ids          Array of reply IDs to move
	 * @param    string    $notes              Optional notes
	 * @return   int|WP_Error                 New ticket ID on success, WP_Error on failure
	 */
	public function split_ticket( $parent_id, $new_subject, $reply_ids = array(), $notes = '' ) {
		global $wpdb;

		// Verify parent ticket exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$parent_ticket = $ticket_model->get( $parent_id );
		if ( ! $parent_ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Parent ticket not found.', METS_TEXT_DOMAIN ) );
		}

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Create new ticket with same customer and entity
			$new_ticket_id = $ticket_model->create( array(
				'entity_id'      => $parent_ticket->entity_id,
				'subject'        => sanitize_text_field( $new_subject ),
				'description'    => sprintf(
					__( 'Split from ticket #%s', METS_TEXT_DOMAIN ),
					$parent_ticket->ticket_number
				),
				'customer_name'  => $parent_ticket->customer_name,
				'customer_email' => $parent_ticket->customer_email,
				'customer_phone' => $parent_ticket->customer_phone,
				'status'         => 'new',
				'priority'       => $parent_ticket->priority,
				'category'       => $parent_ticket->category,
				'created_by'     => get_current_user_id(),
			) );

			if ( is_wp_error( $new_ticket_id ) ) {
				throw new Exception( $new_ticket_id->get_error_message() );
			}

			// Move selected replies to new ticket
			if ( ! empty( $reply_ids ) ) {
				foreach ( $reply_ids as $reply_id ) {
					$wpdb->update(
						$wpdb->prefix . 'mets_ticket_replies',
						array( 'ticket_id' => $new_ticket_id ),
						array( 'id' => intval( $reply_id ) ),
						array( '%d' ),
						array( '%d' )
					);
				}
			}

			// Create relationship
			$this->create( $parent_id, $new_ticket_id, 'split', $notes );

			// Add system notes to both tickets
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
			$reply_model = new METS_Ticket_Reply_Model();

			// Note on parent ticket
			$parent_note = sprintf(
				__( 'Ticket was split. New ticket #%s created by %s.', METS_TEXT_DOMAIN ),
				$ticket_model->get( $new_ticket_id )->ticket_number,
				wp_get_current_user()->display_name
			);

			$reply_model->create( array(
				'ticket_id'        => $parent_id,
				'user_id'          => get_current_user_id(),
				'user_type'        => 'system',
				'content'          => $parent_note,
				'is_internal_note' => true,
			) );

			// Note on new ticket
			$child_note = sprintf(
				__( 'This ticket was split from ticket #%s by %s.', METS_TEXT_DOMAIN ),
				$parent_ticket->ticket_number,
				wp_get_current_user()->display_name
			);

			$reply_model->create( array(
				'ticket_id'        => $new_ticket_id,
				'user_id'          => get_current_user_id(),
				'user_type'        => 'system',
				'content'          => $child_note,
				'is_internal_note' => true,
			) );

			// Commit transaction
			$wpdb->query( 'COMMIT' );

			// Trigger action
			do_action( 'mets_ticket_split', $parent_id, $new_ticket_id, get_current_user_id() );

			return $new_ticket_id;

		} catch ( Exception $e ) {
			// Rollback on error
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'split_failed', $e->getMessage() );
		}
	}

	/**
	 * Link tickets as related
	 *
	 * @since    1.0.1
	 * @param    int       $ticket_id_1    First ticket ID
	 * @param    int       $ticket_id_2    Second ticket ID
	 * @param    string    $notes          Optional notes
	 * @return   bool|WP_Error            True on success, WP_Error on failure
	 */
	public function link_as_related( $ticket_id_1, $ticket_id_2, $notes = '' ) {
		return $this->create( $ticket_id_1, $ticket_id_2, 'related', $notes );
	}

	/**
	 * Mark tickets as duplicates
	 *
	 * @since    1.0.1
	 * @param    int       $original_id    Original ticket ID
	 * @param    int       $duplicate_id   Duplicate ticket ID
	 * @param    string    $notes          Optional notes
	 * @return   bool|WP_Error            True on success, WP_Error on failure
	 */
	public function mark_as_duplicate( $original_id, $duplicate_id, $notes = '' ) {
		global $wpdb;

		// Create relationship
		$result = $this->create( $original_id, $duplicate_id, 'duplicate', $notes );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update duplicate ticket status to 'closed'
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$original_ticket = $ticket_model->get( $original_id );

		$ticket_model->update( $duplicate_id, array(
			'status' => 'closed',
		) );

		// Add note to duplicate ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();

		$duplicate_note = sprintf(
			__( 'Marked as duplicate of ticket #%s by %s.', METS_TEXT_DOMAIN ),
			$original_ticket->ticket_number,
			wp_get_current_user()->display_name
		);

		$reply_model->create( array(
			'ticket_id'        => $duplicate_id,
			'user_id'          => get_current_user_id(),
			'user_type'        => 'system',
			'content'          => $duplicate_note,
			'is_internal_note' => false, // Visible to customer
		) );

		return true;
	}
}
