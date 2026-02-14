<?php
/**
 * Ticket business logic service
 *
 * Encapsulates ticket operations: creation, updates, assignment, status transitions.
 * This is the single entry point for all ticket business logic,
 * used by admin handlers, public handlers, and REST API alike.
 *
 * @package    MultiEntityTicketSystem
 * @since      1.1.0
 */

class METS_Ticket_Service {

	/** @var METS_Ticket_Model */
	private $ticket_model;

	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$this->ticket_model = new METS_Ticket_Model();
	}

	/**
	 * Create a new ticket.
	 *
	 * @param array $data Ticket data (entity_id, subject, description, customer_name, customer_email, etc.)
	 * @return int|WP_Error Ticket ID on success.
	 */
	public function create_ticket( $data ) {
		return $this->ticket_model->create( $data );
	}

	/**
	 * Update ticket properties with change tracking.
	 *
	 * @param int   $ticket_id Ticket ID.
	 * @param array $data      Properties to update (status, priority, category, assigned_to).
	 * @return array|WP_Error  Array with 'changes' key listing human-readable changes.
	 */
	public function update_ticket_properties( $ticket_id, $data ) {
		$current = $this->ticket_model->get( $ticket_id );
		if ( ! $current ) {
			return new WP_Error( 'not_found', __( 'Ticket not found.', METS_TEXT_DOMAIN ) );
		}

		// Track changes
		$changes = array();
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );

		if ( isset( $data['status'] ) && $current->status !== $data['status'] ) {
			$old_label = isset( $statuses[ $current->status ]['label'] ) ? $statuses[ $current->status ]['label'] : ucfirst( $current->status );
			$new_label = isset( $statuses[ $data['status'] ]['label'] ) ? $statuses[ $data['status'] ]['label'] : ucfirst( $data['status'] );
			$changes[] = sprintf( __( 'Status changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_label, $new_label );
		}

		if ( isset( $data['priority'] ) && $current->priority !== $data['priority'] ) {
			$old_label = isset( $priorities[ $current->priority ]['label'] ) ? $priorities[ $current->priority ]['label'] : ucfirst( $current->priority );
			$new_label = isset( $priorities[ $data['priority'] ]['label'] ) ? $priorities[ $data['priority'] ]['label'] : ucfirst( $data['priority'] );
			$changes[] = sprintf( __( 'Priority changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_label, $new_label );
		}

		if ( isset( $data['category'] ) && $current->category !== $data['category'] ) {
			$categories = get_option( 'mets_ticket_categories', array() );
			$old_cat = $current->category && isset( $categories[ $current->category ] ) ? $categories[ $current->category ] : ( $current->category ?: __( 'None', METS_TEXT_DOMAIN ) );
			$new_cat = $data['category'] && isset( $categories[ $data['category'] ] ) ? $categories[ $data['category'] ] : ( $data['category'] ?: __( 'None', METS_TEXT_DOMAIN ) );
			$changes[] = sprintf( __( 'Category changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_cat, $new_cat );
		}

		if ( isset( $data['assigned_to'] ) && $current->assigned_to !== $data['assigned_to'] ) {
			$old_user_obj = $current->assigned_to ? get_user_by( 'ID', $current->assigned_to ) : false;
			$old_user = $old_user_obj ? $old_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
			$new_user_obj = $data['assigned_to'] ? get_user_by( 'ID', $data['assigned_to'] ) : false;
			$new_user = $new_user_obj ? $new_user_obj->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
			$changes[] = sprintf( __( 'Assignment changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_user, $new_user );
		}

		$result = $this->ticket_model->update( $ticket_id, $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'changes' => $changes );
	}
}
