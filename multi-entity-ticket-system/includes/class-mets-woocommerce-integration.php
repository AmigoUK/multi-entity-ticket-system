<?php
/**
 * WooCommerce Integration
 *
 * Handles integration with WooCommerce for order-related tickets
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * The WooCommerce Integration class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_WooCommerce_Integration {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_WooCommerce_Integration    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_WooCommerce_Integration    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the WooCommerce integration
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Only initialize if WooCommerce is active
		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		$this->init_hooks();
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @since    1.0.0
	 * @return   bool    Whether WooCommerce is active
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Add support ticket tab to My Account
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_support_tab' ) );
		add_action( 'woocommerce_account_support-tickets_endpoint', array( $this, 'support_tickets_content' ) );
		add_action( 'init', array( $this, 'add_endpoints' ) );

		// Add ticket creation button to order details
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_order_ticket_button' ) );
		add_action( 'woocommerce_view_order', array( $this, 'add_order_ticket_button' ) );

		// Admin hooks
		add_action( 'add_meta_boxes', array( $this, 'add_order_tickets_metabox' ) );
		add_filter( 'manage_shop_order_posts_columns', array( $this, 'add_tickets_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_tickets_column' ), 10, 2 );

		// AJAX handlers
		add_action( 'wp_ajax_mets_create_order_ticket', array( $this, 'ajax_create_order_ticket' ) );
		add_action( 'wp_ajax_nopriv_mets_create_order_ticket', array( $this, 'ajax_create_order_ticket' ) );

		// Auto-create tickets for specific order statuses
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_auto_create_ticket' ), 10, 4 );

		// Product support integration
		add_action( 'woocommerce_single_product_summary', array( $this, 'add_product_support_button' ), 25 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'add_product_support_tab' ) );

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add support tickets tab to My Account
	 *
	 * @since    1.0.0
	 * @param    array    $items    Account menu items
	 * @return   array              Modified menu items
	 */
	public function add_support_tab( $items ) {
		// Insert before logout
		$logout = $items['customer-logout'];
		unset( $items['customer-logout'] );
		
		$items['support-tickets'] = __( 'Support Tickets', METS_TEXT_DOMAIN );
		$items['customer-logout'] = $logout;
		
		return $items;
	}

	/**
	 * Add custom endpoints
	 *
	 * @since    1.0.0
	 */
	public function add_endpoints() {
		add_rewrite_endpoint( 'support-tickets', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'create-ticket', EP_ROOT | EP_PAGES );
	}

	/**
	 * Display support tickets content in My Account
	 *
	 * @since    1.0.0
	 */
	public function support_tickets_content() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$current_user = wp_get_current_user();
		$customer_id = $current_user->ID;

		// Get customer's tickets
		global $wpdb;
		$tickets = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.*, e.name as entity_name, u.display_name as assigned_agent_name 
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			LEFT JOIN {$wpdb->users} u ON t.assigned_to = u.ID
			WHERE t.customer_id = %d
			ORDER BY t.created_at DESC",
			$customer_id
		) );

		// Get customer's orders for ticket creation
		$orders = wc_get_orders( array(
			'customer_id' => $customer_id,
			'limit' => 50,
			'orderby' => 'date',
			'order' => 'DESC'
		) );

		include METS_PLUGIN_PATH . 'public/templates/woocommerce/my-account-support-tickets.php';
	}

	/**
	 * Add ticket creation button to order details
	 *
	 * @since    1.0.0
	 * @param    int    $order_id    Order ID
	 */
	public function add_order_ticket_button( $order_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if user owns this order
		if ( $order->get_customer_id() !== get_current_user_id() ) {
			return;
		}

		// Check if there are existing tickets for this order
		global $wpdb;
		$existing_tickets = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE JSON_EXTRACT(metadata, '$.wc_order_id') = %d",
			$order_id
		) );

		echo '<div class="mets-order-support">';
		echo '<h3>' . __( 'Need Help?', METS_TEXT_DOMAIN ) . '</h3>';
		
		if ( $existing_tickets > 0 ) {
			echo '<p>' . sprintf( 
				__( 'You have %d support ticket(s) related to this order.', METS_TEXT_DOMAIN ), 
				$existing_tickets 
			) . '</p>';
			echo '<a href="' . wc_get_account_endpoint_url( 'support-tickets' ) . '" class="button">' . 
				__( 'View Support Tickets', METS_TEXT_DOMAIN ) . '</a>';
		}
		
		echo '<button type="button" class="button mets-create-order-ticket" data-order-id="' . $order_id . '">' . 
			__( 'Create Support Ticket', METS_TEXT_DOMAIN ) . '</button>';
		echo '</div>';
	}

	/**
	 * Add order tickets metabox to admin
	 *
	 * @since    1.0.0
	 */
	public function add_order_tickets_metabox() {
		add_meta_box(
			'mets-order-tickets',
			__( 'Support Tickets', METS_TEXT_DOMAIN ),
			array( $this, 'display_order_tickets_metabox' ),
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * Display order tickets metabox content
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $post    Post object
	 */
	public function display_order_tickets_metabox( $post ) {
		$order_id = $post->ID;
		
		global $wpdb;
		$tickets = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, subject, status, priority, created_at 
			FROM {$wpdb->prefix}mets_tickets 
			WHERE JSON_EXTRACT(metadata, '$.wc_order_id') = %d
			ORDER BY created_at DESC",
			$order_id
		) );

		if ( empty( $tickets ) ) {
			echo '<p>' . __( 'No support tickets for this order.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		echo '<div id="mets-order-tickets-list">';
		foreach ( $tickets as $ticket ) {
			$status_class = 'mets-status-' . sanitize_html_class( $ticket->status );
			$priority_class = 'mets-priority-' . sanitize_html_class( $ticket->priority );
			
			echo '<div class="mets-ticket-item ' . $status_class . ' ' . $priority_class . '">';
			echo '<strong><a href="' . admin_url( 'admin.php?page=mets-tickets&action=view&id=' . $ticket->id ) . '">';
			echo '#' . $ticket->id . ': ' . esc_html( $ticket->subject ) . '</a></strong><br>';
			echo '<small>';
			echo __( 'Status:', METS_TEXT_DOMAIN ) . ' <span class="status">' . ucfirst( $ticket->status ) . '</span> | ';
			echo __( 'Priority:', METS_TEXT_DOMAIN ) . ' <span class="priority">' . ucfirst( $ticket->priority ) . '</span><br>';
			echo __( 'Created:', METS_TEXT_DOMAIN ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) );
			echo '</small>';
			echo '</div>';
		}
		echo '</div>';

		// Add quick ticket creation form
		echo '<div id="mets-quick-ticket-form" style="margin-top: 15px;">';
		echo '<h4>' . __( 'Create Ticket', METS_TEXT_DOMAIN ) . '</h4>';
		echo '<input type="text" id="mets-quick-subject" placeholder="' . __( 'Subject', METS_TEXT_DOMAIN ) . '" style="width: 100%; margin-bottom: 5px;">';
		echo '<textarea id="mets-quick-description" placeholder="' . __( 'Description', METS_TEXT_DOMAIN ) . '" style="width: 100%; height: 60px; margin-bottom: 5px;"></textarea>';
		echo '<select id="mets-quick-priority" style="width: 100%; margin-bottom: 5px;">';
		echo '<option value="low">' . __( 'Low Priority', METS_TEXT_DOMAIN ) . '</option>';
		echo '<option value="medium" selected>' . __( 'Medium Priority', METS_TEXT_DOMAIN ) . '</option>';
		echo '<option value="high">' . __( 'High Priority', METS_TEXT_DOMAIN ) . '</option>';
		echo '<option value="critical">' . __( 'Critical Priority', METS_TEXT_DOMAIN ) . '</option>';
		echo '</select>';
		echo '<button type="button" class="button button-primary" id="mets-create-order-ticket-admin" data-order-id="' . $order_id . '">';
		echo __( 'Create Ticket', METS_TEXT_DOMAIN );
		echo '</button>';
		echo '</div>';
	}

	/**
	 * Add tickets column to orders list
	 *
	 * @since    1.0.0
	 * @param    array    $columns    Existing columns
	 * @return   array                Modified columns
	 */
	public function add_tickets_column( $columns ) {
		$columns['tickets'] = __( 'Tickets', METS_TEXT_DOMAIN );
		return $columns;
	}

	/**
	 * Display tickets column content
	 *
	 * @since    1.0.0
	 * @param    string    $column     Column name
	 * @param    int       $post_id    Order ID
	 */
	public function display_tickets_column( $column, $post_id ) {
		if ( $column !== 'tickets' ) {
			return;
		}

		global $wpdb;
		$ticket_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE JSON_EXTRACT(metadata, '$.wc_order_id') = %d",
			$post_id
		) );

		if ( $ticket_count > 0 ) {
			echo '<a href="' . admin_url( 'admin.php?page=mets-tickets&wc_order_id=' . $post_id ) . '">';
			echo $ticket_count . ' ' . _n( 'ticket', 'tickets', $ticket_count, METS_TEXT_DOMAIN );
			echo '</a>';
		} else {
			echo '—';
		}
	}

	/**
	 * Handle AJAX order ticket creation
	 *
	 * @since    1.0.0
	 */
	public function ajax_create_order_ticket() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_create_order_ticket' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$subject = sanitize_text_field( $_POST['subject'] );
		$description = wp_kses_post( $_POST['description'] );
		$priority = sanitize_text_field( $_POST['priority'] ?? 'medium' );

		if ( empty( $subject ) || empty( $description ) ) {
			wp_send_json_error( __( 'Subject and description are required.', METS_TEXT_DOMAIN ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Invalid order.', METS_TEXT_DOMAIN ) );
		}

		// Check permissions - user must own the order or be an admin
		if ( ! current_user_can( 'manage_shop_orders' ) && $order->get_customer_id() !== get_current_user_id() ) {
			wp_send_json_error( __( 'Permission denied.', METS_TEXT_DOMAIN ) );
		}

		// Get default entity or create one for WooCommerce
		$entity_id = $this->get_or_create_wc_entity();

		// Create ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$current_user = wp_get_current_user();
		$customer_id = $order->get_customer_id() ?: $current_user->ID;

		$ticket_data = array(
			'subject' => $subject,
			'description' => $description,
			'entity_id' => $entity_id,
			'customer_id' => $customer_id,
			'priority' => $priority,
			'status' => 'open',
			'source' => 'woocommerce',
			'metadata' => json_encode( array(
				'wc_order_id' => $order_id,
				'wc_order_number' => $order->get_order_number(),
				'wc_order_total' => $order->get_total(),
				'wc_order_date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
				'wc_products' => $this->get_order_products( $order )
			) )
		);

		$ticket_id = $ticket_model->create( $ticket_data );

		if ( $ticket_id ) {
			// Trigger ticket created action
			do_action( 'mets_ticket_created', $ticket_id, $ticket_data );

			wp_send_json_success( array(
				'message' => __( 'Support ticket created successfully!', METS_TEXT_DOMAIN ),
				'ticket_id' => $ticket_id,
				'redirect_url' => current_user_can( 'manage_tickets' ) 
					? admin_url( 'admin.php?page=mets-tickets&action=view&id=' . $ticket_id )
					: wc_get_account_endpoint_url( 'support-tickets' )
			) );
		} else {
			wp_send_json_error( __( 'Failed to create support ticket.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * Maybe auto-create ticket for order status changes
	 *
	 * @since    1.0.0
	 * @param    int       $order_id     Order ID
	 * @param    string    $old_status   Previous status
	 * @param    string    $new_status   New status
	 * @param    WC_Order  $order        Order object
	 */
	public function maybe_auto_create_ticket( $order_id, $old_status, $new_status, $order ) {
		// Get auto-ticket settings
		$auto_ticket_statuses = get_option( 'mets_wc_auto_ticket_statuses', array() );
		
		if ( ! in_array( $new_status, $auto_ticket_statuses ) ) {
			return;
		}

		// Check if ticket already exists for this order
		global $wpdb;
		$existing_ticket = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}mets_tickets 
			WHERE JSON_EXTRACT(metadata, '$.wc_order_id') = %d
			AND JSON_EXTRACT(metadata, '$.auto_created') = 'true'
			AND JSON_EXTRACT(metadata, '$.trigger_status') = %s",
			$order_id,
			$new_status
		) );

		if ( $existing_ticket ) {
			return; // Ticket already exists
		}

		// Create auto-ticket
		$entity_id = $this->get_or_create_wc_entity();
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();

		$subject = sprintf( __( 'Order %s - Status: %s', METS_TEXT_DOMAIN ), $order->get_order_number(), wc_get_order_status_name( $new_status ) );
		$description = sprintf( 
			__( 'This ticket was automatically created for order %s when the status changed to %s.', METS_TEXT_DOMAIN ),
			$order->get_order_number(),
			wc_get_order_status_name( $new_status )
		);

		$ticket_data = array(
			'subject' => $subject,
			'description' => $description,
			'entity_id' => $entity_id,
			'customer_id' => $order->get_customer_id(),
			'priority' => $this->get_status_priority( $new_status ),
			'status' => 'open',
			'source' => 'woocommerce_auto',
			'metadata' => json_encode( array(
				'wc_order_id' => $order_id,
				'wc_order_number' => $order->get_order_number(),
				'wc_order_total' => $order->get_total(),
				'wc_order_date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
				'wc_products' => $this->get_order_products( $order ),
				'auto_created' => 'true',
				'trigger_status' => $new_status,
				'previous_status' => $old_status
			) )
		);

		$ticket_id = $ticket_model->create( $ticket_data );

		if ( $ticket_id ) {
			do_action( 'mets_ticket_created', $ticket_id, $ticket_data );
		}
	}

	/**
	 * Add product support button
	 *
	 * @since    1.0.0
	 */
	public function add_product_support_button() {
		global $product;
		
		if ( ! $product ) {
			return;
		}

		echo '<div class="mets-product-support">';
		echo '<button type="button" class="button mets-product-support-btn" data-product-id="' . $product->get_id() . '">';
		echo __( 'Get Product Support', METS_TEXT_DOMAIN );
		echo '</button>';
		echo '</div>';
	}

	/**
	 * Add product support tab
	 *
	 * @since    1.0.0
	 * @param    array    $tabs    Product tabs
	 * @return   array             Modified tabs
	 */
	public function add_product_support_tab( $tabs ) {
		$tabs['mets_support'] = array(
			'title' => __( 'Support', METS_TEXT_DOMAIN ),
			'priority' => 50,
			'callback' => array( $this, 'product_support_tab_content' )
		);
		
		return $tabs;
	}

	/**
	 * Product support tab content
	 *
	 * @since    1.0.0
	 */
	public function product_support_tab_content() {
		global $product;
		
		echo '<div id="mets-product-support-tab">';
		echo '<h3>' . __( 'Need help with this product?', METS_TEXT_DOMAIN ) . '</h3>';
		echo '<p>' . __( 'Create a support ticket and our team will help you.', METS_TEXT_DOMAIN ) . '</p>';
		
		if ( is_user_logged_in() ) {
			echo '<button type="button" class="button mets-create-product-ticket" data-product-id="' . $product->get_id() . '">';
			echo __( 'Create Support Ticket', METS_TEXT_DOMAIN );
			echo '</button>';
		} else {
			echo '<p>' . __( 'Please', METS_TEXT_DOMAIN ) . ' <a href="' . wp_login_url( get_permalink() ) . '">' . __( 'login', METS_TEXT_DOMAIN ) . '</a> ' . __( 'to create a support ticket.', METS_TEXT_DOMAIN ) . '</p>';
		}
		
		echo '</div>';
	}

	/**
	 * Enqueue scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( is_account_page() || is_product() || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_script( 
				'mets-woocommerce', 
				METS_PLUGIN_URL . 'public/js/mets-woocommerce.js', 
				array( 'jquery' ), 
				METS_VERSION, 
				true 
			);
			
			wp_localize_script( 'mets-woocommerce', 'mets_wc_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'mets_create_order_ticket' ),
				'strings' => array(
					'subject_required' => __( 'Subject is required.', METS_TEXT_DOMAIN ),
					'description_required' => __( 'Description is required.', METS_TEXT_DOMAIN ),
					'creating_ticket' => __( 'Creating ticket...', METS_TEXT_DOMAIN ),
					'ticket_created' => __( 'Ticket created successfully!', METS_TEXT_DOMAIN ),
					'error_occurred' => __( 'An error occurred. Please try again.', METS_TEXT_DOMAIN )
				)
			) );
			
			wp_enqueue_style( 
				'mets-woocommerce', 
				METS_PLUGIN_URL . 'public/css/mets-woocommerce.css', 
				array(), 
				METS_VERSION 
			);
		}
	}

	/**
	 * Get or create WooCommerce entity
	 *
	 * @since    1.0.0
	 * @return   int    Entity ID
	 */
	private function get_or_create_wc_entity() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		// Try to find existing WooCommerce entity
		global $wpdb;
		$entity_id = $wpdb->get_var(
			"SELECT id FROM {$wpdb->prefix}mets_entities 
			WHERE name = 'WooCommerce' AND type = 'company'"
		);

		if ( ! $entity_id ) {
			// Create WooCommerce entity
			$entity_data = array(
				'name' => 'WooCommerce',
				'type' => 'company',
				'description' => __( 'WooCommerce Store Support', METS_TEXT_DOMAIN ),
				'contact_email' => get_option( 'admin_email' ),
				'status' => 'active',
				'metadata' => json_encode( array(
					'auto_created' => true,
					'integration' => 'woocommerce'
				) )
			);

			$entity_id = $entity_model->create( $entity_data );
		}

		return $entity_id;
	}

	/**
	 * Get order products data
	 *
	 * @since    1.0.0
	 * @param    WC_Order    $order    Order object
	 * @return   array                 Products data
	 */
	private function get_order_products( $order ) {
		$products = array();
		
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$products[] = array(
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'sku' => $product->get_sku(),
					'quantity' => $item->get_quantity(),
					'price' => $item->get_total()
				);
			}
		}
		
		return $products;
	}

	/**
	 * Get priority based on order status
	 *
	 * @since    1.0.0
	 * @param    string    $status    Order status
	 * @return   string               Priority level
	 */
	private function get_status_priority( $status ) {
		$priority_map = array(
			'cancelled' => 'high',
			'refunded' => 'high',
			'failed' => 'critical',
			'on-hold' => 'medium',
			'pending' => 'low',
			'processing' => 'low',
			'completed' => 'low'
		);

		return $priority_map[ $status ] ?? 'medium';
	}

	/**
	 * Get WooCommerce integration settings
	 *
	 * @since    1.0.0
	 * @return   array    Settings array
	 */
	public static function get_settings() {
		return get_option( 'mets_woocommerce_settings', array(
			'enabled' => true,
			'auto_ticket_statuses' => array(),
			'show_support_tab' => true,
			'show_order_button' => true,
			'default_priority' => 'medium'
		) );
	}

	/**
	 * Update WooCommerce integration settings
	 *
	 * @since    1.0.0
	 * @param    array    $settings    Settings to update
	 * @return   bool                  Whether the update was successful
	 */
	public static function update_settings( $settings ) {
		return update_option( 'mets_woocommerce_settings', $settings );
	}
}