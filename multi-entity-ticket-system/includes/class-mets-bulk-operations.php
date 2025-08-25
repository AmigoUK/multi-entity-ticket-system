<?php
/**
 * Bulk Operations Handler
 *
 * Handles bulk operations for tickets, entities, and other system components
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * The Bulk Operations class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Bulk_Operations {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Bulk_Operations    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Bulk_Operations    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the bulk operations
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Admin AJAX handlers
		add_action( 'wp_ajax_mets_bulk_ticket_action', array( $this, 'ajax_bulk_ticket_action' ) );
		add_action( 'wp_ajax_mets_bulk_entity_action', array( $this, 'ajax_bulk_entity_action' ) );
		add_action( 'wp_ajax_mets_bulk_kb_action', array( $this, 'ajax_bulk_kb_action' ) );
		add_action( 'wp_ajax_mets_export_bulk_data', array( $this, 'ajax_export_bulk_data' ) );
		add_action( 'wp_ajax_mets_import_data', array( $this, 'ajax_import_data' ) );
		
		// Scheduled bulk operations
		add_action( 'mets_bulk_cleanup', array( $this, 'scheduled_cleanup' ) );
		add_action( 'mets_bulk_archive', array( $this, 'scheduled_archive' ) );
		
		// Add bulk action dropdown to admin lists
		add_filter( 'bulk_actions-edit-mets_ticket', array( $this, 'add_ticket_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-mets_ticket', array( $this, 'handle_ticket_bulk_actions' ), 10, 3 );
	}

	/**
	 * Handle bulk ticket actions
	 *
	 * @since    1.0.0
	 */
	public function ajax_bulk_ticket_action() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_bulk_action' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$action = sanitize_text_field( $_POST['action_type'] );
		$ticket_ids = array_map( 'intval', $_POST['ticket_ids'] ?? array() );

		if ( empty( $ticket_ids ) ) {
			wp_send_json_error( __( 'No tickets selected.', METS_TEXT_DOMAIN ) );
		}

		$result = $this->process_bulk_ticket_action( $action, $ticket_ids, $_POST );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * Process bulk ticket action
	 *
	 * @since    1.0.0
	 * @param    string    $action       Action to perform
	 * @param    array     $ticket_ids   Ticket IDs
	 * @param    array     $data         Additional data
	 * @return   array                   Result array
	 */
	private function process_bulk_ticket_action( $action, $ticket_ids, $data = array() ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$processed = 0;
		$errors = array();

		switch ( $action ) {
			case 'update_status':
				$new_status = sanitize_text_field( $data['new_status'] ?? '' );
				if ( empty( $new_status ) ) {
					return array( 'success' => false, 'message' => __( 'Status is required.', METS_TEXT_DOMAIN ) );
				}

				foreach ( $ticket_ids as $ticket_id ) {
					$updated = $ticket_model->update( $ticket_id, array( 'status' => $new_status ) );
					if ( $updated ) {
						$processed++;
						do_action( 'mets_ticket_status_changed', $ticket_id, '', $new_status, get_current_user_id() );
					} else {
						$errors[] = sprintf( __( 'Failed to update ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'update_priority':
				$new_priority = sanitize_text_field( $data['new_priority'] ?? '' );
				if ( empty( $new_priority ) ) {
					return array( 'success' => false, 'message' => __( 'Priority is required.', METS_TEXT_DOMAIN ) );
				}

				foreach ( $ticket_ids as $ticket_id ) {
					$updated = $ticket_model->update( $ticket_id, array( 'priority' => $new_priority ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to update ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'assign_agent':
				$agent_id = intval( $data['agent_id'] ?? 0 );
				if ( ! $agent_id ) {
					return array( 'success' => false, 'message' => __( 'Agent is required.', METS_TEXT_DOMAIN ) );
				}

				foreach ( $ticket_ids as $ticket_id ) {
					$updated = $ticket_model->update( $ticket_id, array( 'assigned_to' => $agent_id ) );
					if ( $updated ) {
						$processed++;
						do_action( 'mets_ticket_assigned', $ticket_id, $agent_id );
					} else {
						$errors[] = sprintf( __( 'Failed to assign ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'move_entity':
				$entity_id = intval( $data['entity_id'] ?? 0 );
				if ( ! $entity_id ) {
					return array( 'success' => false, 'message' => __( 'Entity is required.', METS_TEXT_DOMAIN ) );
				}

				foreach ( $ticket_ids as $ticket_id ) {
					$updated = $ticket_model->update( $ticket_id, array( 'entity_id' => $entity_id ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to move ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'add_reply':
				$reply_content = wp_kses_post( $data['reply_content'] ?? '' );
				if ( empty( $reply_content ) ) {
					return array( 'success' => false, 'message' => __( 'Reply content is required.', METS_TEXT_DOMAIN ) );
				}

				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
				$reply_model = new METS_Ticket_Reply_Model();

				foreach ( $ticket_ids as $ticket_id ) {
					$reply_data = array(
						'ticket_id' => $ticket_id,
						'user_id' => get_current_user_id(),
						'content' => $reply_content,
						'is_internal_note' => isset( $data['is_internal'] ) ? 1 : 0
					);

					$reply_id = $reply_model->create( $reply_data );
					if ( $reply_id ) {
						$processed++;
						do_action( 'mets_ticket_replied', $ticket_id, $reply_data );
					} else {
						$errors[] = sprintf( __( 'Failed to add reply to ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'merge_tickets':
				if ( count( $ticket_ids ) < 2 ) {
					return array( 'success' => false, 'message' => __( 'At least 2 tickets required for merging.', METS_TEXT_DOMAIN ) );
				}

				$result = $this->merge_tickets( $ticket_ids );
				return $result;

			case 'archive':
				foreach ( $ticket_ids as $ticket_id ) {
					$updated = $ticket_model->update( $ticket_id, array( 
						'status' => 'archived',
						'archived_at' => current_time( 'mysql' )
					) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to archive ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			case 'delete':
				foreach ( $ticket_ids as $ticket_id ) {
					$deleted = $ticket_model->delete( $ticket_id );
					if ( $deleted ) {
						$processed++;
						do_action( 'mets_ticket_deleted', $ticket_id );
					} else {
						$errors[] = sprintf( __( 'Failed to delete ticket #%d', METS_TEXT_DOMAIN ), $ticket_id );
					}
				}
				break;

			default:
				return array( 'success' => false, 'message' => __( 'Unknown action.', METS_TEXT_DOMAIN ) );
		}

		$message = sprintf( 
			__( 'Processed %d of %d tickets successfully.', METS_TEXT_DOMAIN ), 
			$processed, 
			count( $ticket_ids ) 
		);

		if ( ! empty( $errors ) ) {
			$message .= ' ' . __( 'Errors:', METS_TEXT_DOMAIN ) . ' ' . implode( ', ', array_slice( $errors, 0, 3 ) );
			if ( count( $errors ) > 3 ) {
				$message .= sprintf( __( ' and %d more.', METS_TEXT_DOMAIN ), count( $errors ) - 3 );
			}
		}

		return array(
			'success' => true,
			'processed' => $processed,
			'total' => count( $ticket_ids ),
			'errors' => $errors,
			'message' => $message
		);
	}

	/**
	 * Merge multiple tickets into one
	 *
	 * @since    1.0.0
	 * @param    array    $ticket_ids    Ticket IDs to merge
	 * @return   array                   Result array
	 */
	private function merge_tickets( $ticket_ids ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();

		// Get all tickets
		$tickets = array();
		foreach ( $ticket_ids as $ticket_id ) {
			$ticket = $ticket_model->get( $ticket_id );
			if ( $ticket ) {
				$tickets[] = $ticket;
			}
		}

		if ( count( $tickets ) < 2 ) {
			return array( 'success' => false, 'message' => __( 'Invalid tickets for merging.', METS_TEXT_DOMAIN ) );
		}

		// Use the first (oldest) ticket as the main ticket
		usort( $tickets, function( $a, $b ) {
			return strtotime( $a->created_at ) - strtotime( $b->created_at );
		} );

		$main_ticket = $tickets[0];
		$merge_tickets = array_slice( $tickets, 1 );

		// Combine subjects and descriptions
		$combined_subject = $main_ticket->subject;
		$combined_description = $main_ticket->description;

		foreach ( $merge_tickets as $ticket ) {
			$combined_subject .= ' + ' . $ticket->subject;
			$combined_description .= "\n\n--- " . sprintf( __( 'Merged from ticket #%d', METS_TEXT_DOMAIN ), $ticket->id ) . " ---\n";
			$combined_description .= $ticket->description;
		}

		// Update main ticket
		$updated = $ticket_model->update( $main_ticket->id, array(
			'subject' => $combined_subject,
			'description' => $combined_description,
			'priority' => $this->get_highest_priority( $tickets ),
			'updated_at' => current_time( 'mysql' )
		) );

		if ( ! $updated ) {
			return array( 'success' => false, 'message' => __( 'Failed to update main ticket.', METS_TEXT_DOMAIN ) );
		}

		// Move all replies to main ticket
		foreach ( $merge_tickets as $ticket ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'mets_ticket_replies',
				array( 'ticket_id' => $main_ticket->id ),
				array( 'ticket_id' => $ticket->id ),
				array( '%d' ),
				array( '%d' )
			);

			// Add merge note
			$reply_model->create( array(
				'ticket_id' => $main_ticket->id,
				'user_id' => get_current_user_id(),
				'content' => sprintf( __( 'Ticket #%d merged into this ticket.', METS_TEXT_DOMAIN ), $ticket->id ),
				'is_internal_note' => 1
			) );

			// Delete merged ticket
			$ticket_model->delete( $ticket->id );
		}

		do_action( 'mets_tickets_merged', $main_ticket->id, array_column( $merge_tickets, 'id' ) );

		return array(
			'success' => true,
			'message' => sprintf( 
				__( 'Successfully merged %d tickets into ticket #%d.', METS_TEXT_DOMAIN ), 
				count( $merge_tickets ), 
				$main_ticket->id 
			),
			'merged_ticket_id' => $main_ticket->id
		);
	}

	/**
	 * Get highest priority from tickets
	 *
	 * @since    1.0.0
	 * @param    array    $tickets    Ticket objects
	 * @return   string               Highest priority
	 */
	private function get_highest_priority( $tickets ) {
		$priorities = array( 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4 );
		$highest = 'low';
		$highest_value = 1;

		foreach ( $tickets as $ticket ) {
			$value = $priorities[ $ticket->priority ] ?? 1;
			if ( $value > $highest_value ) {
				$highest_value = $value;
				$highest = $ticket->priority;
			}
		}

		return $highest;
	}

	/**
	 * Handle bulk entity actions
	 *
	 * @since    1.0.0
	 */
	public function ajax_bulk_entity_action() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_bulk_action' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		if ( ! current_user_can( 'manage_entities' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$action = sanitize_text_field( $_POST['action_type'] );
		$entity_ids = array_map( 'intval', $_POST['entity_ids'] ?? array() );

		if ( empty( $entity_ids ) ) {
			wp_send_json_error( __( 'No entities selected.', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		$processed = 0;
		$errors = array();

		switch ( $action ) {
			case 'activate':
				foreach ( $entity_ids as $entity_id ) {
					$updated = $entity_model->update( $entity_id, array( 'status' => 'active' ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to activate entity #%d', METS_TEXT_DOMAIN ), $entity_id );
					}
				}
				break;

			case 'deactivate':
				foreach ( $entity_ids as $entity_id ) {
					$updated = $entity_model->update( $entity_id, array( 'status' => 'inactive' ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to deactivate entity #%d', METS_TEXT_DOMAIN ), $entity_id );
					}
				}
				break;

			case 'change_parent':
				$parent_id = intval( $_POST['parent_id'] ?? 0 );
				foreach ( $entity_ids as $entity_id ) {
					$updated = $entity_model->update( $entity_id, array( 'parent_id' => $parent_id ?: null ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to update entity #%d', METS_TEXT_DOMAIN ), $entity_id );
					}
				}
				break;

			case 'delete':
				foreach ( $entity_ids as $entity_id ) {
					// Check if entity has active tickets
					global $wpdb;
					$ticket_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE entity_id = %d AND status NOT IN ('closed', 'archived')",
						$entity_id
					) );

					if ( $ticket_count > 0 ) {
						$errors[] = sprintf( __( 'Entity #%d has active tickets and cannot be deleted', METS_TEXT_DOMAIN ), $entity_id );
						continue;
					}

					$deleted = $entity_model->delete( $entity_id );
					if ( $deleted ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to delete entity #%d', METS_TEXT_DOMAIN ), $entity_id );
					}
				}
				break;

			default:
				wp_send_json_error( __( 'Unknown action.', METS_TEXT_DOMAIN ) );
		}

		$message = sprintf( 
			__( 'Processed %d of %d entities successfully.', METS_TEXT_DOMAIN ), 
			$processed, 
			count( $entity_ids ) 
		);

		if ( ! empty( $errors ) ) {
			$message .= ' ' . __( 'Errors:', METS_TEXT_DOMAIN ) . ' ' . implode( ', ', array_slice( $errors, 0, 3 ) );
		}

		wp_send_json_success( array(
			'processed' => $processed,
			'total' => count( $entity_ids ),
			'errors' => $errors,
			'message' => $message
		) );
	}

	/**
	 * Handle bulk KB actions
	 *
	 * @since    1.0.0
	 */
	public function ajax_bulk_kb_action() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_bulk_action' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		if ( ! current_user_can( 'edit_kb_articles' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$action = sanitize_text_field( $_POST['action_type'] );
		$article_ids = array_map( 'intval', $_POST['article_ids'] ?? array() );

		if ( empty( $article_ids ) ) {
			wp_send_json_error( __( 'No articles selected.', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$processed = 0;
		$errors = array();

		switch ( $action ) {
			case 'publish':
				foreach ( $article_ids as $article_id ) {
					$updated = $article_model->update( $article_id, array( 'status' => 'published' ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to publish article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			case 'draft':
				foreach ( $article_ids as $article_id ) {
					$updated = $article_model->update( $article_id, array( 'status' => 'draft' ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to draft article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			case 'feature':
				foreach ( $article_ids as $article_id ) {
					$updated = $article_model->update( $article_id, array( 'featured' => 1 ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to feature article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			case 'unfeature':
				foreach ( $article_ids as $article_id ) {
					$updated = $article_model->update( $article_id, array( 'featured' => 0 ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to unfeature article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			case 'change_category':
				$category_id = intval( $_POST['category_id'] ?? 0 );
				foreach ( $article_ids as $article_id ) {
					$updated = $article_model->update( $article_id, array( 'category_id' => $category_id ?: null ) );
					if ( $updated ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to update article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			case 'delete':
				foreach ( $article_ids as $article_id ) {
					$deleted = $article_model->delete( $article_id );
					if ( $deleted ) {
						$processed++;
					} else {
						$errors[] = sprintf( __( 'Failed to delete article #%d', METS_TEXT_DOMAIN ), $article_id );
					}
				}
				break;

			default:
				wp_send_json_error( __( 'Unknown action.', METS_TEXT_DOMAIN ) );
		}

		$message = sprintf( 
			__( 'Processed %d of %d articles successfully.', METS_TEXT_DOMAIN ), 
			$processed, 
			count( $article_ids ) 
		);

		if ( ! empty( $errors ) ) {
			$message .= ' ' . __( 'Errors:', METS_TEXT_DOMAIN ) . ' ' . implode( ', ', array_slice( $errors, 0, 3 ) );
		}

		wp_send_json_success( array(
			'processed' => $processed,
			'total' => count( $article_ids ),
			'errors' => $errors,
			'message' => $message
		) );
	}

	/**
	 * Export bulk data
	 *
	 * @since    1.0.0
	 */
	public function ajax_export_bulk_data() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_bulk_action' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$export_type = sanitize_text_field( $_POST['export_type'] );
		$format = sanitize_text_field( $_POST['format'] ?? 'csv' );
		$filters = $_POST['filters'] ?? array();

		$export_data = $this->prepare_export_data( $export_type, $filters );

		if ( empty( $export_data ) ) {
			wp_send_json_error( __( 'No data to export.', METS_TEXT_DOMAIN ) );
		}

		$filename = $this->generate_export_file( $export_data, $format, $export_type );

		if ( $filename ) {
			wp_send_json_success( array(
				'filename' => $filename,
				'download_url' => wp_nonce_url( 
					admin_url( 'admin.php?action=mets_download_export&file=' . urlencode( $filename ) ),
					'mets_download_export'
				),
				'message' => __( 'Export completed successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to generate export file.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * Prepare export data
	 *
	 * @since    1.0.0
	 * @param    string    $type       Export type
	 * @param    array     $filters    Filters to apply
	 * @return   array                 Export data
	 */
	private function prepare_export_data( $type, $filters = array() ) {
		global $wpdb;
		$data = array();

		switch ( $type ) {
			case 'tickets':
				$where_clauses = array( '1=1' );
				$where_values = array();

				if ( ! empty( $filters['status'] ) ) {
					$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
					$where_clauses[] = "t.status IN ($placeholders)";
					$where_values = array_merge( $where_values, $filters['status'] );
				}

				if ( ! empty( $filters['entity_id'] ) ) {
					$where_clauses[] = 't.entity_id = %d';
					$where_values[] = intval( $filters['entity_id'] );
				}

				if ( ! empty( $filters['date_from'] ) ) {
					$where_clauses[] = 't.created_at >= %s';
					$where_values[] = sanitize_text_field( $filters['date_from'] );
				}

				if ( ! empty( $filters['date_to'] ) ) {
					$where_clauses[] = 't.created_at <= %s';
					$where_values[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
				}

				$where_sql = implode( ' AND ', $where_clauses );

				$query = "
					SELECT t.*, e.name as entity_name, 
						   c.display_name as customer_name, c.user_email as customer_email,
						   a.display_name as agent_name
					FROM {$wpdb->prefix}mets_tickets t
					LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
					LEFT JOIN {$wpdb->users} c ON t.customer_id = c.ID
					LEFT JOIN {$wpdb->users} a ON t.assigned_to = a.ID
					WHERE $where_sql
					ORDER BY t.created_at DESC
				";

				if ( ! empty( $where_values ) ) {
					$query = $wpdb->prepare( $query, $where_values );
				}

				$data = $wpdb->get_results( $query, ARRAY_A );
				break;

			case 'entities':
				$data = $wpdb->get_results(
					"SELECT e.*, 
						    (SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE entity_id = e.id) as ticket_count
					 FROM {$wpdb->prefix}mets_entities e
					 ORDER BY e.name",
					ARRAY_A
				);
				break;

			case 'kb_articles':
				$data = $wpdb->get_results(
					"SELECT a.*, c.name as category_name, e.name as entity_name
					 FROM {$wpdb->prefix}mets_kb_articles a
					 LEFT JOIN {$wpdb->prefix}mets_kb_categories c ON a.category_id = c.id
					 LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
					 ORDER BY a.created_at DESC",
					ARRAY_A
				);
				break;

			case 'analytics':
				// Get comprehensive analytics data
				$data = array(
					'summary' => $this->get_analytics_summary(),
					'monthly_stats' => $this->get_monthly_stats(),
					'sla_performance' => $this->get_sla_performance_data(),
					'kb_performance' => $this->get_kb_performance_data()
				);
				break;
		}

		return $data;
	}

	/**
	 * Generate export file
	 *
	 * @since    1.0.0
	 * @param    array     $data        Data to export
	 * @param    string    $format      Export format
	 * @param    string    $type        Export type
	 * @return   string|false           Filename or false on failure
	 */
	private function generate_export_file( $data, $format, $type ) {
		if ( empty( $data ) ) {
			return false;
		}

		$uploads_dir = wp_upload_dir();
		$export_dir = $uploads_dir['basedir'] . '/mets-exports';

		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = 'mets-' . $type . '-' . date( 'Y-m-d-H-i-s' ) . '.' . $format;
		$filepath = $export_dir . '/' . $filename;

		switch ( $format ) {
			case 'csv':
				$success = $this->generate_csv_file( $data, $filepath );
				break;

			case 'json':
				$success = file_put_contents( $filepath, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
				break;

			case 'xml':
				$success = $this->generate_xml_file( $data, $filepath, $type );
				break;

			default:
				return false;
		}

		return $success ? $filename : false;
	}

	/**
	 * Generate CSV file
	 *
	 * @since    1.0.0
	 * @param    array     $data        Data to export
	 * @param    string    $filepath    File path
	 * @return   bool                   Success status
	 */
	private function generate_csv_file( $data, $filepath ) {
		$file = fopen( $filepath, 'w' );
		if ( ! $file ) {
			return false;
		}

		if ( ! empty( $data ) ) {
			// Write headers
			fputcsv( $file, array_keys( $data[0] ) );
			
			// Write data
			foreach ( $data as $row ) {
				fputcsv( $file, $row );
			}
		}

		fclose( $file );
		return true;
	}

	/**
	 * Generate XML file
	 *
	 * @since    1.0.0
	 * @param    array     $data        Data to export
	 * @param    string    $filepath    File path
	 * @param    string    $root_name   Root element name
	 * @return   bool                   Success status
	 */
	private function generate_xml_file( $data, $filepath, $root_name ) {
		$xml = new SimpleXMLElement( '<' . $root_name . '></' . $root_name . '>' );
		
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$item = $xml->addChild( 'item' );
				foreach ( $value as $sub_key => $sub_value ) {
					$item->addChild( $sub_key, htmlspecialchars( $sub_value ) );
				}
			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}
		}

		return $xml->asXML( $filepath );
	}

	/**
	 * Import data from file
	 *
	 * @since    1.0.0
	 */
	public function ajax_import_data() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_bulk_action' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( __( 'No file uploaded.', METS_TEXT_DOMAIN ) );
		}

		$file = $_FILES['import_file'];
		$import_type = sanitize_text_field( $_POST['import_type'] );

		// Validate file
		$allowed_types = array( 'text/csv', 'application/json', 'text/xml', 'application/xml' );
		if ( ! in_array( $file['type'], $allowed_types ) ) {
			wp_send_json_error( __( 'Invalid file type. Only CSV, JSON, and XML files are allowed.', METS_TEXT_DOMAIN ) );
		}

		$result = $this->process_import_file( $file, $import_type );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * Process import file
	 *
	 * @since    1.0.0
	 * @param    array     $file         File data
	 * @param    string    $type         Import type
	 * @return   array                   Result array
	 */
	private function process_import_file( $file, $type ) {
		$filepath = $file['tmp_name'];
		
		// Parse file based on type
		$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		
		switch ( strtolower( $extension ) ) {
			case 'csv':
				$data = $this->parse_csv_file( $filepath );
				break;
			case 'json':
				$data = $this->parse_json_file( $filepath );
				break;
			case 'xml':
				$data = $this->parse_xml_file( $filepath );
				break;
			default:
				return array( 'success' => false, 'message' => __( 'Unsupported file format.', METS_TEXT_DOMAIN ) );
		}

		if ( empty( $data ) ) {
			return array( 'success' => false, 'message' => __( 'No valid data found in file.', METS_TEXT_DOMAIN ) );
		}

		// Import data based on type
		switch ( $type ) {
			case 'entities':
				$result = $this->import_entities( $data );
				break;
			case 'tickets':
				$result = $this->import_tickets( $data );
				break;
			case 'kb_articles':
				$result = $this->import_kb_articles( $data );
				break;
			default:
				return array( 'success' => false, 'message' => __( 'Unknown import type.', METS_TEXT_DOMAIN ) );
		}

		return $result;
	}

	/**
	 * Parse CSV file
	 *
	 * @since    1.0.0
	 * @param    string    $filepath    File path
	 * @return   array                  Parsed data
	 */
	private function parse_csv_file( $filepath ) {
		$data = array();
		$file = fopen( $filepath, 'r' );
		
		if ( $file ) {
			$headers = fgetcsv( $file );
			while ( ( $row = fgetcsv( $file ) ) !== false ) {
				if ( count( $headers ) === count( $row ) ) {
					$data[] = array_combine( $headers, $row );
				}
			}
			fclose( $file );
		}

		return $data;
	}

	/**
	 * Parse JSON file
	 *
	 * @since    1.0.0
	 * @param    string    $filepath    File path
	 * @return   array                  Parsed data
	 */
	private function parse_json_file( $filepath ) {
		$content = file_get_contents( $filepath );
		return json_decode( $content, true ) ?: array();
	}

	/**
	 * Parse XML file
	 *
	 * @since    1.0.0
	 * @param    string    $filepath    File path
	 * @return   array                  Parsed data
	 */
	private function parse_xml_file( $filepath ) {
		$data = array();
		$xml = simplexml_load_file( $filepath );
		
		if ( $xml ) {
			foreach ( $xml->children() as $child ) {
				$item = array();
				foreach ( $child as $key => $value ) {
					$item[ (string) $key ] = (string) $value;
				}
				$data[] = $item;
			}
		}

		return $data;
	}

	/**
	 * Import entities
	 *
	 * @since    1.0.0
	 * @param    array    $data    Entity data
	 * @return   array             Result array
	 */
	private function import_entities( $data ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		$imported = 0;
		$errors = array();

		foreach ( $data as $entity_data ) {
			// Validate required fields
			if ( empty( $entity_data['name'] ) || empty( $entity_data['type'] ) ) {
				$errors[] = __( 'Missing required fields: name and type', METS_TEXT_DOMAIN );
				continue;
			}

			// Check if entity already exists
			global $wpdb;
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}mets_entities WHERE name = %s",
				$entity_data['name']
			) );

			if ( $existing ) {
				$errors[] = sprintf( __( 'Entity "%s" already exists', METS_TEXT_DOMAIN ), $entity_data['name'] );
				continue;
			}

			// Prepare entity data
			$prepared_data = array(
				'name' => sanitize_text_field( $entity_data['name'] ),
				'type' => sanitize_text_field( $entity_data['type'] ),
				'description' => sanitize_textarea_field( $entity_data['description'] ?? '' ),
				'contact_email' => sanitize_email( $entity_data['contact_email'] ?? '' ),
				'status' => in_array( $entity_data['status'] ?? 'active', array( 'active', 'inactive' ) ) 
					? $entity_data['status'] : 'active',
				'metadata' => ! empty( $entity_data['metadata'] ) ? $entity_data['metadata'] : '{}'
			);

			$entity_id = $entity_model->create( $prepared_data );
			if ( $entity_id ) {
				$imported++;
			} else {
				$errors[] = sprintf( __( 'Failed to import entity "%s"', METS_TEXT_DOMAIN ), $entity_data['name'] );
			}
		}

		return array(
			'success' => true,
			'imported' => $imported,
			'total' => count( $data ),
			'errors' => $errors,
			'message' => sprintf( __( 'Successfully imported %d of %d entities.', METS_TEXT_DOMAIN ), $imported, count( $data ) )
		);
	}

	/**
	 * Import tickets
	 *
	 * @since    1.0.0
	 * @param    array    $data    Ticket data
	 * @return   array             Result array
	 */
	private function import_tickets( $data ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$imported = 0;
		$errors = array();

		foreach ( $data as $ticket_data ) {
			// Validate required fields
			if ( empty( $ticket_data['subject'] ) || empty( $ticket_data['description'] ) ) {
				$errors[] = __( 'Missing required fields: subject and description', METS_TEXT_DOMAIN );
				continue;
			}

			// Prepare ticket data
			$prepared_data = array(
				'subject' => sanitize_text_field( $ticket_data['subject'] ),
				'description' => wp_kses_post( $ticket_data['description'] ),
				'status' => in_array( $ticket_data['status'] ?? 'open', array( 'open', 'in_progress', 'resolved', 'closed', 'on_hold' ) ) 
					? $ticket_data['status'] : 'open',
				'priority' => in_array( $ticket_data['priority'] ?? 'medium', array( 'low', 'medium', 'high', 'critical' ) ) 
					? $ticket_data['priority'] : 'medium',
				'entity_id' => intval( $ticket_data['entity_id'] ?? 1 ),
				'customer_id' => intval( $ticket_data['customer_id'] ?? 0 ),
				'source' => 'import'
			);

			$ticket_id = $ticket_model->create( $prepared_data );
			if ( $ticket_id ) {
				$imported++;
				do_action( 'mets_ticket_created', $ticket_id, $prepared_data );
			} else {
				$errors[] = sprintf( __( 'Failed to import ticket "%s"', METS_TEXT_DOMAIN ), $ticket_data['subject'] );
			}
		}

		return array(
			'success' => true,
			'imported' => $imported,
			'total' => count( $data ),
			'errors' => $errors,
			'message' => sprintf( __( 'Successfully imported %d of %d tickets.', METS_TEXT_DOMAIN ), $imported, count( $data ) )
		);
	}

	/**
	 * Import KB articles
	 *
	 * @since    1.0.0
	 * @param    array    $data    Article data
	 * @return   array             Result array
	 */
	private function import_kb_articles( $data ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$imported = 0;
		$errors = array();

		foreach ( $data as $article_data ) {
			// Validate required fields
			if ( empty( $article_data['title'] ) || empty( $article_data['content'] ) ) {
				$errors[] = __( 'Missing required fields: title and content', METS_TEXT_DOMAIN );
				continue;
			}

			// Check if article already exists
			$existing = $article_model->get_by_slug( sanitize_title( $article_data['title'] ) );
			if ( $existing ) {
				$errors[] = sprintf( __( 'Article "%s" already exists', METS_TEXT_DOMAIN ), $article_data['title'] );
				continue;
			}

			// Prepare article data
			$prepared_data = array(
				'title' => sanitize_text_field( $article_data['title'] ),
				'content' => wp_kses_post( $article_data['content'] ),
				'slug' => sanitize_title( $article_data['title'] ),
				'status' => in_array( $article_data['status'] ?? 'published', array( 'draft', 'pending_review', 'published' ) ) 
					? $article_data['status'] : 'published',
				'visibility' => in_array( $article_data['visibility'] ?? 'customer', array( 'internal', 'staff', 'customer' ) ) 
					? $article_data['visibility'] : 'customer',
				'entity_id' => intval( $article_data['entity_id'] ?? 0 ) ?: null,
				'category_id' => intval( $article_data['category_id'] ?? 0 ) ?: null,
				'featured' => ! empty( $article_data['featured'] ) ? 1 : 0,
				'author_id' => get_current_user_id()
			);

			$article_id = $article_model->create( $prepared_data );
			if ( $article_id ) {
				$imported++;
			} else {
				$errors[] = sprintf( __( 'Failed to import article "%s"', METS_TEXT_DOMAIN ), $article_data['title'] );
			}
		}

		return array(
			'success' => true,
			'imported' => $imported,
			'total' => count( $data ),
			'errors' => $errors,
			'message' => sprintf( __( 'Successfully imported %d of %d articles.', METS_TEXT_DOMAIN ), $imported, count( $data ) )
		);
	}

	/**
	 * Scheduled cleanup
	 *
	 * @since    1.0.0
	 */
	public function scheduled_cleanup() {
		global $wpdb;

		// Clean up old export files (older than 7 days)
		$uploads_dir = wp_upload_dir();
		$export_dir = $uploads_dir['basedir'] . '/mets-exports';

		if ( file_exists( $export_dir ) ) {
			$files = glob( $export_dir . '/mets-*' );
			$week_ago = time() - ( 7 * 24 * 60 * 60 );

			foreach ( $files as $file ) {
				if ( filemtime( $file ) < $week_ago ) {
					unlink( $file );
				}
			}
		}

		// Clean up orphaned ticket replies
		$wpdb->query(
			"DELETE tr FROM {$wpdb->prefix}mets_ticket_replies tr
			 LEFT JOIN {$wpdb->prefix}mets_tickets t ON tr.ticket_id = t.id
			 WHERE t.id IS NULL"
		);

		// Clean up orphaned KB analytics
		$wpdb->query(
			"DELETE ka FROM {$wpdb->prefix}mets_kb_analytics ka
			 LEFT JOIN {$wpdb->prefix}mets_kb_articles a ON ka.article_id = a.id
			 WHERE a.id IS NULL"
		);

		do_action( 'mets_bulk_cleanup_completed' );
	}

	/**
	 * Scheduled archive
	 *
	 * @since    1.0.0
	 */
	public function scheduled_archive() {
		$settings = get_option( 'mets_bulk_settings', array() );
		$auto_archive_days = intval( $settings['auto_archive_days'] ?? 0 );

		if ( $auto_archive_days > 0 ) {
			global $wpdb;
			
			$archive_date = date( 'Y-m-d H:i:s', strtotime( "-{$auto_archive_days} days" ) );
			
			$archived = $wpdb->update(
				$wpdb->prefix . 'mets_tickets',
				array( 
					'status' => 'archived',
					'archived_at' => current_time( 'mysql' )
				),
				array( 'status' => 'closed' ),
				array( '%s', '%s' ),
				array( '%s' )
			);

			if ( $archived > 0 ) {
				do_action( 'mets_bulk_archive_completed', $archived );
			}
		}
	}

	/**
	 * Add ticket bulk actions
	 *
	 * @since    1.0.0
	 * @param    array    $actions    Existing actions
	 * @return   array                Modified actions
	 */
	public function add_ticket_bulk_actions( $actions ) {
		$actions['mets_bulk_status'] = __( 'Change Status', METS_TEXT_DOMAIN );
		$actions['mets_bulk_priority'] = __( 'Change Priority', METS_TEXT_DOMAIN );
		$actions['mets_bulk_assign'] = __( 'Assign Agent', METS_TEXT_DOMAIN );
		$actions['mets_bulk_archive'] = __( 'Archive', METS_TEXT_DOMAIN );
		return $actions;
	}

	/**
	 * Handle ticket bulk actions
	 *
	 * @since    1.0.0
	 * @param    string    $redirect_to    Redirect URL
	 * @param    string    $action         Action name
	 * @param    array     $post_ids       Post IDs
	 * @return   string                    Modified redirect URL
	 */
	public function handle_ticket_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( strpos( $action, 'mets_bulk_' ) === 0 ) {
			$processed = count( $post_ids );
			$redirect_to = add_query_arg( array(
				'mets_bulk_action' => $action,
				'processed' => $processed
			), $redirect_to );
		}
		return $redirect_to;
	}

	/**
	 * Get analytics summary
	 *
	 * @since    1.0.0
	 * @return   array    Analytics summary
	 */
	private function get_analytics_summary() {
		global $wpdb;

		return array(
			'total_tickets' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets" ),
			'open_tickets' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE status = 'open'" ),
			'resolved_tickets' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE status = 'resolved'" ),
			'total_entities' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_entities WHERE status = 'active'" ),
			'total_articles' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_articles WHERE status = 'published'" ),
			'avg_resolution_time' => $wpdb->get_var( 
				"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) 
				 FROM {$wpdb->prefix}mets_tickets 
				 WHERE status IN ('resolved', 'closed') AND resolved_at IS NOT NULL" 
			)
		);
	}

	/**
	 * Get monthly stats
	 *
	 * @since    1.0.0
	 * @return   array    Monthly statistics
	 */
	private function get_monthly_stats() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT 
				DATE_FORMAT(created_at, '%Y-%m') as month,
				COUNT(*) as ticket_count,
				SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_count
			 FROM {$wpdb->prefix}mets_tickets 
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
			 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
			 ORDER BY month",
			ARRAY_A
		);
	}

	/**
	 * Get SLA performance data
	 *
	 * @since    1.0.0
	 * @return   array    SLA performance data
	 */
	private function get_sla_performance_data() {
		global $wpdb;

		return array(
			'sla_compliance' => $wpdb->get_var( 
				"SELECT (COUNT(CASE WHEN sla_status IN ('met', 'response_met', 'resolution_met') THEN 1 END) * 100.0 / COUNT(*))
				 FROM {$wpdb->prefix}mets_tickets 
				 WHERE sla_due_date IS NOT NULL" 
			),
			'breached_tickets' => $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				 WHERE sla_status = 'breached'" 
			),
			'avg_response_time' => $wpdb->get_var( 
				"SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) 
				 FROM {$wpdb->prefix}mets_tickets 
				 WHERE first_response_at IS NOT NULL" 
			)
		);
	}

	/**
	 * Get KB performance data
	 *
	 * @since    1.0.0
	 * @return   array    KB performance data
	 */
	private function get_kb_performance_data() {
		global $wpdb;

		return array(
			'total_views' => $wpdb->get_var( "SELECT SUM(view_count) FROM {$wpdb->prefix}mets_kb_articles" ),
			'total_searches' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_analytics WHERE event_type = 'search'" ),
			'avg_helpfulness' => $wpdb->get_var( 
				"SELECT (SUM(helpful_count) * 100.0 / (SUM(helpful_count) + SUM(not_helpful_count)))
				 FROM {$wpdb->prefix}mets_kb_articles 
				 WHERE (helpful_count + not_helpful_count) > 0" 
			),
			'popular_articles' => $wpdb->get_results(
				"SELECT id, title, view_count, helpful_count, not_helpful_count
				 FROM {$wpdb->prefix}mets_kb_articles 
				 WHERE status = 'published'
				 ORDER BY view_count DESC 
				 LIMIT 10",
				ARRAY_A
			)
		);
	}
}