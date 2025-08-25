<?php
/**
 * Tickets list table class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/tickets
 * @since      1.0.0
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Tickets list table class.
 *
 * This class handles the display of tickets in a WordPress-style list table
 * with sorting, filtering, and bulk actions.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/tickets
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Tickets_List extends WP_List_Table {

	/**
	 * Ticket model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Ticket_Model    $ticket_model    Ticket model instance.
	 */
	private $ticket_model;

	/**
	 * Entity model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Entity_Model    $entity_model    Entity model instance.
	 */
	private $entity_model;

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'ticket',
			'plural'   => 'tickets',
			'ajax'     => false,
		) );

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$this->ticket_model = new METS_Ticket_Model();
		$this->entity_model = new METS_Entity_Model();
	}

	/**
	 * Get columns
	 *
	 * @since    1.0.0
	 * @return   array    Columns array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'ticket_number' => __( 'Ticket #', METS_TEXT_DOMAIN ),
			'subject'       => __( 'Subject', METS_TEXT_DOMAIN ),
			'customer'      => __( 'Customer', METS_TEXT_DOMAIN ),
			'entity'        => __( 'Entity', METS_TEXT_DOMAIN ),
			'status'        => __( 'Status', METS_TEXT_DOMAIN ),
			'priority'      => __( 'Priority', METS_TEXT_DOMAIN ),
			'assigned_to'   => __( 'Assigned To', METS_TEXT_DOMAIN ),
			'created_at'    => __( 'Created', METS_TEXT_DOMAIN ),
			'updated_at'    => __( 'Last Modified', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @since    1.0.0
	 * @return   array    Sortable columns array
	 */
	public function get_sortable_columns() {
		return array(
			'ticket_number' => array( 'ticket_number', false ),
			'subject'       => array( 'subject', false ),
			'status'        => array( 'status', false ),
			'priority'      => array( 'priority', false ),
			'created_at'    => array( 'created_at', true ),
			'updated_at'    => array( 'updated_at', true ),
		);
	}

	/**
	 * Column default
	 *
	 * @since    1.0.0
	 * @param    object    $item         Item object
	 * @param    string    $column_name  Column name
	 * @return   string                  Column content
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'entity':
				return esc_html( $item->entity_name );
			case 'created_at':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->created_at ) );
			case 'updated_at':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->updated_at ) );
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Column checkbox
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Checkbox HTML
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="tickets[]" value="%s" />',
			$item->id
		);
	}

	/**
	 * Column ticket number
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_ticket_number( $item ) {
		$edit_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $item->id );
		$delete_url = wp_nonce_url(
			admin_url( 'admin.php?page=mets-all-tickets&action=delete&ticket_id=' . $item->id ),
			'delete_ticket_' . $item->id
		);

		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', METS_TEXT_DOMAIN ) ),
			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Are you sure you want to delete this ticket?', METS_TEXT_DOMAIN ) ),
				__( 'Delete', METS_TEXT_DOMAIN )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->ticket_number ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column subject
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_subject( $item ) {
		$edit_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $item->id );
		
		return sprintf(
			'<a href="%s" class="row-title">%s</a>',
			esc_url( $edit_url ),
			esc_html( $item->subject )
		);
	}

	/**
	 * Column customer
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_customer( $item ) {
		$customer_info = sprintf(
			'<strong>%s</strong><br><a href="mailto:%s">%s</a>',
			esc_html( $item->customer_name ),
			esc_attr( $item->customer_email ),
			esc_html( $item->customer_email )
		);
		
		// Add phone number if available
		if ( ! empty( $item->customer_phone ) ) {
			$customer_info .= sprintf(
				'<br><a href="tel:%s">%s</a>',
				esc_attr( $item->customer_phone ),
				esc_html( $item->customer_phone )
			);
		}
		
		return $customer_info;
	}

	/**
	 * Column status
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_status( $item ) {
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$status_label = isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ]['label'] : ucfirst( $item->status );
		$status_color = isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ]['color'] : '#007cba';

		return sprintf(
			'<span class="mets-status-badge" style="background-color: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">%s</span>',
			esc_attr( $status_color ),
			esc_html( $status_label )
		);
	}

	/**
	 * Column priority
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_priority( $item ) {
		$priorities = get_option( 'mets_ticket_priorities', array() );
		$priority_label = isset( $priorities[ $item->priority ] ) ? $priorities[ $item->priority ]['label'] : ucfirst( $item->priority );
		$priority_color = isset( $priorities[ $item->priority ] ) ? $priorities[ $item->priority ]['color'] : '#007cba';

		return sprintf(
			'<span class="mets-priority-badge" style="color: %s; font-weight: bold;">%s</span>',
			esc_attr( $priority_color ),
			esc_html( $priority_label )
		);
	}

	/**
	 * Column assigned to
	 *
	 * @since    1.0.0
	 * @param    object    $item    Item object
	 * @return   string             Column content
	 */
	public function column_assigned_to( $item ) {
		if ( ! empty( $item->assigned_to_name ) ) {
			return esc_html( $item->assigned_to_name );
		}

		return '<em>' . __( 'Unassigned', METS_TEXT_DOMAIN ) . '</em>';
	}

	/**
	 * Get bulk actions
	 *
	 * @since    1.0.0
	 * @return   array    Bulk actions array
	 */
	public function get_bulk_actions() {
		return array(
			'delete'        => __( 'Delete', METS_TEXT_DOMAIN ),
			'assign'        => __( 'Assign', METS_TEXT_DOMAIN ),
			'change_status' => __( 'Change Status', METS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Extra table navigation
	 *
	 * @since    1.0.0
	 * @param    string    $which    Top or bottom
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'top' ) {
			$this->status_filter();
			$this->entity_filter();
			$this->priority_filter();
			$this->assignment_filter();
		}
	}

	/**
	 * Status filter
	 *
	 * @since    1.0.0
	 */
	private function status_filter() {
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$statuses = get_option( 'mets_ticket_statuses', array() );

		echo '<select name="status" id="filter-by-status">';
		echo '<option value="">' . __( 'All Statuses', METS_TEXT_DOMAIN ) . '</option>';

		foreach ( $statuses as $status_key => $status_data ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $status_key ),
				selected( $current_status, $status_key, false ),
				esc_html( $status_data['label'] )
			);
		}

		echo '</select>';
	}

	/**
	 * Entity filter
	 *
	 * @since    1.0.0
	 */
	private function entity_filter() {
		$current_entity = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : 0;
		$entities = $this->entity_model->get_all( array( 'status' => 'active', 'parent_id' => 'all' ) );

		echo '<select name="entity_id" id="filter-by-entity">';
		echo '<option value="">' . __( 'All Entities', METS_TEXT_DOMAIN ) . '</option>';

		foreach ( $entities as $entity ) {
			$prefix = ! empty( $entity->parent_id ) ? '— ' : '';
			printf(
				'<option value="%s" %s>%s%s</option>',
				esc_attr( $entity->id ),
				selected( $current_entity, $entity->id, false ),
				$prefix,
				esc_html( $entity->name )
			);
		}

		echo '</select>';
	}

	/**
	 * Priority filter
	 *
	 * @since    1.0.0
	 */
	private function priority_filter() {
		$current_priority = isset( $_GET['priority'] ) ? sanitize_text_field( $_GET['priority'] ) : '';
		$priorities = get_option( 'mets_ticket_priorities', array() );

		// Sort priorities by order
		uasort( $priorities, function( $a, $b ) {
			return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
		} );

		echo '<select name="priority" id="filter-by-priority">';
		echo '<option value="">' . __( 'All Priorities', METS_TEXT_DOMAIN ) . '</option>';

		foreach ( $priorities as $priority_key => $priority_data ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $priority_key ),
				selected( $current_priority, $priority_key, false ),
				esc_html( $priority_data['label'] )
			);
		}

		echo '</select>';
	}

	/**
	 * Assignment filter
	 *
	 * @since    1.0.0
	 */
	private function assignment_filter() {
		$current_assigned = isset( $_GET['assigned_to'] ) ? sanitize_text_field( $_GET['assigned_to'] ) : '';

		// Get all users with ticket handling capabilities
		$users = get_users( array(
			'meta_query' => array(
				array(
					'key'     => 'wp_capabilities',
					'value'   => 'ticket',
					'compare' => 'LIKE',
				),
			),
		) );

		echo '<select name="assigned_to" id="filter-by-assignment">';
		echo '<option value="">' . __( 'All Assignments', METS_TEXT_DOMAIN ) . '</option>';
		echo '<option value="unassigned"' . selected( $current_assigned, 'unassigned', false ) . '>' . __( 'Unassigned', METS_TEXT_DOMAIN ) . '</option>';

		foreach ( $users as $user ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $user->ID ),
				selected( $current_assigned, $user->ID, false ),
				esc_html( $user->display_name )
			);
		}

		echo '</select>';

		submit_button( __( 'Filter', METS_TEXT_DOMAIN ), 'secondary', 'filter_action', false );
	}

	/**
	 * Prepare items
	 *
	 * @since    1.0.0
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Handle bulk actions
		$this->process_bulk_action();

		// Get current page and per page values
		$per_page = $this->get_items_per_page( 'tickets_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;

		// Get search term
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		// Get filters
		$entity_filter = isset( $_GET['entity_id'] ) && ! empty( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;
		
		// If entity filter is set, expand to include child entities
		if ( ! empty( $entity_filter ) ) {
			$entity_filter = $this->expand_entity_filter( $entity_filter );
		}
		
		$filters = array(
			'entity_id'   => $entity_filter,
			'status'      => isset( $_GET['status'] ) && ! empty( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : null,
			'priority'    => isset( $_GET['priority'] ) && ! empty( $_GET['priority'] ) ? sanitize_text_field( $_GET['priority'] ) : null,
			'assigned_to' => isset( $_GET['assigned_to'] ) && ! empty( $_GET['assigned_to'] ) ? sanitize_text_field( $_GET['assigned_to'] ) : null,
			'search'      => $search,
		);

		// Get sorting
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'updated_at';
		$order = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		// Filter by user permissions
		$user_entities = $this->get_user_entity_access();
		if ( ! empty( $user_entities ) ) {
			if ( ! empty( $filters['entity_id'] ) ) {
				// Intersect user entities with the expanded entity filter
				if ( is_array( $filters['entity_id'] ) ) {
					$filters['entity_id'] = array_intersect( $filters['entity_id'], $user_entities );
				} else {
					$filters['entity_id'] = in_array( $filters['entity_id'], $user_entities ) ? $filters['entity_id'] : array();
				}
			} else {
				$filters['entity_id'] = $user_entities;
			}
		}

		// Build query args
		$args = array_merge( $filters, array(
			'orderby' => $orderby,
			'order'   => $order,
			'limit'   => $per_page,
			'offset'  => $offset,
		) );

		// Get tickets and total count
		$tickets = $this->ticket_model->get_all( $args );
		$total_items = $this->ticket_model->get_count( $filters );

		// Set items and pagination
		$this->items = $tickets;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Get user entity access
	 *
	 * @since    1.0.0
	 * @return   array|null    Entity IDs user has access to, or null for all access
	 */
	private function get_user_entity_access() {
		$current_user = wp_get_current_user();

		// Admins and ticket admins have access to all entities
		if ( current_user_can( 'manage_ticket_system' ) || current_user_can( 'view_all_tickets' ) ) {
			return null;
		}

		// Get entities this user has access to
		global $wpdb;
		$entity_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT entity_id FROM {$wpdb->prefix}mets_user_entities WHERE user_id = %d",
			$current_user->ID
		) );

		return ! empty( $entity_ids ) ? array_map( 'intval', $entity_ids ) : array( 0 ); // Return 0 to show no tickets if no access
	}

	/**
	 * Process bulk actions
	 *
	 * @since    1.0.0
	 */
	public function process_bulk_action() {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		$ticket_ids = isset( $_GET['tickets'] ) ? array_map( 'intval', $_GET['tickets'] ) : array();

		if ( empty( $ticket_ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				check_admin_referer( 'bulk-tickets' );
				foreach ( $ticket_ids as $ticket_id ) {
					$this->ticket_model->delete( $ticket_id );
				}
				set_transient( 'mets_admin_notice', array(
					'message' => sprintf(
						_n( '%d ticket deleted.', '%d tickets deleted.', count( $ticket_ids ), METS_TEXT_DOMAIN ),
						count( $ticket_ids )
					),
					'type' => 'success'
				), 45 );
				break;

			case 'assign':
				// This would be handled by AJAX or additional form
				break;

			case 'change_status':
				// This would be handled by AJAX or additional form
				break;
		}

		// Redirect to remove the action from URL
		wp_redirect( remove_query_arg( array( 'action', 'tickets' ) ) );
		exit;
	}

	/**
	 * Expand entity filter to include child entities
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID to expand
	 * @return   array|int            Array of entity IDs (parent + children) or single ID if no children
	 */
	private function expand_entity_filter( $entity_id ) {
		// Get child entities
		$children = $this->entity_model->get_children( $entity_id );
		
		if ( empty( $children ) ) {
			// No children, return single entity ID
			return $entity_id;
		}
		
		// Build array of entity IDs (parent + all children)
		$entity_ids = array( $entity_id );
		foreach ( $children as $child ) {
			$entity_ids[] = $child->id;
		}
		
		return $entity_ids;
	}
}