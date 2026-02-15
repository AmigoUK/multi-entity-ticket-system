<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Settings handler instance.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      METS_Admin_Settings    $settings_handler    Handles settings operations.
	 */
	private $settings_handler;

	/**
	 * Test data manager instance (dev/staging only).
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      METS_Test_Data_Manager|null
	 */
	private $test_data_manager = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add dashboard widget hook
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

		// Add report export handler
		add_action( 'init', array( $this, 'handle_report_export' ) );

		// Delegate AJAX handlers to dedicated class
		require_once METS_PLUGIN_PATH . 'admin/class-mets-admin-ajax.php';
		$ajax_handler = new METS_Admin_Ajax( $this->plugin_name, $this->version );

		// Dashboard widget AJAX
		add_action( 'wp_ajax_mets_refresh_sla_widget', array( $ajax_handler, 'ajax_refresh_sla_widget' ) );
		add_action( 'wp_ajax_mets_refresh_tickets_widget', array( $ajax_handler, 'ajax_refresh_tickets_widget' ) );
		add_action( 'wp_ajax_mets_get_entity_categories', array( $ajax_handler, 'ajax_get_entity_categories' ) );

		// KB article AJAX
		add_action( 'wp_ajax_mets_admin_search_kb_articles', array( $ajax_handler, 'ajax_admin_search_kb_articles' ) );
		add_action( 'wp_ajax_mets_link_kb_article', array( $ajax_handler, 'ajax_link_kb_article' ) );
		add_action( 'wp_ajax_mets_unlink_kb_article', array( $ajax_handler, 'ajax_unlink_kb_article' ) );
		add_action( 'wp_ajax_mets_mark_kb_helpful', array( $ajax_handler, 'ajax_mark_kb_helpful' ) );
		add_action( 'wp_ajax_mets_get_kb_tag', array( $ajax_handler, 'ajax_get_kb_tag' ) );

		// WooCommerce integration AJAX
		add_action( 'wp_ajax_mets_flush_rewrite_rules', array( $ajax_handler, 'ajax_flush_rewrite_rules' ) );
		add_action( 'wp_ajax_mets_create_wc_entity', array( $ajax_handler, 'ajax_create_wc_entity' ) );
		add_action( 'wp_ajax_mets_test_wc_integration', array( $ajax_handler, 'ajax_test_wc_integration' ) );

		// Bulk operations AJAX
		add_action( 'wp_ajax_mets_load_bulk_tickets', array( $ajax_handler, 'ajax_load_bulk_tickets' ) );
		add_action( 'wp_ajax_mets_load_bulk_entities', array( $ajax_handler, 'ajax_load_bulk_entities' ) );
		add_action( 'wp_ajax_mets_load_bulk_kb_articles', array( $ajax_handler, 'ajax_load_bulk_kb_articles' ) );

		// Performance optimization AJAX
		add_action( 'wp_ajax_mets_optimize_all_tables', array( $ajax_handler, 'ajax_optimize_all_tables' ) );
		add_action( 'wp_ajax_mets_create_indexes', array( $ajax_handler, 'ajax_create_indexes' ) );
		add_action( 'wp_ajax_mets_warm_cache', array( $ajax_handler, 'ajax_warm_cache' ) );
		add_action( 'wp_ajax_mets_flush_all_cache', array( $ajax_handler, 'ajax_flush_all_cache' ) );
		add_action( 'wp_ajax_mets_flush_cache_group', array( $ajax_handler, 'ajax_flush_cache_group' ) );

		// Ticket & entity AJAX (not registered in original constructor but methods existed)
		add_action( 'wp_ajax_mets_search_entities', array( $ajax_handler, 'ajax_search_entities' ) );
		add_action( 'wp_ajax_mets_get_entity_agents', array( $ajax_handler, 'ajax_get_entity_agents' ) );
		add_action( 'wp_ajax_mets_assign_ticket', array( $ajax_handler, 'ajax_assign_ticket' ) );
		add_action( 'wp_ajax_mets_change_ticket_status', array( $ajax_handler, 'ajax_change_ticket_status' ) );
		add_action( 'wp_ajax_mets_check_workflow_transition', array( $ajax_handler, 'ajax_check_workflow_transition' ) );
		add_action( 'wp_ajax_mets_get_allowed_transitions', array( $ajax_handler, 'ajax_get_allowed_transitions' ) );

		// Agent management AJAX
		add_action( 'wp_ajax_mets_assign_agent_entities', array( $ajax_handler, 'ajax_assign_agent_entities' ) );
		add_action( 'wp_ajax_mets_update_agent_role', array( $ajax_handler, 'ajax_update_agent_role' ) );
		add_action( 'wp_ajax_mets_get_agent_performance', array( $ajax_handler, 'ajax_get_agent_performance' ) );

		// KB article review AJAX
		add_action( 'wp_ajax_mets_request_article_changes', array( $ajax_handler, 'ajax_request_article_changes' ) );

		// Delegate settings management to dedicated class
		require_once METS_PLUGIN_PATH . 'admin/class-mets-admin-settings.php';
		$this->settings_handler = new METS_Admin_Settings( $this->plugin_name, $this->version );

		// Test Data Manager (dev/staging only)
		require_once METS_PLUGIN_PATH . 'admin/class-mets-test-data-manager.php';
		if ( METS_Test_Data_Manager::is_enabled() ) {
			$this->test_data_manager = new METS_Test_Data_Manager();
			add_action( 'wp_ajax_mets_import_test_data', array( $this->test_data_manager, 'ajax_import_test_data' ) );
			add_action( 'wp_ajax_mets_remove_test_data', array( $this->test_data_manager, 'ajax_remove_test_data' ) );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, METS_PLUGIN_URL . 'assets/css/mets-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, METS_PLUGIN_URL . 'assets/js/mets-admin.js', array( 'jquery' ), $this->version, false );

		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'mets_admin_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mets_admin_nonce' ),
		) );

		// Test Data Manager JS (only on its own screen)
		$screen = get_current_screen();
		if ( $screen && $screen->id === 'mets-tickets_page_mets-test-data' && $this->test_data_manager ) {
			wp_enqueue_script( 'mets-test-data', METS_PLUGIN_URL . 'assets/js/mets-test-data.js', array( 'jquery' ), $this->version, true );
			wp_localize_script( 'mets-test-data', 'mets_test_data', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'mets_admin_nonce' ),
			) );
		}
	}

	/**
	 * Ensure KB capabilities are available
	 *
	 * @since    1.0.0
	 */
	private function ensure_kb_capabilities() {
		// Check if KB capabilities need to be added
		$current_user = wp_get_current_user();
		if ( ! $current_user->has_cap( 'read_kb_articles' ) && ( $current_user->has_cap( 'manage_tickets' ) || $current_user->has_cap( 'manage_options' ) ) ) {
			// Load KB activator and run capability setup
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mets-kb-activator.php';
			if ( class_exists( 'METS_KB_Activator' ) ) {
				METS_KB_Activator::add_kb_capabilities_to_existing_users();
			}
		}
	}

	/**
	 * Maybe update capabilities for existing installations
	 *
	 * @since    1.0.0
	 */
	private function maybe_update_capabilities() {
		// Only run this for admin users to avoid performance issues
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Check if capability update is needed
		require_once METS_PLUGIN_PATH . 'includes/class-mets-capability-updater.php';
		if ( METS_Capability_Updater::needs_update() ) {
			METS_Capability_Updater::update_capabilities();
		}
	}

	/**
	 * Add admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Ensure KB capabilities are available
		$this->ensure_kb_capabilities();

		// ==============================================
		// MAIN MENU: METS TICKETS
		// ==============================================
		add_menu_page(
			__( 'METS Tickets', METS_TEXT_DOMAIN ),
			__( 'METS Tickets', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-tickets',
			array( $this, 'display_dashboard_page' ),
			'dashicons-tickets-alt',
			30
		);

		// ==============================================
		// 1. DASHBOARD (Primary)
		// ==============================================
		add_submenu_page(
			'mets-tickets',
			__( 'Dashboard', METS_TEXT_DOMAIN ),
			__( '📊 Dashboard', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-tickets',
			array( $this, 'display_dashboard_page' )
		);

		// ==============================================
		// 2. TICKETS (Primary)
		// ==============================================
		add_submenu_page(
			'mets-tickets',
			__( 'All Tickets', METS_TEXT_DOMAIN ),
			__( '🎫 All Tickets', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-all-tickets',
			array( $this, 'display_tickets_page' )
		);

		add_submenu_page(
			'mets-tickets',
			__( 'Add New Ticket', METS_TEXT_DOMAIN ),
			__( '➕ Add New Ticket', METS_TEXT_DOMAIN ),
			'edit_tickets',
			'mets-add-ticket',
			array( $this, 'display_add_ticket_page' )
		);

		// My Assigned Tickets (for agents)
		if ( current_user_can( 'edit_tickets' ) && ! current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'My Assigned Tickets', METS_TEXT_DOMAIN ),
				__( '👤 My Tickets', METS_TEXT_DOMAIN ),
				'edit_tickets',
				'mets-my-tickets',
				array( $this, 'display_my_tickets_page' )
			);
		}

		// Bulk Operations
		add_submenu_page(
			'mets-tickets',
			__( 'Bulk Operations', METS_TEXT_DOMAIN ),
			__( '⚡ Bulk Operations', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-bulk-operations',
			array( $this, 'display_bulk_operations_page' )
		);

		// ==============================================
		// 3. KNOWLEDGE BASE (Primary)
		// ==============================================
		add_submenu_page(
			'mets-tickets',
			__( 'Knowledge Base', METS_TEXT_DOMAIN ),
			__( '📚 Knowledge Base', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-kb-articles',
			array( $this, 'display_kb_articles_page' )
		);

		add_submenu_page(
			'mets-tickets',
			__( 'Add New Article', METS_TEXT_DOMAIN ),
			__( '📝 Add Article', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-kb-add-article',
			array( $this, 'display_kb_add_article_page' )
		);

		// Pending Review (only for users who can review)
		if ( current_user_can( 'review_kb_articles' ) || current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'KB Pending Review', METS_TEXT_DOMAIN ),
				__( '⏳ Pending Review', METS_TEXT_DOMAIN ),
				'manage_tickets',
				'mets-kb-pending-review',
				array( $this, 'display_kb_pending_review_page' )
			);
		}

		add_submenu_page(
			'mets-tickets',
			__( 'KB Categories', METS_TEXT_DOMAIN ),
			__( '📂 KB Categories', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-kb-categories',
			array( $this, 'display_kb_categories_page' )
		);

		add_submenu_page(
			'mets-tickets',
			__( 'KB Tags', METS_TEXT_DOMAIN ),
			__( '🏷️ KB Tags', METS_TEXT_DOMAIN ),
			'manage_tickets',
			'mets-kb-tags',
			array( $this, 'display_kb_tags_page' )
		);

		// ==============================================
		// 4. TEAM MANAGEMENT (Primary for Managers)
		// ==============================================
		if ( current_user_can( 'manage_agents' ) || current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'Agents', METS_TEXT_DOMAIN ),
				__( '👥 Agents', METS_TEXT_DOMAIN ),
				'manage_agents',
				'mets-agents',
				array( $this, 'display_agents_page' )
			);

			add_submenu_page(
				'mets-tickets',
				__( 'Team Performance', METS_TEXT_DOMAIN ),
				__( '📊 Team Performance', METS_TEXT_DOMAIN ),
				'manage_agents',
				'mets-manager-dashboard',
				array( $this, 'display_manager_dashboard_page' )
			);
		}

		// ==============================================
		// 5. ENTITIES (Primary for Admins)
		// ==============================================
		if ( current_user_can( 'manage_entities' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'All Entities', METS_TEXT_DOMAIN ),
				__( '🏢 Entities', METS_TEXT_DOMAIN ),
				'manage_entities',
				'mets-entities',
				array( $this, 'display_entities_page' )
			);
		}

		// ==============================================
		// 6. REPORTS & ANALYTICS (Secondary)
		// ==============================================
		if ( current_user_can( 'view_reports' ) || current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'Ticket Reports', METS_TEXT_DOMAIN ),
				__( '📈 Reports', METS_TEXT_DOMAIN ),
				'view_reports',
				'mets-reporting-dashboard',
				array( $this, 'display_reporting_dashboard_page' )
			);

			add_submenu_page(
				'mets-tickets',
				__( 'Custom Reports', METS_TEXT_DOMAIN ),
				__( '📋 Custom Reports', METS_TEXT_DOMAIN ),
				'view_reports',
				'mets-custom-reports',
				array( $this, 'display_custom_reports_page' )
			);

			// KB Analytics (only for users who can view analytics)
			if ( current_user_can( 'view_kb_analytics' ) || current_user_can( 'manage_tickets' ) ) {
				add_submenu_page(
					'mets-tickets',
					__( 'KB Analytics', METS_TEXT_DOMAIN ),
					__( '📊 KB Analytics', METS_TEXT_DOMAIN ),
					'view_reports',
					'mets-kb-analytics',
					array( $this, 'display_kb_analytics_page' )
				);
			}

			// Performance Dashboard (admin only)
			if ( current_user_can( 'manage_options' ) ) {
				add_submenu_page(
					'mets-tickets',
					__( 'Performance Analytics', METS_TEXT_DOMAIN ),
					__( '⚡ Performance', METS_TEXT_DOMAIN ),
					'manage_options',
					'mets-performance-dashboard',
					array( $this, 'display_performance_dashboard_page' )
				);
			}
		}

		// ==============================================
		// 7. SETTINGS (Bottom - Admin Only)
		// ==============================================
		if ( current_user_can( 'manage_ticket_system' ) ) {
			add_submenu_page(
				'mets-tickets',
				__( 'General Settings', METS_TEXT_DOMAIN ),
				__( '⚙️ Settings', METS_TEXT_DOMAIN ),
				'manage_ticket_system',
				'mets-settings',
				array( $this->settings_handler, 'display_settings_page' )
			);

			add_submenu_page(
				'mets-tickets',
				__( 'SLA Configuration', METS_TEXT_DOMAIN ),
				__( '⏱️ SLA Config', METS_TEXT_DOMAIN ),
				'manage_ticket_system',
				'mets-sla-rules',
				array( $this, 'display_sla_rules_page' )
			);

			add_submenu_page(
				'mets-tickets',
				__( 'Business Hours', METS_TEXT_DOMAIN ),
				__( '🕒 Business Hours', METS_TEXT_DOMAIN ),
				'manage_ticket_system',
				'mets-business-hours',
				array( $this, 'display_business_hours_page' )
			);

			// WooCommerce Integration (only if WooCommerce is active)
			if ( class_exists( 'WooCommerce' ) ) {
				add_submenu_page(
					'mets-tickets',
					__( 'WooCommerce Integration', METS_TEXT_DOMAIN ),
					__( '🛒 WooCommerce', METS_TEXT_DOMAIN ),
					'manage_ticket_system',
					'mets-woocommerce',
					array( $this->settings_handler, 'display_woocommerce_settings_page' )
				);
			}

			// Security Dashboard (super admin only)
			if ( current_user_can( 'manage_options' ) ) {
				add_submenu_page(
					'mets-tickets',
					__( 'Security Dashboard', METS_TEXT_DOMAIN ),
					__( '🔒 Security', METS_TEXT_DOMAIN ),
					'manage_options',
					'mets-security-dashboard',
					array( $this, 'display_security_dashboard_page' )
				);
			}

			// Test Data Manager (dev/staging only)
			if ( $this->test_data_manager ) {
				add_submenu_page(
					'mets-tickets',
					__( 'Test Data Manager', METS_TEXT_DOMAIN ),
					__( '🧪 Test Data', METS_TEXT_DOMAIN ),
					'manage_ticket_system',
					'mets-test-data',
					array( $this->test_data_manager, 'display_test_data_page' )
				);
			}
		}
	}

	/**
	 * Admin init
	 *
	 * @since    1.0.0
	 */
	public function admin_init() {
		// Check and update capabilities if needed
		$this->maybe_update_capabilities();
		
		// Register settings
		register_setting( 'mets_settings', 'mets_general_settings' );
		register_setting( 'mets_settings', 'mets_email_settings' );
		register_setting( 'mets_settings', 'mets_file_upload_settings' );
		register_setting( 'mets_settings', 'mets_smtp_settings' );
		register_setting( 'mets_settings', 'mets_n8n_chat_settings' );
		
		// Handle form submissions early
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Check page parameter in both GET and POST
			$page = $_GET['page'] ?? $_POST['page'] ?? '';

			if ( $page === 'mets-entities' ) {
				$this->handle_entity_form_submission();
			} elseif ( $page === 'mets-tickets' || $page === 'mets-add-ticket' ) {
				$this->handle_ticket_form_submission();
			} elseif ( $page === 'mets-sla-rules' ) {
				$this->handle_sla_rules_form_submission();
			} elseif ( $page === 'mets-business-hours' ) {
				$this->handle_business_hours_form_submission();
			} elseif ( $page === 'mets-kb-add-article' && isset( $_POST['save_article'] ) ) {
				$this->handle_kb_article_form_submission();
			}
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'mets-entities' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['entity_id'] ) ) {
			$this->handle_entity_deletion();
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'mets-tickets' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['ticket_id'] ) ) {
			$this->handle_ticket_deletion();
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'mets-sla-rules' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['rule_id'] ) ) {
			$this->handle_sla_rule_deletion();
		}
		
		// Handle settings form submissions
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['page'] ) && $_POST['page'] === 'mets-settings' ) {
			$this->settings_handler->handle_settings_form_submission();
		}
	}
	
	/**
	 * Handle entity form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_entity_form_submission() {
		if ( ! current_user_can( 'manage_entities' ) ) {
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'admin/entities/class-mets-entity-manager.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$entity_model = new METS_Entity_Model();
		$action = sanitize_text_field( $_POST['action'] );
		
		if ( $action === 'create' ) {
			check_admin_referer( 'create_entity', 'entity_nonce' );
			
			$data = array(
				'name'            => sanitize_text_field( $_POST['entity_name'] ),
				'slug'            => sanitize_text_field( $_POST['entity_slug'] ),
				'parent_id'       => ! empty( $_POST['entity_parent'] ) ? intval( $_POST['entity_parent'] ) : null,
				'description'     => sanitize_textarea_field( $_POST['entity_description'] ),
				'status'          => sanitize_text_field( $_POST['entity_status'] ),
				'logo_url'        => esc_url_raw( $_POST['entity_logo_url'] ),
				'primary_color'   => sanitize_hex_color( $_POST['entity_primary_color'] ),
				'secondary_color' => sanitize_hex_color( $_POST['entity_secondary_color'] ),
			);
			
			$result = $entity_model->create( $data );
			
			if ( is_wp_error( $result ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => sprintf( __( 'Entity created successfully with ID: %d', METS_TEXT_DOMAIN ), $result ),
					'type' => 'success'
				), 45 );
			}
			
			wp_redirect( admin_url( 'admin.php?page=mets-entities' ) );
			exit;
			
		} elseif ( $action === 'update' ) {
			check_admin_referer( 'update_entity', 'entity_nonce' );
			
			$entity_id = intval( $_POST['entity_id'] );
			
			$data = array(
				'name'            => sanitize_text_field( $_POST['entity_name'] ),
				'slug'            => sanitize_text_field( $_POST['entity_slug'] ),
				'parent_id'       => ! empty( $_POST['entity_parent'] ) ? intval( $_POST['entity_parent'] ) : null,
				'description'     => sanitize_textarea_field( $_POST['entity_description'] ),
				'status'          => sanitize_text_field( $_POST['entity_status'] ),
				'logo_url'        => esc_url_raw( $_POST['entity_logo_url'] ),
				'primary_color'   => sanitize_hex_color( $_POST['entity_primary_color'] ),
				'secondary_color' => sanitize_hex_color( $_POST['entity_secondary_color'] ),
			);
			
			$result = $entity_model->update( $entity_id, $data );
			
			if ( is_wp_error( $result ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Entity updated successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			}
			
			wp_redirect( admin_url( 'admin.php?page=mets-entities' ) );
			exit;
		}
	}
	
	/**
	 * Handle entity deletion
	 *
	 * @since    1.0.0
	 */
	private function handle_entity_deletion() {
		if ( ! current_user_can( 'manage_entities' ) ) {
			return;
		}
		
		$entity_id = intval( $_GET['entity_id'] );
		check_admin_referer( 'delete_entity_' . $entity_id );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		
		$result = $entity_model->delete( $entity_id );
		
		if ( is_wp_error( $result ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => $result->get_error_message(),
				'type' => 'error'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Entity deleted successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-entities' ) );
		exit;
	}
	
	/**
	 * Handle ticket form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_ticket_form_submission() {
		if ( ! current_user_can( 'edit_tickets' ) ) {
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();
		
		// Determine which form was submitted based on submit button
		if ( isset( $_POST['submit_ticket'] ) ) {
			$action = sanitize_text_field( $_POST['action'] );
		} elseif ( isset( $_POST['save_properties'] ) ) {
			$action = 'update_properties';
		} elseif ( isset( $_POST['add_reply_submit'] ) ) {
			$action = 'add_reply';
		} else {
			$action = sanitize_text_field( $_POST['action'] );
		}
		
		if ( $action === 'create' ) {
			check_admin_referer( 'create_ticket', 'ticket_nonce' );
			
			$data = array(
				'entity_id'       => intval( $_POST['ticket_entity'] ),
				'subject'         => sanitize_text_field( $_POST['ticket_subject'] ),
				'description'     => wp_kses_post( $_POST['ticket_description'] ),
				'status'          => isset( $_POST['sidebar_ticket_status'] ) ? sanitize_text_field( $_POST['sidebar_ticket_status'] ) : 'new',
				'priority'        => isset( $_POST['sidebar_ticket_priority'] ) ? sanitize_text_field( $_POST['sidebar_ticket_priority'] ) : 'normal',
				'category'        => ! empty( $_POST['sidebar_ticket_category'] ) ? sanitize_text_field( $_POST['sidebar_ticket_category'] ) : '',
				'customer_name'   => sanitize_text_field( $_POST['customer_name'] ),
				'customer_email'  => sanitize_email( $_POST['customer_email'] ),
				'customer_phone'  => sanitize_text_field( $_POST['customer_phone'] ),
				'assigned_to'     => ! empty( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : null,
				'created_by'      => get_current_user_id(),
			);
			
			$result = $ticket_model->create( $data );
			
			if ( is_wp_error( $result ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
				wp_redirect( admin_url( 'admin.php?page=mets-add-ticket' ) );
			} else {
				// Handle file uploads for new ticket
				if ( ! empty( $_FILES['ticket_attachments']['name'][0] ) ) {
					require_once METS_PLUGIN_PATH . 'includes/class-mets-file-handler.php';
					$file_handler = new METS_File_Handler();
					$upload_results = $file_handler->handle_upload( $_FILES['ticket_attachments'], $result );
					
					// Check for upload errors and add to notices
					$upload_errors = array();
					$upload_success_count = 0;
					foreach ( $upload_results as $upload_result ) {
						if ( $upload_result['success'] ) {
							$upload_success_count++;
						} else {
							$upload_errors[] = sprintf( __( 'File "%s": %s', METS_TEXT_DOMAIN ), $upload_result['file'], $upload_result['message'] );
						}
					}
					
					$success_message = sprintf( __( 'Ticket created successfully. Ticket ID: %d', METS_TEXT_DOMAIN ), $result );
					if ( $upload_success_count > 0 ) {
						$success_message .= sprintf( __( ' %d file(s) uploaded.', METS_TEXT_DOMAIN ), $upload_success_count );
					}
					if ( ! empty( $upload_errors ) ) {
						$success_message .= __( ' Upload errors: ', METS_TEXT_DOMAIN ) . implode( ', ', $upload_errors );
					}
					
					set_transient( 'mets_admin_notice', array(
						'message' => $success_message,
						'type' => empty( $upload_errors ) ? 'success' : 'warning'
					), 45 );
				} else {
					set_transient( 'mets_admin_notice', array(
						'message' => sprintf( __( 'Ticket created successfully. Ticket ID: %d', METS_TEXT_DOMAIN ), $result ),
						'type' => 'success'
					), 45 );
				}
				wp_redirect( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $result ) );
			}
			exit;
			
		} elseif ( $action === 'update' ) {
			try {
				check_admin_referer( 'update_ticket', 'ticket_nonce' );
			} catch ( Exception $e ) {
				return;
			}

			$ticket_id = intval( $_POST['ticket_id'] );
			
			// Get current ticket data for change tracking
			$current_ticket = $ticket_model->get( $ticket_id );
			$changes = array();
			
			$data = array(
				'entity_id'       => intval( $_POST['ticket_entity'] ),
				'subject'         => sanitize_text_field( $_POST['ticket_subject'] ),
				'description'     => wp_kses_post( $_POST['ticket_description'] ),
				'customer_name'   => sanitize_text_field( $_POST['customer_name'] ),
				'customer_email'  => sanitize_email( $_POST['customer_email'] ),
				'customer_phone'  => sanitize_text_field( $_POST['customer_phone'] ),
			);
			
			// Track changes for logging
			if ( $current_ticket ) {
				if ( $current_ticket->entity_id != $data['entity_id'] ) {
					$old_entity = $this->get_entity_name( $current_ticket->entity_id );
					$new_entity = $this->get_entity_name( $data['entity_id'] );
					$changes[] = sprintf( __( 'Entity changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_entity, $new_entity );
				}
				if ( $current_ticket->subject != $data['subject'] ) {
					$changes[] = sprintf( __( 'Subject changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $current_ticket->subject, $data['subject'] );
				}
				if ( $current_ticket->customer_name != $data['customer_name'] ) {
					$changes[] = sprintf( __( 'Customer name changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $current_ticket->customer_name, $data['customer_name'] );
				}
				if ( $current_ticket->customer_email != $data['customer_email'] ) {
					$changes[] = sprintf( __( 'Customer email changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $current_ticket->customer_email, $data['customer_email'] );
				}
			}
			
			$result = $ticket_model->update( $ticket_id, $data );
			
			if ( is_wp_error( $result ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				
				// Log changes as system reply
				if ( ! empty( $changes ) ) {
					$change_log = implode( "\n", $changes );
					$reply_data = array(
						'ticket_id'        => $ticket_id,
						'user_id'          => get_current_user_id(),
						'user_type'        => 'system',
						'content'          => '<strong>' . __( 'Ticket Details Updated:', METS_TEXT_DOMAIN ) . '</strong><br>' . nl2br( esc_html( $change_log ) ),
						'is_internal_note' => false,
					);
					$reply_model->create( $reply_data );
				}
				
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Ticket details updated successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			}
			
			$redirect_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id );
			wp_redirect( $redirect_url );
			exit;

		} elseif ( $action === 'update_properties' ) {
			check_admin_referer( 'update_properties', 'properties_nonce' );

			$ticket_id = intval( $_POST['ticket_id'] );

			$data = array(
				'status'      => sanitize_text_field( $_POST['ticket_status'] ),
				'priority'    => sanitize_text_field( $_POST['ticket_priority'] ),
				'category'    => ! empty( $_POST['ticket_category'] ) ? sanitize_text_field( $_POST['ticket_category'] ) : '',
				'assigned_to' => ! empty( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : null,
			);

			// Validate workflow rules for status changes
			$current_ticket = $ticket_model->get( $ticket_id );
			if ( $current_ticket && $current_ticket->status !== $data['status'] ) {
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
				$workflow_model = new METS_Workflow_Model();
				$ticket_data = array(
					'priority' => $data['priority'],
					'category' => $data['category']
				);
				$workflow_result = $workflow_model->is_transition_allowed( $current_ticket->status, $data['status'], get_current_user_id(), $ticket_data );

				if ( is_wp_error( $workflow_result ) ) {
					set_transient( 'mets_admin_notice', array(
						'message' => sprintf( __( 'Status change not allowed: %s', METS_TEXT_DOMAIN ), $workflow_result->get_error_message() ),
						'type' => 'error'
					), 45 );
					wp_redirect( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id ) );
					exit;
				}
			}

			// Delegate to service layer for update + change tracking
			require_once METS_PLUGIN_PATH . 'includes/services/class-mets-ticket-service.php';
			$ticket_service = new METS_Ticket_Service();
			$result = $ticket_service->update_ticket_properties( $ticket_id, $data );

			if ( is_wp_error( $result ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				$changes = $result['changes'];

				// Log changes as system reply
				if ( ! empty( $changes ) ) {
					$change_log = implode( "\n", $changes );
					$reply_data = array(
						'ticket_id'        => $ticket_id,
						'user_id'          => get_current_user_id(),
						'user_type'        => 'system',
						'content'          => '<strong>' . __( 'Ticket Properties Updated:', METS_TEXT_DOMAIN ) . '</strong><br>' . nl2br( esc_html( $change_log ) ),
						'is_internal_note' => false,
					);
					$reply_model->create( $reply_data );
				}

				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Ticket properties updated successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			}

			$redirect_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id );
			wp_redirect( $redirect_url );
			exit;

		} elseif ( $action === 'add_reply' ) {
			check_admin_referer( 'add_reply', 'reply_nonce' );
			
			$ticket_id = intval( $_POST['ticket_id'] );
			$reply_content = wp_kses_post( $_POST['reply_content'] );
			$is_internal = ! empty( $_POST['is_internal_note'] );
			
			if ( ! empty( $reply_content ) ) {
				$reply_data = array(
					'ticket_id'        => $ticket_id,
					'user_id'          => get_current_user_id(),
					'user_type'        => 'agent',
					'content'          => $reply_content,
					'is_internal_note' => $is_internal,
				);
				
				$result = $reply_model->create( $reply_data );
				
				if ( is_wp_error( $result ) ) {
					set_transient( 'mets_admin_notice', array(
						'message' => $result->get_error_message(),
						'type' => 'error'
					), 45 );
				} else {
					$success_message = __( 'Reply added successfully.', METS_TEXT_DOMAIN );
					
					// Handle file uploads for reply
					if ( ! empty( $_FILES['reply_attachments']['name'][0] ) ) {
						require_once METS_PLUGIN_PATH . 'includes/class-mets-file-handler.php';
						$file_handler = new METS_File_Handler();
						$upload_results = $file_handler->handle_upload( $_FILES['reply_attachments'], $ticket_id, $result );
						
						// Check for upload errors and add to notices
						$upload_errors = array();
						$upload_success_count = 0;
						foreach ( $upload_results as $upload_result ) {
							if ( $upload_result['success'] ) {
								$upload_success_count++;
							} else {
								$upload_errors[] = sprintf( __( 'File "%s": %s', METS_TEXT_DOMAIN ), $upload_result['file'], $upload_result['message'] );
							}
						}
						
						if ( $upload_success_count > 0 ) {
							$success_message .= sprintf( __( ' %d file(s) uploaded.', METS_TEXT_DOMAIN ), $upload_success_count );
						}
						if ( ! empty( $upload_errors ) ) {
							$success_message .= __( ' Upload errors: ', METS_TEXT_DOMAIN ) . implode( ', ', $upload_errors );
						}
					}
					
					set_transient( 'mets_admin_notice', array(
						'message' => $success_message,
						'type' => 'success'
					), 45 );
				}
			}
			
			wp_redirect( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id ) );
			exit;
		}
	}
	
	/**
	 * Handle ticket deletion  
	 *
	 * @since    1.0.0
	 */
	private function handle_ticket_deletion() {
		if ( ! current_user_can( 'delete_tickets' ) ) {
			return;
		}
		
		$ticket_id = intval( $_GET['ticket_id'] );
		check_admin_referer( 'delete_ticket_' . $ticket_id );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$result = $ticket_model->delete( $ticket_id );
		
		if ( is_wp_error( $result ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => $result->get_error_message(),
				'type' => 'error'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket deleted successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-all-tickets' ) );
		exit;
	}

	/**
	 * Handle KB article form submission (from admin_init)
	 *
	 * @since    1.0.0
	 */
	private function handle_kb_article_form_submission() {
		error_log( '[METS-KB] Starting KB article form submission' );
		
		if ( ! current_user_can( 'manage_tickets' ) ) {
			error_log( '[METS-KB] Permission denied - user cannot manage tickets' );
			return;
		}
		
		error_log( '[METS-KB] User permissions OK' );
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['mets_kb_nonce'], 'mets_kb_save_article' ) ) {
			error_log( '[METS-KB] Nonce verification failed' );
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		error_log( '[METS-KB] Nonce verification OK' );
		
		// Get article ID from URL
		$article_id = isset( $_GET['article_id'] ) ? intval( $_GET['article_id'] ) : 0;
		error_log( '[METS-KB] Article ID: ' . $article_id );
		
		// Load required models
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$tag_model = new METS_KB_Tag_Model();
		
		error_log( '[METS-KB] Models loaded, calling handle_kb_article_save' );
		
		// Process the form (using existing method)
		$this->handle_kb_article_save( $article_model, $tag_model, $article_id );
		
		error_log( '[METS-KB] handle_kb_article_save completed' );
	}

	/**
	 * Display tickets page
	 *
	 * @since    1.0.0
	 */
	public function display_tickets_page() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			// Use nl2br for line breaks in error messages (especially for SMTP troubleshooting)
			$message = nl2br( esc_html( $notice['message'] ) );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), $message );
		}

		// Get current action
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$ticket_id = isset( $_GET['ticket_id'] ) ? intval( $_GET['ticket_id'] ) : 0;

		// Display appropriate view
		switch ( $action ) {
			case 'add':
				$this->display_add_ticket_page();
				break;
			case 'edit':
				$this->display_edit_ticket_page( $ticket_id );
				break;
			default:
				$this->display_tickets_list();
				break;
		}
	}

	/**
	 * Display tickets list
	 *
	 * @since    1.0.0
	 */
	private function display_tickets_list() {
		require_once METS_PLUGIN_PATH . 'admin/tickets/class-mets-tickets-list.php';
		
		$tickets_list = new METS_Tickets_List();
		$tickets_list->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . __( 'All Tickets', METS_TEXT_DOMAIN ) . '</h1>';
		
		if ( current_user_can( 'edit_tickets' ) ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=mets-add-ticket' ) ) . '" class="page-title-action">' . __( 'Add New', METS_TEXT_DOMAIN ) . '</a>';
		}
		
		echo '<hr class="wp-header-end">';

		// KB Quick Search Widget
		$this->display_kb_quick_search_widget();

		// Search form
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			printf( '<span class="subtitle">%s</span>', sprintf( __( 'Search results for: %s', METS_TEXT_DOMAIN ), '<strong>' . esc_html( $_GET['s'] ) . '</strong>' ) );
		}

		echo '<form method="get" action="">';
		echo '<input type="hidden" name="page" value="mets-tickets">';
		$tickets_list->search_box( __( 'Search Tickets', METS_TEXT_DOMAIN ), 'ticket' );
		echo '</form>';

		echo '<form method="get" action="">';
		echo '<input type="hidden" name="page" value="mets-tickets">';
		$tickets_list->display();
		echo '</form>';
		
		echo '</div>';
	}

	/**
	 * Display edit ticket page
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 */
	private function display_edit_ticket_page( $ticket_id ) {
		require_once METS_PLUGIN_PATH . 'admin/tickets/class-mets-ticket-manager.php';
		$ticket_manager = new METS_Ticket_Manager();
		$ticket_manager->display_edit_page( $ticket_id );
	}

	/**
	 * Display KB Quick Search Widget
	 *
	 * @since    1.0.0
	 */
	private function display_kb_quick_search_widget() {
		// Check if user has KB access
		if ( ! current_user_can( 'read_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			return;
		}
		
		?>
		<div id="mets-kb-quick-search" style="background: #f8f9fa; border: 1px solid #c3c4c7; border-radius: 4px; padding: 15px; margin: 15px 0;">
			<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
				<div style="flex: 1; min-width: 300px;">
					<label for="kb-quick-search-input" style="font-weight: 600; margin-right: 10px;">
						<?php _e( 'Quick KB Search:', METS_TEXT_DOMAIN ); ?>
					</label>
					<input type="text" id="kb-quick-search-input" class="regular-text" 
						   placeholder="<?php esc_attr_e( 'Search knowledge base articles...', METS_TEXT_DOMAIN ); ?>" 
						   style="margin-right: 10px;">
					<button type="button" id="kb-quick-search-btn" class="button">
						<?php _e( 'Search', METS_TEXT_DOMAIN ); ?>
					</button>
				</div>
				<div style="display: flex; gap: 10px;">
					<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="button button-secondary">
						<?php _e( 'Browse Articles', METS_TEXT_DOMAIN ); ?>
					</a>
					<?php if ( current_user_can( 'edit_kb_articles' ) ) : ?>
					<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="button button-primary">
						<?php _e( 'New Article', METS_TEXT_DOMAIN ); ?>
					</a>
					<?php endif; ?>
				</div>
			</div>
			
			<div id="kb-quick-search-results" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #c3c4c7;">
				<div id="kb-quick-results-list"></div>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			var quickSearchTimeout;
			
			// Quick search functionality
			$('#kb-quick-search-btn, #kb-quick-search-input').on('click keypress', function(e) {
				if (e.type === 'click' || e.which === 13) {
					e.preventDefault();
					performQuickKBSearch();
				}
			});
			
			// Real-time search with debounce
			$('#kb-quick-search-input').on('input', function() {
				clearTimeout(quickSearchTimeout);
				var searchTerm = $(this).val().trim();
				
				if (searchTerm.length < 2) {
					$('#kb-quick-search-results').hide();
					return;
				}
				
				quickSearchTimeout = setTimeout(function() {
					performQuickKBSearch();
				}, 500);
			});
			
			function performQuickKBSearch() {
				var searchTerm = $('#kb-quick-search-input').val().trim();
				
				if (searchTerm.length < 2) {
					$('#kb-quick-search-results').hide();
					return;
				}
				
				$('#kb-quick-results-list').html('<p><?php _e( 'Searching...', METS_TEXT_DOMAIN ); ?></p>');
				$('#kb-quick-search-results').show();
				
				$.post(mets_admin_ajax.ajax_url, {
					action: 'mets_admin_search_kb_articles',
					nonce: mets_admin_ajax.nonce,
					search: searchTerm,
					entity_id: 0,
					limit: 8
				}, function(response) {
					if (response.success) {
						displayQuickKBResults(response.data.articles);
					} else {
						$('#kb-quick-results-list').html('<p class="error"><?php _e( 'Search failed. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
					}
				}).fail(function() {
					$('#kb-quick-results-list').html('<p class="error"><?php _e( 'Search request failed.', METS_TEXT_DOMAIN ); ?></p>');
				});
			}
			
			function displayQuickKBResults(articles) {
				var resultsHtml = '';
				
				if (articles.length === 0) {
					resultsHtml = '<p><?php _e( 'No articles found.', METS_TEXT_DOMAIN ); ?></p>';
				} else {
					resultsHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px;">';
					$.each(articles, function(i, article) {
						var helpfulScore = article.helpful_yes > 0 || article.helpful_no > 0 
							? Math.round((article.helpful_yes / (article.helpful_yes + article.helpful_no)) * 100) + '% helpful'
							: '';
						
						resultsHtml += '<div style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white;">';
						resultsHtml += '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">';
						resultsHtml += '<a href="' + article.url + '" style="font-weight: 600; color: #0073aa; text-decoration: none; flex: 1;">' + article.title + '</a>';
						resultsHtml += '<small style="color: #666; margin-left: 10px;">' + article.entity_name + '</small>';
						resultsHtml += '</div>';
						if (article.excerpt) {
							resultsHtml += '<div style="color: #666; font-size: 13px; margin-bottom: 5px;">' + article.excerpt.substring(0, 100) + (article.excerpt.length > 100 ? '...' : '') + '</div>';
						}
						if (helpfulScore) {
							resultsHtml += '<div style="font-size: 12px; color: #28a745;">' + helpfulScore + '</div>';
						}
						resultsHtml += '</div>';
					});
					resultsHtml += '</div>';
					
					if (articles.length >= 8) {
						resultsHtml += '<div style="text-align: center; margin-top: 10px;">';
						resultsHtml += '<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="button button-secondary"><?php _e( 'View All Results', METS_TEXT_DOMAIN ); ?></a>';
						resultsHtml += '</div>';
					}
				}
				
				$('#kb-quick-results-list').html(resultsHtml);
			}
		});
		</script>
		<?php
	}

	/**
	 * Display add ticket page
	 *
	 * @since    1.0.0
	 */
	public function display_add_ticket_page() {
		require_once METS_PLUGIN_PATH . 'admin/tickets/class-mets-ticket-manager.php';
		$ticket_manager = new METS_Ticket_Manager();
		$ticket_manager->display_add_page();
	}

	/**
	 * Display entities page
	 *
	 * @since    1.0.0
	 */
	public function display_entities_page() {
		// Include entities management
		require_once METS_PLUGIN_PATH . 'admin/entities/class-mets-entity-manager.php';
		$entity_manager = new METS_Entity_Manager();
		$entity_manager->display_page();
	}

	/**
	 * Display users page
	 *
	 * @since    1.0.0
	 */
	public function display_users_page() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'User Management', METS_TEXT_DOMAIN ) . '</h1>';
		echo '<p>' . __( 'User management page placeholder - to be implemented in Phase 1', METS_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}

	/**
	 * Display agents page
	 *
	 * @since    1.0.0
	 */
	public function display_agents_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Initialize agent management class if not already loaded
		if ( ! class_exists( 'METS_Agent_Management' ) ) {
			require_once METS_PLUGIN_PATH . 'admin/class-mets-agent-management.php';
		}

		$agent_management = new METS_Agent_Management();
		$agent_management->display_agent_management_page();
	}

	/**
	 * Display manager dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_manager_dashboard_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Initialize manager dashboard class if not already loaded
		if ( ! class_exists( 'METS_Manager_Dashboard' ) ) {
			require_once METS_PLUGIN_PATH . 'admin/class-mets-manager-dashboard.php';
		}

		$manager_dashboard = new METS_Manager_Dashboard();
		$manager_dashboard->display_dashboard();
	}

	/**
	 * Display reports page
	 *
	 * @since    1.0.0
	 */
	public function display_reports_page() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Reports', METS_TEXT_DOMAIN ) . '</h1>';
		echo '<p>' . __( 'Reports page placeholder - to be implemented in Phase 5', METS_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}

	/**
	 * Get general settings value
	 *
	 * @since    1.0.0
	 * @param    string    $key      Setting key
	 * @param    mixed     $default  Default value if setting doesn't exist
	 * @return   mixed               Setting value or default
	 */
	public static function get_general_setting( $key, $default = '' ) {
		$settings = get_option( 'mets_general_settings', array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Display admin notices
	 *
	 * @since    1.0.0
	 */
	private function display_admin_notices() {
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			// Use nl2br for line breaks in error messages (especially for SMTP troubleshooting)
			$message = nl2br( esc_html( $notice['message'] ) );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), $message );
		}
	}

	/**
	 * Display SLA Rules page
	 *
	 * @since    1.0.0
	 */
	public function display_sla_rules_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$rule_id = isset( $_GET['rule_id'] ) ? intval( $_GET['rule_id'] ) : 0;

		switch ( $action ) {
			case 'add':
				$this->display_add_sla_rule_form();
				break;
			case 'edit':
				$this->display_edit_sla_rule_form( $rule_id );
				break;
			default:
				$this->display_sla_rules_list();
				break;
		}
	}

	/**
	 * Display SLA Rules list
	 *
	 * @since    1.0.0
	 */
	private function display_sla_rules_list() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$sla_model = new METS_SLA_Rule_Model();
		$entity_model = new METS_Entity_Model();
		
		$result = $sla_model->get_all( array(
			'per_page' => 20,
			'page' => isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1
		) );
		
		$rules = $result['rules'];
		$total = $result['total'];
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'SLA Rules', METS_TEXT_DOMAIN ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-sla-rules&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', METS_TEXT_DOMAIN ); ?></a>
			
			<?php $this->display_admin_notices(); ?>
			
			<div class="postbox">
				<div class="inside">
					<?php if ( empty( $rules ) ) : ?>
						<p><?php _e( 'No SLA rules found.', METS_TEXT_DOMAIN ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th scope="col"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Response Time', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Resolution Time', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rules as $rule ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $rule->name ); ?></strong></td>
										<td>
											<?php 
											if ( $rule->entity_id ) {
												$entity = $entity_model->get( $rule->entity_id );
												echo $entity ? esc_html( $entity->name ) : __( 'Unknown Entity', METS_TEXT_DOMAIN );
											} else {
												echo '<span class="global-rule">' . __( 'Global Rule', METS_TEXT_DOMAIN ) . '</span>';
											}
											?>
										</td>
										<td>
											<span class="priority-badge priority-<?php echo esc_attr( $rule->priority ); ?>">
												<?php echo esc_html( ucfirst( $rule->priority ) ); ?>
											</span>
										</td>
										<td><?php echo $rule->response_time ? sprintf( _n( '%d hour', '%d hours', $rule->response_time, METS_TEXT_DOMAIN ), $rule->response_time ) : '—'; ?></td>
										<td><?php echo $rule->resolution_time ? sprintf( _n( '%d hour', '%d hours', $rule->resolution_time, METS_TEXT_DOMAIN ), $rule->resolution_time ) : '—'; ?></td>
										<td>
											<?php if ( $rule->is_active ) : ?>
												<span class="status-active"><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></span>
											<?php else : ?>
												<span class="status-inactive"><?php _e( 'Inactive', METS_TEXT_DOMAIN ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<a href="<?php echo admin_url( 'admin.php?page=mets-sla-rules&action=edit&rule_id=' . $rule->id ); ?>" class="button button-small"><?php _e( 'Edit', METS_TEXT_DOMAIN ); ?></a>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-sla-rules&action=delete&rule_id=' . $rule->id ), 'delete_rule_' . $rule->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this SLA rule?', METS_TEXT_DOMAIN ); ?>')"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<style>
		.priority-badge {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}
		.priority-low { background: #d4edda; color: #155724; }
		.priority-normal { background: #cce5ff; color: #004085; }
		.priority-high { background: #fff3cd; color: #856404; }
		.priority-urgent { background: #f8d7da; color: #721c24; }
		.status-active { color: #28a745; font-weight: bold; }
		.status-inactive { color: #6c757d; }
		.global-rule { font-style: italic; color: #666; }
		</style>
		<?php
	}

	/**
	 * Display add SLA rule form
	 *
	 * @since    1.0.0
	 */
	private function display_add_sla_rule_form() {
		$this->display_sla_rule_form();
	}

	/**
	 * Display edit SLA rule form
	 *
	 * @since    1.0.0
	 * @param    int    $rule_id    Rule ID
	 */
	private function display_edit_sla_rule_form( $rule_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		$sla_model = new METS_SLA_Rule_Model();
		$rule = $sla_model->get_by_id( $rule_id );
		
		if ( ! $rule ) {
			wp_die( __( 'SLA rule not found.', METS_TEXT_DOMAIN ) );
		}
		
		$this->display_sla_rule_form( $rule );
	}

	/**
	 * Display SLA rule form
	 *
	 * @since    1.0.0
	 * @param    object|null    $rule    Rule object for editing
	 */
	private function display_sla_rule_form( $rule = null ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all(array('parent_id' => 'all'));
		
		$is_edit = ! is_null( $rule );
		$page_title = $is_edit ? __( 'Edit SLA Rule', METS_TEXT_DOMAIN ) : __( 'Add SLA Rule', METS_TEXT_DOMAIN );
		$button_text = $is_edit ? __( 'Update Rule', METS_TEXT_DOMAIN ) : __( 'Create Rule', METS_TEXT_DOMAIN );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $page_title ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-sla-rules' ); ?>" class="page-title-action"><?php _e( '← Back to Rules', METS_TEXT_DOMAIN ); ?></a>
			
			<?php $this->display_admin_notices(); ?>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_sla_rule_form', 'sla_rule_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_sla_rule' : 'add_sla_rule'; ?>">
				<input type="hidden" name="page" value="mets-sla-rules">
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule->id ); ?>">
				<?php endif; ?>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Rule Details', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="rule_name"><?php _e( 'Rule Name', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" id="rule_name" name="rule_name" value="<?php echo $is_edit ? esc_attr( $rule->name ) : ''; ?>" class="regular-text" required>
									<p class="description"><?php _e( 'A descriptive name for this SLA rule.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="entity_id"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<select id="entity_id" name="entity_id">
										<option value=""><?php _e( 'Global Rule (All Entities)', METS_TEXT_DOMAIN ); ?></option>
										<?php 
										// Organize entities hierarchically for consistent display
										$organized_entities = $this->organize_entities_hierarchically( $entities );
										$this->render_hierarchical_entity_options( $organized_entities, $is_edit ? $rule->entity_id : '' );
										?>
									</select>
									<p class="description"><?php _e( 'Leave empty to apply this rule to all entities, or select a specific entity. Child locations are shown indented under their parent.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="priority"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<select id="priority" name="priority" required>
										<option value="low" <?php selected( $is_edit ? $rule->priority : '', 'low' ); ?>><?php _e( 'Low', METS_TEXT_DOMAIN ); ?></option>
										<option value="normal" <?php selected( $is_edit ? $rule->priority : 'normal', 'normal' ); ?>><?php _e( 'Normal', METS_TEXT_DOMAIN ); ?></option>
										<option value="high" <?php selected( $is_edit ? $rule->priority : '', 'high' ); ?>><?php _e( 'High', METS_TEXT_DOMAIN ); ?></option>
										<option value="urgent" <?php selected( $is_edit ? $rule->priority : '', 'urgent' ); ?>><?php _e( 'Urgent', METS_TEXT_DOMAIN ); ?></option>
										<option value="all" <?php selected( $is_edit ? $rule->priority : '', 'all' ); ?>><?php _e( 'All Priorities', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'The ticket priority this rule applies to.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'SLA Timeframes', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="response_time"><?php _e( 'Response Time (hours)', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="number" id="response_time" name="response_time" value="<?php echo $is_edit ? esc_attr( $rule->response_time ) : ''; ?>" min="0" step="0.5" class="small-text">
									<p class="description"><?php _e( 'Time limit for first response to tickets. Leave empty to disable.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="resolution_time"><?php _e( 'Resolution Time (hours)', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="number" id="resolution_time" name="resolution_time" value="<?php echo $is_edit ? esc_attr( $rule->resolution_time ) : ''; ?>" min="0" step="0.5" class="small-text">
									<p class="description"><?php _e( 'Time limit for resolving tickets. Leave empty to disable.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="escalation_time"><?php _e( 'Escalation Time (hours)', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="number" id="escalation_time" name="escalation_time" value="<?php echo $is_edit ? esc_attr( $rule->escalation_time ) : ''; ?>" min="0" step="0.5" class="small-text">
									<p class="description"><?php _e( 'Time after which tickets should be escalated. Leave empty to disable.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="business_hours_only"><?php _e( 'Business Hours Only', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="business_hours_only" name="business_hours_only" value="1" <?php checked( $is_edit ? $rule->business_hours_only : false ); ?>>
										<?php _e( 'Calculate SLA timeframes using business hours only', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, SLA calculations will exclude weekends and non-business hours.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Rule Status', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="is_active"><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="is_active" name="is_active" value="1" <?php checked( $is_edit ? $rule->is_active : true ); ?>>
										<?php _e( 'Enable this SLA rule', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'Inactive rules will not be applied to new tickets.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<?php submit_button( $button_text, 'primary', 'submit' ); ?>
			</form>
		</div>
		
		<style>
		.required { color: #d63638; }
		.form-table th { width: 200px; }
		</style>
		<?php
	}

	/**
	 * Handle SLA rules form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_sla_rules_form_submission() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}
		
		$action = $_POST['action'] ?? '';
		
		if ( $action === 'add_sla_rule' ) {
			$this->handle_add_sla_rule();
		} elseif ( $action === 'edit_sla_rule' ) {
			$this->handle_edit_sla_rule();
		}
	}

	/**
	 * Handle add SLA rule
	 *
	 * @since    1.0.0
	 */
	private function handle_add_sla_rule() {
		if ( ! wp_verify_nonce( $_POST['sla_rule_nonce'], 'mets_sla_rule_form' ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		$sla_model = new METS_SLA_Rule_Model();
		
		$data = array(
			'entity_id' => ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null,
			'name' => sanitize_text_field( $_POST['rule_name'] ),
			'priority' => sanitize_text_field( $_POST['priority'] ),
			'response_time_hours' => ! empty( $_POST['response_time'] ) ? floatval( $_POST['response_time'] ) : null,
			'resolution_time_hours' => ! empty( $_POST['resolution_time'] ) ? floatval( $_POST['resolution_time'] ) : null,
			'escalation_time_hours' => ! empty( $_POST['escalation_time'] ) ? floatval( $_POST['escalation_time'] ) : null,
			'business_hours_only' => isset( $_POST['business_hours_only'] ) ? 1 : 0,
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
		);
		
		$rule_id = $sla_model->create( $data );
		
		if ( $rule_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'SLA rule created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			wp_redirect( admin_url( 'admin.php?page=mets-sla-rules' ) );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create SLA rule.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		exit;
	}

	/**
	 * Handle edit SLA rule
	 *
	 * @since    1.0.0
	 */
	private function handle_edit_sla_rule() {
		if ( ! wp_verify_nonce( $_POST['sla_rule_nonce'], 'mets_sla_rule_form' ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		$sla_model = new METS_SLA_Rule_Model();
		
		$rule_id = intval( $_POST['rule_id'] );
		$data = array(
			'entity_id' => ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null,
			'name' => sanitize_text_field( $_POST['rule_name'] ),
			'priority' => sanitize_text_field( $_POST['priority'] ),
			'response_time_hours' => ! empty( $_POST['response_time'] ) ? floatval( $_POST['response_time'] ) : null,
			'resolution_time_hours' => ! empty( $_POST['resolution_time'] ) ? floatval( $_POST['resolution_time'] ) : null,
			'escalation_time_hours' => ! empty( $_POST['escalation_time'] ) ? floatval( $_POST['escalation_time'] ) : null,
			'business_hours_only' => isset( $_POST['business_hours_only'] ) ? 1 : 0,
			'is_active' => isset( $_POST['is_active'] ) ? 1 : 0,
		);
		
		$result = $sla_model->update( $rule_id, $data );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'SLA rule updated successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			wp_redirect( admin_url( 'admin.php?page=mets-sla-rules' ) );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to update SLA rule.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		exit;
	}

	/**
	 * Handle SLA rule deletion
	 *
	 * @since    1.0.0
	 */
	private function handle_sla_rule_deletion() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}
		
		$rule_id = intval( $_GET['rule_id'] );
		check_admin_referer( 'delete_rule_' . $rule_id );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-sla-rule-model.php';
		$sla_model = new METS_SLA_Rule_Model();
		
		$result = $sla_model->delete( $rule_id );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'SLA rule deleted successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to delete SLA rule.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-sla-rules' ) );
		exit;
	}

	/**
	 * Display Business Hours page
	 *
	 * @since    1.0.0
	 */
	public function display_business_hours_page() {
		$entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-business-hours-model.php';
		
		$entity_model = new METS_Entity_Model();
		$business_hours_model = new METS_Business_Hours_Model();
		$all_entities = $entity_model->get_all(array('parent_id' => 'all'));
		
		// Organize entities into hierarchical structure
		$entities = $this->organize_entities_hierarchically( $all_entities );
		
		// Get current entity's business hours or global defaults
		if ( $entity_id ) {
			$entity = $entity_model->get( $entity_id );
			if ( ! $entity ) {
				wp_die( __( 'Entity not found.', METS_TEXT_DOMAIN ) );
			}
			$business_hours = $business_hours_model->get_by_entity( $entity_id );
		} else {
			$business_hours = $business_hours_model->get_global_hours();
		}
		
		// Convert to associative array by day for easier access
		$hours_by_day = array();
		foreach ( $business_hours as $hour ) {
			$hours_by_day[ $hour->day_of_week ] = $hour;
		}
		
		$days_of_week = array(
			1 => __( 'Monday', METS_TEXT_DOMAIN ),
			2 => __( 'Tuesday', METS_TEXT_DOMAIN ),
			3 => __( 'Wednesday', METS_TEXT_DOMAIN ),
			4 => __( 'Thursday', METS_TEXT_DOMAIN ),
			5 => __( 'Friday', METS_TEXT_DOMAIN ),
			6 => __( 'Saturday', METS_TEXT_DOMAIN ),
			0 => __( 'Sunday', METS_TEXT_DOMAIN ),
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Business Hours', METS_TEXT_DOMAIN ); ?></h1>
			
			<?php $this->display_admin_notices(); ?>
			
			<!-- Entity Selector -->
			<div class="postbox" style="margin-top: 20px;">
				<h3 class="hndle"><span><?php _e( 'Select Entity', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<form method="get" action="">
						<input type="hidden" name="page" value="mets-business-hours">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="entity_select"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<select id="entity_select" name="entity_id" onchange="this.form.submit()">
										<option value=""><?php _e( 'Global Business Hours (Default)', METS_TEXT_DOMAIN ); ?></option>
										<?php $this->render_hierarchical_entity_options( $entities, $entity_id ); ?>
									</select>
									<p class="description">
										<?php _e( 'Configure business hours for a specific entity or set global defaults. Parent entities and their child locations are shown in a hierarchical structure.', METS_TEXT_DOMAIN ); ?>
									</p>
									<?php if ( $entity_id ) : ?>
										<p class="description hierarchy-info">
											<strong><?php _e( 'Note:', METS_TEXT_DOMAIN ); ?></strong>
											<?php 
											$current_entity = $entity_model->get( $entity_id );
											if ( $current_entity && $current_entity->parent_id ) {
												_e( 'You are configuring business hours for a child location. These hours will override any parent entity settings for SLA calculations.', METS_TEXT_DOMAIN );
											} else {
												_e( 'You are configuring business hours for a parent entity. Child locations can have their own specific hours if needed.', METS_TEXT_DOMAIN );
											}
											?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					</form>
				</div>
			</div>
			
			<!-- Business Hours Form -->
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_business_hours_form', 'business_hours_nonce' ); ?>
				<input type="hidden" name="action" value="save_business_hours">
				<input type="hidden" name="page" value="mets-business-hours">
				<input type="hidden" name="entity_id" value="<?php echo esc_attr( $entity_id ); ?>">
				
				<div class="postbox">
					<h3 class="hndle">
						<span>
							<?php 
							if ( $entity_id ) {
								printf( __( 'Business Hours for %s', METS_TEXT_DOMAIN ), esc_html( $this->get_entity_name_with_hierarchy( $entity_id ) ) );
							} else {
								_e( 'Global Business Hours', METS_TEXT_DOMAIN );
							}
							?>
						</span>
					</h3>
					<div class="inside">
						<table class="form-table business-hours-table">
							<thead>
								<tr>
									<th><?php _e( 'Day', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'Start Time', METS_TEXT_DOMAIN ); ?></th>
									<th><?php _e( 'End Time', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $days_of_week as $day_num => $day_name ) : 
									$current_hour = isset( $hours_by_day[ $day_num ] ) ? $hours_by_day[ $day_num ] : null;
									$is_active = $current_hour ? $current_hour->is_active : false;
									$start_time = $current_hour ? $current_hour->start_time : '09:00';
									$end_time = $current_hour ? $current_hour->end_time : '17:00';
								?>
									<tr class="business-hour-row">
										<td>
											<strong><?php echo esc_html( $day_name ); ?></strong>
											<input type="hidden" name="days[<?php echo $day_num; ?>][day]" value="<?php echo $day_num; ?>">
										</td>
										<td>
											<label>
												<input type="checkbox" 
													   name="days[<?php echo $day_num; ?>][active]" 
													   value="1" 
													   <?php checked( $is_active ); ?>
													   class="day-active-checkbox"
													   data-day="<?php echo $day_num; ?>">
												<?php _e( 'Open', METS_TEXT_DOMAIN ); ?>
											</label>
										</td>
										<td>
											<input type="time" 
												   name="days[<?php echo $day_num; ?>][start_time]" 
												   value="<?php echo esc_attr( $start_time ); ?>"
												   class="time-field"
												   data-day="<?php echo $day_num; ?>"
												   <?php disabled( ! $is_active ); ?>>
										</td>
										<td>
											<input type="time" 
												   name="days[<?php echo $day_num; ?>][end_time]" 
												   value="<?php echo esc_attr( $end_time ); ?>"
												   class="time-field"
												   data-day="<?php echo $day_num; ?>"
												   <?php disabled( ! $is_active ); ?>>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<p class="description">
							<?php _e( 'Set the business hours for SLA calculations. When "Business Hours Only" is enabled in SLA rules, these hours will be used to calculate due dates excluding weekends and non-business hours.', METS_TEXT_DOMAIN ); ?>
						</p>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Quick Actions', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<p>
							<button type="button" class="button" onclick="setStandardHours()">
								<?php _e( 'Set Standard Hours (9 AM - 5 PM, Mon-Fri)', METS_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="button" onclick="setExtendedHours()">
								<?php _e( 'Set Extended Hours (8 AM - 6 PM, Mon-Sat)', METS_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="button" onclick="set24Hours()">
								<?php _e( 'Set 24/7 Hours', METS_TEXT_DOMAIN ); ?>
							</button>
						</p>
					</div>
				</div>
				
				<?php submit_button( __( 'Save Business Hours', METS_TEXT_DOMAIN ), 'primary', 'submit' ); ?>
			</form>
		</div>
		
		<style>
		.business-hours-table {
			width: 100%;
			border-collapse: collapse;
		}
		.business-hours-table th,
		.business-hours-table td {
			padding: 12px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		.business-hours-table th {
			background-color: #f9f9f9;
			font-weight: bold;
		}
		.business-hour-row:hover {
			background-color: #f5f5f5;
		}
		.time-field {
			width: 100px;
		}
		.business-hour-row.inactive {
			opacity: 0.6;
		}
		.business-hour-row.inactive .time-field {
			background-color: #f5f5f5;
		}
		
		/* Hierarchical entity styling */
		#entity_select {
			min-width: 300px;
		}
		#entity_select option.parent-entity {
			font-weight: bold;
			color: #2271b1;
		}
		#entity_select option.child-entity {
			color: #666;
			font-style: italic;
			padding-left: 20px;
		}
		
		/* Enhanced description for child entities */
		.description {
			margin-top: 5px;
		}
		.description.hierarchy-info {
			background: #f0f8ff;
			padding: 8px;
			border-left: 3px solid #2271b1;
			margin-top: 10px;
		}
		</style>
		
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Handle day active/inactive toggle
			document.querySelectorAll('.day-active-checkbox').forEach(function(checkbox) {
				checkbox.addEventListener('change', function() {
					var day = this.dataset.day;
					var row = this.closest('.business-hour-row');
					var timeFields = row.querySelectorAll('.time-field[data-day="' + day + '"]');
					
					if (this.checked) {
						row.classList.remove('inactive');
						timeFields.forEach(function(field) {
							field.disabled = false;
						});
					} else {
						row.classList.add('inactive');
						timeFields.forEach(function(field) {
							field.disabled = true;
						});
					}
				});
				
				// Initialize state
				if (!checkbox.checked) {
					checkbox.closest('.business-hour-row').classList.add('inactive');
				}
			});
		});
		
		function setStandardHours() {
			// Monday to Friday: 9 AM - 5 PM
			var standardDays = [1, 2, 3, 4, 5];
			
			// First, uncheck all days
			document.querySelectorAll('.day-active-checkbox').forEach(function(checkbox) {
				checkbox.checked = false;
				checkbox.dispatchEvent(new Event('change'));
			});
			
			// Set standard hours for weekdays
			standardDays.forEach(function(day) {
				var checkbox = document.querySelector('.day-active-checkbox[data-day="' + day + '"]');
				var startTime = document.querySelector('input[name="days[' + day + '][start_time]"]');
				var endTime = document.querySelector('input[name="days[' + day + '][end_time]"]');
				
				checkbox.checked = true;
				checkbox.dispatchEvent(new Event('change'));
				startTime.value = '09:00';
				endTime.value = '17:00';
			});
		}
		
		function setExtendedHours() {
			// Monday to Saturday: 8 AM - 6 PM
			var extendedDays = [1, 2, 3, 4, 5, 6];
			
			// First, uncheck all days
			document.querySelectorAll('.day-active-checkbox').forEach(function(checkbox) {
				checkbox.checked = false;
				checkbox.dispatchEvent(new Event('change'));
			});
			
			// Set extended hours
			extendedDays.forEach(function(day) {
				var checkbox = document.querySelector('.day-active-checkbox[data-day="' + day + '"]');
				var startTime = document.querySelector('input[name="days[' + day + '][start_time]"]');
				var endTime = document.querySelector('input[name="days[' + day + '][end_time]"]');
				
				checkbox.checked = true;
				checkbox.dispatchEvent(new Event('change'));
				startTime.value = '08:00';
				endTime.value = '18:00';
			});
		}
		
		function set24Hours() {
			// All days: 24 hours
			document.querySelectorAll('.day-active-checkbox').forEach(function(checkbox) {
				var day = checkbox.dataset.day;
				var startTime = document.querySelector('input[name="days[' + day + '][start_time]"]');
				var endTime = document.querySelector('input[name="days[' + day + '][end_time]"]');
				
				checkbox.checked = true;
				checkbox.dispatchEvent(new Event('change'));
				startTime.value = '00:00';
				endTime.value = '23:59';
			});
		}
		</script>
		<?php
	}

	/**
	 * Handle business hours form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_business_hours_form_submission() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}
		
		$action = $_POST['action'] ?? '';
		
		if ( $action === 'save_business_hours' ) {
			$this->handle_save_business_hours();
		}
	}

	/**
	 * Handle save business hours
	 *
	 * @since    1.0.0
	 */
	private function handle_save_business_hours() {
		if ( ! wp_verify_nonce( $_POST['business_hours_nonce'], 'mets_business_hours_form' ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-business-hours-model.php';
		$business_hours_model = new METS_Business_Hours_Model();
		
		$entity_id = ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		$days_data = $_POST['days'] ?? array();
		
		// Clear existing business hours for this entity
		$business_hours_model->clear_entity_hours( $entity_id );
		
		$success_count = 0;
		$total_days = 0;
		
		foreach ( $days_data as $day_data ) {
			$total_days++;
			
			$data = array(
				'entity_id' => $entity_id,
				'day_of_week' => intval( $day_data['day'] ),
				'start_time' => sanitize_text_field( $day_data['start_time'] ),
				'end_time' => sanitize_text_field( $day_data['end_time'] ),
				'is_active' => isset( $day_data['active'] ) ? 1 : 0,
			);
			
			if ( $business_hours_model->create( $data ) ) {
				$success_count++;
			}
		}
		
		if ( $success_count === $total_days ) {
			$entity_name = $entity_id ? $this->get_entity_name_with_hierarchy( $entity_id ) : __( 'Global Settings', METS_TEXT_DOMAIN );
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf(
					__( 'Business hours updated successfully for %s.', METS_TEXT_DOMAIN ),
					$entity_name
				),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Some business hours could not be saved. Please try again.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		
		$redirect_url = admin_url( 'admin.php?page=mets-business-hours' );
		if ( $entity_id ) {
			$redirect_url .= '&entity_id=' . $entity_id;
		}
		
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Organize entities into hierarchical structure
	 *
	 * @since    1.0.0
	 * @param    array    $entities    Flat array of entities
	 * @return   array                 Hierarchically organized entities
	 */
	private function organize_entities_hierarchically( $entities ) {
		$parents = array();
		$children = array();
		
		// Separate parents and children
		foreach ( $entities as $entity ) {
			if ( empty( $entity->parent_id ) ) {
				$parents[] = $entity;
			} else {
				if ( ! isset( $children[ $entity->parent_id ] ) ) {
					$children[ $entity->parent_id ] = array();
				}
				$children[ $entity->parent_id ][] = $entity;
			}
		}
		
		// Combine parents with their children
		$organized = array();
		foreach ( $parents as $parent ) {
			$organized[] = $parent;
			if ( isset( $children[ $parent->id ] ) ) {
				foreach ( $children[ $parent->id ] as $child ) {
					$organized[] = $child;
				}
			}
		}
		
		return $organized;
	}

	/**
	 * Render hierarchical entity options for select dropdown
	 *
	 * @since    1.0.0
	 * @param    array    $entities     Hierarchically organized entities
	 * @param    int      $selected_id  Currently selected entity ID
	 */
	private function render_hierarchical_entity_options( $entities, $selected_id ) {
		$parent_ids = array();
		
		// First pass: identify parent entities
		foreach ( $entities as $entity ) {
			if ( empty( $entity->parent_id ) ) {
				$parent_ids[] = $entity->id;
			}
		}
		
		foreach ( $entities as $entity ) {
			$is_parent = empty( $entity->parent_id );
			$is_child = ! empty( $entity->parent_id );
			
			// Determine option styling and text
			if ( $is_parent ) {
				$option_class = 'parent-entity';
				$prefix = '';
				$display_name = $entity->name;
			} else {
				$option_class = 'child-entity';
				$prefix = '&nbsp;&nbsp;&nbsp;├─ ';
				$display_name = $entity->name;
			}
			
			// Additional styling for child entities
			$style = $is_child ? 'style="color: #666; font-style: italic;"' : '';
			
			printf(
				'<option value="%d" class="%s" %s %s>%s%s</option>',
				esc_attr( $entity->id ),
				esc_attr( $option_class ),
				selected( $selected_id, $entity->id, false ),
				$style,
				$prefix,
				esc_html( $display_name )
			);
		}
	}

	/**
	 * Get entity name with hierarchy info
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   string               Entity name with hierarchy info
	 */
	private function get_entity_name_with_hierarchy( $entity_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get( $entity_id );
		
		if ( ! $entity ) {
			return __( 'Unknown Entity', METS_TEXT_DOMAIN );
		}
		
		$name = $entity->name;
		
		// If this is a child entity, add parent info
		if ( $entity->parent_id ) {
			$parent = $entity_model->get( $entity->parent_id );
			if ( $parent ) {
				$name = $parent->name . ' → ' . $entity->name;
			}
		}
		
		return $name;
	}

	/**
	 * Get entity name by ID
	 *
	 * @since    1.0.0
	 * @param    int    $entity_id    Entity ID
	 * @return   string               Entity name
	 */
	private function get_entity_name( $entity_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get( $entity_id );
		return $entity ? $entity->name : __( 'Unknown Entity', METS_TEXT_DOMAIN );
	}

	/**
	 * Add dashboard widgets
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widgets() {
		// Only show for users who can manage tickets
		if ( ! current_user_can( 'manage_tickets' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'mets_sla_dashboard',
			__( 'SLA Monitor', METS_TEXT_DOMAIN ),
			array( $this, 'render_sla_dashboard_widget' ),
			array( $this, 'render_sla_dashboard_widget_config' )
		);

		wp_add_dashboard_widget(
			'mets_tickets_overview',
			__( 'Tickets Overview', METS_TEXT_DOMAIN ),
			array( $this, 'render_tickets_overview_widget' )
		);
	}

	/**
	 * Render SLA dashboard widget
	 *
	 * @since    1.0.0
	 */
	public function render_sla_dashboard_widget() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-calculator.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';

		$sla_monitor = METS_SLA_Monitor::get_instance();
		$sla_calculator = new METS_SLA_Calculator();
		$ticket_model = new METS_Ticket_Model();

		// Get monitoring metrics
		$metrics = $sla_monitor->get_monitoring_metrics();

		// Get tickets approaching breach
		$approaching_breach = $sla_calculator->get_tickets_approaching_breach( 4 ); // 4 hours warning

		// Get breached tickets
		$breached_tickets = $sla_calculator->get_breached_tickets();

		// Get active tickets count
		global $wpdb;
		$active_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE status NOT IN ('closed', 'resolved')"
		);

		// Get tickets with SLA
		$sla_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE status NOT IN ('closed', 'resolved') 
			AND (sla_response_due IS NOT NULL OR sla_resolution_due IS NOT NULL)"
		);

		?>
		<div class="mets-sla-dashboard">
			<div class="mets-sla-metrics">
				<div class="mets-metric-grid">
					<div class="mets-metric-item <?php echo count( $breached_tickets ) > 0 ? 'critical' : 'good'; ?>">
						<div class="mets-metric-number"><?php echo count( $breached_tickets ); ?></div>
						<div class="mets-metric-label"><?php _e( 'Breached SLAs', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-metric-item <?php echo count( $approaching_breach ) > 0 ? 'warning' : 'good'; ?>">
						<div class="mets-metric-number"><?php echo count( $approaching_breach ); ?></div>
						<div class="mets-metric-label"><?php _e( 'Approaching Breach', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-metric-item">
						<div class="mets-metric-number"><?php echo $sla_tickets; ?></div>
						<div class="mets-metric-label"><?php _e( 'Tickets with SLA', METS_TEXT_DOMAIN ); ?></div>
					</div>
					<div class="mets-metric-item">
						<div class="mets-metric-number"><?php echo $active_tickets; ?></div>
						<div class="mets-metric-label"><?php _e( 'Active Tickets', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $breached_tickets ) ): ?>
			<div class="mets-sla-alerts">
				<h4 class="mets-alert-title critical">
					<span class="dashicons dashicons-warning"></span>
					<?php _e( 'SLA Breaches Require Attention', METS_TEXT_DOMAIN ); ?>
				</h4>
				<ul class="mets-alert-list">
					<?php 
					$breach_limit = 5; // Show max 5 breached tickets
					$breach_count = 0;
					foreach ( $breached_tickets as $ticket_id ): 
						if ( $breach_count >= $breach_limit ) break;
						$ticket = $ticket_model->get( $ticket_id );
						if ( $ticket ):
							$breach_count++;
					?>
						<li>
							<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&action=view&ticket_id=' . $ticket->id ); ?>">
								#<?php echo esc_html( $ticket->ticket_number ); ?> - <?php echo esc_html( wp_trim_words( $ticket->subject, 8 ) ); ?>
							</a>
							<span class="mets-ticket-priority priority-<?php echo esc_attr( $ticket->priority ); ?>">
								<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
							</span>
						</li>
					<?php 
						endif;
					endforeach; 
					if ( count( $breached_tickets ) > $breach_limit ):
					?>
						<li class="mets-more-items">
							<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&sla_status=breached' ); ?>">
								<?php printf( __( '+%d more breached tickets', METS_TEXT_DOMAIN ), count( $breached_tickets ) - $breach_limit ); ?>
							</a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $approaching_breach ) ): ?>
			<div class="mets-sla-alerts">
				<h4 class="mets-alert-title warning">
					<span class="dashicons dashicons-clock"></span>
					<?php _e( 'SLAs Approaching Breach', METS_TEXT_DOMAIN ); ?>
				</h4>
				<ul class="mets-alert-list">
					<?php 
					$warning_limit = 3; // Show max 3 approaching tickets
					$warning_count = 0;
					foreach ( $approaching_breach as $ticket_id ): 
						if ( $warning_count >= $warning_limit ) break;
						$ticket = $ticket_model->get( $ticket_id );
						if ( $ticket ):
							$warning_count++;
					?>
						<li>
							<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&action=view&ticket_id=' . $ticket->id ); ?>">
								#<?php echo esc_html( $ticket->ticket_number ); ?> - <?php echo esc_html( wp_trim_words( $ticket->subject, 8 ) ); ?>
							</a>
							<span class="mets-ticket-priority priority-<?php echo esc_attr( $ticket->priority ); ?>">
								<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
							</span>
						</li>
					<?php 
						endif;
					endforeach; 
					if ( count( $approaching_breach ) > $warning_limit ):
					?>
						<li class="mets-more-items">
							<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&sla_status=warning' ); ?>">
								<?php printf( __( '+%d more tickets approaching breach', METS_TEXT_DOMAIN ), count( $approaching_breach ) - $warning_limit ); ?>
							</a>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php endif; ?>

			<div class="mets-sla-summary">
				<div class="mets-summary-stats">
					<?php if ( ! empty( $metrics['last_check'] ) ): ?>
					<p class="mets-last-check">
						<strong><?php _e( 'Last Monitoring Check:', METS_TEXT_DOMAIN ); ?></strong>
						<span title="<?php echo esc_attr( $metrics['last_check'] ); ?>">
							<?php echo human_time_diff( strtotime( $metrics['last_check'] ) ); ?> <?php _e( 'ago', METS_TEXT_DOMAIN ); ?>
						</span>
					</p>
					<?php endif; ?>
					
					<div class="mets-monitoring-stats">
						<span class="mets-stat">
							<strong><?php echo $metrics['total_warnings'] ?? 0; ?></strong> <?php _e( 'warnings sent', METS_TEXT_DOMAIN ); ?>
						</span>
						<span class="mets-stat">
							<strong><?php echo $metrics['total_breaches'] ?? 0; ?></strong> <?php _e( 'total breaches', METS_TEXT_DOMAIN ); ?>
						</span>
					</div>
				</div>

				<div class="mets-dashboard-actions">
					<a href="<?php echo admin_url( 'admin.php?page=mets-sla-rules' ); ?>" class="button">
						<?php _e( 'Manage SLA Rules', METS_TEXT_DOMAIN ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets' ); ?>" class="button button-primary">
						<?php _e( 'View All Tickets', METS_TEXT_DOMAIN ); ?>
					</a>
				</div>
			</div>
		</div>

		<style>
		.mets-sla-dashboard {
			font-size: 13px;
		}
		.mets-metric-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 10px;
			margin-bottom: 15px;
		}
		.mets-metric-item {
			text-align: center;
			padding: 10px;
			background: #f9f9f9;
			border-radius: 4px;
			border: 1px solid #ddd;
		}
		.mets-metric-item.critical {
			background: #fef2f2;
			border-color: #dc3545;
			color: #dc3545;
		}
		.mets-metric-item.warning {
			background: #fffbf2;
			border-color: #ffc107;
			color: #856404;
		}
		.mets-metric-item.good {
			background: #f0f9ff;
			border-color: #28a745;
			color: #28a745;
		}
		.mets-metric-number {
			font-size: 24px;
			font-weight: bold;
			line-height: 1;
		}
		.mets-metric-label {
			font-size: 11px;
			margin-top: 4px;
			text-transform: uppercase;
		}
		.mets-alert-title {
			margin: 15px 0 8px 0;
			font-size: 14px;
		}
		.mets-alert-title.critical {
			color: #dc3545;
		}
		.mets-alert-title.warning {
			color: #856404;
		}
		.mets-alert-title .dashicons {
			margin-right: 5px;
		}
		.mets-alert-list {
			margin: 0;
			padding: 0;
		}
		.mets-alert-list li {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 4px 0;
			border-bottom: 1px solid #eee;
		}
		.mets-alert-list li:last-child {
			border-bottom: none;
		}
		.mets-alert-list li.mets-more-items {
			font-style: italic;
			color: #666;
		}
		.mets-ticket-priority {
			font-size: 10px;
			padding: 2px 6px;
			border-radius: 3px;
			text-transform: uppercase;
			font-weight: bold;
		}
		.mets-ticket-priority.priority-urgent {
			background: #dc3545;
			color: white;
		}
		.mets-ticket-priority.priority-high {
			background: #fd7e14;
			color: white;
		}
		.mets-ticket-priority.priority-normal {
			background: #6c757d;
			color: white;
		}
		.mets-ticket-priority.priority-low {
			background: #28a745;
			color: white;
		}
		.mets-summary-stats {
			margin: 15px 0 10px 0;
			padding-top: 10px;
			border-top: 1px solid #eee;
		}
		.mets-last-check {
			margin: 0 0 8px 0;
			color: #666;
		}
		.mets-monitoring-stats {
			display: flex;
			gap: 15px;
		}
		.mets-stat {
			color: #666;
			font-size: 12px;
		}
		.mets-dashboard-actions {
			margin-top: 10px;
		}
		.mets-dashboard-actions .button {
			margin-right: 8px;
		}
		</style>
		<?php
	}

	/**
	 * Render tickets overview widget
	 *
	 * @since    1.0.0
	 */
	public function render_tickets_overview_widget() {
		global $wpdb;

		// Get ticket counts by status
		$status_counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count 
			FROM {$wpdb->prefix}mets_tickets 
			GROUP BY status"
		);

		// Get recent tickets (last 7 days)
		$recent_tickets = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
				WHERE created_at >= %s",
				date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
			)
		);

		// Get tickets by priority
		$priority_counts = $wpdb->get_results(
			"SELECT priority, COUNT(*) as count 
			FROM {$wpdb->prefix}mets_tickets 
			WHERE status NOT IN ('closed', 'resolved')
			GROUP BY priority"
		);

		?>
		<div class="mets-tickets-overview">
			<div class="mets-overview-stats">
				<h4><?php _e( 'Ticket Status', METS_TEXT_DOMAIN ); ?></h4>
				<ul class="mets-status-list">
					<?php 
					$status_labels = array(
						'new' => __( 'New', METS_TEXT_DOMAIN ),
						'open' => __( 'Open', METS_TEXT_DOMAIN ),
						'in_progress' => __( 'In Progress', METS_TEXT_DOMAIN ),
						'pending' => __( 'Pending', METS_TEXT_DOMAIN ),
						'resolved' => __( 'Resolved', METS_TEXT_DOMAIN ),
						'closed' => __( 'Closed', METS_TEXT_DOMAIN ),
					);
					
					$counts_by_status = array();
					foreach ( $status_counts as $status ) {
						$counts_by_status[ $status->status ] = $status->count;
					}
					
					foreach ( $status_labels as $status => $label ):
						$count = $counts_by_status[ $status ] ?? 0;
					?>
						<li class="mets-status-item status-<?php echo esc_attr( $status ); ?>">
							<span class="mets-status-label"><?php echo esc_html( $label ); ?></span>
							<span class="mets-status-count"><?php echo $count; ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="mets-overview-stats">
				<h4><?php _e( 'Open Tickets by Priority', METS_TEXT_DOMAIN ); ?></h4>
				<ul class="mets-priority-list">
					<?php 
					$priority_labels = array(
						'urgent' => __( 'Urgent', METS_TEXT_DOMAIN ),
						'high' => __( 'High', METS_TEXT_DOMAIN ),
						'normal' => __( 'Normal', METS_TEXT_DOMAIN ),
						'low' => __( 'Low', METS_TEXT_DOMAIN ),
					);
					
					$counts_by_priority = array();
					foreach ( $priority_counts as $priority ) {
						$counts_by_priority[ $priority->priority ] = $priority->count;
					}
					
					foreach ( $priority_labels as $priority => $label ):
						$count = $counts_by_priority[ $priority ] ?? 0;
					?>
						<li class="mets-priority-item priority-<?php echo esc_attr( $priority ); ?>">
							<span class="mets-priority-label"><?php echo esc_html( $label ); ?></span>
							<span class="mets-priority-count"><?php echo $count; ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="mets-overview-recent">
				<p class="mets-recent-stat">
					<strong><?php echo $recent_tickets; ?></strong> 
					<?php _e( 'new tickets in the last 7 days', METS_TEXT_DOMAIN ); ?>
				</p>
			</div>

			<div class="mets-overview-actions">
				<a href="<?php echo admin_url( 'admin.php?page=mets-add-ticket' ); ?>" class="button button-primary">
					<?php _e( 'Add New Ticket', METS_TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets' ); ?>" class="button">
					<?php _e( 'View All Tickets', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>
		</div>

		<style>
		.mets-tickets-overview h4 {
			margin: 0 0 10px 0;
			font-size: 14px;
		}
		.mets-status-list, .mets-priority-list {
			margin: 0 0 15px 0;
			padding: 0;
		}
		.mets-status-item, .mets-priority-item {
			display: flex;
			justify-content: space-between;
			padding: 4px 0;
			border-bottom: 1px solid #eee;
		}
		.mets-status-item:last-child, .mets-priority-item:last-child {
			border-bottom: none;
		}
		.mets-status-count, .mets-priority-count {
			font-weight: bold;
		}
		.mets-status-item.status-new .mets-status-count {
			color: #007cba;
		}
		.mets-status-item.status-open .mets-status-count {
			color: #28a745;
		}
		.mets-status-item.status-in_progress .mets-status-count {
			color: #ffc107;
		}
		.mets-status-item.status-pending .mets-status-count {
			color: #6c757d;
		}
		.mets-priority-item.priority-urgent .mets-priority-count {
			color: #dc3545;
		}
		.mets-priority-item.priority-high .mets-priority-count {
			color: #fd7e14;
		}
		.mets-priority-item.priority-normal .mets-priority-count {
			color: #6c757d;
		}
		.mets-priority-item.priority-low .mets-priority-count {
			color: #28a745;
		}
		.mets-recent-stat {
			margin: 10px 0;
			padding: 10px;
			background: #f8f9fa;
			border-left: 4px solid #007cba;
			color: #666;
		}
		.mets-overview-actions {
			margin-top: 10px;
		}
		.mets-overview-actions .button {
			margin-right: 8px;
		}
		</style>
		<?php
	}

	/**
	 * Render SLA dashboard widget config
	 *
	 * @since    1.0.0
	 */
	public function render_sla_dashboard_widget_config() {
		// Get current user's widget options
		$options = get_user_meta( get_current_user_id(), 'mets_sla_dashboard_options', true );
		if ( ! is_array( $options ) ) {
			$options = array(
				'refresh_interval' => 300, // 5 minutes
				'show_warnings' => true,
				'show_metrics' => true,
			);
		}

		if ( isset( $_POST['mets_sla_dashboard_submit'] ) ) {
			$options['refresh_interval'] = intval( $_POST['refresh_interval'] );
			$options['show_warnings'] = isset( $_POST['show_warnings'] );
			$options['show_metrics'] = isset( $_POST['show_metrics'] );
			
			update_user_meta( get_current_user_id(), 'mets_sla_dashboard_options', $options );
		}

		?>
		<p>
			<label for="refresh_interval"><?php _e( 'Refresh Interval (seconds):', METS_TEXT_DOMAIN ); ?></label>
			<input type="number" id="refresh_interval" name="refresh_interval" value="<?php echo esc_attr( $options['refresh_interval'] ); ?>" min="60" max="3600" />
		</p>
		<p>
			<input type="checkbox" id="show_warnings" name="show_warnings" <?php checked( $options['show_warnings'] ); ?> />
			<label for="show_warnings"><?php _e( 'Show SLA warnings', METS_TEXT_DOMAIN ); ?></label>
		</p>
		<p>
			<input type="checkbox" id="show_metrics" name="show_metrics" <?php checked( $options['show_metrics'] ); ?> />
			<label for="show_metrics"><?php _e( 'Show monitoring metrics', METS_TEXT_DOMAIN ); ?></label>
		</p>
		<input type="hidden" name="mets_sla_dashboard_submit" value="1" />
		<?php
	}

	/**
	 * Display main knowledgebase page (dashboard)
	 *
	 * @since    1.0.0
	 */
	public function display_knowledgebase_page() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';

		$article_model = new METS_KB_Article_Model();
		$category_model = new METS_KB_Category_Model();
		$entity_model = new METS_Entity_Model();

		// Get current entity filter
		$current_entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;
		$entities = $entity_model->get_all( array( 'parent_id' => 'all' ) );

		// Get statistics
		$total_articles = $article_model->get_articles_with_inheritance( array(
			'entity_id' => $current_entity_id,
			'status' => array( 'published' ),
			'per_page' => 1
		) )['total'];

		$pending_review = count( $article_model->get_by_status( 'pending_review', $current_entity_id ) );
		$draft_articles = count( $article_model->get_by_status( 'draft', $current_entity_id ) );

		// Get recent articles
		$recent_articles = $article_model->get_articles_with_inheritance( array(
			'entity_id' => $current_entity_id,
			'status' => array( 'published', 'approved' ),
			'per_page' => 5,
			'orderby' => 'created_at',
			'order' => 'DESC'
		) )['articles'];

		// Get categories
		$categories = $category_model->get_by_entity( $current_entity_id, true, true );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Knowledgebase Dashboard', METS_TEXT_DOMAIN ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="page-title-action"><?php _e( 'Add New Article', METS_TEXT_DOMAIN ); ?></a>

			<?php $this->display_admin_notices(); ?>

			<!-- Entity Filter -->
			<div class="mets-entity-filter" style="margin: 20px 0;">
				<form method="get" style="display: inline-block;">
					<input type="hidden" name="page" value="mets-knowledgebase">
					<label for="entity_filter"><?php _e( 'Filter by Entity:', METS_TEXT_DOMAIN ); ?></label>
					<select name="entity_id" id="entity_filter" onchange="this.form.submit()">
						<option value=""><?php _e( 'All Entities (with inheritance)', METS_TEXT_DOMAIN ); ?></option>
						<?php foreach ( $entities as $entity ): ?>
							<option value="<?php echo esc_attr( $entity->id ); ?>" <?php selected( $current_entity_id, $entity->id ); ?>>
								<?php echo esc_html( $entity->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>
			</div>

			<!-- Statistics Cards -->
			<div class="mets-kb-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				<div class="postbox">
					<div class="inside" style="text-align: center; padding: 20px;">
						<h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $total_articles; ?></h3>
						<p style="margin: 5px 0 0 0; color: #666;"><?php _e( 'Published Articles', METS_TEXT_DOMAIN ); ?></p>
					</div>
				</div>
				
				<?php if ( current_user_can( 'review_kb_articles' ) ): ?>
				<div class="postbox">
					<div class="inside" style="text-align: center; padding: 20px;">
						<h3 style="margin: 0; font-size: 32px; color: #d63638;"><?php echo $pending_review; ?></h3>
						<p style="margin: 5px 0 0 0; color: #666;">
							<a href="<?php echo admin_url( 'admin.php?page=mets-kb-pending-review' ); ?>" style="text-decoration: none; color: inherit;">
								<?php _e( 'Pending Review', METS_TEXT_DOMAIN ); ?>
							</a>
						</p>
					</div>
				</div>
				<?php endif; ?>

				<div class="postbox">
					<div class="inside" style="text-align: center; padding: 20px;">
						<h3 style="margin: 0; font-size: 32px; color: #dba617;"><?php echo $draft_articles; ?></h3>
						<p style="margin: 5px 0 0 0; color: #666;"><?php _e( 'Draft Articles', METS_TEXT_DOMAIN ); ?></p>
					</div>
				</div>

				<div class="postbox">
					<div class="inside" style="text-align: center; padding: 20px;">
						<h3 style="margin: 0; font-size: 32px; color: #00a32a;"><?php echo count( $categories ); ?></h3>
						<p style="margin: 5px 0 0 0; color: #666;">
							<a href="<?php echo admin_url( 'admin.php?page=mets-kb-categories' ); ?>" style="text-decoration: none; color: inherit;">
								<?php _e( 'Categories', METS_TEXT_DOMAIN ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>

			<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
				<!-- Recent Articles -->
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Recent Articles', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<?php if ( ! empty( $recent_articles ) ): ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th scope="col"><?php _e( 'Title', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col"><?php _e( 'Views', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col"><?php _e( 'Date', METS_TEXT_DOMAIN ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $recent_articles as $article ): ?>
									<tr>
										<td>
											<strong>
												<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article->id ); ?>">
													<?php echo esc_html( $article->title ); ?>
												</a>
											</strong>
										</td>
										<td><?php echo $article->entity_name ? esc_html( $article->entity_name ) : __( 'Global', METS_TEXT_DOMAIN ); ?></td>
										<td>
											<span class="mets-status-badge status-<?php echo esc_attr( $article->status ); ?>">
												<?php echo esc_html( ucfirst( str_replace( '_', ' ', $article->status ) ) ); ?>
											</span>
										</td>
										<td><?php echo number_format( $article->view_count ); ?></td>
										<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<p style="text-align: center; margin-top: 15px;">
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="button">
									<?php _e( 'View All Articles', METS_TEXT_DOMAIN ); ?>
								</a>
							</p>
						<?php else: ?>
							<p><?php _e( 'No articles found.', METS_TEXT_DOMAIN ); ?></p>
							<p>
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="button button-primary">
									<?php _e( 'Create Your First Article', METS_TEXT_DOMAIN ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Categories -->
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Categories', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<?php if ( ! empty( $categories ) ): ?>
							<ul style="margin: 0; padding: 0; list-style: none;">
								<?php foreach ( $categories as $category ): ?>
									<li style="margin: 0 0 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid <?php echo esc_attr( $category->color ); ?>;">
										<div style="display: flex; justify-content: space-between; align-items: center;">
											<div>
												<span class="dashicons <?php echo esc_attr( $category->icon ); ?>" style="color: <?php echo esc_attr( $category->color ); ?>;"></span>
												<strong><?php echo esc_html( $category->name ); ?></strong>
											</div>
											<span class="badge"><?php echo number_format( $category->article_count ); ?></span>
										</div>
										<?php if ( $category->description ): ?>
											<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">
												<?php echo esc_html( $category->description ); ?>
											</p>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<p style="text-align: center; margin-top: 15px;">
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-categories' ); ?>" class="button">
									<?php _e( 'Manage Categories', METS_TEXT_DOMAIN ); ?>
								</a>
							</p>
						<?php else: ?>
							<p><?php _e( 'No categories found.', METS_TEXT_DOMAIN ); ?></p>
							<p>
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-categories' ); ?>" class="button button-primary">
									<?php _e( 'Create Categories', METS_TEXT_DOMAIN ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="postbox" style="margin-top: 20px;">
				<h3 class="hndle"><span><?php _e( 'Quick Actions', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="button button-primary button-large" style="text-align: center; padding: 20px;">
							<span class="dashicons dashicons-plus-alt" style="font-size: 20px; margin-right: 8px; vertical-align: middle;"></span>
							<?php _e( 'New Article', METS_TEXT_DOMAIN ); ?>
						</a>
						
						<?php if ( current_user_can( 'manage_kb_categories' ) ): ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-categories' ); ?>" class="button button-large" style="text-align: center; padding: 20px;">
							<span class="dashicons dashicons-category" style="font-size: 20px; margin-right: 8px; vertical-align: middle;"></span>
							<?php _e( 'Categories', METS_TEXT_DOMAIN ); ?>
						</a>
						<?php endif; ?>

						<?php if ( current_user_can( 'manage_kb_tags' ) ): ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-tags' ); ?>" class="button button-large" style="text-align: center; padding: 20px;">
							<span class="dashicons dashicons-tag" style="font-size: 20px; margin-right: 8px; vertical-align: middle;"></span>
							<?php _e( 'Tags', METS_TEXT_DOMAIN ); ?>
						</a>
						<?php endif; ?>

						<?php if ( current_user_can( 'view_kb_analytics' ) ): ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-analytics' ); ?>" class="button button-large" style="text-align: center; padding: 20px;">
							<span class="dashicons dashicons-chart-area" style="font-size: 20px; margin-right: 8px; vertical-align: middle;"></span>
							<?php _e( 'Analytics', METS_TEXT_DOMAIN ); ?>
						</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<style>
		.mets-status-badge {
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}
		.status-draft { background: #dba617; color: white; }
		.status-pending_review { background: #d63638; color: white; }
		.status-approved { background: #00a32a; color: white; }
		.status-published { background: #0073aa; color: white; }
		.status-rejected { background: #666; color: white; }
		.status-archived { background: #7e8993; color: white; }
		.badge {
			background: #0073aa;
			color: white;
			padding: 2px 6px;
			border-radius: 10px;
			font-size: 11px;
			font-weight: bold;
		}
		</style>
		<?php
	}

	/**
	 * Handle knowledgebase form submissions
	 *
	 * @since    1.0.0
	 */
	private function handle_kb_form_submission() {
		// Verify nonce
		if ( ! isset( $_POST['kb_nonce'] ) || ! wp_verify_nonce( $_POST['kb_nonce'], 'mets_kb_form' ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Security check failed. Please try again.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 30 );
			return;
		}

		$action = $_POST['action'] ?? '';

		switch ( $action ) {
			case 'add_article':
			case 'edit_article':
				$this->handle_article_form_submission();
				break;
			case 'add_category':
			case 'edit_category':
				$this->handle_category_form_submission();
				break;
			case 'add_tag':
			case 'edit_tag':
				$this->handle_tag_form_submission();
				break;
			case 'review_article':
				$this->handle_article_review_submission();
				break;
			default:
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Unknown action.', METS_TEXT_DOMAIN ),
					'type' => 'error'
				), 30 );
				break;
		}
	}

	/**
	 * Placeholder methods for KB pages (to be implemented)
	 */
	public function display_kb_articles_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$article_id = isset( $_GET['article_id'] ) ? intval( $_GET['article_id'] ) : 0;

		switch ( $action ) {
			case 'edit':
				$this->display_edit_article_form( $article_id );
				break;
			case 'view':
				$this->display_view_article( $article_id );
				break;
			default:
				$this->display_articles_list();
				break;
		}
	}

	public function display_kb_add_article_page() {
		$this->display_edit_article_form( 0 );
	}

	public function display_kb_pending_review_page() {
		// Check user capabilities
		if ( ! current_user_can( 'review_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Handle bulk actions
		if ( isset( $_POST['action'] ) && isset( $_POST['articles'] ) ) {
			check_admin_referer( 'mets_kb_bulk_review' );
			$this->handle_bulk_review_actions();
		}

		// Handle individual actions
		if ( isset( $_GET['action'] ) && isset( $_GET['article_id'] ) ) {
			check_admin_referer( 'mets_kb_review_' . $_GET['article_id'] );
			$this->handle_review_action();
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		// Get pending articles
		$pending_articles = $article_model->get_by_status( 'pending' );
		
		?>
		<div class="wrap">
			<h1><?php _e( 'Pending Review', METS_TEXT_DOMAIN ); ?></h1>
			
			<?php if ( empty( $pending_articles ) ) : ?>
				<div class="notice notice-info">
					<p><?php _e( 'No articles pending review at this time.', METS_TEXT_DOMAIN ); ?></p>
				</div>
			<?php else : ?>
				<form method="post" id="kb-review-form">
					<?php wp_nonce_field( 'mets_kb_bulk_review' ); ?>
					
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select name="action" id="bulk-action-selector-top">
								<option value="-1"><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></option>
								<option value="approve"><?php _e( 'Approve', METS_TEXT_DOMAIN ); ?></option>
								<option value="reject"><?php _e( 'Reject', METS_TEXT_DOMAIN ); ?></option>
								<option value="request_changes"><?php _e( 'Request Changes', METS_TEXT_DOMAIN ); ?></option>
							</select>
							<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', METS_TEXT_DOMAIN ); ?>" />
						</div>
					</div>
					
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<td id="cb" class="manage-column column-cb check-column">
									<input id="cb-select-all-1" type="checkbox" />
								</td>
								<th scope="col" class="manage-column column-title"><?php _e( 'Title', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" class="manage-column column-author"><?php _e( 'Author', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" class="manage-column column-category"><?php _e( 'Category', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" class="manage-column column-submitted"><?php _e( 'Submitted', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" class="manage-column column-actions"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						
						<tbody>
							<?php foreach ( $pending_articles as $article ) : 
								$author = get_userdata( $article->author_id );
								$category = $article_model->get_category( $article->category_id );
							?>
								<tr id="article-<?php echo esc_attr( $article->id ); ?>">
									<th scope="row" class="check-column">
										<input type="checkbox" name="articles[]" value="<?php echo esc_attr( $article->id ); ?>" />
									</th>
									<td class="column-title">
										<strong>
											<a href="#" onclick="togglePreview(<?php echo esc_attr( $article->id ); ?>); return false;">
												<?php echo esc_html( $article->title ); ?>
											</a>
										</strong>
										<div class="row-actions">
											<span class="edit">
												<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article&edit=' . $article->id ); ?>">
													<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
												</a> |
											</span>
											<span class="view">
												<a href="#" onclick="togglePreview(<?php echo esc_attr( $article->id ); ?>); return false;">
													<?php _e( 'Preview', METS_TEXT_DOMAIN ); ?>
												</a>
											</span>
										</div>
										<div id="preview-<?php echo esc_attr( $article->id ); ?>" class="article-preview" style="display: none; margin-top: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
											<h4><?php echo esc_html( $article->title ); ?></h4>
											<div class="article-content">
												<?php echo wp_kses_post( wp_trim_words( $article->content, 100 ) ); ?>
												<?php if ( str_word_count( $article->content ) > 100 ) : ?>
													<p><em><?php _e( '...content truncated', METS_TEXT_DOMAIN ); ?></em></p>
												<?php endif; ?>
											</div>
											<div class="article-meta" style="margin-top: 10px; font-size: 0.9em; color: #666;">
												<strong><?php _e( 'Tags:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $article->tags ?: __( 'None', METS_TEXT_DOMAIN ) ); ?><br>
												<strong><?php _e( 'Excerpt:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $article->excerpt ?: __( 'No excerpt', METS_TEXT_DOMAIN ) ); ?>
											</div>
										</div>
									</td>
									<td class="column-author">
										<?php echo $author ? esc_html( $author->display_name ) : __( 'Unknown', METS_TEXT_DOMAIN ); ?>
									</td>
									<td class="column-category">
										<?php echo $category ? esc_html( $category->name ) : __( 'Uncategorized', METS_TEXT_DOMAIN ); ?>
									</td>
									<td class="column-submitted">
										<?php echo esc_html( human_time_diff( strtotime( $article->created_at ), current_time( 'timestamp' ) ) ); ?> ago
									</td>
									<td class="column-actions">
										<div class="review-actions">
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-kb-pending-review&action=approve&article_id=' . $article->id ), 'mets_kb_review_' . $article->id ); ?>" 
											   class="button button-primary button-small">
												<?php _e( 'Approve', METS_TEXT_DOMAIN ); ?>
											</a>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-kb-pending-review&action=reject&article_id=' . $article->id ), 'mets_kb_review_' . $article->id ); ?>" 
											   class="button button-secondary button-small">
												<?php _e( 'Reject', METS_TEXT_DOMAIN ); ?>
											</a>
											<a href="#" onclick="requestChanges(<?php echo esc_attr( $article->id ); ?>); return false;" 
											   class="button button-secondary button-small">
												<?php _e( 'Request Changes', METS_TEXT_DOMAIN ); ?>
											</a>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
			<?php endif; ?>
		</div>

		<!-- Request Changes Modal -->
		<div id="request-changes-modal" style="display: none;">
			<div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000;">
				<div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 90%;">
					<h3><?php _e( 'Request Changes', METS_TEXT_DOMAIN ); ?></h3>
					<form id="changes-form">
						<input type="hidden" id="changes-article-id" value="">
						<p>
							<label for="changes-message"><?php _e( 'Feedback for author:', METS_TEXT_DOMAIN ); ?></label>
							<textarea id="changes-message" rows="5" style="width: 100%;" placeholder="<?php esc_attr_e( 'Please provide specific feedback about what needs to be changed...', METS_TEXT_DOMAIN ); ?>"></textarea>
						</p>
						<p>
							<button type="button" class="button button-primary" onclick="submitChanges()"><?php _e( 'Send Feedback', METS_TEXT_DOMAIN ); ?></button>
							<button type="button" class="button" onclick="closeChangesModal()"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>

		<style>
			.article-preview {
				border-radius: 4px;
			}
			.review-actions .button {
				margin-right: 5px;
			}
			.article-content {
				line-height: 1.6;
			}
		</style>

		<script>
		function togglePreview(articleId) {
			var preview = document.getElementById('preview-' + articleId);
			if (preview.style.display === 'none') {
				preview.style.display = 'block';
			} else {
				preview.style.display = 'none';
			}
		}
		
		function requestChanges(articleId) {
			document.getElementById('changes-article-id').value = articleId;
			document.getElementById('request-changes-modal').style.display = 'block';
		}
		
		function closeChangesModal() {
			document.getElementById('request-changes-modal').style.display = 'none';
			document.getElementById('changes-message').value = '';
		}
		
		function submitChanges() {
			var articleId = document.getElementById('changes-article-id').value;
			var message = document.getElementById('changes-message').value;
			
			if (!message.trim()) {
				alert('<?php echo esc_js( __( 'Please provide feedback message.', METS_TEXT_DOMAIN ) ); ?>');
				return;
			}
			
			// Send AJAX request to request changes
			var data = new FormData();
			data.append('action', 'mets_request_article_changes');
			data.append('article_id', articleId);
			data.append('message', message);
			data.append('nonce', '<?php echo wp_create_nonce( 'mets_kb_changes' ); ?>');
			
			fetch(ajaxurl, {
				method: 'POST',
				body: data
			})
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					closeChangesModal();
					location.reload();
				} else {
					alert(result.data.message || 'Error occurred');
				}
			});
		}
		
		// Select all functionality
		var selectAll = document.getElementById('cb-select-all-1');
		if (selectAll) {
			selectAll.addEventListener('change', function() {
				var checkboxes = document.querySelectorAll('input[name="articles[]"]');
				for (var i = 0; i < checkboxes.length; i++) {
					checkboxes[i].checked = this.checked;
				}
			});
		}
		</script>
		<?php
	}

	/**
	 * Handle bulk review actions
	 *
	 * @since    1.0.0
	 */
	private function handle_bulk_review_actions() {
		$action = sanitize_text_field( $_POST['action'] );
		$article_ids = array_map( 'intval', $_POST['articles'] );
		
		if ( empty( $article_ids ) || $action === '-1' ) {
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		$success_count = 0;
		
		foreach ( $article_ids as $article_id ) {
			$result = false;
			
			switch ( $action ) {
				case 'approve':
					$result = $article_model->update( $article_id, array( 'status' => 'published' ) );
					if ( $result ) {
						// Notify author of approval
						$this->notify_author_status_change( $article_id, 'approved' );
					}
					break;
					
				case 'reject':
					$result = $article_model->update( $article_id, array( 'status' => 'draft' ) );
					if ( $result ) {
						// Notify author of rejection
						$this->notify_author_status_change( $article_id, 'rejected' );
					}
					break;
					
				case 'request_changes':
					$result = $article_model->update( $article_id, array( 'status' => 'draft' ) );
					if ( $result ) {
						// Notify author that changes are requested
						$this->notify_author_status_change( $article_id, 'changes_requested' );
					}
					break;
			}
			
			if ( $result ) {
				$success_count++;
			}
		}
		
		// Show admin notice
		$message = sprintf(
			_n( '%d article processed successfully.', '%d articles processed successfully.', $success_count, METS_TEXT_DOMAIN ),
			$success_count
		);
		
		set_transient( 'mets_admin_notice', array(
			'message' => $message,
			'type' => 'success'
		), 45 );
	}

	/**
	 * Handle individual review action
	 *
	 * @since    1.0.0
	 */
	private function handle_review_action() {
		$action = sanitize_text_field( $_GET['action'] );
		$article_id = intval( $_GET['article_id'] );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		$result = false;
		$message = '';
		
		switch ( $action ) {
			case 'approve':
				$result = $article_model->update( $article_id, array( 'status' => 'published' ) );
				$message = __( 'Article approved and published.', METS_TEXT_DOMAIN );
				if ( $result ) {
					$this->notify_author_status_change( $article_id, 'approved' );
				}
				break;
				
			case 'reject':
				$result = $article_model->update( $article_id, array( 'status' => 'draft' ) );
				$message = __( 'Article rejected and moved to drafts.', METS_TEXT_DOMAIN );
				if ( $result ) {
					$this->notify_author_status_change( $article_id, 'rejected' );
				}
				break;
		}
		
		// Show admin notice
		set_transient( 'mets_admin_notice', array(
			'message' => $result ? $message : __( 'Action failed. Please try again.', METS_TEXT_DOMAIN ),
			'type' => $result ? 'success' : 'error'
		), 45 );
		
		// Redirect to avoid resubmission
		wp_redirect( admin_url( 'admin.php?page=mets-kb-pending-review' ) );
		exit;
	}

	/**
	 * Notify author of status change
	 *
	 * @since    1.0.0
	 * @param    int       $article_id    Article ID
	 * @param    string    $status        New status
	 */
	private function notify_author_status_change( $article_id, $status ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get_by_id( $article_id );
		
		if ( ! $article ) {
			return;
		}
		
		$author = get_userdata( $article->author_id );
		if ( ! $author ) {
			return;
		}
		
		$subject = '';
		$message = '';
		
		switch ( $status ) {
			case 'approved':
				$subject = sprintf( __( 'Article Approved: %s', METS_TEXT_DOMAIN ), $article->title );
				$message = sprintf(
					__( "Hello %s,\n\nGreat news! Your article '%s' has been approved and is now published in the knowledge base.\n\nYou can view it here: %s\n\nThank you for contributing to our knowledge base!", METS_TEXT_DOMAIN ),
					$author->display_name,
					$article->title,
					home_url( '/knowledgebase/article/' . $article->slug )
				);
				break;
				
			case 'rejected':
				$subject = sprintf( __( 'Article Rejected: %s', METS_TEXT_DOMAIN ), $article->title );
				$message = sprintf(
					__( "Hello %s,\n\nYour article '%s' has been reviewed but needs some improvements before it can be published.\n\nPlease review the feedback and resubmit when ready.\n\nThank you for your understanding.", METS_TEXT_DOMAIN ),
					$author->display_name,
					$article->title
				);
				break;
				
			case 'changes_requested':
				$subject = sprintf( __( 'Changes Requested: %s', METS_TEXT_DOMAIN ), $article->title );
				$message = sprintf(
					__( "Hello %s,\n\nYour article '%s' has been reviewed and some changes have been requested.\n\nPlease check the feedback and make the necessary updates, then resubmit for review.\n\nThank you for your contribution!", METS_TEXT_DOMAIN ),
					$author->display_name,
					$article->title
				);
				break;
		}
		
		if ( $subject && $message ) {
			wp_mail( $author->user_email, $subject, $message );
		}
	}

	public function display_kb_categories_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Handle form submissions
		if ( isset( $_POST['action'] ) ) {
			check_admin_referer( 'mets_kb_categories' );
			$this->handle_category_actions();
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		$category_model = new METS_KB_Category_Model();

		// Get all categories
		$categories = $category_model->get_all();
		
		?>
		<div class="wrap">
			<h1><?php _e( 'Knowledge Base Categories', METS_TEXT_DOMAIN ); ?></h1>
			
			<div id="col-container" class="wp-clearfix">
				<!-- Add New Category Form -->
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php _e( 'Add New Category', METS_TEXT_DOMAIN ); ?></h2>
							<form id="addcat" method="post" action="">
								<?php wp_nonce_field( 'mets_kb_categories' ); ?>
								<input type="hidden" name="action" value="add_category" />
								
								<div class="form-field form-required">
									<label for="category-name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></label>
									<input name="name" id="category-name" type="text" value="" size="40" aria-required="true" />
									<p><?php _e( 'The name is how it appears on your site.', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<div class="form-field">
									<label for="category-slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></label>
									<input name="slug" id="category-slug" type="text" value="" style="width: 95%" />
									<p><?php _e( 'The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<div class="form-field">
									<label for="category-parent"><?php _e( 'Parent Category', METS_TEXT_DOMAIN ); ?></label>
									<select name="parent_id" id="category-parent">
										<option value="0"><?php _e( 'None', METS_TEXT_DOMAIN ); ?></option>
										<?php foreach ( $categories as $category ) : ?>
											<?php if ( $category->parent_id == 0 ) : // Only show top-level as parents ?>
												<option value="<?php echo esc_attr( $category->id ); ?>"><?php echo esc_html( $category->name ); ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
									</select>
									<p><?php _e( 'Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<div class="form-field">
									<label for="category-description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></label>
									<textarea name="description" id="category-description" rows="5" cols="50"></textarea>
									<p><?php _e( 'The description is not prominent by default; however, some themes may show it.', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<div class="form-field">
									<label for="category-color"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></label>
									<input name="color" id="category-color" type="color" value="#0073aa" />
									<p><?php _e( 'Choose a color to help identify this category.', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<div class="form-field">
									<label for="category-icon"><?php _e( 'Icon Class', METS_TEXT_DOMAIN ); ?></label>
									<input name="icon" id="category-icon" type="text" value="" placeholder="dashicons-category" />
									<p><?php _e( 'CSS class for icon (e.g., dashicons-category, fa-folder, etc.).', METS_TEXT_DOMAIN ); ?></p>
								</div>
								
								<p class="submit">
									<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add New Category', METS_TEXT_DOMAIN ); ?>" />
								</p>
							</form>
						</div>
					</div>
				</div>
				
				<!-- Categories List -->
				<div id="col-right">
					<div class="col-wrap">
						<form id="categories-filter" method="post">
							<?php wp_nonce_field( 'mets_kb_categories' ); ?>
							<input type="hidden" name="action" value="bulk_action" />
							
							<div class="tablenav top">
								<div class="alignleft actions bulkactions">
									<select name="bulk_action" id="bulk-action-selector-top">
										<option value="-1"><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></option>
										<option value="delete"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', METS_TEXT_DOMAIN ); ?>" />
								</div>
							</div>
							
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<td class="manage-column column-cb check-column">
											<input id="cb-select-all-1" type="checkbox" />
										</td>
										<th scope="col" class="manage-column column-name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col" class="manage-column column-description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col" class="manage-column column-slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></th>
										<th scope="col" class="manage-column column-count"><?php _e( 'Articles', METS_TEXT_DOMAIN ); ?></th>
									</tr>
								</thead>
								
								<tbody>
									<?php if ( empty( $categories ) ) : ?>
										<tr>
											<td colspan="5" style="text-align: center; padding: 20px;">
												<?php _e( 'No categories found. Create your first category using the form on the left.', METS_TEXT_DOMAIN ); ?>
											</td>
										</tr>
									<?php else : ?>
										<?php $this->display_category_rows( $categories, $category_model ); ?>
									<?php endif; ?>
								</tbody>
							</table>
						</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Edit Category Modal -->
		<div id="edit-category-modal" style="display: none;">
			<div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000;">
				<div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 5px; max-width: 500px; width: 90%;">
					<h3><?php _e( 'Edit Category', METS_TEXT_DOMAIN ); ?></h3>
					<form id="edit-category-form" method="post">
						<?php wp_nonce_field( 'mets_kb_categories' ); ?>
						<input type="hidden" name="action" value="edit_category" />
						<input type="hidden" id="edit-category-id" name="category_id" value="" />
						
						<p>
							<label for="edit-category-name"><?php _e( 'Name:', METS_TEXT_DOMAIN ); ?></label>
							<input type="text" id="edit-category-name" name="name" style="width: 100%;" required />
						</p>
						
						<p>
							<label for="edit-category-slug"><?php _e( 'Slug:', METS_TEXT_DOMAIN ); ?></label>
							<input type="text" id="edit-category-slug" name="slug" style="width: 100%;" />
						</p>
						
						<p>
							<label for="edit-category-parent"><?php _e( 'Parent:', METS_TEXT_DOMAIN ); ?></label>
							<select id="edit-category-parent" name="parent_id" style="width: 100%;">
								<option value="0"><?php _e( 'None', METS_TEXT_DOMAIN ); ?></option>
								<?php foreach ( $categories as $category ) : ?>
									<?php if ( $category->parent_id == 0 ) : ?>
										<option value="<?php echo esc_attr( $category->id ); ?>"><?php echo esc_html( $category->name ); ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</p>
						
						<p>
							<label for="edit-category-description"><?php _e( 'Description:', METS_TEXT_DOMAIN ); ?></label>
							<textarea id="edit-category-description" name="description" rows="3" style="width: 100%;"></textarea>
						</p>
						
						<p>
							<label for="edit-category-color"><?php _e( 'Color:', METS_TEXT_DOMAIN ); ?></label>
							<input type="color" id="edit-category-color" name="color" />
						</p>
						
						<p>
							<label for="edit-category-icon"><?php _e( 'Icon Class:', METS_TEXT_DOMAIN ); ?></label>
							<input type="text" id="edit-category-icon" name="icon" style="width: 100%;" />
						</p>
						
						<p>
							<button type="submit" class="button button-primary"><?php _e( 'Update Category', METS_TEXT_DOMAIN ); ?></button>
							<button type="button" class="button" onclick="closeEditModal()"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>

		<style>
			#col-container {
				display: flex;
				gap: 20px;
			}
			#col-left {
				flex: 0 0 300px;
			}
			#col-right {
				flex: 1;
			}
			.form-wrap {
				background: #fff;
				border: 1px solid #c3c4c7;
				padding: 20px;
			}
			.form-field {
				margin-bottom: 20px;
			}
			.form-field label {
				display: block;
				font-weight: 600;
				margin-bottom: 5px;
			}
			.form-field input,
			.form-field select,
			.form-field textarea {
				width: 100%;
				max-width: 100%;
			}
			.form-field p {
				margin-top: 5px;
				font-style: italic;
				color: #646970;
			}
			.category-color-preview {
				display: inline-block;
				width: 20px;
				height: 20px;
				border-radius: 50%;
				margin-right: 10px;
				vertical-align: middle;
			}
			.category-hierarchy {
				padding-left: 20px;
			}
		</style>

		<script>
		function editCategory(id, name, slug, parentId, description, color, icon) {
			document.getElementById('edit-category-id').value = id;
			document.getElementById('edit-category-name').value = name;
			document.getElementById('edit-category-slug').value = slug;
			document.getElementById('edit-category-parent').value = parentId;
			document.getElementById('edit-category-description').value = description;
			document.getElementById('edit-category-color').value = color;
			document.getElementById('edit-category-icon').value = icon;
			document.getElementById('edit-category-modal').style.display = 'block';
		}
		
		function closeEditModal() {
			document.getElementById('edit-category-modal').style.display = 'none';
		}
		
		function deleteCategory(id, name) {
			if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete this category?', METS_TEXT_DOMAIN ) ); ?> "' + name + '"')) {
				var form = document.createElement('form');
				form.method = 'POST';
				form.innerHTML = 
					'<input type="hidden" name="action" value="delete_category" />' +
					'<input type="hidden" name="category_id" value="' + id + '" />' +
					'<?php echo wp_nonce_field( 'mets_kb_categories', '_wpnonce', true, false ); ?>';
				document.body.appendChild(form);
				form.submit();
			}
		}
		
		// Auto-generate slug from name
		document.getElementById('category-name').addEventListener('input', function() {
			var name = this.value;
			var slug = name.toLowerCase()
				.replace(/[^a-z0-9 -]/g, '')
				.replace(/\s+/g, '-')
				.replace(/-+/g, '-')
				.trim('-');
			document.getElementById('category-slug').value = slug;
		});
		
		// Select all functionality
		var selectAllCats = document.getElementById('cb-select-all-1');
		if (selectAllCats) {
			selectAllCats.addEventListener('change', function() {
				var checkboxes = document.querySelectorAll('input[name="categories[]"]');
				for (var i = 0; i < checkboxes.length; i++) {
					checkboxes[i].checked = this.checked;
				}
			});
		}
		</script>
		<?php
	}

	/**
	 * Display category rows in hierarchical order
	 *
	 * @since    1.0.0
	 * @param    array    $categories       Categories array
	 * @param    object   $category_model  Category model instance
	 * @param    int      $parent_id       Parent ID for recursion
	 * @param    int      $level           Nesting level
	 */
	private function display_category_rows( $categories, $category_model, $parent_id = 0, $level = 0 ) {
		foreach ( $categories as $category ) {
			if ( $category->parent_id == $parent_id ) {
				$article_count = isset( $category->article_count ) ? (int) $category->article_count : 0;
				$indent = str_repeat( '— ', $level );
				
				echo '<tr id="category-' . esc_attr( $category->id ) . '">';
				echo '<th scope="row" class="check-column">';
				echo '<input type="checkbox" name="categories[]" value="' . esc_attr( $category->id ) . '" />';
				echo '</th>';
				
				echo '<td class="column-name">';
				if ( $category->color ) {
					echo '<span class="category-color-preview" style="background-color: ' . esc_attr( $category->color ) . ';"></span>';
				}
				if ( $category->icon ) {
					echo '<span class="dashicons ' . esc_attr( $category->icon ) . '" style="margin-right: 5px;"></span>';
				}
				echo '<strong>' . esc_html( $indent . $category->name ) . '</strong>';
				echo '<div class="row-actions">';
				echo '<span class="edit">';
				echo '<a href="#" onclick="editCategory(' . 
					 esc_attr( $category->id ) . ', \'' . 
					 esc_js( $category->name ) . '\', \'' . 
					 esc_js( $category->slug ) . '\', ' . 
					 esc_attr( $category->parent_id ) . ', \'' . 
					 esc_js( $category->description ) . '\', \'' . 
					 esc_js( $category->color ) . '\', \'' . 
					 esc_js( $category->icon ) . '\'); return false;">' . 
					 __( 'Edit', METS_TEXT_DOMAIN ) . '</a> | ';
				echo '</span>';
				echo '<span class="delete">';
				echo '<a href="#" onclick="deleteCategory(' . esc_attr( $category->id ) . ', \'' . esc_js( $category->name ) . '\'); return false;" class="submitdelete">' . __( 'Delete', METS_TEXT_DOMAIN ) . '</a>';
				echo '</span>';
				echo '</div>';
				echo '</td>';
				
				echo '<td class="column-description">' . esc_html( wp_trim_words( $category->description, 10 ) ) . '</td>';
				echo '<td class="column-slug">' . esc_html( $category->slug ) . '</td>';
				echo '<td class="column-count">';
				if ( $article_count > 0 ) {
					echo '<a href="' . admin_url( 'admin.php?page=mets-kb-articles&category=' . $category->id ) . '">' . $article_count . '</a>';
				} else {
					echo '0';
				}
				echo '</td>';
				echo '</tr>';
				
				// Recursively display child categories
				$this->display_category_rows( $categories, $category_model, $category->id, $level + 1 );
			}
		}
	}

	/**
	 * Handle category management actions
	 *
	 * @since    1.0.0
	 */
	private function handle_category_actions() {
		$action = sanitize_text_field( $_POST['action'] );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		$category_model = new METS_KB_Category_Model();

		switch ( $action ) {
			case 'add_category':
				$this->handle_add_category( $category_model );
				break;

			case 'edit_category':
				$this->handle_edit_category( $category_model );
				break;

			case 'delete_category':
				$this->handle_delete_category( $category_model );
				break;

			case 'bulk_action':
				$this->handle_bulk_category_actions( $category_model );
				break;
		}
	}

	/**
	 * Handle add category
	 *
	 * @since    1.0.0
	 * @param    object   $category_model   Category model instance
	 */
	private function handle_add_category( $category_model ) {
		$name = sanitize_text_field( $_POST['name'] );
		$slug = sanitize_title( $_POST['slug'] ?: $name );
		$parent_id = intval( $_POST['parent_id'] );
		$description = sanitize_textarea_field( $_POST['description'] );
		$color = sanitize_hex_color( $_POST['color'] );
		$icon = sanitize_text_field( $_POST['icon'] );
		
		if ( empty( $name ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category name is required.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		$category_data = array(
			'name' => $name,
			'slug' => $slug,
			'parent_id' => $parent_id,
			'description' => $description,
			'color' => $color,
			'icon' => $icon
		);
		
		$result = $category_model->create( $category_data );

		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create category.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle edit category
	 *
	 * @since    1.0.0
	 * @param    object   $category_model   Category model instance
	 */
	private function handle_edit_category( $category_model ) {
		$category_id = intval( $_POST['category_id'] );
		$name = sanitize_text_field( $_POST['name'] );
		$slug = sanitize_title( $_POST['slug'] ?: $name );
		$parent_id = intval( $_POST['parent_id'] );
		$description = sanitize_textarea_field( $_POST['description'] );
		$color = sanitize_hex_color( $_POST['color'] );
		$icon = sanitize_text_field( $_POST['icon'] );
		
		if ( empty( $name ) || ! $category_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Invalid category data.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		// Prevent setting parent as itself or its child
		if ( $parent_id == $category_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'A category cannot be its own parent.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		$category_data = array(
			'name' => $name,
			'slug' => $slug,
			'parent_id' => $parent_id,
			'description' => $description,
			'color' => $color,
			'icon' => $icon
		);
		
		$result = $category_model->update( $category_id, $category_data );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category updated successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to update category.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle delete category
	 *
	 * @since    1.0.0
	 * @param    object   $category_model   Category model instance
	 */
	private function handle_delete_category( $category_model ) {
		$category_id = intval( $_POST['category_id'] );

		if ( ! $category_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Invalid category ID.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}

		// Check if category has articles
		global $wpdb;
		$article_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_article_categories WHERE category_id = %d",
			$category_id
		) );
		if ( $article_count > 0 ) {
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf( __( 'Cannot delete category. It contains %d articles.', METS_TEXT_DOMAIN ), $article_count ),
				'type' => 'error'
			), 45 );
			return;
		}

		// Check if category has child categories
		$child_categories = $category_model->get_all( array( 'parent_id' => $category_id ) );
		if ( ! empty( $child_categories ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Cannot delete category. It has child categories.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}

		$result = $category_model->delete( $category_id );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category deleted successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to delete category.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle bulk category actions
	 *
	 * @since    1.0.0
	 * @param    object   $category_model   Category model instance
	 */
	private function handle_bulk_category_actions( $category_model ) {
		$bulk_action = sanitize_text_field( $_POST['bulk_action'] );
		$category_ids = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();

		if ( empty( $category_ids ) || $bulk_action === '-1' ) {
			return;
		}

		$success_count = 0;
		$error_count = 0;

		switch ( $bulk_action ) {
			case 'delete':
				global $wpdb;
				foreach ( $category_ids as $category_id ) {
					// Check constraints before deleting
					$article_count = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_article_categories WHERE category_id = %d",
						$category_id
					) );
					$child_categories = $category_model->get_all( array( 'parent_id' => $category_id ) );

					if ( $article_count > 0 || ! empty( $child_categories ) ) {
						$error_count++;
						continue;
					}

					if ( $category_model->delete( $category_id ) ) {
						$success_count++;
					} else {
						$error_count++;
					}
				}
				break;
		}
		
		// Show results
		if ( $success_count > 0 ) {
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf( 
					_n( '%d category deleted successfully.', '%d categories deleted successfully.', $success_count, METS_TEXT_DOMAIN ),
					$success_count
				),
				'type' => 'success'
			), 45 );
		}
		
		if ( $error_count > 0 ) {
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf(
					_n( '%d category could not be deleted (contains articles or child categories).', '%d categories could not be deleted (contain articles or child categories).', $error_count, METS_TEXT_DOMAIN ),
					$error_count
				),
				'type' => 'warning'
			), 45 );
		}
	}

	/**
	 * Handle KB tag actions
	 *
	 * @since    1.0.0
	 */
	private function handle_kb_tag_actions() {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['action'] );
		
		switch ( $action ) {
			case 'add_kb_tag':
				if ( ! wp_verify_nonce( $_POST['mets_tag_nonce'], 'mets_add_kb_tag' ) ) {
					return;
				}
				$this->handle_add_kb_tag();
				break;
				
			case 'edit_kb_tag':
				if ( ! wp_verify_nonce( $_POST['mets_edit_tag_nonce'], 'mets_edit_kb_tag' ) ) {
					return;
				}
				$this->handle_edit_kb_tag();
				break;
				
			case 'delete_kb_tag':
				if ( ! wp_verify_nonce( $_POST['mets_delete_tag_nonce'], 'mets_delete_kb_tag' ) ) {
					return;
				}
				$this->handle_delete_kb_tag();
				break;
				
			case 'bulk_tag_action':
				if ( ! wp_verify_nonce( $_POST['mets_bulk_tag_nonce'], 'mets_bulk_tag_action' ) ) {
					return;
				}
				$this->handle_bulk_kb_tag_actions();
				break;
		}
	}

	/**
	 * Handle add KB tag
	 *
	 * @since    1.0.0
	 */
	private function handle_add_kb_tag() {
		$name = sanitize_text_field( $_POST['tag_name'] );
		$slug = sanitize_title( $_POST['tag_slug'] ?: $name );
		$description = sanitize_textarea_field( $_POST['tag_description'] );
		
		if ( empty( $name ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag name is required.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$tag_data = array(
			'name' => $name,
			'slug' => $slug,
			'description' => $description
		);
		
		$result = $tag_model->create( $tag_data );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create tag.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle edit KB tag
	 *
	 * @since    1.0.0
	 */
	private function handle_edit_kb_tag() {
		$tag_id = intval( $_POST['tag_id'] );
		$name = sanitize_text_field( $_POST['tag_name'] );
		$slug = sanitize_title( $_POST['tag_slug'] ?: $name );
		$description = sanitize_textarea_field( $_POST['tag_description'] );
		
		if ( empty( $name ) || ! $tag_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Invalid tag data.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$tag_data = array(
			'name' => $name,
			'slug' => $slug,
			'description' => $description
		);
		
		$result = $tag_model->update( $tag_id, $tag_data );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag updated successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to update tag.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle delete KB tag
	 *
	 * @since    1.0.0
	 */
	private function handle_delete_kb_tag() {
		$tag_id = intval( $_POST['tag_id'] );
		
		if ( ! $tag_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Invalid tag ID.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$result = $tag_model->delete( $tag_id );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag deleted successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to delete tag.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
	}

	/**
	 * Handle bulk KB tag actions
	 *
	 * @since    1.0.0
	 */
	private function handle_bulk_kb_tag_actions() {
		$bulk_action = sanitize_text_field( $_POST['bulk_action'] );
		$tag_ids = isset( $_POST['tag_ids'] ) ? array_map( 'intval', $_POST['tag_ids'] ) : array();
		
		if ( empty( $tag_ids ) || $bulk_action === '-1' ) {
			return;
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$success_count = 0;
		$error_count = 0;
		
		switch ( $bulk_action ) {
			case 'delete':
				foreach ( $tag_ids as $tag_id ) {
					if ( $tag_model->delete( $tag_id ) ) {
						$success_count++;
					} else {
						$error_count++;
					}
				}
				break;
		}
		
		// Show results
		if ( $success_count > 0 ) {
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf( 
					_n( '%d tag deleted successfully.', '%d tags deleted successfully.', $success_count, METS_TEXT_DOMAIN ),
					$success_count
				),
				'type' => 'success'
			), 45 );
		}
		
		if ( $error_count > 0 ) {
			set_transient( 'mets_admin_notice', array(
				'message' => sprintf(
					_n( '%d tag could not be deleted.', '%d tags could not be deleted.', $error_count, METS_TEXT_DOMAIN ),
					$error_count
				),
				'type' => 'warning'
			), 45 );
		}
	}

	public function display_kb_tags_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_kb_categories' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Handle form submissions
		$this->handle_kb_tag_actions();

		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), esc_html( $notice['message'] ) );
		}

		// Get tags
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$args = array(
			'orderby' => 'name',
			'order' => 'ASC'
		);
		
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}
		
		$tags = $tag_model->get_all( $args );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'KB Tags', METS_TEXT_DOMAIN ); ?></h1>
			<a href="#" class="page-title-action" id="add-new-tag"><?php _e( 'Add New Tag', METS_TEXT_DOMAIN ); ?></a>
			<hr class="wp-header-end">

			<!-- Add New Tag Form -->
			<div id="add-tag-form" style="display: none; background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin: 20px 0;">
				<h2><?php _e( 'Add New Tag', METS_TEXT_DOMAIN ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'mets_add_kb_tag', 'mets_tag_nonce' ); ?>
					<input type="hidden" name="action" value="add_kb_tag">
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="tag_name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<input type="text" name="tag_name" id="tag_name" class="regular-text" required>
								<p class="description"><?php _e( 'The name is how it appears on your site.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tag_slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="text" name="tag_slug" id="tag_slug" class="regular-text">
								<p class="description"><?php _e( 'The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens. Leave blank to auto-generate.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tag_description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<textarea name="tag_description" id="tag_description" rows="5" cols="50" class="large-text"></textarea>
								<p class="description"><?php _e( 'Optional description for this tag.', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Add Tag', METS_TEXT_DOMAIN ); ?>">
						<button type="button" class="button" onclick="hideAddTagForm()"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
					</p>
				</form>
			</div>

			<!-- Search Form -->
			<form method="get" action="">
				<input type="hidden" name="page" value="mets-kb-tags">
				<p class="search-box">
					<label class="screen-reader-text" for="tag-search-input"><?php _e( 'Search Tags:', METS_TEXT_DOMAIN ); ?></label>
					<input type="search" id="tag-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search tags...', METS_TEXT_DOMAIN ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Tags', METS_TEXT_DOMAIN ); ?>">
				</p>
			</form>

			<!-- Tags List -->
			<form method="post" id="tags-form">
				<?php wp_nonce_field( 'mets_bulk_tag_action', 'mets_bulk_tag_nonce' ); ?>
				<input type="hidden" name="action" value="bulk_tag_action">
				
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action', METS_TEXT_DOMAIN ); ?></label>
						<select name="bulk_action" id="bulk-action-selector-top">
							<option value="-1"><?php _e( 'Bulk Actions', METS_TEXT_DOMAIN ); ?></option>
							<option value="delete"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', METS_TEXT_DOMAIN ); ?>">
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped tags">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all-1">
							</td>
							<th scope="col" class="manage-column column-name column-primary sortable desc">
								<a href="#">
									<span><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th scope="col" class="manage-column column-description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col" class="manage-column column-slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col" class="manage-column column-count num"><?php _e( 'Count', METS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php if ( empty( $tags ) ) : ?>
							<tr class="no-items">
								<td class="colspanchange" colspan="5">
									<?php _e( 'No tags found.', METS_TEXT_DOMAIN ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $tags as $tag ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="tag_ids[]" value="<?php echo esc_attr( $tag->id ); ?>" id="cb-select-<?php echo esc_attr( $tag->id ); ?>">
									</th>
									<td class="name column-name has-row-actions column-primary" data-colname="Name">
										<strong>
											<a class="row-title" href="#" onclick="editTag(<?php echo esc_attr( $tag->id ); ?>); return false;" aria-label="<?php echo esc_attr( sprintf( __( 'Edit "%s"', METS_TEXT_DOMAIN ), $tag->name ) ); ?>">
												<?php echo esc_html( $tag->name ); ?>
											</a>
										</strong>
										<div class="row-actions">
											<span class="edit">
												<a href="#" onclick="editTag(<?php echo esc_attr( $tag->id ); ?>); return false;" aria-label="<?php echo esc_attr( sprintf( __( 'Edit "%s"', METS_TEXT_DOMAIN ), $tag->name ) ); ?>">
													<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
												</a> |
											</span>
											<span class="delete">
												<a href="#" onclick="deleteTag(<?php echo esc_attr( $tag->id ); ?>, '<?php echo esc_js( $tag->name ); ?>'); return false;" class="submitdelete" aria-label="<?php echo esc_attr( sprintf( __( 'Delete "%s"', METS_TEXT_DOMAIN ), $tag->name ) ); ?>">
													<?php _e( 'Delete', METS_TEXT_DOMAIN ); ?>
												</a>
											</span>
										</div>
										<button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e( 'Show more details', METS_TEXT_DOMAIN ); ?></span></button>
									</td>
									<td class="description column-description" data-colname="Description">
										<?php echo esc_html( $tag->description ); ?>
									</td>
									<td class="slug column-slug" data-colname="Slug">
										<?php echo esc_html( $tag->slug ); ?>
									</td>
									<td class="count column-count" data-colname="Count">
										<a href="<?php echo admin_url( 'admin.php?page=mets-knowledgebase&tag=' . urlencode( $tag->slug ) ); ?>">
											<?php echo esc_html( $tag->usage_count ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
		</div>

		<!-- Edit Tag Modal -->
		<div id="edit-tag-modal" style="display: none;">
			<div class="modal-overlay" onclick="closeEditTagModal()"></div>
			<div class="modal-content">
				<div class="modal-header">
					<h2><?php _e( 'Edit Tag', METS_TEXT_DOMAIN ); ?></h2>
					<button type="button" class="modal-close" onclick="closeEditTagModal()">&times;</button>
				</div>
				<form method="post" action="" id="edit-tag-form">
					<?php wp_nonce_field( 'mets_edit_kb_tag', 'mets_edit_tag_nonce' ); ?>
					<input type="hidden" name="action" value="edit_kb_tag">
					<input type="hidden" name="tag_id" id="edit_tag_id">
					
					<div class="modal-body">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="edit_tag_name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
								</th>
								<td>
									<input type="text" name="tag_name" id="edit_tag_name" class="regular-text" required>
									<p class="description"><?php _e( 'The name is how it appears on your site.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="edit_tag_slug"><?php _e( 'Slug', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="text" name="tag_slug" id="edit_tag_slug" class="regular-text">
									<p class="description"><?php _e( 'The slug is the URL-friendly version of the name.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="edit_tag_description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<textarea name="tag_description" id="edit_tag_description" rows="5" cols="50" class="large-text"></textarea>
									<p class="description"><?php _e( 'Optional description for this tag.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
					
					<div class="modal-footer">
						<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Update Tag', METS_TEXT_DOMAIN ); ?>">
						<button type="button" class="button" onclick="closeEditTagModal()"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
					</div>
				</form>
			</div>
		</div>

		<style>
			#add-tag-form {
				border-radius: 4px;
			}
			
			.modal-overlay {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0,0,0,0.5);
				z-index: 100000;
			}
			
			.modal-content {
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				background: white;
				border-radius: 4px;
				box-shadow: 0 4px 20px rgba(0,0,0,0.3);
				max-width: 600px;
				width: 90%;
				max-height: 90vh;
				overflow-y: auto;
				z-index: 100001;
			}
			
			.modal-header {
				padding: 20px 20px 0;
				display: flex;
				justify-content: space-between;
				align-items: center;
				border-bottom: 1px solid #ddd;
				margin-bottom: 20px;
			}
			
			.modal-header h2 {
				margin: 0;
				padding-bottom: 10px;
			}
			
			.modal-close {
				background: none;
				border: none;
				font-size: 24px;
				cursor: pointer;
				padding: 0;
				width: 30px;
				height: 30px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			
			.modal-body {
				padding: 0 20px;
			}
			
			.modal-footer {
				padding: 20px;
				border-top: 1px solid #ddd;
				text-align: right;
			}
			
			.modal-footer .button {
				margin-left: 10px;
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Auto-generate slug from name
			$('#tag_name').on('input', function() {
				var name = $(this).val();
				var slug = name.toLowerCase()
					.replace(/[^a-z0-9\s-]/g, '')
					.replace(/\s+/g, '-')
					.replace(/-+/g, '-')
					.replace(/^-|-$/g, '');
				$('#tag_slug').val(slug);
			});

			// Check all functionality
			$('#cb-select-all-1').on('change', function() {
				$('input[name="tag_ids[]"]').prop('checked', $(this).is(':checked'));
			});
		});

		function showAddTagForm() {
			document.getElementById('add-tag-form').style.display = 'block';
			document.getElementById('tag_name').focus();
		}

		function hideAddTagForm() {
			document.getElementById('add-tag-form').style.display = 'none';
			document.getElementById('add-tag-form').getElementsByTagName('form')[0].reset();
		}

		// Add event listener for "Add New Tag" button
		document.getElementById('add-new-tag').addEventListener('click', function(e) {
			e.preventDefault();
			showAddTagForm();
		});

		function editTag(tagId) {
			// Make AJAX request to get tag data
			jQuery.post(ajaxurl, {
				action: 'mets_get_kb_tag',
				tag_id: tagId,
				nonce: '<?php echo wp_create_nonce( 'mets_get_kb_tag' ); ?>'
			}, function(response) {
				if (response.success) {
					var tag = response.data;
					document.getElementById('edit_tag_id').value = tag.id;
					document.getElementById('edit_tag_name').value = tag.name;
					document.getElementById('edit_tag_slug').value = tag.slug;
					document.getElementById('edit_tag_description').value = tag.description || '';
					document.getElementById('edit-tag-modal').style.display = 'block';
				} else {
					alert('<?php esc_js_e( 'Error loading tag data.', METS_TEXT_DOMAIN ); ?>');
				}
			});
		}

		function closeEditTagModal() {
			document.getElementById('edit-tag-modal').style.display = 'none';
		}

		function deleteTag(tagId, tagName) {
			if (confirm('<?php esc_js_e( 'Are you sure you want to delete this tag?', METS_TEXT_DOMAIN ); ?>\n\n' + tagName)) {
				// Create a form and submit it
				var form = document.createElement('form');
				form.method = 'POST';
				form.style.display = 'none';
				
				var actionInput = document.createElement('input');
				actionInput.type = 'hidden';
				actionInput.name = 'action';
				actionInput.value = 'delete_kb_tag';
				form.appendChild(actionInput);
				
				var tagIdInput = document.createElement('input');
				tagIdInput.type = 'hidden';
				tagIdInput.name = 'tag_id';
				tagIdInput.value = tagId;
				form.appendChild(tagIdInput);
				
				var nonceInput = document.createElement('input');
				nonceInput.type = 'hidden';
				nonceInput.name = 'mets_delete_tag_nonce';
				nonceInput.value = '<?php echo wp_create_nonce( 'mets_delete_kb_tag' ); ?>';
				form.appendChild(nonceInput);
				
				document.body.appendChild(form);
				form.submit();
			}
		}
		</script>
		<?php
	}

	public function display_kb_analytics_page() {
		require_once METS_PLUGIN_PATH . 'admin/kb/class-mets-kb-analytics.php';
		$analytics_page = new METS_KB_Analytics();
		$analytics_page->display_analytics_page();
	}

	public function display_reporting_dashboard_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-reporting-dashboard.php';
		$dashboard = new METS_Reporting_Dashboard();
		$dashboard->display_dashboard();
	}

	public function display_custom_reports_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-custom-report-builder.php';
		$report_builder = new METS_Custom_Report_Builder();
		$report_builder->display_report_builder();
	}

	/**
	 * Display performance dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_performance_dashboard_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-performance-dashboard.php';
		$performance_dashboard = new METS_Performance_Dashboard();
		$performance_dashboard->display_dashboard();
	}

	/**
	 * Handle report export requests
	 *
	 * @since    1.0.0
	 */
	public function handle_report_export() {
		if ( ! isset( $_POST['do_export'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'mets_export_report' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to export reports.', METS_TEXT_DOMAIN ) );
		}

		$format = sanitize_text_field( $_POST['export_format'] ?? 'csv' );
		$export_data = json_decode( stripslashes( $_POST['export_data'] ?? '{}' ), true );
		$config = json_decode( stripslashes( $_POST['export_config'] ?? '{}' ), true );

		if ( empty( $export_data ) ) {
			wp_die( __( 'No data to export.', METS_TEXT_DOMAIN ) );
		}

		switch ( $format ) {
			case 'csv':
				$this->export_csv( $export_data, $config );
				break;
			case 'pdf':
				$this->export_pdf( $export_data, $config );
				break;
			default:
				wp_die( __( 'Invalid export format.', METS_TEXT_DOMAIN ) );
		}

		exit;
	}

	/**
	 * Export report data as CSV
	 *
	 * @since    1.0.0
	 * @param    array    $export_data    Export data
	 * @param    array    $config         Report configuration
	 */
	private function export_csv( $export_data, $config ) {
		$filename = sanitize_file_name( $export_data['title'] ) . '_' . date( 'Y-m-d_H-i-s' ) . '.csv';
		
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Add BOM for Excel compatibility
		fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Add report header
		fputcsv( $output, array( $export_data['title'] ) );
		fputcsv( $output, array( sprintf( __( 'Generated on: %s', METS_TEXT_DOMAIN ), date_i18n( get_option( 'datetime_format' ), strtotime( $export_data['generated_at'] ) ) ) ) );
		fputcsv( $output, array() ); // Empty row

		// Add summary if available
		if ( ! empty( $export_data['summary'] ) ) {
			fputcsv( $output, array( __( 'Summary Statistics', METS_TEXT_DOMAIN ) ) );
			
			$summary = (array) $export_data['summary'];
			foreach ( $summary as $key => $value ) {
				$label = ucwords( str_replace( '_', ' ', $key ) );
				fputcsv( $output, array( $label, $value ) );
			}
			
			fputcsv( $output, array() ); // Empty row
		}

		// Add detailed data
		if ( ! empty( $export_data['headers'] ) && ! empty( $export_data['data'] ) ) {
			fputcsv( $output, array( __( 'Detailed data', METS_TEXT_DOMAIN ) ) );
			fputcsv( $output, $export_data['headers'] );
			
			foreach ( $export_data['data'] as $row ) {
				fputcsv( $output, $row );
			}
		}

		fclose( $output );
	}

	/**
	 * Export report data as PDF
	 *
	 * @since    1.0.0
	 * @param    array    $export_data    Export data
	 * @param    array    $config         Report configuration
	 */
	private function export_pdf( $export_data, $config ) {
		// For PDF export, we'll create an HTML version and use browser's print functionality
		// In a production environment, you might want to use a library like TCPDF or mPDF
		
		$filename = sanitize_file_name( $export_data['title'] ) . '_' . date( 'Y-m-d_H-i-s' ) . '.pdf';
		
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $export_data['title'] ); ?></title>
			<style>
				body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
				h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
				h2 { color: #0073aa; margin-top: 30px; }
				table { width: 100%; border-collapse: collapse; margin: 20px 0; }
				th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
				th { background: #f8f9fa; font-weight: bold; }
				tr:nth-child(even) { background: #f9f9f9; }
				.summary-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
				.summary-card { padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; text-align: center; }
				.summary-value { font-size: 24px; font-weight: bold; color: #0073aa; }
				.summary-label { font-size: 12px; color: #666; margin-top: 5px; }
				.meta-info { color: #666; font-size: 14px; margin-bottom: 30px; }
				@media print {
					.no-print { display: none; }
					body { margin: 0; }
				}
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( $export_data['title'] ); ?></h1>
			<div class="meta-info">
				<?php printf( __( 'Generated on: %s', METS_TEXT_DOMAIN ), date_i18n( get_option( 'datetime_format' ), strtotime( $export_data['generated_at'] ) ) ); ?>
			</div>

			<?php if ( ! empty( $export_data['summary'] ) ) : ?>
			<h2><?php _e( 'Summary Statistics', METS_TEXT_DOMAIN ); ?></h2>
			<div class="summary-stats">
				<?php 
				$summary = (array) $export_data['summary'];
				foreach ( $summary as $key => $value ) :
					$label = ucwords( str_replace( '_', ' ', $key ) );
				?>
				<div class="summary-card">
					<div class="summary-value"><?php echo esc_html( $value ); ?></div>
					<div class="summary-label"><?php echo esc_html( $label ); ?></div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $export_data['headers'] ) && ! empty( $export_data['data'] ) ) : ?>
			<h2><?php _e( 'Detailed Data', METS_TEXT_DOMAIN ); ?></h2>
			<table>
				<thead>
					<tr>
						<?php foreach ( $export_data['headers'] as $header ) : ?>
						<th><?php echo esc_html( $header ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $export_data['data'] as $row ) : ?>
					<tr>
						<?php foreach ( $row as $cell ) : ?>
						<td><?php echo esc_html( $cell ); ?></td>
						<?php endforeach; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

			<script class="no-print">
				// Auto-trigger print dialog for PDF generation
				window.onload = function() {
					setTimeout(function() {
						window.print();
					}, 500);
				};
			</script>
		</body>
		</html>
		<?php
	}

	/**
	 * Placeholder methods for form handling (to be implemented)
	 */
	private function handle_article_form_submission() {
		// Implementation coming in next phase
	}

	private function handle_category_form_submission() {
		// Implementation coming in next phase
	}

	private function handle_tag_form_submission() {
		// Implementation coming in next phase
	}

	private function handle_article_review_submission() {
		// Implementation coming in next phase
	}

	/**
	 * Display articles list
	 *
	 * @since    1.0.0
	 */
	private function display_articles_list() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';

		$article_model = new METS_KB_Article_Model();
		$category_model = new METS_KB_Category_Model();
		$entity_model = new METS_Entity_Model();

		// Get filter parameters
		$current_entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$current_category = isset( $_GET['category_id'] ) ? intval( $_GET['category_id'] ) : null;
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$per_page = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

		// Build query args
		$query_args = array(
			'entity_id' => $current_entity_id,
			'status' => ! empty( $current_status ) ? array( $current_status ) : array( 'draft', 'pending_review', 'approved', 'published', 'rejected', 'archived' ),
			'visibility' => array( 'internal', 'staff', 'customer' ),
			'category_id' => $current_category,
			'search' => $search_query,
			'per_page' => $per_page,
			'page' => $current_page,
			'orderby' => 'created_at',
			'order' => 'DESC'
		);

		// Get articles
		$results = $article_model->get_articles_with_inheritance( $query_args );
		$articles = $results['articles'];
		$total_articles = $results['total'];
		$total_pages = $results['pages'];

		// Get filter options
		$entities = $entity_model->get_all( array( 'parent_id' => 'all' ) );
		$categories = $category_model->get_by_entity( $current_entity_id, true, false );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'All Articles', METS_TEXT_DOMAIN ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>" class="page-title-action"><?php _e( 'Add New Article', METS_TEXT_DOMAIN ); ?></a>

			<?php $this->display_admin_notices(); ?>

			<!-- Filters -->
			<div class="mets-filters" style="background: #fff; padding: 15px; margin: 20px 0; border: 1px solid #ccd0d4;">
				<form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
					<input type="hidden" name="page" value="mets-kb-articles">
					
					<!-- Search -->
					<div>
						<label for="search_articles"><?php _e( 'Search Articles:', METS_TEXT_DOMAIN ); ?></label>
						<input type="text" id="search_articles" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php _e( 'Search titles and content...', METS_TEXT_DOMAIN ); ?>" class="regular-text">
					</div>

					<!-- Entity Filter -->
					<div>
						<label for="entity_filter"><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></label>
						<select name="entity_id" id="entity_filter">
							<option value=""><?php _e( 'All Entities', METS_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $entities as $entity ): ?>
								<option value="<?php echo esc_attr( $entity->id ); ?>" <?php selected( $current_entity_id, $entity->id ); ?>>
									<?php echo esc_html( $entity->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Status Filter -->
					<div>
						<label for="status_filter"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
						<select name="status" id="status_filter">
							<option value=""><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
							<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php _e( 'Draft', METS_TEXT_DOMAIN ); ?></option>
							<option value="pending_review" <?php selected( $current_status, 'pending_review' ); ?>><?php _e( 'Pending Review', METS_TEXT_DOMAIN ); ?></option>
							<option value="approved" <?php selected( $current_status, 'approved' ); ?>><?php _e( 'Approved', METS_TEXT_DOMAIN ); ?></option>
							<option value="published" <?php selected( $current_status, 'published' ); ?>><?php _e( 'Published', METS_TEXT_DOMAIN ); ?></option>
							<option value="rejected" <?php selected( $current_status, 'rejected' ); ?>><?php _e( 'Rejected', METS_TEXT_DOMAIN ); ?></option>
							<option value="archived" <?php selected( $current_status, 'archived' ); ?>><?php _e( 'Archived', METS_TEXT_DOMAIN ); ?></option>
						</select>
					</div>

					<!-- Category Filter -->
					<div>
						<label for="category_filter"><?php _e( 'Category:', METS_TEXT_DOMAIN ); ?></label>
						<select name="category_id" id="category_filter">
							<option value=""><?php _e( 'All Categories', METS_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $categories as $category ): ?>
								<option value="<?php echo esc_attr( $category->id ); ?>" <?php selected( $current_category, $category->id ); ?>>
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Submit -->
					<div>
						<input type="submit" class="button" value="<?php _e( 'Filter', METS_TEXT_DOMAIN ); ?>">
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="button"><?php _e( 'Clear', METS_TEXT_DOMAIN ); ?></a>
					</div>
				</form>
			</div>

			<!-- Results Info -->
			<div class="tablenav top">
				<div class="alignleft actions">
					<span class="displaying-num">
						<?php printf( _n( '%s article', '%s articles', $total_articles, METS_TEXT_DOMAIN ), number_format( $total_articles ) ); ?>
					</span>
				</div>
				<?php if ( $total_pages > 1 ): ?>
				<div class="tablenav-pages">
					<?php
					$pagination_args = array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => __( '&laquo; Previous' ),
						'next_text' => __( 'Next &raquo;' ),
						'total' => $total_pages,
						'current' => $current_page
					);
					echo paginate_links( $pagination_args );
					?>
				</div>
				<?php endif; ?>
			</div>

			<!-- Articles Table -->
			<?php if ( ! empty( $articles ) ): ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-cb check-column">
							<input type="checkbox" id="cb-select-all-1" />
						</th>
						<th scope="col" style="width: 40%;"><?php _e( 'Title', METS_TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php _e( 'Author', METS_TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php _e( 'Views', METS_TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php _e( 'Date', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $articles as $article ): ?>
					<tr>
						<th scope="row" class="check-column">
							<input type="checkbox" name="article[]" value="<?php echo esc_attr( $article->id ); ?>" />
						</th>
						<td class="column-title">
							<strong>
								<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article->id ); ?>">
									<?php echo esc_html( $article->title ); ?>
								</a>
							</strong>
							<?php if ( $article->featured ): ?>
								<span class="dashicons dashicons-star-filled" style="color: #dba617;" title="<?php _e( 'Featured Article', METS_TEXT_DOMAIN ); ?>"></span>
							<?php endif; ?>
							<div class="row-actions">
								<span class="edit">
									<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article->id ); ?>"><?php _e( 'Edit', METS_TEXT_DOMAIN ); ?></a> |
								</span>
								<span class="view">
									<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles&action=view&article_id=' . $article->id ); ?>"><?php _e( 'View', METS_TEXT_DOMAIN ); ?></a>
								</span>
								<?php if ( current_user_can( 'delete_kb_articles' ) && ( current_user_can( 'delete_others_kb_articles' ) || $article->author_id == get_current_user_id() ) ): ?>
								| <span class="delete">
									<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-kb-articles&action=delete&article_id=' . $article->id ), 'delete_article_' . $article->id ); ?>" 
									   onclick="return confirm('<?php _e( 'Are you sure you want to delete this article?', METS_TEXT_DOMAIN ); ?>');" 
									   style="color: #a00;"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></a>
								</span>
								<?php endif; ?>
								<?php if ( current_user_can( 'review_kb_articles' ) && $article->status === 'pending_review' ): ?>
								| <span class="review">
									<a href="<?php echo admin_url( 'admin.php?page=mets-kb-pending-review&action=review&article_id=' . $article->id ); ?>" style="color: #d63638;"><?php _e( 'Review', METS_TEXT_DOMAIN ); ?></a>
								</span>
								<?php endif; ?>
							</div>
						</td>
						<td>
							<?php echo $article->entity_name ? esc_html( $article->entity_name ) : '<em>' . __( 'Global', METS_TEXT_DOMAIN ) . '</em>'; ?>
						</td>
						<td>
							<?php echo esc_html( $article->author_name ); ?>
						</td>
						<td>
							<span class="mets-status-badge status-<?php echo esc_attr( $article->status ); ?>">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $article->status ) ) ); ?>
							</span>
							<?php if ( $article->status === 'published' && $article->visibility !== 'customer' ): ?>
								<br><small style="color: #666;"><?php echo esc_html( ucfirst( $article->visibility ) ); ?> only</small>
							<?php endif; ?>
						</td>
						<td>
							<?php echo number_format( $article->view_count ); ?>
							<?php if ( $article->helpful_count > 0 || $article->not_helpful_count > 0 ): ?>
								<br><small style="color: #666;">
									👍 <?php echo $article->helpful_count; ?> 
									👎 <?php echo $article->not_helpful_count; ?>
								</small>
							<?php endif; ?>
						</td>
						<td>
							<?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?>
							<br><small style="color: #666;"><?php echo date_i18n( get_option( 'time_format' ), strtotime( $article->created_at ) ); ?></small>
							<?php if ( $article->updated_at !== $article->created_at ): ?>
								<br><small style="color: #999;"><?php _e( 'Modified', METS_TEXT_DOMAIN ); ?></small>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Bottom Pagination -->
			<?php if ( $total_pages > 1 ): ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php echo paginate_links( $pagination_args ); ?>
				</div>
			</div>
			<?php endif; ?>

			<?php else: ?>
			<div class="notice notice-info">
				<p>
					<?php if ( ! empty( $search_query ) || ! empty( $current_status ) || ! empty( $current_category ) || ! empty( $current_entity_id ) ): ?>
						<?php _e( 'No articles found matching your criteria.', METS_TEXT_DOMAIN ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>"><?php _e( 'Clear filters', METS_TEXT_DOMAIN ); ?></a>
					<?php else: ?>
						<?php _e( 'No articles found.', METS_TEXT_DOMAIN ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article' ); ?>"><?php _e( 'Create your first article', METS_TEXT_DOMAIN ); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Display edit article form
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID (0 for new article)
	 */
	private function display_edit_article_form( $article_id ) {
		// Check capabilities
		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		global $wpdb;
		$is_edit = $article_id > 0;
		
		// Initialize models
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$category_model = new METS_KB_Category_Model();
		$tag_model = new METS_KB_Tag_Model();
		$entity_model = new METS_Entity_Model();

		// Get article data if editing
		$article = null;
		if ( $is_edit ) {
			$article = $article_model->get( $article_id );
			if ( ! $article ) {
				echo '<div class="wrap"><div class="notice notice-error"><p>' . __( 'Article not found.', METS_TEXT_DOMAIN ) . '</p></div></div>';
				return;
			}
			
			// Check permissions for editing this article
			if ( ! $this->can_edit_kb_article( $article ) ) {
				wp_die( __( 'You do not have permission to edit this article.', METS_TEXT_DOMAIN ) );
			}
		}

		// Display success message if redirected
		if ( isset( $_GET['message'] ) ) {
			$message_type = $_GET['message'];
			switch ( $message_type ) {
				case 'created':
					$this->add_admin_notice( __( 'Article created successfully.', METS_TEXT_DOMAIN ), 'success' );
					break;
				case 'updated':
					$this->add_admin_notice( __( 'Article updated successfully.', METS_TEXT_DOMAIN ), 'success' );
					break;
			}
		}

		// Form processing now handled in admin_init - removed duplicate processing

		// Get data for form
		$entities = $entity_model->get_all( array( 'parent_id' => 'all' ) );
		$categories = array();
		$current_entity_id = $article ? $article->entity_id : ( isset( $_GET['entity'] ) ? intval( $_GET['entity'] ) : null );
		
		// Get categories - if no entity selected, get global categories
		if ( $current_entity_id !== null ) {
			$categories = $category_model->get_by_entity( $current_entity_id, true );
		} else {
			// Get global categories
			$categories = $category_model->get_by_entity( null );
		}
		
		$current_tags = $is_edit ? $tag_model->get_article_tags( $article_id ) : array();
		$popular_tags = $tag_model->get_popular_tags( 20 );

		?>
		<div class="wrap">
			<h1><?php echo $is_edit ? __( 'Edit Article', METS_TEXT_DOMAIN ) : __( 'Add New Article', METS_TEXT_DOMAIN ); ?></h1>
			
			<form method="post" id="kb-article-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'mets_kb_save_article', 'mets_kb_nonce' ); ?>
				
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<!-- Title -->
							<div id="titlediv">
								<div id="titlewrap">
									<input type="text" name="title" id="title" 
										   value="<?php echo $article ? esc_attr( $article->title ) : ''; ?>" 
										   placeholder="<?php esc_attr_e( 'Enter article title', METS_TEXT_DOMAIN ); ?>"
										   autocomplete="off" required>
								</div>
							</div>

							<!-- Content Editor -->
							<div id="postdivrich" class="postarea">
								<?php
								$content = $article ? $article->content : '';
								$editor_id = 'article_content';
								$settings = array(
									'textarea_name' => 'content',
									'media_buttons' => true,
									'teeny' => false,
									'textarea_rows' => 20,
									'tabindex' => 2,
									'editor_class' => 'kb-content-editor'
								);
								wp_editor( $content, $editor_id, $settings );
								?>
							</div>
						</div>

						<div id="postbox-container-1" class="postbox-container">
							<!-- Publish Box -->
							<div id="submitdiv" class="postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php _e( 'Publish', METS_TEXT_DOMAIN ); ?></h2>
								</div>
								<div class="inside">
									<div class="submitbox" id="submitpost">
										<div id="minor-publishing">
											<!-- Status -->
											<div class="misc-pub-section">
												<label for="article_status"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
												<select name="status" id="article_status">
													<option value="draft" <?php selected( $article ? $article->status : 'draft', 'draft' ); ?>><?php _e( 'Draft', METS_TEXT_DOMAIN ); ?></option>
													<?php if ( current_user_can( 'publish_kb_articles' ) ): ?>
														<option value="pending_review" <?php selected( $article ? $article->status : '', 'pending_review' ); ?>><?php _e( 'Pending Review', METS_TEXT_DOMAIN ); ?></option>
														<option value="published" <?php selected( $article ? $article->status : '', 'published' ); ?>><?php _e( 'Published', METS_TEXT_DOMAIN ); ?></option>
													<?php endif; ?>
												</select>
											</div>

											<!-- Visibility -->
											<div class="misc-pub-section">
												<label for="article_visibility"><?php _e( 'Visibility:', METS_TEXT_DOMAIN ); ?></label>
												<select name="visibility" id="article_visibility">
													<option value="internal" <?php selected( $article ? $article->visibility : 'internal', 'internal' ); ?>><?php _e( 'Internal Only', METS_TEXT_DOMAIN ); ?></option>
													<option value="staff" <?php selected( $article ? $article->visibility : '', 'staff' ); ?>><?php _e( 'Staff & Agents', METS_TEXT_DOMAIN ); ?></option>
													<option value="customer" <?php selected( $article ? $article->visibility : '', 'customer' ); ?>><?php _e( 'All Users', METS_TEXT_DOMAIN ); ?></option>
												</select>
											</div>

											<!-- Featured -->
											<div class="misc-pub-section">
												<label>
													<input type="checkbox" name="is_featured" value="1" <?php checked( $article ? $article->featured : 0, 1 ); ?>>
													<?php _e( 'Featured Article', METS_TEXT_DOMAIN ); ?>
												</label>
											</div>
										</div>

										<div id="major-publishing-actions">
											<div id="delete-action">
												<?php if ( $is_edit && current_user_can( 'delete_kb_articles' ) ): ?>
													<a class="submitdelete deletion" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-kb-articles&action=delete&article_id=' . $article_id ), 'delete_kb_article_' . $article_id ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this article?', METS_TEXT_DOMAIN ); ?>')">
														<?php _e( 'Move to Trash', METS_TEXT_DOMAIN ); ?>
													</a>
												<?php endif; ?>
											</div>

											<div id="publishing-action">
												<input type="submit" name="save_article" id="publish" class="button button-primary button-large" 
													   value="<?php echo $is_edit ? __( 'Update Article', METS_TEXT_DOMAIN ) : __( 'Save Article', METS_TEXT_DOMAIN ); ?>">
											</div>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>

							<!-- Entity & Categories -->
							<div id="categorydiv" class="postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php _e( 'Entity & Categories', METS_TEXT_DOMAIN ); ?></h2>
								</div>
								<div class="inside">
									<!-- Entity Selection -->
									<div class="kb-field-group">
										<label for="article_entity"><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></label>
										<select name="entity_id" id="article_entity" required>
											<option value=""><?php _e( 'Select Entity...', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $entities as $entity ): ?>
												<option value="<?php echo $entity->id; ?>" <?php selected( $current_entity_id, $entity->id ); ?>>
													<?php echo esc_html( $entity->name ); ?>
													<?php if ( isset( $entity->parent_name ) && $entity->parent_name ): ?>
														(<?php echo esc_html( $entity->parent_name ); ?>)
													<?php endif; ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<!-- Categories -->
									<div class="kb-field-group" id="categories-container">
										<label><?php _e( 'Categories:', METS_TEXT_DOMAIN ); ?></label>
										<div id="category-checkboxes">
											<?php if ( $categories ): ?>
												<?php
												$selected_categories = array();
												if ( $is_edit ) {
													$article_categories = $wpdb->get_col( $wpdb->prepare(
														"SELECT category_id FROM {$wpdb->prefix}mets_kb_article_categories WHERE article_id = %d",
														$article_id
													) );
													$selected_categories = array_flip( $article_categories );
												}
												?>
												<?php foreach ( $categories as $category ): ?>
													<label class="kb-category-item">
														<input type="checkbox" name="categories[]" value="<?php echo $category->id; ?>" 
															   <?php checked( isset( $selected_categories[ $category->id ] ) ); ?>>
														<?php echo esc_html( $category->name ); ?>
														<?php if ( $category->entity_name && $category->entity_name !== 'Global' ): ?>
															<small>(<?php echo esc_html( $category->entity_name ); ?>)</small>
														<?php endif; ?>
													</label>
												<?php endforeach; ?>
											<?php else: ?>
												<p class="description"><?php _e( 'Select an entity to load categories.', METS_TEXT_DOMAIN ); ?></p>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>

							<!-- Tags -->
							<div id="tagsdiv-post_tag" class="postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php _e( 'Tags', METS_TEXT_DOMAIN ); ?></h2>
								</div>
								<div class="inside">
									<div class="kb-field-group">
										<label for="article_tags"><?php _e( 'Tags (comma separated):', METS_TEXT_DOMAIN ); ?></label>
										<textarea name="tags" id="article_tags" rows="3" placeholder="<?php esc_attr_e( 'Enter tags separated by commas...', METS_TEXT_DOMAIN ); ?>"><?php 
											if ( $current_tags ) {
												echo esc_textarea( implode( ', ', wp_list_pluck( $current_tags, 'name' ) ) );
											}
										?></textarea>
									</div>

									<?php if ( $popular_tags ): ?>
										<div class="kb-field-group">
											<label><?php _e( 'Popular Tags:', METS_TEXT_DOMAIN ); ?></label>
											<div class="kb-popular-tags">
												<?php foreach ( $popular_tags as $tag ): ?>
													<button type="button" class="button button-small kb-add-tag" data-tag="<?php echo esc_attr( $tag->name ); ?>">
														<?php echo esc_html( $tag->name ); ?> (<?php echo $tag->usage_count; ?>)
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- File Attachments -->
							<div id="attachmentsdiv" class="postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php _e( 'File Attachments', METS_TEXT_DOMAIN ); ?></h2>
								</div>
								<div class="inside">
									<div class="kb-field-group">
										<label for="article_attachments"><?php _e( 'Upload Files:', METS_TEXT_DOMAIN ); ?></label>
										<input type="file" name="attachments[]" id="article_attachments" multiple 
											   accept=".pdf,.doc,.docx,.odt,.ods,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp">
										<p class="description">
											<?php _e( 'Allowed file types: PDF, Word, OpenDocument, Excel, Images. Maximum size: 10MB per file.', METS_TEXT_DOMAIN ); ?>
										</p>
									</div>

									<?php if ( $is_edit ): ?>
										<div class="kb-field-group">
											<label><?php _e( 'Current Attachments:', METS_TEXT_DOMAIN ); ?></label>
											<div id="current-attachments">
												<?php
												require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
												$attachment_model = new METS_KB_Attachment_Model();
												$attachments = $attachment_model->get_by_article( $article_id );
												?>
												<?php if ( $attachments ): ?>
													<?php foreach ( $attachments as $attachment ): ?>
														<div class="kb-attachment-item" data-attachment-id="<?php echo $attachment->id; ?>">
															<span class="dashicons <?php echo $attachment_model->get_file_icon_class( $attachment->mime_type ); ?>"></span>
															<span class="filename"><?php echo esc_html( $attachment->original_filename ); ?></span>
															<span class="filesize">(<?php echo $attachment_model->format_file_size( $attachment->file_size ); ?>)</span>
															<button type="button" class="button button-small kb-remove-attachment" data-attachment-id="<?php echo $attachment->id; ?>">
																<?php _e( 'Remove', METS_TEXT_DOMAIN ); ?>
															</button>
														</div>
													<?php endforeach; ?>
												<?php else: ?>
													<p class="description"><?php _e( 'No attachments found.', METS_TEXT_DOMAIN ); ?></p>
												<?php endif; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- SEO Settings -->
							<div id="seodiv" class="postbox">
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php _e( 'SEO Settings', METS_TEXT_DOMAIN ); ?></h2>
								</div>
								<div class="inside">
									<div class="kb-field-group">
										<label for="meta_title"><?php _e( 'Meta Title:', METS_TEXT_DOMAIN ); ?></label>
										<input type="text" name="meta_title" id="meta_title" maxlength="60" 
											   value="<?php echo $article ? esc_attr( $article->meta_title ) : ''; ?>" 
											   placeholder="<?php esc_attr_e( 'Leave empty to use article title', METS_TEXT_DOMAIN ); ?>">
									</div>

									<div class="kb-field-group">
										<label for="meta_description"><?php _e( 'Meta Description:', METS_TEXT_DOMAIN ); ?></label>
										<textarea name="meta_description" id="meta_description" rows="3" maxlength="160" 
												  placeholder="<?php esc_attr_e( 'Brief description for search engines...', METS_TEXT_DOMAIN ); ?>"><?php echo $article ? esc_textarea( $article->meta_description ) : ''; ?></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>

		<style>
		.kb-field-group {
			margin-bottom: 15px;
		}
		.kb-field-group label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}
		.kb-field-group input, .kb-field-group select, .kb-field-group textarea {
			width: 100%;
		}
		.kb-category-item {
			display: block;
			margin-bottom: 5px;
		}
		.kb-popular-tags {
			margin-top: 10px;
		}
		.kb-popular-tags .button {
			margin: 2px;
		}
		.kb-attachment-item {
			display: flex;
			align-items: center;
			padding: 5px 0;
			border-bottom: 1px solid #ddd;
		}
		.kb-attachment-item .dashicons {
			margin-right: 5px;
		}
		.kb-attachment-item .filename {
			flex: 1;
			margin-right: 10px;
		}
		.kb-attachment-item .filesize {
			color: #666;
			margin-right: 10px;
		}
		#title {
			width: 100%;
			font-size: 1.7em;
			line-height: 100%;
			height: 1.7em;
			padding: 3px 8px;
			margin: 0;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Load categories when entity changes
			$('#article_entity').change(function() {
				var entityId = $(this).val();
				if (entityId) {
					$.post(ajaxurl, {
						action: 'mets_get_entity_categories',
						entity_id: entityId,
						nonce: $('#mets_kb_nonce').val()
					}, function(response) {
						if (response.success) {
							$('#category-checkboxes').html(response.data);
						}
					});
				} else {
					$('#category-checkboxes').html('<p class="description"><?php _e( 'Select an entity to load categories.', METS_TEXT_DOMAIN ); ?></p>');
				}
			});

			// Add tag functionality
			$('.kb-add-tag').click(function() {
				var tagName = $(this).data('tag');
				var currentTags = $('#article_tags').val();
				var tagsArray = currentTags ? currentTags.split(',').map(s => s.trim()) : [];
				
				if (tagsArray.indexOf(tagName) === -1) {
					tagsArray.push(tagName);
					$('#article_tags').val(tagsArray.join(', '));
				}
			});

			// Remove attachment functionality
			$('.kb-remove-attachment').click(function() {
				var attachmentId = $(this).data('attachment-id');
				var $item = $(this).closest('.kb-attachment-item');
				
				if (confirm('<?php _e( 'Are you sure you want to remove this attachment?', METS_TEXT_DOMAIN ); ?>')) {
					$.post(ajaxurl, {
						action: 'mets_remove_kb_attachment',
						attachment_id: attachmentId,
						nonce: $('#mets_kb_nonce').val()
					}, function(response) {
						if (response.success) {
							$item.remove();
						} else {
							alert(response.data || '<?php _e( 'Error removing attachment.', METS_TEXT_DOMAIN ); ?>');
						}
					});
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display view article placeholder
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 */
	private function display_view_article( $article_id ) {
		echo '<div class="wrap"><h1>View Article</h1><p>Article viewer - implementation in progress... Article ID: ' . $article_id . '</p></div>';
	}

	/**
	 * Check if current user can edit a KB article
	 *
	 * @since    1.0.0
	 * @param    object    $article    Article object
	 * @return   bool                  True if can edit, false otherwise
	 */
	private function can_edit_kb_article( $article ) {
		// Administrators and managers can edit any article
		if ( current_user_can( 'manage_kb_articles' ) || current_user_can( 'manage_tickets' ) ) {
			return true;
		}

		// Authors can edit their own articles
		if ( current_user_can( 'edit_kb_articles' ) && $article->author_id == get_current_user_id() ) {
			return true;
		}

		// Check entity-based permissions
		if ( current_user_can( 'edit_entity_kb_articles' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
			$entity_model = new METS_Entity_Model();
			$accessible_entities = $entity_model->get_accessible_entities();
			
			foreach ( $accessible_entities as $entity ) {
				if ( $entity->id == $article->entity_id ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Handle KB article save
	 *
	 * @since    1.0.0
	 * @param    object    $article_model    Article model instance
	 * @param    object    $tag_model        Tag model instance
	 * @param    int       $article_id       Article ID (0 for new)
	 */
	private function handle_kb_article_save( $article_model, $tag_model, $article_id ) {
		// Sanitize and validate input
		error_log( '[METS-KB] Debug - POST data keys: ' . implode( ', ', array_keys( $_POST ) ) );
		
		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		$entity_id = intval( $_POST['entity_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? 'draft' );
		$visibility = sanitize_text_field( $_POST['visibility'] ?? 'customer' );
		$is_featured = isset( $_POST['is_featured'] ) ? 1 : 0;
		$meta_title = sanitize_text_field( $_POST['meta_title'] );
		$meta_description = sanitize_textarea_field( $_POST['meta_description'] );
		$categories = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
		$tags = isset( $_POST['tags'] ) ? sanitize_textarea_field( $_POST['tags'] ) : '';

		error_log( '[METS-KB] Parsed - Title: ' . $title . ', Entity: ' . $entity_id . ', Content len: ' . strlen( $content ) );
		
		// Validate required fields
		if ( empty( $title ) || empty( $content ) ) {
			error_log( '[METS-KB] Validation FAILED - missing title or content' );
			$this->add_admin_notice( __( 'Please fill in all required fields (title and content).', METS_TEXT_DOMAIN ), 'error' );
			return;
		}

		error_log( '[METS-KB] Validation PASSED' );

		// Convert empty entity_id to null for global articles
		if ( empty( $entity_id ) ) {
			$entity_id = null;
		}

		// Prepare article data
		$article_data = array(
			'title' => $title,
			'content' => $content,
			'entity_id' => $entity_id,
			'status' => $status,
			'visibility' => $visibility,
			'featured' => $is_featured,
			'meta_title' => $meta_title,
			'meta_description' => $meta_description,
			'author_id' => get_current_user_id()
		);

		// Generate excerpt if not provided
		$article_data['excerpt'] = wp_trim_words( strip_tags( $content ), 30 );

		try {
			global $wpdb;
			$wpdb->query( 'START TRANSACTION' );

			// Save article
			if ( $article_id > 0 ) {
				error_log( '[METS-KB] Updating existing article ID: ' . $article_id );
				// Update existing article
				$result = $article_model->update( $article_id, $article_data );
				$saved_article_id = $article_id;
				$action = 'updated';
				error_log( '[METS-KB] Update result: ' . ( $result ? 'SUCCESS' : 'FAILED' ) );
			} else {
				error_log( '[METS-KB] Creating new article' );
				// Create new article
				$result = $article_model->create( $article_data );
				$saved_article_id = $result;
				$action = 'created';
				error_log( '[METS-KB] Create result: ' . ( $result ? 'SUCCESS (ID: ' . $saved_article_id . ')' : 'FAILED' ) );
			}

			if ( ! $result ) {
				error_log( '[METS-KB] Article save failed - throwing exception' );
				throw new Exception( __( 'Failed to save article.', METS_TEXT_DOMAIN ) );
			}
			
			error_log( '[METS-KB] Article saved successfully, proceeding with tags/categories' );

			// Handle categories
			if ( ! empty( $categories ) ) {
				// Remove existing category associations
				$wpdb->delete(
					$wpdb->prefix . 'mets_kb_article_categories',
					array( 'article_id' => $saved_article_id ),
					array( '%d' )
				);

				// Add new category associations
				foreach ( $categories as $category_id ) {
					$wpdb->insert(
						$wpdb->prefix . 'mets_kb_article_categories',
						array(
							'article_id' => $saved_article_id,
							'category_id' => $category_id
						),
						array( '%d', '%d' )
					);
				}
			}

			// Handle tags
			if ( ! empty( $tags ) ) {
				$tag_names = array_map( 'trim', explode( ',', $tags ) );
				$tag_names = array_filter( $tag_names ); // Remove empty strings
				$tag_model->set_article_tags( $saved_article_id, $tag_names );
			}

			// Handle file attachments
			if ( ! empty( $_FILES['attachments']['name'][0] ) ) {
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
				$attachment_model = new METS_KB_Attachment_Model();

				$upload_count = 0;
				$upload_errors = array();

				for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
					if ( $_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK ) {
						$file = array(
							'name' => $_FILES['attachments']['name'][$i],
							'type' => $_FILES['attachments']['type'][$i],
							'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
							'error' => $_FILES['attachments']['error'][$i],
							'size' => $_FILES['attachments']['size'][$i]
						);

						$upload_result = $attachment_model->upload_attachment( $saved_article_id, $file );
						if ( is_wp_error( $upload_result ) ) {
							$upload_errors[] = $file['name'] . ': ' . $upload_result->get_error_message();
						} else {
							$upload_count++;
						}
					}
				}

				if ( $upload_count > 0 ) {
					$this->add_admin_notice( sprintf( __( '%d file(s) uploaded successfully.', METS_TEXT_DOMAIN ), $upload_count ), 'success' );
				}

				if ( ! empty( $upload_errors ) ) {
					$this->add_admin_notice( __( 'Some files could not be uploaded:', METS_TEXT_DOMAIN ) . '<br>' . implode( '<br>', $upload_errors ), 'warning' );
				}
			}

			$wpdb->query( 'COMMIT' );

			// Redirect to prevent resubmission  
			if ( $action === 'created' ) {
				error_log( '[METS-KB] Redirecting after successful creation - Article ID: ' . $saved_article_id );
				wp_redirect( admin_url( 'admin.php?page=mets-kb-add-article&article_id=' . $saved_article_id . '&message=created' ) );
				exit;
			} else {
				error_log( '[METS-KB] Redirecting after successful update - Article ID: ' . $article_id );
				// For updates, redirect to edit page to prevent resubmission
				wp_redirect( admin_url( 'admin.php?page=mets-kb-add-article&article_id=' . $article_id . '&message=updated' ) );
				exit;
			}

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[METS-KB] Exception caught: ' . $e->getMessage() );
			$this->add_admin_notice( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Display bulk operations page
	 *
	 * @since    1.0.0
	 */
	public function display_bulk_operations_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-bulk-operations-page.php';
		$bulk_page = new METS_Bulk_Operations_Page();
		$bulk_page->display_bulk_operations_page();
	}

	/**
	 * Add admin notice
	 *
	 * @since    1.0.0
	 * @param    string    $message    Notice message
	 * @param    string    $type       Notice type (success, error, warning, info)
	 */
	private function add_admin_notice( $message, $type = 'info' ) {
		$class = 'notice notice-' . $type;
		if ( $type === 'error' || $type === 'success' ) {
			$class .= ' is-dismissible';
		}
		
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Display security dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_security_dashboard_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Initialize security dashboard
		require_once METS_PLUGIN_PATH . 'admin/class-mets-security-dashboard.php';
		$security_dashboard = new METS_Security_Dashboard();
		
		// Display the dashboard
		$security_dashboard->display_dashboard();
	}

	/**
	 * Display dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard_page() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
			delete_transient( 'mets_admin_notice' );
		}

		// Display the comprehensive dashboard
		require_once METS_PLUGIN_PATH . 'admin/class-mets-comprehensive-dashboard.php';
		$dashboard = new METS_Comprehensive_Dashboard();
		$dashboard->display_dashboard();
	}

	/**
	 * Display my tickets page for agents
	 *
	 * @since    1.0.0
	 */
	public function display_my_tickets_page() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
			delete_transient( 'mets_admin_notice' );
		}

		require_once METS_PLUGIN_PATH . 'admin/tickets/class-mets-ticket-list-table.php';
		$ticket_list_table = new METS_Ticket_List_Table();
		
		// Set filter for current user's assigned tickets
		$_GET['assigned_to'] = get_current_user_id();
		$ticket_list_table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . __( 'My Assigned Tickets', METS_TEXT_DOMAIN ) . '</h1>';
		
		// Show only tickets assigned to current user
		echo '<p class="description">' . __( 'These are tickets currently assigned to you.', METS_TEXT_DOMAIN ) . '</p>';
		
		echo '<form method="post">';
		$ticket_list_table->display();
		echo '</form>';
		echo '</div>';
	}
}