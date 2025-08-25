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
		
		// Add AJAX handlers for dashboard widget updates
		add_action( 'wp_ajax_mets_refresh_sla_widget', array( $this, 'ajax_refresh_sla_widget' ) );
		add_action( 'wp_ajax_mets_refresh_tickets_widget', array( $this, 'ajax_refresh_tickets_widget' ) );
		add_action( 'wp_ajax_mets_get_entity_categories', array( $this, 'ajax_get_entity_categories' ) );
		add_action( 'wp_ajax_mets_clear_models_cache', array( $this, 'ajax_clear_models_cache' ) );
		
		// Add AJAX handler for KB article search in admin
		add_action( 'wp_ajax_mets_admin_search_kb_articles', array( $this, 'ajax_admin_search_kb_articles' ) );
		
		// Add AJAX handlers for ticket-article linking
		add_action( 'wp_ajax_mets_link_kb_article', array( $this, 'ajax_link_kb_article' ) );
		add_action( 'wp_ajax_mets_unlink_kb_article', array( $this, 'ajax_unlink_kb_article' ) );
		add_action( 'wp_ajax_mets_mark_kb_helpful', array( $this, 'ajax_mark_kb_helpful' ) );
		
		// Add AJAX handler for KB attachment removal
		add_action( 'wp_ajax_mets_remove_kb_attachment', array( $this, 'ajax_remove_kb_attachment' ) );
		
		// Add AJAX handlers for KB tag management
		add_action( 'wp_ajax_mets_get_kb_tag', array( $this, 'ajax_get_kb_tag' ) );
		add_action( 'wp_ajax_mets_add_kb_tag', array( $this, 'ajax_add_kb_tag' ) );
		add_action( 'wp_ajax_mets_update_kb_tag', array( $this, 'ajax_update_kb_tag' ) );
		add_action( 'wp_ajax_mets_delete_kb_tag', array( $this, 'ajax_delete_kb_tag' ) );
		
		// Add report export handler
		add_action( 'init', array( $this, 'handle_report_export' ) );
		
		// Add WooCommerce integration AJAX handlers
		add_action( 'wp_ajax_mets_flush_rewrite_rules', array( $this, 'ajax_flush_rewrite_rules' ) );
		add_action( 'wp_ajax_mets_create_wc_entity', array( $this, 'ajax_create_wc_entity' ) );
		add_action( 'wp_ajax_mets_test_wc_integration', array( $this, 'ajax_test_wc_integration' ) );
		
		// Add bulk operations AJAX handlers
		add_action( 'wp_ajax_mets_load_bulk_tickets', array( $this, 'ajax_load_bulk_tickets' ) );
		add_action( 'wp_ajax_mets_load_bulk_entities', array( $this, 'ajax_load_bulk_entities' ) );
		add_action( 'wp_ajax_mets_load_bulk_kb_articles', array( $this, 'ajax_load_bulk_kb_articles' ) );
		
		// Add performance optimization AJAX handlers
		add_action( 'wp_ajax_mets_optimize_all_tables', array( $this, 'ajax_optimize_all_tables' ) );
		add_action( 'wp_ajax_mets_create_indexes', array( $this, 'ajax_create_indexes' ) );
		add_action( 'wp_ajax_mets_warm_cache', array( $this, 'ajax_warm_cache' ) );
		add_action( 'wp_ajax_mets_flush_all_cache', array( $this, 'ajax_flush_all_cache' ) );
		add_action( 'wp_ajax_mets_flush_cache_group', array( $this, 'ajax_flush_cache_group' ) );
		
		// Add mobile form AJAX handlers
		add_action( 'wp_ajax_mets_get_entities', array( $this, 'ajax_get_entities_for_mobile' ) );
		
		// Add manager dashboard AJAX handlers
		add_action( 'wp_ajax_mets_refresh_dashboard', array( $this, 'ajax_refresh_dashboard' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, METS_PLUGIN_URL . 'assets/css/mets-admin.css', array(), $this->version, 'all' );
		
		// Enqueue mobile navigation styles
		wp_enqueue_style( 
			$this->plugin_name . '-mobile-nav', 
			METS_PLUGIN_URL . 'assets/css/mets-mobile-navigation.css', 
			array( $this->plugin_name ), 
			$this->version, 
			'all' 
		);
		
		// Enqueue mobile ticket form styles on ticket pages
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'mets-tickets' || $_GET['page'] === 'mets-add-ticket' ) ) {
			wp_enqueue_style( 
				$this->plugin_name . '-mobile-ticket-form', 
				METS_PLUGIN_URL . 'assets/css/mets-mobile-ticket-form.css', 
				array( $this->plugin_name ), 
				$this->version, 
				'all' 
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, METS_PLUGIN_URL . 'assets/js/mets-admin.js', array( 'jquery' ), $this->version, false );
		
		// Enqueue mobile navigation script
		wp_enqueue_script( 
			$this->plugin_name . '-mobile-nav', 
			METS_PLUGIN_URL . 'assets/js/mets-mobile-navigation.js', 
			array( 'jquery', $this->plugin_name ), 
			$this->version, 
			false 
		);
		
		// Enqueue role pre-selection script on user creation page
		global $pagenow;
		if ( $pagenow === 'user-new.php' && isset( $_GET['mets_role'] ) ) {
			wp_enqueue_script( 
				$this->plugin_name . '-user-role-preselect', 
				METS_PLUGIN_URL . 'assets/js/mets-user-role-preselect.js', 
				array( 'jquery' ), 
				$this->version, 
				true 
			);
		}
		
		// Enqueue mobile ticket form script on ticket pages
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'mets-tickets' || $_GET['page'] === 'mets-add-ticket' ) ) {
			wp_enqueue_script( 
				$this->plugin_name . '-mobile-ticket-form', 
				METS_PLUGIN_URL . 'assets/js/mets-mobile-ticket-form.js', 
				array( 'jquery', $this->plugin_name ), 
				$this->version, 
				false 
			);
		}
		
		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'mets_admin_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mets_admin_nonce' ),
		) );
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

		// Helper function to safely translate strings for menu registration
		$translate = function( $text ) {
			return did_action( 'init' ) ? __( $text, METS_TEXT_DOMAIN ) : $text;
		};

		// ==============================================
		// 1. SUPPORT TICKETS - Primary Operational Menu  
		// ==============================================
		add_menu_page(
			$translate( 'Support Tickets' ),
			$translate( 'Support Tickets' ),
			'edit_tickets',
			'mets-tickets',
			array( $this, 'display_dashboard_page' ),
			'dashicons-tickets-alt',
			30
		);

		// Dashboard - Overview of ticket activity
		add_submenu_page(
			'mets-tickets',
			$translate( 'Dashboard' ),
			$translate( 'Dashboard' ),
			'edit_tickets',
			'mets-tickets',
			array( $this, 'display_dashboard_page' )
		);

		// All Tickets - Full ticket management
		if ( current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				$translate( 'All Tickets' ),
				$translate( 'All Tickets' ),
				'manage_tickets',
				'mets-all-tickets',
				array( $this, 'display_tickets_page' )
			);
		}

		// My Assigned Tickets - For agents to see their work
		if ( current_user_can( 'edit_tickets' ) && ! current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				$translate( 'My Assigned Tickets' ),
				$translate( 'My Tickets' ),
				'edit_tickets',
				'mets-my-tickets',
				array( $this, 'display_my_tickets_page' )
			);
		}

		// Add New Ticket
		add_submenu_page(
			'mets-tickets',
			$translate( 'Add New Ticket' ),
			$translate( 'Add New Ticket' ),
			'edit_tickets',
			'mets-add-ticket',
			array( $this, 'display_add_ticket_page' )
		);

		// Bulk Operations - For managers and supervisors
		if ( current_user_can( 'manage_tickets' ) ) {
			add_submenu_page(
				'mets-tickets',
				$translate( 'Bulk Operations' ),
				$translate( 'Bulk Operations' ),
				'manage_tickets',
				'mets-bulk-operations',
				array( $this, 'display_bulk_operations_page' )
			);
		}

		// ==============================================
		// 2. KNOWLEDGE BASE - Separate KB Management
		// ==============================================
		if ( current_user_can( 'view_kb_articles' ) || current_user_can( 'manage_options' ) || current_user_can( 'manage_tickets' ) ) {
			add_menu_page(
				$translate( 'Knowledge Base' ),
				$translate( 'Knowledge Base' ),
				current_user_can( 'view_kb_articles' ) ? 'view_kb_articles' : ( current_user_can( 'manage_tickets' ) ? 'manage_tickets' : 'manage_options' ),
				'mets-kb',
				array( $this, 'display_kb_articles_page' ),
				'dashicons-book',
				35
			);

			// KB Management Overview (first submenu item to match main menu)
			add_submenu_page(
				'mets-kb',
				$translate( 'KB Management' ),
				$translate( 'KB Management' ),
				current_user_can( 'view_kb_articles' ) ? 'view_kb_articles' : ( current_user_can( 'manage_tickets' ) ? 'manage_tickets' : 'manage_options' ),
				'mets-kb',
				array( $this, 'display_kb_articles_page' )
			);

			// All Articles
			add_submenu_page(
				'mets-kb',
				$translate( 'All Articles' ),
				$translate( 'All Articles' ),
				current_user_can( 'view_kb_articles' ) ? 'view_kb_articles' : ( current_user_can( 'manage_tickets' ) ? 'manage_tickets' : 'manage_options' ),
				'mets-kb-articles',
				array( $this, 'display_kb_articles_page' )
			);

			// Add New Article
			if ( current_user_can( 'edit_kb_articles' ) ) {
				add_submenu_page(
					'mets-kb',
					$translate( 'Add New Article' ),
					$translate( 'Add New Article' ),
					'edit_kb_articles',
					'mets-kb-add-article',
					array( $this, 'display_kb_add_article_page' )
				);
			}

			// Categories Management
			if ( current_user_can( 'manage_kb_categories' ) ) {
				add_submenu_page(
					'mets-kb',
					$translate( 'Categories' ),
					$translate( 'Categories' ),
					'manage_kb_categories',
					'mets-kb-categories',
					array( $this, 'display_kb_categories_page' )
				);
			}

			// Tags Management
			if ( current_user_can( 'manage_kb_tags' ) ) {
				add_submenu_page(
					'mets-kb',
					$translate( 'Tags' ),
					$translate( 'Tags' ),
					'manage_kb_tags',
					'mets-kb-tags',
					array( $this, 'display_kb_tags_page' )
				);
			}

			// Pending Review - For reviewers and managers
			if ( current_user_can( 'review_kb_articles' ) || current_user_can( 'manage_tickets' ) ) {
				add_submenu_page(
					'mets-kb',
					$translate( 'Pending Review' ),
					$translate( 'Pending Review' ),
					'review_kb_articles',
					'mets-kb-pending-review',
					array( $this, 'display_kb_pending_review_page' )
				);
			}
		}

		// ==============================================
		// 3. TEAM MANAGEMENT - Unified Management Interface
		// ==============================================
		if ( current_user_can( 'manage_agents' ) || current_user_can( 'manage_tickets' ) ) {
			add_menu_page(
				$translate( 'Team Management' ),
				$translate( 'Team Management' ),
				'manage_agents',
				'mets-user-roles-permissions',
				array( $this, 'display_user_roles_permissions_page' ),
				'dashicons-groups',
				40
			);

			// Team Performance
			add_submenu_page(
				'mets-user-roles-permissions',
				$translate( 'Team Performance' ),
				$translate( 'Team Performance' ),
				'manage_agents',
				'mets-manager-dashboard',
				array( $this, 'display_manager_dashboard_page' )
			);

			// Entity Management
			if ( current_user_can( 'manage_entities' ) ) {
				add_submenu_page(
					'mets-user-roles-permissions',
					$translate( 'Entity Management' ),
					$translate( 'Entity Management' ),
					'manage_entities',
					'mets-entities',
					array( $this, 'display_entities_page' )
				);
			}
		}

		// ==============================================
		// 4. REPORTS & ANALYTICS - Consolidated Reporting
		// ==============================================
		if ( current_user_can( 'view_reports' ) || current_user_can( 'manage_tickets' ) ) {
			add_menu_page(
				$translate( 'Reports & Analytics' ),
				$translate( 'Reports & Analytics' ),
				'view_reports',
				'mets-reports',
				array( $this, 'display_reporting_dashboard_page' ),
				'dashicons-chart-bar',
				45
			);

			// Ticket Reports
			add_submenu_page(
				'mets-reports',
				$translate( 'Ticket Reports' ),
				$translate( 'Ticket Reports' ),
				'view_reports',
				'mets-reporting-dashboard',
				array( $this, 'display_reporting_dashboard_page' )
			);

			// KB Analytics
			if ( current_user_can( 'view_kb_analytics' ) || current_user_can( 'manage_tickets' ) ) {
				add_submenu_page(
					'mets-reports',
					$translate( 'KB Analytics' ),
					$translate( 'KB Analytics' ),
					'view_kb_analytics',
					'mets-kb-analytics',
					array( $this, 'display_kb_analytics_page' )
				);
			}

			// Custom Reports
			add_submenu_page(
				'mets-reports',
				$translate( 'Custom Reports' ),
				$translate( 'Custom Reports' ),
				'view_reports',
				'mets-custom-reports',
				array( $this, 'display_custom_reports_page' )
			);

			// Performance Dashboard (admin only)
			if ( current_user_can( 'manage_options' ) ) {
				add_submenu_page(
					'mets-reports',
					$translate( 'Performance Dashboard' ),
					$translate( 'Performance Dashboard' ),
					'manage_options',
					'mets-performance-dashboard',
					array( $this, 'display_performance_dashboard_page' )
				);
			}
		}

		// ==============================================
		// 5. METS SETTINGS - Admin-Only Configuration
		// ==============================================
		if ( current_user_can( 'manage_ticket_system' ) ) {
			add_menu_page(
				$translate( 'METS Settings' ),
				$translate( 'METS Settings' ),
				'manage_ticket_system',
				'mets-settings',
				array( $this, 'display_settings_page' ),
				'dashicons-admin-generic',
				999
			);

			// General Settings
			add_submenu_page(
				'mets-settings',
				$translate( 'General Settings' ),
				$translate( 'General Settings' ),
				'manage_ticket_system',
				'mets-settings',
				array( $this, 'display_settings_page' )
			);

			// SLA Configuration
			add_submenu_page(
				'mets-settings',
				$translate( 'SLA Configuration' ),
				$translate( 'SLA Configuration' ),
				'manage_ticket_system',
				'mets-sla-rules',
				array( $this, 'display_sla_rules_page' )
			);

			// Business Hours
			add_submenu_page(
				'mets-settings',
				$translate( 'Business Hours' ),
				$translate( 'Business Hours' ),
				'manage_ticket_system',
				'mets-business-hours',
				array( $this, 'display_business_hours_page' )
			);

			// Automation Rules
			add_submenu_page(
				'mets-settings',
				$translate( 'Automation Rules' ),
				$translate( 'Automation Rules' ),
				'manage_ticket_system',
				'mets-automation',
				array( $this, 'display_automation_page' )
			);

			// AI Settings
			add_submenu_page(
				'mets-settings',
				$translate( 'AI Settings' ),
				$translate( 'AI Settings' ),
				'manage_ticket_system',
				'mets-ai-settings',
				array( $this, 'display_ai_settings_page' )
			);
			
			// AI Chat Widget
			add_submenu_page(
				'mets-settings',
				$translate( 'AI Chat Widget' ),
				$translate( 'AI Chat Widget' ),
				'manage_ticket_system',
				'mets-ai-chat-widget',
				array( $this, 'display_ai_chat_widget_settings_page' )
			);

			// Performance KPIs Settings
			add_submenu_page(
				'mets-settings',
				$translate( 'Performance KPIs' ),
				$translate( 'Performance KPIs' ),
				'manage_ticket_system',
				'mets-performance-kpis',
				array( $this, 'display_performance_kpis_page' )
			);

			// Email Templates
			add_submenu_page(
				'mets-settings',
				$translate( 'Email Templates' ),
				$translate( 'Email Templates' ),
				'manage_ticket_system',
				'mets-email-templates',
				array( $this, 'display_email_templates_page' )
			);

			// WooCommerce Integration (only if WooCommerce is active)
			if ( class_exists( 'WooCommerce' ) ) {
				add_submenu_page(
					'mets-settings',
					$translate( 'WooCommerce Integration' ),
					$translate( 'WooCommerce Integration' ),
					'manage_ticket_system',
					'mets-woocommerce',
					array( $this, 'display_woocommerce_settings_page' )
				);
			}

			// Security Dashboard (super admin only)
			if ( current_user_can( 'manage_options' ) ) {
				add_submenu_page(
					'mets-settings',
					$translate( 'Security Dashboard' ),
					$translate( 'Security Dashboard' ),
					'manage_options',
					'mets-security-dashboard',
					array( $this, 'display_security_dashboard_page' )
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
		
		// Register AI settings
		register_setting( 'mets_ai_settings_group', 'mets_openrouter_api_key', array(
			'sanitize_callback' => 'sanitize_text_field'
		) );
		register_setting( 'mets_ai_settings_group', 'mets_openrouter_model', array(
			'sanitize_callback' => 'sanitize_text_field'
		) );
		register_setting( 'mets_ai_settings_group', 'mets_openrouter_max_tokens', array(
			'sanitize_callback' => 'absint'
		) );
		register_setting( 'mets_ai_settings_group', 'mets_ai_features_enabled', array(
			'sanitize_callback' => array( $this, 'sanitize_ai_features' )
		) );
		register_setting( 'mets_ai_settings_group', 'mets_ai_master_prompt', array(
			'sanitize_callback' => 'sanitize_textarea_field'
		) );
		register_setting( 'mets_ai_settings_group', 'mets_ai_knowledge_base', array(
			'sanitize_callback' => 'sanitize_textarea_field'
		) );
		
		// Debug admin_init
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			error_log( '[METS] admin_init: POST request detected' );
			error_log( '[METS] GET page: ' . ( $_GET['page'] ?? 'not set' ) );
			error_log( '[METS] POST page: ' . ( $_POST['page'] ?? 'not set' ) );
		}
		
		// Handle form submissions early
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Check page parameter in both GET and POST
			$page = $_GET['page'] ?? $_POST['page'] ?? '';
			
			if ( $page === 'mets-entities' ) {
				error_log( '[METS] Handling entity form submission' );
				$this->handle_entity_form_submission();
			} elseif ( $page === 'mets-tickets' || $page === 'mets-add-ticket' ) {
				error_log( '[METS] Handling ticket form submission for page: ' . $page );
				$this->handle_ticket_form_submission();
			} elseif ( $page === 'mets-sla-rules' ) {
				error_log( '[METS] Handling SLA rules form submission' );
				$this->handle_sla_rules_form_submission();
			} elseif ( $page === 'mets-business-hours' ) {
				error_log( '[METS] Handling business hours form submission' );
				$this->handle_business_hours_form_submission();
			} elseif ( $page === 'mets-automation' ) {
				error_log( '[METS] Handling automation form submission' );
				$this->handle_automation_form_submission();
			} elseif ( ( $page === 'mets-kb-add-article' || $page === 'mets-kb-articles' ) && isset( $_POST['save_article'] ) ) {
				error_log( '[METS] Handling KB article form submission - Page: ' . $page . ', POST keys: ' . implode(',', array_keys($_POST)) );
				$this->handle_kb_article_form_submission();
			}
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'mets-entities' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['entity_id'] ) ) {
			$this->handle_entity_deletion();
		} elseif ( isset( $_GET['page'] ) && ( $_GET['page'] === 'mets-tickets' || $_GET['page'] === 'mets-all-tickets' ) && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['ticket_id'] ) ) {
			$this->handle_ticket_deletion();
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'mets-sla-rules' && isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['rule_id'] ) ) {
			$this->handle_sla_rule_deletion();
		}
		
		// Handle settings form submissions
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['page'] ) && $_POST['page'] === 'mets-settings' ) {
			$this->handle_settings_form_submission();
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
		
		// Debug: Log form submission
		error_log( '[METS] Form submission started' );
		error_log( '[METS] POST data: ' . print_r( $_POST, true ) );
		
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
		
		error_log( '[METS] Detected action: ' . $action );
		error_log( '[METS] Submit button values: submit_ticket=' . ( $_POST['submit_ticket'] ?? 'not set' ) . ', save_properties=' . ( $_POST['save_properties'] ?? 'not set' ) . ', add_reply_submit=' . ( $_POST['add_reply_submit'] ?? 'not set' ) );
		
		if ( $action === 'create' ) {
			check_admin_referer( 'create_ticket', 'ticket_nonce' );
			
			$data = array(
				'entity_id'       => intval( $_POST['ticket_entity'] ),
				'subject'         => sanitize_text_field( $_POST['ticket_subject'] ),
				'description'     => wp_kses_post( $_POST['ticket_description'] ),
				'status'          => isset( $_POST['ticket_status'] ) ? sanitize_text_field( $_POST['ticket_status'] ) : 'new',
				'priority'        => isset( $_POST['ticket_priority'] ) ? sanitize_text_field( $_POST['ticket_priority'] ) : 'normal',
				'category'        => ! empty( $_POST['ticket_category'] ) ? sanitize_text_field( $_POST['ticket_category'] ) : '',
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
				
				// Check if the ticket was created from the list view vs detail view
				$redirect_to_all_tickets = false;
				if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
					$referer = $_SERVER['HTTP_REFERER'];
					// Only redirect to list view if referer is the list view (not detail view)
					// List view: page=mets-all-tickets (no action=edit)
					// Detail view: page=mets-all-tickets&action=edit&ticket_id=X
					if ( strpos( $referer, 'page=mets-all-tickets' ) !== false && 
						 strpos( $referer, 'action=edit' ) === false ) {
						$redirect_to_all_tickets = true;
					}
				}
				
				// Redirect appropriately based on where the form was submitted from
				if ( $redirect_to_all_tickets ) {
					wp_redirect( admin_url( 'admin.php?page=mets-all-tickets' ) );
				} else {
					wp_redirect( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $result ) );
				}
			}
			exit;
			
		} elseif ( $action === 'update' ) {
			error_log( '[METS] Processing update action' );
			
			try {
				check_admin_referer( 'update_ticket', 'ticket_nonce' );
				error_log( '[METS] Nonce check passed' );
			} catch ( Exception $e ) {
				error_log( '[METS] Nonce check failed: ' . $e->getMessage() );
				return;
			}
			
			$ticket_id = intval( $_POST['ticket_id'] );
			error_log( '[METS] Updating ticket ID: ' . $ticket_id );
			
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
				error_log( '[METS] Update failed: ' . $result->get_error_message() );
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				error_log( '[METS] Update successful' );
				
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
			error_log( '[METS] Redirecting to: ' . $redirect_url );
			wp_redirect( $redirect_url );
			exit;
			
		} elseif ( $action === 'update_properties' ) {
			check_admin_referer( 'update_properties', 'properties_nonce' );
			
			$ticket_id = intval( $_POST['ticket_id'] );
			error_log( '[METS] Updating properties for ticket ID: ' . $ticket_id );
			
			// Get current ticket data for change tracking
			$current_ticket = $ticket_model->get( $ticket_id );
			$changes = array();
			
			$data = array(
				'status'      => sanitize_text_field( $_POST['ticket_status'] ),
				'priority'    => sanitize_text_field( $_POST['ticket_priority'] ),
				'category'    => ! empty( $_POST['ticket_category'] ) ? sanitize_text_field( $_POST['ticket_category'] ) : '',
				'assigned_to' => ! empty( $_POST['assigned_to'] ) ? intval( $_POST['assigned_to'] ) : null,
			);
			
			// Validate workflow rules for status changes
			if ( $current_ticket && $current_ticket->status != $data['status'] ) {
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
				$workflow_model = new METS_Workflow_Model();
				
				$ticket_data = array(
					'priority' => $data['priority'],
					'category' => $data['category']
				);
				
				$result = $workflow_model->is_transition_allowed( $current_ticket->status, $data['status'], get_current_user_id(), $ticket_data );
				
				if ( is_wp_error( $result ) ) {
					set_transient( 'mets_admin_notice', array(
						'message' => sprintf( __( 'Status change not allowed: %s', METS_TEXT_DOMAIN ), $result->get_error_message() ),
						'type' => 'error'
					), 45 );
					
					$redirect_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id );
					wp_redirect( $redirect_url );
					exit;
				}
			}
			
			// Track changes for logging
			if ( $current_ticket ) {
				if ( $current_ticket->status != $data['status'] ) {
					// Get status display names
					$statuses = get_option( 'mets_ticket_statuses', array() );
					$old_status_label = isset( $statuses[$current_ticket->status] ) ? $statuses[$current_ticket->status]['label'] : ucfirst( $current_ticket->status );
					$new_status_label = isset( $statuses[$data['status']] ) ? $statuses[$data['status']]['label'] : ucfirst( $data['status'] );
					
					$status_change = sprintf( __( 'Status changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_status_label, $new_status_label );
					
					// Add user information for workflow tracking
					$current_user = wp_get_current_user();
					$status_change .= ' ' . sprintf( __( 'by %s', METS_TEXT_DOMAIN ), $current_user->display_name );
					
					$changes[] = $status_change;
				}
				if ( $current_ticket->priority != $data['priority'] ) {
					// Get priority display names
					$priorities = get_option( 'mets_ticket_priorities', array() );
					$old_priority_label = isset( $priorities[$current_ticket->priority] ) ? $priorities[$current_ticket->priority]['label'] : ucfirst( $current_ticket->priority );
					$new_priority_label = isset( $priorities[$data['priority']] ) ? $priorities[$data['priority']]['label'] : ucfirst( $data['priority'] );
					
					$changes[] = sprintf( __( 'Priority changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_priority_label, $new_priority_label );
				}
				if ( $current_ticket->category != $data['category'] ) {
					// Get category display names
					$categories = get_option( 'mets_ticket_categories', array() );
					$old_cat = $current_ticket->category && isset( $categories[$current_ticket->category] ) ? $categories[$current_ticket->category] : ( $current_ticket->category ?: __( 'None', METS_TEXT_DOMAIN ) );
					$new_cat = $data['category'] && isset( $categories[$data['category']] ) ? $categories[$data['category']] : ( $data['category'] ?: __( 'None', METS_TEXT_DOMAIN ) );
					
					$changes[] = sprintf( __( 'Category changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_cat, $new_cat );
				}
				if ( $current_ticket->assigned_to != $data['assigned_to'] ) {
					$old_user = $current_ticket->assigned_to ? get_user_by( 'ID', $current_ticket->assigned_to )->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
					$new_user = $data['assigned_to'] ? get_user_by( 'ID', $data['assigned_to'] )->display_name : __( 'Unassigned', METS_TEXT_DOMAIN );
					$changes[] = sprintf( __( 'Assignment changed from "%s" to "%s"', METS_TEXT_DOMAIN ), $old_user, $new_user );
				}
			}
			
			error_log( '[METS] Properties update data: ' . print_r( $data, true ) );
			
			$result = $ticket_model->update( $ticket_id, $data );
			
			if ( is_wp_error( $result ) ) {
				error_log( '[METS] Properties update failed: ' . $result->get_error_message() );
				set_transient( 'mets_admin_notice', array(
					'message' => $result->get_error_message(),
					'type' => 'error'
				), 45 );
			} else {
				error_log( '[METS] Properties update successful' );
				
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
			
			// Check if the properties update was submitted from the list view vs detail view
			$redirect_to_all_tickets = false;
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referer = $_SERVER['HTTP_REFERER'];
				// Only redirect to list view if referer is the list view (not detail view)
				// List view: page=mets-all-tickets (no action=edit)
				// Detail view: page=mets-all-tickets&action=edit&ticket_id=X
				if ( strpos( $referer, 'page=mets-all-tickets' ) !== false && 
					 strpos( $referer, 'action=edit' ) === false ) {
					$redirect_to_all_tickets = true;
				}
			}
			
			// Redirect appropriately based on where the form was submitted from
			if ( $redirect_to_all_tickets ) {
				$redirect_url = admin_url( 'admin.php?page=mets-all-tickets' );
			} else {
				$redirect_url = admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id );
			}
			
			error_log( '[METS] Redirecting to: ' . $redirect_url );
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
			
			// Check if the reply was added from the list view vs detail view
			$redirect_to_all_tickets = false;
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referer = $_SERVER['HTTP_REFERER'];
				// Only redirect to list view if referer is the list view (not detail view)
				// List view: page=mets-all-tickets (no action=edit)
				// Detail view: page=mets-all-tickets&action=edit&ticket_id=X
				if ( strpos( $referer, 'page=mets-all-tickets' ) !== false && 
					 strpos( $referer, 'action=edit' ) === false ) {
					$redirect_to_all_tickets = true;
				}
			}
			
			// Redirect appropriately based on where the form was submitted from
			if ( $redirect_to_all_tickets ) {
				wp_redirect( admin_url( 'admin.php?page=mets-all-tickets' ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket_id ) );
			}
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
		
		// After deleting a ticket, always redirect to the list view since the ticket no longer exists
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
		
		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) && ! current_user_can( 'manage_options' ) ) {
			error_log( '[METS-KB] Permission denied - user cannot edit KB articles' );
			wp_die( __( 'You do not have sufficient permissions to edit KB articles.', METS_TEXT_DOMAIN ) );
		}
		
		error_log( '[METS-KB] User permissions OK' );
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['mets_kb_nonce'], 'mets_kb_save_article' ) ) {
			error_log( '[METS-KB] Nonce verification failed' );
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		error_log( '[METS-KB] Nonce verification OK' );
		
		// Get article ID from form data (with fallback to URL)
		$article_id = isset( $_POST['article_id'] ) ? intval( $_POST['article_id'] ) : ( isset( $_GET['article_id'] ) ? intval( $_GET['article_id'] ) : 0 );
		error_log( '[METS-KB] Article ID: ' . $article_id );
		
		// Check if KB tables exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'mets_kb_articles';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			error_log( '[METS-KB] KB tables do not exist - running activator' );
			require_once METS_PLUGIN_PATH . 'includes/class-mets-kb-activator.php';
			METS_KB_Activator::activate();
		}
		
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
	 * Handle settings form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_settings_form_submission() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}
		
		$action = sanitize_text_field( $_POST['action'] );
		$tab = isset( $_POST['tab'] ) ? sanitize_text_field( $_POST['tab'] ) : 'statuses';
		
		if ( $action === 'save_statuses' ) {
			check_admin_referer( 'mets_save_statuses', 'statuses_nonce' );
			
			$statuses = array();
			
			// Save existing statuses
			if ( isset( $_POST['statuses'] ) && is_array( $_POST['statuses'] ) ) {
				foreach ( $_POST['statuses'] as $key => $status ) {
					if ( ! empty( $status['label'] ) ) {
						$statuses[ sanitize_key( $key ) ] = array(
							'label' => sanitize_text_field( $status['label'] ),
							'color' => sanitize_hex_color( $status['color'] ),
						);
					}
				}
			}
			
			// Add new status if provided
			if ( isset( $_POST['new_status'] ) && ! empty( $_POST['new_status']['key'] ) && ! empty( $_POST['new_status']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_status']['key'] );
				if ( ! isset( $statuses[ $new_key ] ) ) {
					$statuses[ $new_key ] = array(
						'label' => sanitize_text_field( $_POST['new_status']['label'] ),
						'color' => sanitize_hex_color( $_POST['new_status']['color'] ),
					);
				}
			}
			
			// Save to database
			update_option( 'mets_ticket_statuses', $statuses );
			
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket statuses saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			
		} elseif ( $action === 'save_general_settings' ) {
			check_admin_referer( 'mets_save_general_settings', 'general_settings_nonce' );
			
			$settings = array();
			
			// Save portal header text
			if ( isset( $_POST['portal_header_text'] ) ) {
				$settings['portal_header_text'] = sanitize_textarea_field( $_POST['portal_header_text'] );
			}
			
			// Save new ticket link text
			if ( isset( $_POST['new_ticket_link_text'] ) ) {
				$settings['new_ticket_link_text'] = sanitize_text_field( $_POST['new_ticket_link_text'] );
			}
			
			// Save new ticket link URL
			if ( isset( $_POST['new_ticket_link_url'] ) ) {
				$settings['new_ticket_link_url'] = esc_url_raw( $_POST['new_ticket_link_url'] );
			}
			
			// Save ticket portal URL
			if ( isset( $_POST['ticket_portal_url'] ) ) {
				$settings['ticket_portal_url'] = esc_url_raw( $_POST['ticket_portal_url'] );
			}
			
			// Save terms and conditions URL
			if ( isset( $_POST['terms_conditions_url'] ) ) {
				$settings['terms_conditions_url'] = esc_url_raw( $_POST['terms_conditions_url'] );
			}
			
			// Save to database
			update_option( 'mets_general_settings', $settings );
			
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'General settings saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			
		} elseif ( $action === 'save_priorities' ) {
			check_admin_referer( 'mets_save_priorities', 'priorities_nonce' );
			
			$priorities = array();
			
			// Save existing priorities
			if ( isset( $_POST['priorities'] ) && is_array( $_POST['priorities'] ) ) {
				foreach ( $_POST['priorities'] as $key => $priority ) {
					if ( ! empty( $priority['label'] ) ) {
						$priorities[ sanitize_key( $key ) ] = array(
							'label' => sanitize_text_field( $priority['label'] ),
							'color' => sanitize_hex_color( $priority['color'] ),
							'order' => intval( $priority['order'] ),
						);
					}
				}
			}
			
			// Add new priority if provided
			if ( isset( $_POST['new_priority'] ) && ! empty( $_POST['new_priority']['key'] ) && ! empty( $_POST['new_priority']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_priority']['key'] );
				if ( ! isset( $priorities[ $new_key ] ) ) {
					$priorities[ $new_key ] = array(
						'label' => sanitize_text_field( $_POST['new_priority']['label'] ),
						'color' => sanitize_hex_color( $_POST['new_priority']['color'] ),
						'order' => intval( $_POST['new_priority']['order'] ),
					);
				}
			}
			
			// Save to database
			update_option( 'mets_ticket_priorities', $priorities );
			
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket priorities saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			
		} elseif ( $action === 'save_categories' ) {
			check_admin_referer( 'mets_save_categories', 'categories_nonce' );
			
			$categories = array();
			
			// Save existing categories
			if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
				foreach ( $_POST['categories'] as $key => $label ) {
					if ( ! empty( $label ) ) {
						$categories[ sanitize_key( $key ) ] = sanitize_text_field( $label );
					}
				}
			}
			
			// Add new category if provided
			if ( isset( $_POST['new_category'] ) && ! empty( $_POST['new_category']['key'] ) && ! empty( $_POST['new_category']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_category']['key'] );
				if ( ! isset( $categories[ $new_key ] ) ) {
					$categories[ $new_key ] = sanitize_text_field( $_POST['new_category']['label'] );
				}
			}
			
			// Save to database
			update_option( 'mets_ticket_categories', $categories );
			
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket categories saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
			
		} elseif ( $action === 'save_workflow' ) {
			check_admin_referer( 'mets_save_workflow', 'workflow_nonce' );
			
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
			$workflow_model = new METS_Workflow_Model();
			
			// Handle workflow rule creation/update
			if ( isset( $_POST['workflow_rule'] ) ) {
				$rule_data = $_POST['workflow_rule'];
				
				// Validate required fields
				if ( ! empty( $rule_data['from_status'] ) && ! empty( $rule_data['to_status'] ) && ! empty( $rule_data['allowed_roles'] ) ) {
					$result = $workflow_model->create( $rule_data );
					
					if ( is_wp_error( $result ) ) {
						set_transient( 'mets_admin_notice', array(
							'message' => $result->get_error_message(),
							'type' => 'error'
						), 45 );
					} else {
						set_transient( 'mets_admin_notice', array(
							'message' => __( 'Workflow rule saved successfully.', METS_TEXT_DOMAIN ),
							'type' => 'success'
						), 45 );
					}
				}
			}
			
			// Handle rule deletion
			if ( isset( $_POST['delete_rule'] ) && ! empty( $_POST['rule_id'] ) ) {
				$rule_id = intval( $_POST['rule_id'] );
				$result = $workflow_model->delete( $rule_id );
				
				if ( is_wp_error( $result ) ) {
					set_transient( 'mets_admin_notice', array(
						'message' => $result->get_error_message(),
						'type' => 'error'
					), 45 );
				} else {
					set_transient( 'mets_admin_notice', array(
						'message' => __( 'Workflow rule deleted successfully.', METS_TEXT_DOMAIN ),
						'type' => 'success'
					), 45 );
				}
			}
		} elseif ( $action === 'save_smtp_settings' ) {
			check_admin_referer( 'mets_save_smtp_settings', 'smtp_settings_nonce' );
			
			$smtp_manager = METS_SMTP_Manager::get_instance();
			
			// Prepare settings array
			$method = sanitize_text_field( $_POST['smtp_method'] ?? 'wordpress' );
			$settings = array(
				'enabled' => isset( $_POST['smtp_enabled'] ) && $_POST['smtp_enabled'] === '1',
				'method' => $method,
				'provider' => sanitize_text_field( $_POST['smtp_provider'] ?? '' ),
				'host' => sanitize_text_field( $_POST['smtp_host'] ?? '' ),
				'port' => intval( $_POST['smtp_port'] ?? 587 ),
				'encryption' => sanitize_text_field( $_POST['smtp_encryption'] ?? 'tls' ),
				'auth_required' => isset( $_POST['smtp_auth_required'] ) && $_POST['smtp_auth_required'] === '1',
				'username' => sanitize_text_field( $_POST['smtp_username'] ?? '' ),
				'password' => $_POST['smtp_password'] ?? '',
				'from_email' => sanitize_email( $_POST['smtp_from_email'] ?? '' ),
				'from_name' => sanitize_text_field( $_POST['smtp_from_name'] ?? '' ),
				'reply_to' => sanitize_email( $_POST['smtp_reply_to'] ?? '' ),
				'test_email' => sanitize_email( $_POST['smtp_test_email'] ?? '' ),
			);
			
			// Logic fix: If "Enable SMTP" is checked but method is "wordpress", 
			// automatically set method to "smtp" to maintain consistency
			if ( $settings['enabled'] && $settings['method'] === 'wordpress' ) {
				$settings['method'] = 'smtp';
			}
			
			// Conversely, if method is "smtp" but "Enable SMTP" is unchecked,
			// disable SMTP functionality  
			if ( ! $settings['enabled'] && $settings['method'] === 'smtp' ) {
				$settings['enabled'] = false;
				$settings['method'] = 'wordpress';
			}
			
			// Debug logging (sanitized)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$debug_settings = $settings;
				if ( isset( $debug_settings['password'] ) ) {
					$debug_settings['password'] = str_repeat( '*', strlen( $debug_settings['password'] ) );
				}
				error_log( 'METS SMTP Settings Debug - Saving settings: ' . print_r( $debug_settings, true ) );
			}
			
			// Save settings
			if ( $smtp_manager->save_global_settings( $settings ) ) {
				// Verify settings were saved correctly (sanitized debug)
				$saved_settings = $smtp_manager->get_global_settings();
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$debug_saved = $saved_settings;
					if ( isset( $debug_saved['password'] ) ) {
						$debug_saved['password'] = str_repeat( '*', strlen( $debug_saved['password'] ) );
					}
					error_log( 'METS SMTP Settings Debug - Settings after save: ' . print_r( $debug_saved, true ) );
				}
				
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'SMTP settings saved successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			} else {
				// Get validation errors for more specific error message
				$validation = $smtp_manager->validate_settings( $settings );
				$error_message = __( 'Failed to save SMTP settings.', METS_TEXT_DOMAIN );
				
				if ( ! $validation['valid'] && ! empty( $validation['errors'] ) ) {
					$error_message .= ' ' . implode( ' ', $validation['errors'] );
				}
				
				set_transient( 'mets_admin_notice', array(
					'message' => $error_message,
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'test_smtp_connection' ) {
			check_admin_referer( 'mets_test_smtp_connection', 'test_smtp_nonce' );
			
			$smtp_manager = METS_SMTP_Manager::get_instance();
			
			// Prepare test settings
			$settings = array(
				'enabled' => true,
				'method' => sanitize_text_field( $_POST['smtp_method'] ?? 'smtp' ),
				'provider' => sanitize_text_field( $_POST['smtp_provider'] ?? '' ),
				'host' => sanitize_text_field( $_POST['smtp_host'] ?? '' ),
				'port' => intval( $_POST['smtp_port'] ?? 587 ),
				'encryption' => sanitize_text_field( $_POST['smtp_encryption'] ?? 'tls' ),
				'auth_required' => isset( $_POST['smtp_auth_required'] ) && $_POST['smtp_auth_required'] === '1',
				'username' => sanitize_text_field( $_POST['smtp_username'] ?? '' ),
				'password' => $_POST['smtp_password'] ?? '',
			);
			
			// Test connection
			$result = $smtp_manager->test_connection( $settings );
			
			if ( $result['success'] ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'success'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'send_test_email' ) {
			check_admin_referer( 'mets_send_test_email', 'test_email_nonce' );
			
			$smtp_manager = METS_SMTP_Manager::get_instance();
			$settings = $smtp_manager->get_global_settings();
			$test_email = sanitize_email( $_POST['test_email_address'] ?? '' );
			
			// Send test email
			$result = $smtp_manager->send_test_email( $settings, $test_email );
			
			if ( $result['success'] ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'success'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'save_n8n_chat_settings' ) {
			check_admin_referer( 'mets_save_n8n_chat_settings', 'n8n_chat_settings_nonce' );
			
			// Prepare settings array
			$settings = array(
				'enabled' => isset( $_POST['n8n_enabled'] ) && $_POST['n8n_enabled'] === '1',
				'webhook_url' => esc_url_raw( $_POST['n8n_webhook_url'] ?? '' ),
				'position' => sanitize_text_field( $_POST['n8n_position'] ?? 'bottom-right' ),
				'initial_message' => sanitize_textarea_field( $_POST['n8n_initial_message'] ?? '' ),
				'theme_color' => sanitize_hex_color( $_POST['n8n_theme_color'] ?? '#007cba' ),
				'window_title' => sanitize_text_field( $_POST['n8n_window_title'] ?? 'Support Chat' ),
				'subtitle' => sanitize_text_field( $_POST['n8n_subtitle'] ?? '' ),
				'show_on_mobile' => isset( $_POST['n8n_show_on_mobile'] ) && $_POST['n8n_show_on_mobile'] === '1',
				'allowed_pages' => sanitize_text_field( $_POST['n8n_allowed_pages'] ?? 'all' ),
				'specific_pages' => sanitize_text_field( $_POST['n8n_specific_pages'] ?? '' ),
			);
			
			// Validate webhook URL if chat is enabled
			if ( $settings['enabled'] && empty( $settings['webhook_url'] ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Webhook URL is required when n8n chat is enabled.', METS_TEXT_DOMAIN ),
					'type' => 'error'
				), 45 );
			} else {
				// Save settings
				update_option( 'mets_n8n_chat_settings', $settings );
				
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'n8n chat settings saved successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			}
		}
		
		// Redirect back to settings page
		$redirect_url = admin_url( "admin.php?page=mets-settings&tab={$tab}" );
		wp_redirect( $redirect_url );
		exit;
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
		echo '<input type="hidden" name="page" value="mets-all-tickets">';
		$tickets_list->search_box( __( 'Search Tickets', METS_TEXT_DOMAIN ), 'ticket' );
		echo '</form>';

		echo '<form method="get" action="">';
		echo '<input type="hidden" name="page" value="mets-all-tickets">';
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
	 * Display team overview page
	 *
	 * @since    1.0.0
	 */
	public function display_team_overview_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_agents' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Team Management Overview', METS_TEXT_DOMAIN ) . '</h1>';
		
		echo '<div class="mets-team-overview-dashboard">';
		echo '<div class="mets-grid-container">';
		
		// Quick stats cards
		echo '<div class="mets-stats-row">';
		$this->display_team_quick_stats();
		echo '</div>';
		
		// Quick action buttons
		echo '<div class="mets-quick-actions">';
		echo '<h2>' . esc_html__( 'Quick Actions', METS_TEXT_DOMAIN ) . '</h2>';
		echo '<div class="mets-action-buttons">';
		
		if ( current_user_can( 'manage_agents' ) ) {
			echo '<a href="' . admin_url( 'admin.php?page=mets-agents' ) . '" class="button button-primary">';
			echo '<span class="dashicons dashicons-admin-users"></span> ' . esc_html__( 'Manage Agents', METS_TEXT_DOMAIN );
			echo '</a>';
		}
		
		echo '<a href="' . admin_url( 'admin.php?page=mets-manager-dashboard' ) . '" class="button button-secondary">';
		echo '<span class="dashicons dashicons-chart-bar"></span> ' . esc_html__( 'Team Performance', METS_TEXT_DOMAIN );
		echo '</a>';
		
		if ( current_user_can( 'manage_entities' ) ) {
			echo '<a href="' . admin_url( 'admin.php?page=mets-entities' ) . '" class="button button-secondary">';
			echo '<span class="dashicons dashicons-building"></span> ' . esc_html__( 'Manage Entities', METS_TEXT_DOMAIN );
			echo '</a>';
		}
		
		echo '</div>'; // .mets-action-buttons
		echo '</div>'; // .mets-quick-actions
		
		echo '</div>'; // .mets-grid-container
		echo '</div>'; // .mets-team-overview-dashboard
		echo '</div>'; // .wrap
		
		// Add some basic styling
		echo '<style>
		.mets-team-overview-dashboard { margin-top: 20px; }
		.mets-stats-row { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
		.mets-quick-actions { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; }
		.mets-action-buttons { display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
		.mets-action-buttons .button { display: inline-flex; align-items: center; gap: 8px; }
		.mets-action-buttons .dashicons { font-size: 16px; line-height: 1; }
		</style>';
	}

	/**
	 * Display team quick stats
	 *
	 * @since    1.0.0
	 */
	private function display_team_quick_stats() {
		// Initialize agent management class to get stats
		if ( ! class_exists( 'METS_Agent_Management' ) ) {
			require_once METS_PLUGIN_PATH . 'admin/class-mets-agent-management.php';
		}
		
		$agent_management = new METS_Agent_Management();
		$agent_management->display_agent_statistics();
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
	 * Display user roles and permissions page
	 *
	 * @since    1.0.0
	 */
	public function display_user_roles_permissions_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Initialize user roles and permissions class if not already loaded
		if ( ! class_exists( 'METS_User_Roles_Permissions' ) ) {
			require_once METS_PLUGIN_PATH . 'admin/class-mets-user-roles-permissions.php';
		}

		$user_roles_permissions = METS_User_Roles_Permissions::get_instance();
		$user_roles_permissions->display_roles_permissions_page();
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
	 * Display settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			// Use nl2br for line breaks in error messages (especially for SMTP troubleshooting)
			$message = nl2br( esc_html( $notice['message'] ) );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), $message );
		}

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		echo '<div class="wrap">';
		echo '<h1>' . __( 'Ticket System Settings', METS_TEXT_DOMAIN ) . '</h1>';
		
		// Tab navigation
		echo '<nav class="nav-tab-wrapper">';
		$tabs = array(
			'general'    => __( 'General', METS_TEXT_DOMAIN ),
			'statuses'   => __( 'Statuses', METS_TEXT_DOMAIN ),
			'priorities' => __( 'Priorities', METS_TEXT_DOMAIN ),
			'categories' => __( 'Categories', METS_TEXT_DOMAIN ),
			'workflow'   => __( 'Workflow Rules', METS_TEXT_DOMAIN ),
			'email_smtp' => __( 'Email & SMTP', METS_TEXT_DOMAIN ),
			'n8n_chat'   => __( 'n8n Chat', METS_TEXT_DOMAIN ),
			// 'realtime'   => __( 'Real-time Features', METS_TEXT_DOMAIN ), // Removed - WebSocket features deleted
			'shortcodes' => __( 'Shortcodes', METS_TEXT_DOMAIN ),
		);
		
		foreach ( $tabs as $tab_key => $tab_label ) {
			$active_class = $current_tab === $tab_key ? 'nav-tab-active' : '';
			$tab_url = admin_url( "admin.php?page=mets-settings&tab={$tab_key}" );
			echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . $active_class . '">' . esc_html( $tab_label ) . '</a>';
		}
		echo '</nav>';
		
		// Tab content
		echo '<div class="tab-content" style="margin-top: 20px;">';
		switch ( $current_tab ) {
			case 'statuses':
				$this->display_statuses_settings();
				break;
			case 'priorities':
				$this->display_priorities_settings();
				break;
			case 'categories':
				$this->display_categories_settings();
				break;
			case 'workflow':
				$this->display_workflow_settings();
				break;
			case 'email_smtp':
				$this->display_smtp_settings();
				break;
			case 'n8n_chat':
				$this->display_n8n_chat_settings();
				break;
			// case 'realtime': // Removed - WebSocket features deleted
			//	$this->display_realtime_settings();
			//	break;
			case 'shortcodes':
				$this->display_shortcodes_info();
				break;
			case 'general':
			default:
				$this->display_general_settings();
				break;
		}
		echo '</div>';
		
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
	 * Display general settings
	 *
	 * @since    1.0.0
	 */
	private function display_general_settings() {
		$settings = get_option( 'mets_general_settings', array() );
		
		// Fallback to defaults if empty
		$new_ticket_link_url = isset( $settings['new_ticket_link_url'] ) ? $settings['new_ticket_link_url'] : '';
		$new_ticket_link_text = isset( $settings['new_ticket_link_text'] ) ? $settings['new_ticket_link_text'] : __( 'Submit a new ticket', METS_TEXT_DOMAIN );
		$portal_header_text = isset( $settings['portal_header_text'] ) ? $settings['portal_header_text'] : __( 'View your support tickets and their current status. Need help?', METS_TEXT_DOMAIN );
		$ticket_portal_url = isset( $settings['ticket_portal_url'] ) ? $settings['ticket_portal_url'] : '';
		$terms_conditions_url = isset( $settings['terms_conditions_url'] ) ? $settings['terms_conditions_url'] : '';
		
		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Customer Portal Settings', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Configure the customer portal display settings including custom links and text.', METS_TEXT_DOMAIN ); ?></p>
				
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="general">
					<?php wp_nonce_field( 'mets_save_general_settings', 'general_settings_nonce' ); ?>
					<input type="hidden" name="action" value="save_general_settings">
					
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="portal_header_text"><?php _e( 'Portal Header Text', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<textarea id="portal_header_text" name="portal_header_text" rows="2" cols="50" class="large-text"><?php echo esc_textarea( $portal_header_text ); ?></textarea>
									<p class="description"><?php _e( 'Text displayed in the customer portal header (before the new ticket link).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="new_ticket_link_text"><?php _e( 'New Ticket Link Text', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="text" id="new_ticket_link_text" name="new_ticket_link_text" value="<?php echo esc_attr( $new_ticket_link_text ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Text displayed for the new ticket submission link.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="new_ticket_link_url"><?php _e( 'New Ticket Link URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="new_ticket_link_url" name="new_ticket_link_url" value="<?php echo esc_attr( $new_ticket_link_url ); ?>" class="regular-text" placeholder="https://example.com/submit-ticket">
									<p class="description"><?php _e( 'Custom URL for the new ticket link. Leave empty to use default JavaScript behavior (show form on same page).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					
					<h3><?php _e( 'Additional Endpoint URLs', METS_TEXT_DOMAIN ); ?></h3>
					<p><?php _e( 'Configure custom URLs for various system endpoints. These can be used in emails, redirects, or custom integrations.', METS_TEXT_DOMAIN ); ?></p>
					<div class="notice notice-info inline">
						<p><strong><?php _e( 'Developer Note:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'These URLs can be accessed programmatically using:', METS_TEXT_DOMAIN ); ?></p>
						<ul style="margin-left: 20px;">
							<li><code>METS_Core::get_ticket_portal_url()</code></li>
							<li><code>METS_Core::get_terms_conditions_url()</code></li>
							<li><code>METS_Core::get_general_setting('custom_key')</code></li>
						</ul>
					</div>
					
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ticket_portal_url"><?php _e( 'Ticket Portal URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="ticket_portal_url" name="ticket_portal_url" value="<?php echo esc_attr( $ticket_portal_url ); ?>" class="regular-text" placeholder="https://example.com/customer-portal">
									<p class="description"><?php _e( 'URL where customers can view their tickets. Used in email notifications and system redirects.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="terms_conditions_url"><?php _e( 'Terms and Conditions URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="terms_conditions_url" name="terms_conditions_url" value="<?php echo esc_attr( $terms_conditions_url ); ?>" class="regular-text" placeholder="https://example.com/terms">
									<p class="description"><?php _e( 'URL to your terms and conditions page. Can be referenced in ticket forms and email templates.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					
					<?php submit_button( __( 'Save General Settings', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Display status settings
	 *
	 * @since    1.0.0
	 */
	private function display_statuses_settings() {
		$statuses = get_option( 'mets_ticket_statuses', array() );
		
		// Fallback to defaults if empty
		if ( empty( $statuses ) ) {
			$statuses = array(
				'new' => array( 'label' => __( 'New', METS_TEXT_DOMAIN ), 'color' => '#007cba' ),
				'open' => array( 'label' => __( 'Open', METS_TEXT_DOMAIN ), 'color' => '#00a32a' ),
				'in_progress' => array( 'label' => __( 'In Progress', METS_TEXT_DOMAIN ), 'color' => '#f0b849' ),
				'resolved' => array( 'label' => __( 'Resolved', METS_TEXT_DOMAIN ), 'color' => '#46b450' ),
				'closed' => array( 'label' => __( 'Closed', METS_TEXT_DOMAIN ), 'color' => '#787c82' ),
			);
		}
		
		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Statuses', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available statuses for tickets. Each status should have a unique key, display label and color.', METS_TEXT_DOMAIN ); ?></p>
				
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="statuses">
					<?php wp_nonce_field( 'mets_save_statuses', 'statuses_nonce' ); ?>
					<input type="hidden" name="action" value="save_statuses">
					
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 80px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="statuses-list">
							<?php foreach ( $statuses as $key => $status ) : ?>
								<tr>
									<td><input type="text" name="statuses[<?php echo esc_attr( $key ); ?>][key]" value="<?php echo esc_attr( $key ); ?>" class="small-text" readonly></td>
									<td><input type="text" name="statuses[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $status['label'] ); ?>" class="regular-text" required></td>
									<td><input type="color" name="statuses[<?php echo esc_attr( $key ); ?>][color]" value="<?php echo esc_attr( $status['color'] ); ?>" class="color-picker"></td>
									<td><button type="button" class="button button-small remove-status" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Status', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_status_key"><?php _e( 'Status Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_status_key" name="new_status[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., pending', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_status_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_status_label" name="new_status[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Pending Review', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_status_color"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="color" id="new_status_color" name="new_status[color]" value="#0073aa"></td>
							</tr>
						</table>
					</div>
					
					<?php submit_button( __( 'Save Statuses', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Remove status functionality
			$('.remove-status').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this status?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display priorities settings
	 *
	 * @since    1.0.0
	 */
	private function display_priorities_settings() {
		$priorities = get_option( 'mets_ticket_priorities', array() );
		
		// Fallback to defaults if empty
		if ( empty( $priorities ) ) {
			$priorities = array(
				'low' => array( 'label' => __( 'Low', METS_TEXT_DOMAIN ), 'color' => '#00a32a', 'order' => 1 ),
				'normal' => array( 'label' => __( 'Normal', METS_TEXT_DOMAIN ), 'color' => '#007cba', 'order' => 2 ),
				'high' => array( 'label' => __( 'High', METS_TEXT_DOMAIN ), 'color' => '#f0b849', 'order' => 3 ),
				'urgent' => array( 'label' => __( 'Urgent', METS_TEXT_DOMAIN ), 'color' => '#d63638', 'order' => 4 ),
			);
		}
		
		// Sort by order
		uasort( $priorities, function( $a, $b ) {
			return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
		});
		
		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Priorities', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available priorities for tickets. Priorities have an order that determines their escalation level.', METS_TEXT_DOMAIN ); ?></p>
				
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="priorities">
					<?php wp_nonce_field( 'mets_save_priorities', 'priorities_nonce' ); ?>
					<input type="hidden" name="action" value="save_priorities">
					
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 60px;"><?php _e( 'Order', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="priorities-list">
							<?php foreach ( $priorities as $key => $priority ) : ?>
								<tr>
									<td><input type="number" name="priorities[<?php echo esc_attr( $key ); ?>][order]" value="<?php echo esc_attr( $priority['order'] ?? 1 ); ?>" class="small-text" min="1" max="99"></td>
									<td><input type="text" name="priorities[<?php echo esc_attr( $key ); ?>][key]" value="<?php echo esc_attr( $key ); ?>" class="small-text" readonly></td>
									<td><input type="text" name="priorities[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $priority['label'] ); ?>" class="regular-text" required></td>
									<td><input type="color" name="priorities[<?php echo esc_attr( $key ); ?>][color]" value="<?php echo esc_attr( $priority['color'] ); ?>" class="color-picker"></td>
									<td><button type="button" class="button button-small remove-priority" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Priority', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_priority_key"><?php _e( 'Priority Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_priority_key" name="new_priority[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., critical', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_priority_label" name="new_priority[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Critical', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_color"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="color" id="new_priority_color" name="new_priority[color]" value="#d63638"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_order"><?php _e( 'Order', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="number" id="new_priority_order" name="new_priority[order]" value="5" class="small-text" min="1" max="99"></td>
							</tr>
						</table>
					</div>
					
					<?php submit_button( __( 'Save Priorities', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Remove priority functionality
			$('.remove-priority').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this priority?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display categories settings
	 *
	 * @since    1.0.0
	 */
	private function display_categories_settings() {
		$categories = get_option( 'mets_ticket_categories', array() );
		
		// Fallback to defaults if empty
		if ( empty( $categories ) ) {
			$categories = array(
				'general' => __( 'General', METS_TEXT_DOMAIN ),
				'technical' => __( 'Technical Support', METS_TEXT_DOMAIN ),
				'billing' => __( 'Billing', METS_TEXT_DOMAIN ),
				'sales' => __( 'Sales Inquiry', METS_TEXT_DOMAIN ),
			);
		}
		
		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Categories', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available categories for tickets. Categories help organize and filter tickets by topic or department.', METS_TEXT_DOMAIN ); ?></p>
				
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="categories">
					<?php wp_nonce_field( 'mets_save_categories', 'categories_nonce' ); ?>
					<input type="hidden" name="action" value="save_categories">
					
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="categories-list">
							<?php foreach ( $categories as $key => $label ) : ?>
								<tr>
									<td><input type="text" name="categories_keys[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" class="regular-text" readonly></td>
									<td><input type="text" name="categories[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" required></td>
									<td><button type="button" class="button button-small remove-category" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					
					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Category', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_category_key"><?php _e( 'Category Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_category_key" name="new_category[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., feature_request', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_category_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_category_label" name="new_category[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Feature Request', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
						</table>
					</div>
					
					<?php submit_button( __( 'Save Categories', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Remove category functionality
			$('.remove-category').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this category?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX search entities
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_entities() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		// Placeholder for entity search
		wp_send_json_success( array() );
	}

	/**
	 * AJAX get entity agents
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_entity_agents() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$entity_id = isset( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : 0;
		
		if ( ! $entity_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entity ID.', METS_TEXT_DOMAIN ) ) );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$agents = $ticket_model->get_available_agents( $entity_id );
		
		wp_send_json_success( $agents );
	}

	/**
	 * AJAX assign ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_assign_ticket() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		// Placeholder for ticket assignment
		wp_send_json_success( array() );
	}

	/**
	 * AJAX change ticket status
	 *
	 * @since    1.0.0
	 */
	public function ajax_change_ticket_status() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		// Placeholder for status change
		wp_send_json_success( array() );
	}

	/**
	 * AJAX check workflow transition
	 *
	 * @since    1.0.0
	 */
	public function ajax_check_workflow_transition() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$from_status = isset( $_POST['from_status'] ) ? sanitize_text_field( $_POST['from_status'] ) : '';
		$to_status = isset( $_POST['to_status'] ) ? sanitize_text_field( $_POST['to_status'] ) : '';
		$ticket_data = isset( $_POST['ticket_data'] ) ? $_POST['ticket_data'] : array();
		
		if ( empty( $from_status ) || empty( $to_status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status parameters.', METS_TEXT_DOMAIN ) ) );
		}
		
		// Sanitize ticket data
		$sanitized_ticket_data = array();
		if ( isset( $ticket_data['priority'] ) ) {
			$sanitized_ticket_data['priority'] = sanitize_text_field( $ticket_data['priority'] );
		}
		if ( isset( $ticket_data['category'] ) ) {
			$sanitized_ticket_data['category'] = sanitize_text_field( $ticket_data['category'] );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();
		
		$result = $workflow_model->is_transition_allowed( $from_status, $to_status, get_current_user_id(), $sanitized_ticket_data );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 
				'message' => $result->get_error_message(),
				'allowed' => false
			) );
		}
		
		// Check if note is required
		$requires_note = $workflow_model->requires_note( $from_status, $to_status );
		
		wp_send_json_success( array(
			'allowed' => true,
			'requires_note' => $requires_note,
			'message' => __( 'Transition allowed.', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * AJAX get allowed status transitions
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_allowed_transitions() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$from_status = isset( $_POST['from_status'] ) ? sanitize_text_field( $_POST['from_status'] ) : '';
		$ticket_data = isset( $_POST['ticket_data'] ) ? $_POST['ticket_data'] : array();
		
		if ( empty( $from_status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status parameter.', METS_TEXT_DOMAIN ) ) );
		}
		
		// Sanitize ticket data
		$sanitized_ticket_data = array();
		if ( isset( $ticket_data['priority'] ) ) {
			$sanitized_ticket_data['priority'] = sanitize_text_field( $ticket_data['priority'] );
		}
		if ( isset( $ticket_data['category'] ) ) {
			$sanitized_ticket_data['category'] = sanitize_text_field( $ticket_data['category'] );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();
		
		$allowed_statuses = $workflow_model->get_allowed_transitions( $from_status, get_current_user_id(), $sanitized_ticket_data );
		
		// Get status labels
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$status_options = array();
		
		foreach ( $allowed_statuses as $status_key ) {
			$status_options[] = array(
				'key' => $status_key,
				'label' => isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ]['label'] : ucfirst( $status_key ),
				'color' => isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ]['color'] : '#007cba'
			);
		}
		
		wp_send_json_success( array(
			'allowed_statuses' => $allowed_statuses,
			'status_options' => $status_options
		) );
	}
	
	/**
	 * Display workflow settings
	 *
	 * @since    1.0.0
	 */
	private function display_workflow_settings() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();
		
		// Get existing workflow rules
		$workflow_rules = $workflow_model->get_all();
		
		// Get statuses, priorities, and categories for dropdowns
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		$categories = get_option( 'mets_ticket_categories', array() );
		
		// Get WordPress roles
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		
		// Filter to relevant roles
		$ticket_roles = array();
		foreach ( $all_roles as $role_key => $role_data ) {
			if ( isset( $role_data['capabilities'] ) && 
				 ( isset( $role_data['capabilities']['ticket_agent'] ) || 
				   isset( $role_data['capabilities']['ticket_manager'] ) || 
				   isset( $role_data['capabilities']['ticket_admin'] ) ||
				   $role_key === 'administrator' ) ) {
				$ticket_roles[ $role_key ] = $role_data['name'];
			}
		}
		
		?>
		<div class="workflow-settings">
			<h2><?php _e( 'Workflow Rules', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Define rules for status transitions. Each rule specifies which roles can change from one status to another.', METS_TEXT_DOMAIN ); ?></p>
			
			<!-- Existing Rules -->
			<?php if ( ! empty( $workflow_rules ) ) : ?>
				<h3><?php _e( 'Existing Rules', METS_TEXT_DOMAIN ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php _e( 'From Status', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'To Status', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Allowed Roles', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Conditions', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $workflow_rules as $rule ) : ?>
							<tr>
								<td>
									<?php 
									$from_label = isset( $statuses[ $rule->from_status ] ) ? $statuses[ $rule->from_status ]['label'] : ucfirst( $rule->from_status );
									echo esc_html( $from_label );
									?>
								</td>
								<td>
									<?php 
									$to_label = isset( $statuses[ $rule->to_status ] ) ? $statuses[ $rule->to_status ]['label'] : ucfirst( $rule->to_status );
									echo esc_html( $to_label );
									?>
								</td>
								<td>
									<?php 
									$allowed_roles = is_array( $rule->allowed_roles ) ? $rule->allowed_roles : array( $rule->allowed_roles );
									$role_names = array();
									foreach ( $allowed_roles as $role ) {
										$role_names[] = isset( $ticket_roles[ $role ] ) ? $ticket_roles[ $role ] : ucfirst( $role );
									}
									echo esc_html( implode( ', ', $role_names ) );
									?>
								</td>
								<td>
									<?php
									$conditions = array();
									if ( ! empty( $rule->priority_id ) ) {
										$conditions[] = __( 'Priority specific', METS_TEXT_DOMAIN );
									}
									if ( ! empty( $rule->category ) ) {
										$conditions[] = __( 'Category specific', METS_TEXT_DOMAIN );
									}
									if ( $rule->requires_note ) {
										$conditions[] = __( 'Note required', METS_TEXT_DOMAIN );
									}
									if ( $rule->auto_assign ) {
										$conditions[] = __( 'Auto-assign', METS_TEXT_DOMAIN );
									}
									echo ! empty( $conditions ) ? esc_html( implode( ', ', $conditions ) ) : '—';
									?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="page" value="mets-settings">
										<input type="hidden" name="tab" value="workflow">
										<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule->id ); ?>">
										<?php wp_nonce_field( 'mets_save_workflow', 'workflow_nonce' ); ?>
										<input type="hidden" name="action" value="save_workflow">
										<button type="submit" name="delete_rule" value="1" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this workflow rule?', METS_TEXT_DOMAIN ) ); ?>');">
											<?php _e( 'Delete', METS_TEXT_DOMAIN ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			
			<!-- Add New Rule Form -->
			<h3><?php _e( 'Add New Rule', METS_TEXT_DOMAIN ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="workflow">
				<?php wp_nonce_field( 'mets_save_workflow', 'workflow_nonce' ); ?>
				<input type="hidden" name="action" value="save_workflow">
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="from_status"><?php _e( 'From Status', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<select name="workflow_rule[from_status]" id="from_status" class="regular-text" required>
									<option value=""><?php _e( 'Select Status', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $statuses as $status_key => $status_data ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>">
											<?php echo esc_html( $status_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="to_status"><?php _e( 'To Status', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<select name="workflow_rule[to_status]" id="to_status" class="regular-text" required>
									<option value=""><?php _e( 'Select Status', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $statuses as $status_key => $status_data ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>">
											<?php echo esc_html( $status_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php _e( 'Allowed Roles', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<?php foreach ( $ticket_roles as $role_key => $role_name ) : ?>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="workflow_rule[allowed_roles][]" value="<?php echo esc_attr( $role_key ); ?>">
										<?php echo esc_html( $role_name ); ?>
									</label>
								<?php endforeach; ?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="priority_id"><?php _e( 'Priority Restriction', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="workflow_rule[priority_id]" id="priority_id" class="regular-text">
									<option value=""><?php _e( 'Any Priority', METS_TEXT_DOMAIN ); ?></option>
									<?php 
									$priority_index = 1;
									foreach ( $priorities as $priority_key => $priority_data ) : ?>
										<option value="<?php echo esc_attr( $priority_index ); ?>">
											<?php echo esc_html( $priority_data['label'] ); ?>
										</option>
									<?php 
									$priority_index++;
									endforeach; ?>
								</select>
								<p class="description"><?php _e( 'Restrict this rule to tickets with a specific priority (optional).', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="category"><?php _e( 'Category Restriction', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="workflow_rule[category]" id="category" class="regular-text">
									<option value=""><?php _e( 'Any Category', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $categories as $category_key => $category_label ) : ?>
										<option value="<?php echo esc_attr( $category_key ); ?>">
											<?php echo esc_html( $category_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e( 'Restrict this rule to tickets with a specific category (optional).', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php _e( 'Rule Options', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="workflow_rule[auto_assign]" value="1">
									<?php _e( 'Auto-assign ticket to user making the status change', METS_TEXT_DOMAIN ); ?>
								</label>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="workflow_rule[requires_note]" value="1">
									<?php _e( 'Require a note when making this status change', METS_TEXT_DOMAIN ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>
				
				<?php submit_button( __( 'Add Workflow Rule', METS_TEXT_DOMAIN ), 'primary', 'save_workflow_rule' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display shortcodes information
	 *
	 * @since    1.0.0
	 */
	private function display_shortcodes_info() {
		// Get entities for examples
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'status' => 'active', 'limit' => 3 ) );
		
		// Get categories for examples
		$categories = get_option( 'mets_ticket_categories', array() );
		$category_keys = array_keys( array_slice( $categories, 0, 3, true ) );
		?>
		<div class="shortcodes-info">
			<h2><?php _e( 'Available Shortcodes', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Use these shortcodes to add ticket functionality to your pages and posts.', METS_TEXT_DOMAIN ); ?></p>

			<!-- Ticket Form Shortcode -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Ticket Submission Form', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Display a public ticket submission form on any page or post.', METS_TEXT_DOMAIN ); ?></p>
					
					<h4><?php _e( 'Basic Usage', METS_TEXT_DOMAIN ); ?></h4>
					<div class="shortcode-example">
						<code>[ticket_form]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
					</div>
					<p class="description"><?php _e( 'Shows a complete ticket form with all active entities available for selection.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Parameters', METS_TEXT_DOMAIN ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Parameter', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Default', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>entity</strong></td>
								<td><?php _e( 'Pre-select a specific entity by slug. Users won\'t see the entity dropdown.', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'None', METS_TEXT_DOMAIN ); ?></td>
							</tr>
							<tr>
								<td><strong>require_login</strong></td>
								<td><?php _e( 'Require users to be logged in to submit tickets.', METS_TEXT_DOMAIN ); ?></td>
								<td>no</td>
							</tr>
							<tr>
								<td><strong>categories</strong></td>
								<td><?php _e( 'Limit available categories (comma-separated list of category keys).', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'All categories', METS_TEXT_DOMAIN ); ?></td>
							</tr>
							<tr>
								<td><strong>success_message</strong></td>
								<td><?php _e( 'Custom message shown after successful ticket submission.', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'Default message', METS_TEXT_DOMAIN ); ?></td>
							</tr>
						</tbody>
					</table>

					<h4><?php _e( 'Examples', METS_TEXT_DOMAIN ); ?></h4>
					
					<?php if ( ! empty( $entities ) ) : ?>
						<div class="shortcode-example">
							<strong><?php _e( 'Pre-selected Entity:', METS_TEXT_DOMAIN ); ?></strong><br>
							<code>[ticket_form entity="<?php echo esc_attr( $entities[0]->slug ); ?>"]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form entity=&quot;<?php echo esc_attr( $entities[0]->slug ); ?>&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
							<p class="description"><?php printf( __( 'Form will be locked to the "%s" entity.', METS_TEXT_DOMAIN ), esc_html( $entities[0]->name ) ); ?></p>
						</div>
					<?php endif; ?>

					<div class="shortcode-example">
						<strong><?php _e( 'Logged-in Users Only:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_form require_login="yes"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form require_login=&quot;yes&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Form will only be shown to logged-in users.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<?php if ( ! empty( $category_keys ) ) : ?>
						<div class="shortcode-example">
							<strong><?php _e( 'Limited Categories:', METS_TEXT_DOMAIN ); ?></strong><br>
							<code>[ticket_form categories="<?php echo esc_attr( implode( ',', $category_keys ) ); ?>"]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form categories=&quot;<?php echo esc_attr( implode( ',', $category_keys ) ); ?>&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
							<p class="description"><?php _e( 'Form will only show specified categories in the dropdown.', METS_TEXT_DOMAIN ); ?></p>
						</div>
					<?php endif; ?>

					<div class="shortcode-example">
						<strong><?php _e( 'Complete Example:', METS_TEXT_DOMAIN ); ?></strong><br>
						<?php if ( ! empty( $entities ) && ! empty( $category_keys ) ) : ?>
							<code>[ticket_form entity="<?php echo esc_attr( $entities[0]->slug ); ?>" categories="<?php echo esc_attr( implode( ',', array_slice( $category_keys, 0, 2 ) ) ); ?>" success_message="Thank you! We'll respond within 24 hours."]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form entity=&quot;<?php echo esc_attr( $entities[0]->slug ); ?>&quot; categories=&quot;<?php echo esc_attr( implode( ',', array_slice( $category_keys, 0, 2 ) ) ); ?>&quot; success_message=&quot;Thank you! We'll respond within 24 hours.&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<?php else : ?>
							<code>[ticket_form require_login="yes" success_message="Thank you! We'll respond within 24 hours."]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form require_login=&quot;yes&quot; success_message=&quot;Thank you! We'll respond within 24 hours.&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>
						<p class="description"><?php _e( 'Combines multiple parameters for a customized form.', METS_TEXT_DOMAIN ); ?></p>
					</div>
				</div>
			</div>

			<!-- Customer Portal Shortcode -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Customer Portal', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Display a customer portal where logged-in users can view and manage their tickets.', METS_TEXT_DOMAIN ); ?></p>
					
					<h4><?php _e( 'Basic Usage', METS_TEXT_DOMAIN ); ?></h4>
					<div class="shortcode-example">
						<code>[ticket_portal]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
					</div>
					<p class="description"><?php _e( 'Shows a list of tickets for the logged-in user with filtering and pagination.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Parameters', METS_TEXT_DOMAIN ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Parameter', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Default', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>show_closed</strong></td>
								<td><?php _e( 'Include closed tickets in the initial view.', METS_TEXT_DOMAIN ); ?></td>
								<td>no</td>
							</tr>
							<tr>
								<td><strong>per_page</strong></td>
								<td><?php _e( 'Number of tickets to show per page.', METS_TEXT_DOMAIN ); ?></td>
								<td>10</td>
							</tr>
							<tr>
								<td><strong>allow_new_ticket</strong></td>
								<td><?php _e( 'Show links to submit new tickets.', METS_TEXT_DOMAIN ); ?></td>
								<td>yes</td>
							</tr>
						</tbody>
					</table>

					<h4><?php _e( 'Examples', METS_TEXT_DOMAIN ); ?></h4>
					
					<div class="shortcode-example">
						<strong><?php _e( 'Show Closed Tickets:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal show_closed="yes"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal show_closed=&quot;yes&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Portal will include closed tickets in the initial view.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="shortcode-example">
						<strong><?php _e( 'Custom Pagination:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal per_page="20"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal per_page=&quot;20&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Show 20 tickets per page instead of the default 10.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="shortcode-example">
						<strong><?php _e( 'Complete Example:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal show_closed="yes" per_page="15" allow_new_ticket="no"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal show_closed=&quot;yes&quot; per_page=&quot;15&quot; allow_new_ticket=&quot;no&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Portal with all tickets, 15 per page, without new ticket links.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<h4><?php _e( 'Features', METS_TEXT_DOMAIN ); ?></h4>
					<ul style="list-style: disc; margin-left: 20px;">
						<li><?php _e( '<strong>Ticket List:</strong> View all submitted tickets with status, priority, and last update info', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Filtering:</strong> Filter tickets by status and toggle closed tickets', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Pagination:</strong> Automatic pagination for large ticket lists', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Ticket Details:</strong> Click any ticket to view full conversation history', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Reply System:</strong> Add replies directly from the customer portal', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Security:</strong> Users can only see their own tickets based on email address', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				</div>
			</div>

			<!-- Tips Section -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Tips & Best Practices', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<ul style="list-style: disc; margin-left: 20px;">
						<li><?php _e( '<strong>Page vs Post:</strong> Shortcodes work in both pages and posts. For permanent forms, use a dedicated page.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Entity Selection:</strong> Use the entity parameter on department-specific pages to pre-select the appropriate team.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Categories:</strong> Limit categories to reduce confusion and guide users to the right support channel.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Login Requirement:</strong> Consider requiring login for internal support forms or premium features.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Customer Portal:</strong> The ticket_portal shortcode requires users to be logged in and shows only their own tickets.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Portal Pages:</strong> Create dedicated "My Account" or "Support Portal" pages for the best customer experience.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Custom Messages:</strong> Use success_message to provide specific instructions or set expectations.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Styling:</strong> The forms are responsive and inherit your theme styles. Add custom CSS using the provided classes if needed.', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<style>
		.shortcode-example {
			background: #f1f1f1;
			padding: 10px;
			margin: 10px 0;
			border-left: 4px solid #007cba;
			position: relative;
		}
		.shortcode-example code {
			background: none;
			padding: 0;
			display: inline-block;
			margin-right: 10px;
			font-weight: bold;
		}
		.copy-shortcode {
			float: right;
		}
		.coming-soon {
			font-size: 12px;
			color: #666;
			font-weight: normal;
		}
		.shortcodes-info h4 {
			margin-top: 20px;
			margin-bottom: 10px;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('.copy-shortcode').on('click', function() {
				var shortcode = $(this).data('shortcode');
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(shortcode).select();
				document.execCommand('copy');
				$temp.remove();
				
				var $button = $(this);
				var originalText = $button.text();
				$button.text('<?php echo esc_js( __( 'Copied!', METS_TEXT_DOMAIN ) ); ?>');
				setTimeout(function() {
					$button.text(originalText);
				}, 2000);
			});
		});
		</script>
		<?php
	}

	/**
	 * Display SMTP settings tab
	 *
	 * @since    1.0.0
	 */
	private function display_smtp_settings() {
		$smtp_manager = METS_SMTP_Manager::get_instance();
		$settings = $smtp_manager->get_global_settings();
		
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-providers.php';
		$providers = METS_SMTP_Providers::get_providers();
		?>
		<div class="wrap">
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_save_smtp_settings', 'smtp_settings_nonce' ); ?>
				<input type="hidden" name="action" value="save_smtp_settings">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="email_smtp">
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'SMTP Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Enable SMTP', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smtp_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php _e( 'Enable SMTP for email delivery', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, all plugin emails will be sent using SMTP instead of WordPress default mail.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="smtp-toggle-row">
								<th scope="row"><?php _e( 'Email Method', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_method" id="smtp_method">
										<option value="wordpress" <?php selected( $settings['method'], 'wordpress' ); ?>><?php _e( 'WordPress Default', METS_TEXT_DOMAIN ); ?></option>
										<option value="smtp" <?php selected( $settings['method'], 'smtp' ); ?>><?php _e( 'SMTP', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'Choose email delivery method. SMTP is recommended for reliable delivery.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox smtp-settings-section">
					<h3 class="hndle"><span><?php _e( 'SMTP Server Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Provider', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_provider" id="smtp_provider">
										<option value="custom" <?php selected( $settings['provider'], 'custom' ); ?>><?php _e( 'Custom SMTP Server', METS_TEXT_DOMAIN ); ?></option>
										<optgroup label="<?php _e( 'Popular Email Providers', METS_TEXT_DOMAIN ); ?>">
											<?php foreach ( $providers as $key => $provider ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['provider'], $key ); ?>>
													<?php echo esc_html( $provider['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									</select>
									<p class="description"><?php _e( 'Select a popular email provider for auto-configuration, or choose "Custom SMTP Server" to manually configure any SMTP server.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'SMTP Host', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_host" value="<?php echo esc_attr( $settings['host'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP server hostname (e.g., smtp.gmail.com)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'SMTP Port', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="smtp_port" value="<?php echo esc_attr( $settings['port'] ); ?>" class="small-text">
									<p class="description"><?php _e( 'SMTP server port (587 for TLS, 465 for SSL, 25 for no encryption)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'Encryption', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_encryption">
										<option value="none" <?php selected( $settings['encryption'], 'none' ); ?>><?php _e( 'None', METS_TEXT_DOMAIN ); ?></option>
										<option value="tls" <?php selected( $settings['encryption'], 'tls' ); ?>><?php _e( 'TLS', METS_TEXT_DOMAIN ); ?></option>
										<option value="ssl" <?php selected( $settings['encryption'], 'ssl' ); ?>><?php _e( 'SSL', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'TLS is recommended for most servers.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox smtp-settings-section">
					<h3 class="hndle"><span><?php _e( 'Authentication', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Authentication Required', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smtp_auth_required" value="1" <?php checked( $settings['auth_required'] ); ?>>
										<?php _e( 'Enable SMTP authentication', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'Most SMTP servers require authentication.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="smtp-auth-row">
								<th scope="row"><?php _e( 'Username', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_username" value="<?php echo esc_attr( $settings['username'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP username (usually your email address)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr class="smtp-auth-row">
								<th scope="row"><?php _e( 'Password', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="password" name="smtp_password" value="<?php echo esc_attr( $settings['password'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP password (stored encrypted)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<!-- Gmail Setup Instructions -->
							<tr class="gmail-setup-info smtp-auth-row gmail-hidden">
								<td colspan="2">
									<div class="notice notice-info" style="margin: 0; padding: 12px;">
										<h4 style="margin-top: 0;"><?php _e( '📧 Gmail Setup Instructions', METS_TEXT_DOMAIN ); ?></h4>
										<p><?php _e( '<strong>Important:</strong> Gmail requires App Passwords for SMTP authentication. Follow these steps:', METS_TEXT_DOMAIN ); ?></p>
										<ol style="margin-left: 20px;">
											<li><?php _e( '<strong>Enable 2-Factor Authentication</strong> in your Google Account (required for App Passwords)', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Generate an App Password:</strong>', METS_TEXT_DOMAIN ); ?>
												<br><a href="https://myaccount.google.com/apppasswords" target="_blank" class="button button-small" style="margin-top: 5px;"><?php _e( '🔗 Open Google App Passwords', METS_TEXT_DOMAIN ); ?></a>
											</li>
											<li><?php _e( 'Select "Mail" as the app and "Other" as the device', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( 'Copy the generated 16-character App Password', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Use your full Gmail address as Username</strong> (e.g., you@gmail.com)', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Use the App Password (not your regular Gmail password) in the Password field above</strong>', METS_TEXT_DOMAIN ); ?></li>
										</ol>
										<p style="margin-bottom: 0;"><strong><?php _e( 'Note:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Never use your regular Gmail password for SMTP. It will not work and may compromise your account security.', METS_TEXT_DOMAIN ); ?></p>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Email Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'From Email', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="email" name="smtp_from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Email address for outgoing emails', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'From Name', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Name for outgoing emails', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Reply-To Email', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="email" name="smtp_reply_to" value="<?php echo esc_attr( $settings['reply_to'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Email address for replies (optional)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<?php submit_button( __( 'Save SMTP Settings', METS_TEXT_DOMAIN ) ); ?>
			</form>
			
			<!-- Test Section -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Test SMTP Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<form method="post" action="" style="display: inline-block; margin-right: 15px;">
						<?php wp_nonce_field( 'mets_test_smtp_connection', 'test_smtp_nonce' ); ?>
						<input type="hidden" name="action" value="test_smtp_connection">
						<input type="hidden" name="page" value="mets-settings">
						<input type="hidden" name="tab" value="email_smtp">
						<input type="hidden" name="smtp_method" id="test_smtp_method" value="<?php echo esc_attr( $settings['method'] ); ?>">
						<input type="hidden" name="smtp_provider" id="test_smtp_provider" value="<?php echo esc_attr( $settings['provider'] ); ?>">
						<input type="hidden" name="smtp_host" id="test_smtp_host" value="<?php echo esc_attr( $settings['host'] ); ?>">
						<input type="hidden" name="smtp_port" id="test_smtp_port" value="<?php echo esc_attr( $settings['port'] ); ?>">
						<input type="hidden" name="smtp_encryption" id="test_smtp_encryption" value="<?php echo esc_attr( $settings['encryption'] ); ?>">
						<input type="hidden" name="smtp_auth_required" id="test_smtp_auth_required" value="<?php echo $settings['auth_required'] ? '1' : '0'; ?>">
						<input type="hidden" name="smtp_username" id="test_smtp_username" value="<?php echo esc_attr( $settings['username'] ); ?>">
						<input type="hidden" name="smtp_password" id="test_smtp_password" value="<?php echo esc_attr( $settings['password'] ); ?>">
						<?php submit_button( __( 'Test Connection', METS_TEXT_DOMAIN ), 'secondary', 'test_connection', false ); ?>
					</form>
					
					<form method="post" action="" style="display: inline-block;">
						<?php wp_nonce_field( 'mets_send_test_email', 'test_email_nonce' ); ?>
						<input type="hidden" name="action" value="send_test_email">
						<input type="hidden" name="page" value="mets-settings">
						<input type="hidden" name="tab" value="email_smtp">
						<input type="email" name="test_email_address" value="<?php echo esc_attr( $settings['test_email'] ); ?>" placeholder="<?php _e( 'Test email address', METS_TEXT_DOMAIN ); ?>" style="margin-right: 10px;">
						<?php submit_button( __( 'Send Test Email', METS_TEXT_DOMAIN ), 'secondary', 'send_test', false ); ?>
					</form>
					
					<p class="description" style="margin-top: 10px;">
						<?php _e( 'Use "Test Connection" to verify SMTP server connectivity. Use "Send Test Email" to test complete email delivery.', METS_TEXT_DOMAIN ); ?>
					</p>
				</div>
			</div>
		</div>
		
		<style>
		.smtp-toggle-row,
		.smtp-settings-section,
		.smtp-auth-row {
			display: none;
		}
		.smtp-enabled .smtp-toggle-row,
		.smtp-enabled.smtp-method-smtp .smtp-settings-section {
			display: table-row;
		}
		.smtp-enabled.smtp-method-smtp .smtp-settings-section {
			display: block;
		}
		.smtp-enabled.smtp-method-smtp.smtp-auth-enabled .smtp-auth-row {
			display: table-row;
		}
		.custom-smtp-row {
			display: none;
		}
		.smtp-provider-custom .custom-smtp-row {
			display: table-row;
		}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			// Ensure we're only running this on the SMTP settings tab
			var currentTab = '<?php echo isset( $_GET['tab'] ) ? esc_js( $_GET['tab'] ) : 'general'; ?>';
			if (currentTab !== 'email_smtp') {
				return;
			}
			
			function toggleSMTPSections() {
				var $wrap = $('.wrap');
				var smtpEnabled = $('input[name="smtp_enabled"]').is(':checked');
				var smtpMethod = $('#smtp_method').val();
				var authRequired = $('input[name="smtp_auth_required"]').is(':checked');
				var provider = $('#smtp_provider').val();
				
				$wrap.toggleClass('smtp-enabled', smtpEnabled);
				$wrap.toggleClass('smtp-method-smtp', smtpMethod === 'smtp');
				$wrap.toggleClass('smtp-auth-enabled', authRequired);
				$wrap.toggleClass('smtp-provider-custom', provider === 'custom');
				
				// Show/hide Gmail setup instructions - only within the SMTP form
				var $gmailInfo = $('form .gmail-setup-info');
				if (provider === 'gmail' && smtpEnabled && smtpMethod === 'smtp' && authRequired) {
					$gmailInfo.removeClass('gmail-hidden').addClass('gmail-visible');
				} else {
					$gmailInfo.removeClass('gmail-visible').addClass('gmail-hidden');
				}
				
				// Update test form values
				$('#test_smtp_method').val($('#smtp_method').val());
				$('#test_smtp_provider').val($('#smtp_provider').val());
				$('#test_smtp_host').val($('input[name="smtp_host"]').val());
				$('#test_smtp_port').val($('input[name="smtp_port"]').val());
				$('#test_smtp_encryption').val($('select[name="smtp_encryption"]').val());
				$('#test_smtp_auth_required').val($('input[name="smtp_auth_required"]').is(':checked') ? '1' : '0');
				$('#test_smtp_username').val($('input[name="smtp_username"]').val());
				$('#test_smtp_password').val($('input[name="smtp_password"]').val());
			}
			
			// Handle provider selection
			$('#smtp_provider').on('change', function() {
				var provider = $(this).val();
				if (provider !== 'custom') {
					// Auto-fill provider settings
					var providers = <?php echo json_encode( $providers ); ?>;
					if (providers[provider]) {
						$('input[name="smtp_host"]').val(providers[provider].host);
						$('input[name="smtp_port"]').val(providers[provider].port);
						$('select[name="smtp_encryption"]').val(providers[provider].encryption);
					}
				}
				toggleSMTPSections();
			});
			
			// Handle checkbox and select changes
			$('input[name="smtp_enabled"], #smtp_method, input[name="smtp_auth_required"]').on('change', toggleSMTPSections);
			$('input[name="smtp_host"], input[name="smtp_port"], select[name="smtp_encryption"], input[name="smtp_username"], input[name="smtp_password"]').on('input change', toggleSMTPSections);
			
			// Initial toggle
			toggleSMTPSections();
		});
		</script>
		
		<style>
		.gmail-setup-info.gmail-hidden {
			display: none !important;
		}
		.gmail-setup-info.gmail-visible {
			display: table-row !important;
		}
		</style>
		<?php
	}

	/**
	 * Display n8n chat settings
	 *
	 * @since    1.0.0
	 */
	private function display_n8n_chat_settings() {
		// Get settings with defaults
		$defaults = array(
			'enabled' => false,
			'webhook_url' => '',
			'position' => 'bottom-right',
			'initial_message' => __( 'Hello! How can we help you today?', METS_TEXT_DOMAIN ),
			'theme_color' => '#007cba',
			'window_title' => __( 'Support Chat', METS_TEXT_DOMAIN ),
			'subtitle' => __( 'We typically reply within minutes', METS_TEXT_DOMAIN ),
			'show_on_mobile' => true,
			'allowed_pages' => 'all',
			'specific_pages' => '',
		);
		$settings = wp_parse_args( get_option( 'mets_n8n_chat_settings', array() ), $defaults );
		?>
		<div class="wrap">
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_save_n8n_chat_settings', 'n8n_chat_settings_nonce' ); ?>
				<input type="hidden" name="action" value="save_n8n_chat_settings">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="n8n_chat">
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'n8n Chat Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Enable n8n Chat', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="n8n_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php _e( 'Enable n8n chat widget on your website', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, the n8n chat widget will appear on your website.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Webhook URL', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></th>
								<td>
									<input type="url" name="n8n_webhook_url" value="<?php echo esc_attr( $settings['webhook_url'] ); ?>" class="regular-text" placeholder="https://your-domain.com/webhook/..." required>
									<p class="description"><?php _e( 'Enter your n8n webhook URL. Example: https://your-n8n-instance.com/webhook/your-webhook-id/chat', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Chat Widget Appearance', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Position', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="n8n_position">
										<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>><?php _e( 'Bottom Right', METS_TEXT_DOMAIN ); ?></option>
										<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>><?php _e( 'Bottom Left', METS_TEXT_DOMAIN ); ?></option>
										<option value="top-right" <?php selected( $settings['position'], 'top-right' ); ?>><?php _e( 'Top Right', METS_TEXT_DOMAIN ); ?></option>
										<option value="top-left" <?php selected( $settings['position'], 'top-left' ); ?>><?php _e( 'Top Left', METS_TEXT_DOMAIN ); ?></option>
									</select>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Theme Color', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="color" name="n8n_theme_color" value="<?php echo esc_attr( $settings['theme_color'] ); ?>">
									<p class="description"><?php _e( 'Choose the primary color for the chat widget.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Window Title', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_window_title" value="<?php echo esc_attr( $settings['window_title'] ); ?>" class="regular-text">
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Subtitle', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_subtitle" value="<?php echo esc_attr( $settings['subtitle'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Appears below the window title.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Initial Message', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<textarea name="n8n_initial_message" rows="3" class="large-text"><?php echo esc_textarea( $settings['initial_message'] ); ?></textarea>
									<p class="description"><?php _e( 'The first message users see when they open the chat.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row"><?php _e( 'Show on Mobile', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="n8n_show_on_mobile" value="1" <?php checked( $settings['show_on_mobile'] ); ?>>
										<?php _e( 'Display chat widget on mobile devices', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Display Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Show Chat On', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="all" <?php checked( $settings['allowed_pages'], 'all' ); ?>>
										<?php _e( 'All pages', METS_TEXT_DOMAIN ); ?>
									</label><br>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="specific" <?php checked( $settings['allowed_pages'], 'specific' ); ?>>
										<?php _e( 'Specific pages only', METS_TEXT_DOMAIN ); ?>
									</label><br>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="except" <?php checked( $settings['allowed_pages'], 'except' ); ?>>
										<?php _e( 'All pages except', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>
							
							<tr class="n8n-specific-pages" style="<?php echo $settings['allowed_pages'] === 'all' ? 'display:none;' : ''; ?>">
								<th scope="row"><?php _e( 'Page IDs', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_specific_pages" value="<?php echo esc_attr( $settings['specific_pages'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Enter comma-separated page IDs (e.g., 12,34,56).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Integration Guide', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'To set up n8n chat:', METS_TEXT_DOMAIN ); ?></p>
						<ol>
							<li><?php _e( 'Create a workflow in n8n with a Webhook node', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Copy the webhook URL from n8n', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Paste it in the Webhook URL field above', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Configure the appearance settings as desired', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Save settings and the chat widget will appear on your site', METS_TEXT_DOMAIN ); ?></li>
						</ol>
						<p>
							<a href="https://www.npmjs.com/package/@n8n/chat" target="_blank" class="button button-secondary">
								<?php _e( 'View n8n Chat Documentation', METS_TEXT_DOMAIN ); ?>
							</a>
						</p>
					</div>
				</div>
				
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Settings', METS_TEXT_DOMAIN ); ?>">
				</p>
			</form>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Toggle specific pages field
			$('input[name="n8n_allowed_pages"]').on('change', function() {
				if ($(this).val() === 'all') {
					$('.n8n-specific-pages').hide();
				} else {
					$('.n8n-specific-pages').show();
				}
			});
			
			// Validate webhook URL
			$('form').on('submit', function(e) {
				var webhookUrl = $('input[name="n8n_webhook_url"]').val();
				if ($('input[name="n8n_enabled"]').is(':checked') && !webhookUrl) {
					e.preventDefault();
					alert('<?php _e( 'Please enter a valid webhook URL', METS_TEXT_DOMAIN ); ?>');
					$('input[name="n8n_webhook_url"]').focus();
				}
			});
		});
		</script>
		<?php
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
										<td><?php echo isset($rule->response_time_hours) && $rule->response_time_hours ? sprintf( _n( '%d hour', '%d hours', $rule->response_time_hours, METS_TEXT_DOMAIN ), $rule->response_time_hours ) : '—'; ?></td>
										<td><?php echo isset($rule->resolution_time_hours) && $rule->resolution_time_hours ? sprintf( _n( '%d hour', '%d hours', $rule->resolution_time_hours, METS_TEXT_DOMAIN ), $rule->resolution_time_hours ) : '—'; ?></td>
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
									<input type="number" id="response_time" name="response_time_hours" value="<?php echo $is_edit ? esc_attr( $rule->response_time_hours ) : ''; ?>" min="0" step="0.5" class="small-text">
									<p class="description"><?php _e( 'Time limit for first response to tickets. Leave empty to disable.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							
							<tr>
								<th scope="row">
									<label for="resolution_time"><?php _e( 'Resolution Time (hours)', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="number" id="resolution_time" name="resolution_time_hours" value="<?php echo $is_edit ? esc_attr( $rule->resolution_time_hours ) : ''; ?>" min="0" step="0.5" class="small-text">
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
	 * Display Automation page
	 *
	 * @since    1.0.0
	 */
	public function display_automation_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
		$rule_id = isset( $_GET['rule_id'] ) ? intval( $_GET['rule_id'] ) : 0;
		
		switch ( $action ) {
			case 'add':
				$this->display_add_automation_rule_form();
				break;
			case 'edit':
				$this->display_edit_automation_rule_form( $rule_id );
				break;
			default:
				$this->display_automation_rules_list();
				break;
		}
	}

	/**
	 * Display automation rules list
	 *
	 * @since    1.0.0
	 */
	private function display_automation_rules_list() {
		$automation_rules = get_option( 'mets_automation_rules', array() );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Automation Rules', METS_TEXT_DOMAIN ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-automation&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New Rule', METS_TEXT_DOMAIN ); ?></a>
			
			<?php $this->display_admin_notices(); ?>
			
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Automation Rules', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php if ( empty( $automation_rules ) ) : ?>
						<p><?php _e( 'No automation rules found.', METS_TEXT_DOMAIN ); ?></p>
						<p><?php _e( 'Create your first automation rule to automatically handle ticket workflows, assignments, and notifications.', METS_TEXT_DOMAIN ); ?></p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th scope="col"><?php _e( 'Rule Name', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Trigger', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Created', METS_TEXT_DOMAIN ); ?></th>
									<th scope="col"><?php _e( 'Manage', METS_TEXT_DOMAIN ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $automation_rules as $rule_id => $rule ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $rule['name'] ); ?></strong></td>
										<td><?php echo esc_html( $this->get_trigger_display_name( $rule['trigger'] ) ); ?></td>
										<td><?php echo esc_html( implode( ', ', array_map( array( $this, 'get_action_display_name' ), $rule['actions'] ) ) ); ?></td>
										<td>
											<?php if ( $rule['active'] ) : ?>
												<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php _e( 'Active', METS_TEXT_DOMAIN ); ?>
											<?php else : ?>
												<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <?php _e( 'Inactive', METS_TEXT_DOMAIN ); ?>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $rule['created_at'] ) ) ); ?></td>
										<td>
											<a href="<?php echo admin_url( 'admin.php?page=mets-automation&action=edit&rule_id=' . $rule_id ); ?>" class="button button-small"><?php _e( 'Edit', METS_TEXT_DOMAIN ); ?></a>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-automation&action=delete&rule_id=' . $rule_id ), 'delete_automation_rule_' . $rule_id ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this automation rule?', METS_TEXT_DOMAIN ); ?>')"><?php _e( 'Delete', METS_TEXT_DOMAIN ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Quick Setup Guide', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<h4><?php _e( 'Getting Started with Automation', METS_TEXT_DOMAIN ); ?></h4>
					<ol>
						<li><strong><?php _e( 'Choose a Trigger:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Select what event should start the automation (e.g., ticket created, status changed).', METS_TEXT_DOMAIN ); ?></li>
						<li><strong><?php _e( 'Add Actions:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Choose what should happen automatically (e.g., assign to user, send notification, change status).', METS_TEXT_DOMAIN ); ?></li>
						<li><strong><?php _e( 'Test & Activate:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Save and activate your rule to start automating your workflow.', METS_TEXT_DOMAIN ); ?></li>
					</ol>
					
					<h4><?php _e( 'Step-by-Step Example: Auto-assign Urgent Tickets', METS_TEXT_DOMAIN ); ?></h4>
					<div style="background: #f0f8ff; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa; margin: 10px 0;">
						<ol>
							<li><strong><?php _e( 'Rule Name:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( '"Auto-assign urgent tickets to senior agent"', METS_TEXT_DOMAIN ); ?></li>
							<li><strong><?php _e( 'Trigger:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Select "Ticket is created"', METS_TEXT_DOMAIN ); ?></li>
							<li><strong><?php _e( 'Actions:', METS_TEXT_DOMAIN ); ?></strong> 
								<ul style="margin-top: 5px;">
									<li><?php _e( 'Action 1: "Assign to user" → Enter "senior.agent" (username)', METS_TEXT_DOMAIN ); ?></li>
									<li><?php _e( 'Action 2: "Add internal note" → Enter "Auto-assigned urgent ticket to {assignee}"', METS_TEXT_DOMAIN ); ?></li>
									<li><?php _e( 'Action 3: "Send email notification" → Enter "assignee"', METS_TEXT_DOMAIN ); ?></li>
								</ul>
							</li>
							<li><strong><?php _e( 'Activate:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Check "Enable this automation rule" and save', METS_TEXT_DOMAIN ); ?></li>
						</ol>
					</div>
					
					<h4><?php _e( 'Popular Automation Examples', METS_TEXT_DOMAIN ); ?></h4>
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
						<div style="background: #f9f9f9; padding: 12px; border-radius: 4px;">
							<strong><?php _e( '🎯 Auto-assign by Priority', METS_TEXT_DOMAIN ); ?></strong><br>
							<small><?php _e( 'Trigger: Ticket created<br>Action: Assign to user → "urgent.agent"', METS_TEXT_DOMAIN ); ?></small>
						</div>
						<div style="background: #f9f9f9; padding: 12px; border-radius: 4px;">
							<strong><?php _e( '📧 Welcome Email', METS_TEXT_DOMAIN ); ?></strong><br>
							<small><?php _e( 'Trigger: Ticket created<br>Action: Send email → "customer"', METS_TEXT_DOMAIN ); ?></small>
						</div>
						<div style="background: #f9f9f9; padding: 12px; border-radius: 4px;">
							<strong><?php _e( '⏰ Status Auto-update', METS_TEXT_DOMAIN ); ?></strong><br>
							<small><?php _e( 'Trigger: Reply added<br>Action: Change status → "pending"', METS_TEXT_DOMAIN ); ?></small>
						</div>
						<div style="background: #f9f9f9; padding: 12px; border-radius: 4px;">
							<strong><?php _e( '🔔 Manager Notification', METS_TEXT_DOMAIN ); ?></strong><br>
							<small><?php _e( 'Trigger: Status changed<br>Action: Send email → "manager@company.com"', METS_TEXT_DOMAIN ); ?></small>
						</div>
					</div>
					
					<h4 style="margin-top: 20px;"><?php _e( 'Available Variables for Notes & Emails', METS_TEXT_DOMAIN ); ?></h4>
					<div style="background: #fff3cd; padding: 12px; border-radius: 4px; font-family: monospace;">
						<strong><?php _e( 'Use these in action values:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>{ticket_id}</code>, <code>{customer_name}</code>, <code>{customer_email}</code>, <code>{subject}</code>, <code>{priority}</code>, <code>{status}</code>, <code>{assignee}</code>, <code>{entity_name}</code>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display add automation rule form
	 *
	 * @since    1.0.0
	 */
	private function display_add_automation_rule_form() {
		$this->display_automation_rule_form();
	}

	/**
	 * Display edit automation rule form
	 *
	 * @since    1.0.0
	 * @param    int    $rule_id    Rule ID
	 */
	private function display_edit_automation_rule_form( $rule_id ) {
		$automation_rules = get_option( 'mets_automation_rules', array() );
		
		if ( ! isset( $automation_rules[ $rule_id ] ) ) {
			wp_die( __( 'Automation rule not found.', METS_TEXT_DOMAIN ) );
		}
		
		$rule = $automation_rules[ $rule_id ];
		$this->display_automation_rule_form( $rule, $rule_id );
	}

	/**
	 * Display automation rule form
	 *
	 * @since    1.0.0
	 * @param    array|null    $rule      Rule data for editing
	 * @param    int|null      $rule_id   Rule ID for editing
	 */
	private function display_automation_rule_form( $rule = null, $rule_id = null ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'parent_id' => 'all' ) );
		
		// Get available users for dropdowns
		$users = get_users( array( 'capability' => 'edit_tickets' ) );
		
		// Get available ticket statuses
		$statuses = array(
			'open' => __( 'Open', METS_TEXT_DOMAIN ),
			'in_progress' => __( 'In Progress', METS_TEXT_DOMAIN ),
			'pending' => __( 'Pending', METS_TEXT_DOMAIN ),
			'resolved' => __( 'Resolved', METS_TEXT_DOMAIN ),
			'closed' => __( 'Closed', METS_TEXT_DOMAIN )
		);
		
		// Get available priorities
		$priorities = array(
			'low' => __( 'Low', METS_TEXT_DOMAIN ),
			'normal' => __( 'Normal', METS_TEXT_DOMAIN ),
			'high' => __( 'High', METS_TEXT_DOMAIN ),
			'urgent' => __( 'Urgent', METS_TEXT_DOMAIN )
		);
		
		$is_edit = ! is_null( $rule );
		$page_title = $is_edit ? __( 'Edit Automation Rule', METS_TEXT_DOMAIN ) : __( 'Add Automation Rule', METS_TEXT_DOMAIN );
		$button_text = $is_edit ? __( 'Update Rule', METS_TEXT_DOMAIN ) : __( 'Create Rule', METS_TEXT_DOMAIN );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $page_title ); ?></h1>
			<a href="<?php echo admin_url( 'admin.php?page=mets-automation' ); ?>" class="page-title-action"><?php _e( '← Back to Rules', METS_TEXT_DOMAIN ); ?></a>
			
			<?php if ( ! $is_edit ) : ?>
			<div class="automation-templates" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;">
				<h3 style="margin-top: 0;"><?php _e( '🚀 Quick Start Templates', METS_TEXT_DOMAIN ); ?></h3>
				<p><?php _e( 'Click a template to pre-fill the form with common automation scenarios:', METS_TEXT_DOMAIN ); ?></p>
				<div style="display: flex; gap: 10px; flex-wrap: wrap;">
					<button type="button" class="button template-btn" data-template="auto_assign_urgent">
						🎯 <?php _e( 'Auto-assign Urgent Tickets', METS_TEXT_DOMAIN ); ?>
					</button>
					<button type="button" class="button template-btn" data-template="welcome_email">
						📧 <?php _e( 'Send Welcome Email', METS_TEXT_DOMAIN ); ?>
					</button>
					<button type="button" class="button template-btn" data-template="status_update">
						⏰ <?php _e( 'Auto-update Status', METS_TEXT_DOMAIN ); ?>
					</button>
					<button type="button" class="button template-btn" data-template="manager_notify">
						🔔 <?php _e( 'Notify Manager', METS_TEXT_DOMAIN ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>
			
			<?php $this->display_admin_notices(); ?>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_automation_rule_form', 'automation_rule_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo $is_edit ? 'edit_automation_rule' : 'add_automation_rule'; ?>">
				<input type="hidden" name="page" value="mets-automation">
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule_id ); ?>">
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
									<input type="text" id="rule_name" name="rule_name" value="<?php echo $is_edit ? esc_attr( $rule['name'] ) : ''; ?>" class="regular-text" required>
									<p class="description"><?php _e( 'A descriptive name for this automation rule.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Trigger Event', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="trigger_event"><?php _e( 'When should this rule run?', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<select id="trigger_event" name="trigger_event" required>
										<option value=""><?php _e( 'Select trigger event...', METS_TEXT_DOMAIN ); ?></option>
										<option value="ticket_created" <?php selected( $is_edit ? $rule['trigger'] : '', 'ticket_created' ); ?>><?php _e( 'Ticket is created', METS_TEXT_DOMAIN ); ?></option>
										<option value="ticket_updated" <?php selected( $is_edit ? $rule['trigger'] : '', 'ticket_updated' ); ?>><?php _e( 'Ticket is updated', METS_TEXT_DOMAIN ); ?></option>
										<option value="status_changed" <?php selected( $is_edit ? $rule['trigger'] : '', 'status_changed' ); ?>><?php _e( 'Ticket status changes', METS_TEXT_DOMAIN ); ?></option>
										<option value="ticket_assigned" <?php selected( $is_edit ? $rule['trigger'] : '', 'ticket_assigned' ); ?>><?php _e( 'Ticket is assigned', METS_TEXT_DOMAIN ); ?></option>
										<option value="reply_added" <?php selected( $is_edit ? $rule['trigger'] : '', 'reply_added' ); ?>><?php _e( 'Reply is added', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'The event that will trigger this automation rule.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'Choose what should happen when this rule is triggered. You can add multiple actions that will execute in sequence.', METS_TEXT_DOMAIN ); ?></p>
						
						<div class="action-help-guide" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
							<h4><?php _e( 'Action Value Examples:', METS_TEXT_DOMAIN ); ?></h4>
							<ul style="margin: 10px 0;">
								<li><strong><?php _e( 'Assign to user:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Enter username (e.g., "john.doe") or user ID (e.g., "25")', METS_TEXT_DOMAIN ); ?></li>
								<li><strong><?php _e( 'Change status:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Enter status key (e.g., "open", "pending", "resolved", "closed")', METS_TEXT_DOMAIN ); ?></li>
								<li><strong><?php _e( 'Change priority:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Enter priority level (e.g., "low", "normal", "high", "urgent")', METS_TEXT_DOMAIN ); ?></li>
								<li><strong><?php _e( 'Add internal note:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Enter note text (e.g., "Automatically escalated due to high priority")', METS_TEXT_DOMAIN ); ?></li>
								<li><strong><?php _e( 'Send email notification:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Enter email address or "customer", "assignee", "manager"', METS_TEXT_DOMAIN ); ?></li>
							</ul>
							<p><em><?php _e( 'Pro tip: You can use ticket variables like {customer_name}, {ticket_id}, {priority} in note text and email actions.', METS_TEXT_DOMAIN ); ?></em></p>
						</div>
						
						<div id="actions-container">
							<?php
							$actions = $is_edit && isset( $rule['actions'] ) ? $rule['actions'] : array();
							if ( empty( $actions ) ) {
								$actions = array( array( 'type' => '', 'value' => '' ) );
							}
							
							foreach ( $actions as $index => $action ) :
							?>
								<div class="action-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: flex-start;">
									<select name="actions[<?php echo $index; ?>][type]" style="width: 35%;" class="action-type-select">
										<option value=""><?php _e( 'Select action...', METS_TEXT_DOMAIN ); ?></option>
										<option value="assign_to" <?php selected( $action['type'], 'assign_to' ); ?>><?php _e( 'Assign to user', METS_TEXT_DOMAIN ); ?></option>
										<option value="change_status" <?php selected( $action['type'], 'change_status' ); ?>><?php _e( 'Change status', METS_TEXT_DOMAIN ); ?></option>
										<option value="change_priority" <?php selected( $action['type'], 'change_priority' ); ?>><?php _e( 'Change priority', METS_TEXT_DOMAIN ); ?></option>
										<option value="add_note" <?php selected( $action['type'], 'add_note' ); ?>><?php _e( 'Add internal note', METS_TEXT_DOMAIN ); ?></option>
										<option value="send_email" <?php selected( $action['type'], 'send_email' ); ?>><?php _e( 'Send email notification', METS_TEXT_DOMAIN ); ?></option>
									</select>
									
									<div class="action-value-container" style="width: 50%; position: relative;">
										<!-- User assignment dropdown -->
										<select name="actions[<?php echo $index; ?>][value]" class="action-value-field user-select" style="display: none; width: 100%;">
											<option value=""><?php _e( 'Select user...', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $users as $user ) : ?>
												<option value="<?php echo esc_attr( $user->user_login ); ?>" <?php selected( $action['value'], $user->user_login ); ?>>
													<?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										
										<!-- Status dropdown -->
										<select name="actions[<?php echo $index; ?>][value]" class="action-value-field status-select" style="display: none; width: 100%;">
											<option value=""><?php _e( 'Select status...', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $statuses as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $action['value'], $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										
										<!-- Priority dropdown -->
										<select name="actions[<?php echo $index; ?>][value]" class="action-value-field priority-select" style="display: none; width: 100%;">
											<option value=""><?php _e( 'Select priority...', METS_TEXT_DOMAIN ); ?></option>
											<?php foreach ( $priorities as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $action['value'], $key ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										
										<!-- Email recipient dropdown with custom option -->
										<div class="email-select-container action-value-field" style="display: none; width: 100%;">
											<select class="email-recipient-select" style="width: 70%;">
												<option value=""><?php _e( 'Select recipient...', METS_TEXT_DOMAIN ); ?></option>
												<option value="customer" <?php selected( $action['value'], 'customer' ); ?>><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></option>
												<option value="assignee" <?php selected( $action['value'], 'assignee' ); ?>><?php _e( 'Assignee', METS_TEXT_DOMAIN ); ?></option>
												<option value="manager" <?php selected( $action['value'], 'manager' ); ?>><?php _e( 'Manager', METS_TEXT_DOMAIN ); ?></option>
												<option value="custom" <?php selected( $action['value'] != 'customer' && $action['value'] != 'assignee' && $action['value'] != 'manager' && !empty($action['value']), true ); ?>><?php _e( 'Custom Email', METS_TEXT_DOMAIN ); ?></option>
											</select>
											<input type="email" class="custom-email-input" placeholder="<?php esc_attr_e( 'Enter email...', METS_TEXT_DOMAIN ); ?>" style="width: 28%; margin-left: 2%; display: none;" value="<?php echo esc_attr( !in_array($action['value'], ['customer', 'assignee', 'manager']) ? $action['value'] : '' ); ?>">
											<input type="hidden" name="actions[<?php echo $index; ?>][value]" class="email-value-hidden" value="<?php echo esc_attr( $action['value'] ); ?>">
										</div>
										
										<!-- Text area for notes -->
										<div class="note-input-container action-value-field" style="display: none; width: 100%;">
											<textarea name="actions[<?php echo $index; ?>][value]" rows="2" placeholder="<?php esc_attr_e( 'Enter note text...', METS_TEXT_DOMAIN ); ?>" style="width: 100%; resize: vertical;"><?php echo esc_textarea( $action['value'] ); ?></textarea>
											<div class="variable-buttons" style="margin-top: 5px;">
												<small><?php _e( 'Quick variables:', METS_TEXT_DOMAIN ); ?></small>
												<button type="button" class="button button-small variable-btn" data-variable="{customer_name}"><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></button>
												<button type="button" class="button button-small variable-btn" data-variable="{ticket_id}"><?php _e( 'Ticket ID', METS_TEXT_DOMAIN ); ?></button>
												<button type="button" class="button button-small variable-btn" data-variable="{priority}"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></button>
											</div>
										</div>
										
										<!-- Fallback text input -->
										<input type="text" name="actions[<?php echo $index; ?>][value]" class="action-value-field text-input" value="<?php echo esc_attr( $action['value'] ); ?>" placeholder="<?php esc_attr_e( 'Enter action value...', METS_TEXT_DOMAIN ); ?>" style="width: 100%; display: none;">
									</div>
									
									<button type="button" class="button remove-action" style="width: 12%;"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
						
						<button type="button" id="add-action" class="button"><?php _e( 'Add Action', METS_TEXT_DOMAIN ); ?></button>
					</div>
				</div>
				
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Rule Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="rule_active"><?php _e( 'Active', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="rule_active" name="rule_active" value="1" <?php checked( $is_edit ? $rule['active'] : true ); ?>>
										<?php _e( 'Enable this automation rule', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'Inactive rules will not run automatically.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<?php submit_button( $button_text, 'primary', 'submit' ); ?>
			</form>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			var actionIndex = <?php echo count( $actions ); ?>;
			
			// Templates
			var templates = {
				auto_assign_urgent: {
					name: '<?php esc_js( _e( 'Auto-assign Urgent Tickets', METS_TEXT_DOMAIN ) ); ?>',
					trigger: 'ticket_created',
					actions: [
						{type: 'assign_to', value: '<?php echo esc_js( !empty($users) ? $users[0]->user_login : 'admin' ); ?>'},
						{type: 'add_note', value: '<?php esc_js( _e( 'Auto-assigned urgent ticket to {assignee}', METS_TEXT_DOMAIN ) ); ?>'}
					]
				},
				welcome_email: {
					name: '<?php esc_js( _e( 'Send Welcome Email to Customers', METS_TEXT_DOMAIN ) ); ?>',
					trigger: 'ticket_created',
					actions: [
						{type: 'send_email', value: 'customer'},
						{type: 'add_note', value: '<?php esc_js( _e( 'Welcome email sent to {customer_name}', METS_TEXT_DOMAIN ) ); ?>'}
					]
				},
				status_update: {
					name: '<?php esc_js( _e( 'Auto-update Status on Reply', METS_TEXT_DOMAIN ) ); ?>',
					trigger: 'reply_added',
					actions: [
						{type: 'change_status', value: 'pending'}
					]
				},
				manager_notify: {
					name: '<?php esc_js( _e( 'Notify Manager on Resolution', METS_TEXT_DOMAIN ) ); ?>',
					trigger: 'status_changed',
					actions: [
						{type: 'send_email', value: 'manager'},
						{type: 'add_note', value: '<?php esc_js( _e( 'Manager notified of ticket resolution', METS_TEXT_DOMAIN ) ); ?>'}
					]
				}
			};
			
			// Apply template
			$('.template-btn').click(function() {
				var templateId = $(this).data('template');
				var template = templates[templateId];
				
				if (template) {
					$('#rule_name').val(template.name);
					$('#trigger_event').val(template.trigger);
					
					// Clear existing actions
					$('#actions-container').empty();
					actionIndex = 0;
					
					// Add template actions
					$.each(template.actions, function(i, action) {
						addActionRow(action.type, action.value);
					});
					
					// Scroll to form
					$('html, body').animate({
						scrollTop: $('#rule_name').offset().top - 100
					}, 500);
				}
			});
			
			// Show appropriate input field based on action type
			function updateActionValueField(actionRow, actionType) {
				var container = actionRow.find('.action-value-container');
				container.find('.action-value-field').hide();
				
				switch(actionType) {
					case 'assign_to':
						container.find('.user-select').show();
						break;
					case 'change_status':
						container.find('.status-select').show();
						break;
					case 'change_priority':
						container.find('.priority-select').show();
						break;
					case 'send_email':
						container.find('.email-select-container').show();
						updateEmailField(container.find('.email-select-container'));
						break;
					case 'add_note':
						container.find('.note-input-container').show();
						break;
					default:
						container.find('.text-input').show();
				}
			}
			
			// Handle email field display
			function updateEmailField(emailContainer) {
				var select = emailContainer.find('.email-recipient-select');
				var customInput = emailContainer.find('.custom-email-input');
				var hiddenInput = emailContainer.find('.email-value-hidden');
				
				if (select.val() === 'custom') {
					customInput.show();
					hiddenInput.val(customInput.val());
				} else {
					customInput.hide();
					hiddenInput.val(select.val());
				}
			}
			
			// Initialize existing action fields
			$('.action-row').each(function() {
				var actionType = $(this).find('.action-type-select').val();
				updateActionValueField($(this), actionType);
			});
			
			// Handle action type change
			$(document).on('change', '.action-type-select', function() {
				var actionType = $(this).val();
				var actionRow = $(this).closest('.action-row');
				updateActionValueField(actionRow, actionType);
			});
			
			// Handle email recipient change
			$(document).on('change', '.email-recipient-select', function() {
				var emailContainer = $(this).closest('.email-select-container');
				updateEmailField(emailContainer);
			});
			
			// Update hidden field when custom email changes
			$(document).on('input', '.custom-email-input', function() {
				var hiddenInput = $(this).siblings('.email-value-hidden');
				hiddenInput.val($(this).val());
			});
			
			// Variable insertion
			$(document).on('click', '.variable-btn', function() {
				var variable = $(this).data('variable');
				var textarea = $(this).closest('.note-input-container').find('textarea');
				var cursorPos = textarea[0].selectionStart;
				var textBefore = textarea.val().substring(0, cursorPos);
				var textAfter = textarea.val().substring(cursorPos);
				textarea.val(textBefore + variable + textAfter);
				textarea.focus();
				textarea[0].setSelectionRange(cursorPos + variable.length, cursorPos + variable.length);
			});
			
			// Add new action row
			function addActionRow(actionType = '', actionValue = '') {
				var html = '<div class="action-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: flex-start;">' +
					'<select name="actions[' + actionIndex + '][type]" style="width: 35%;" class="action-type-select">' +
					'<option value=""><?php _e( 'Select action...', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="assign_to"><?php _e( 'Assign to user', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="change_status"><?php _e( 'Change status', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="change_priority"><?php _e( 'Change priority', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="add_note"><?php _e( 'Add internal note', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="send_email"><?php _e( 'Send email notification', METS_TEXT_DOMAIN ); ?></option>' +
					'</select>' +
					'<div class="action-value-container" style="width: 50%; position: relative;">' +
					'<select name="actions[' + actionIndex + '][value]" class="action-value-field user-select" style="display: none; width: 100%;">' +
					'<option value=""><?php _e( 'Select user...', METS_TEXT_DOMAIN ); ?></option>' +
					<?php foreach ( $users as $user ) : ?>
					'<option value="<?php echo esc_js( $user->user_login ); ?>"><?php echo esc_js( $user->display_name . ' (' . $user->user_login . ')' ); ?></option>' +
					<?php endforeach; ?>
					'</select>' +
					'<select name="actions[' + actionIndex + '][value]" class="action-value-field status-select" style="display: none; width: 100%;">' +
					'<option value=""><?php _e( 'Select status...', METS_TEXT_DOMAIN ); ?></option>' +
					<?php foreach ( $statuses as $key => $label ) : ?>
					'<option value="<?php echo esc_js( $key ); ?>"><?php echo esc_js( $label ); ?></option>' +
					<?php endforeach; ?>
					'</select>' +
					'<select name="actions[' + actionIndex + '][value]" class="action-value-field priority-select" style="display: none; width: 100%;">' +
					'<option value=""><?php _e( 'Select priority...', METS_TEXT_DOMAIN ); ?></option>' +
					<?php foreach ( $priorities as $key => $label ) : ?>
					'<option value="<?php echo esc_js( $key ); ?>"><?php echo esc_js( $label ); ?></option>' +
					<?php endforeach; ?>
					'</select>' +
					'<div class="email-select-container action-value-field" style="display: none; width: 100%;">' +
					'<select class="email-recipient-select" style="width: 70%;">' +
					'<option value=""><?php _e( 'Select recipient...', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="customer"><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="assignee"><?php _e( 'Assignee', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="manager"><?php _e( 'Manager', METS_TEXT_DOMAIN ); ?></option>' +
					'<option value="custom"><?php _e( 'Custom Email', METS_TEXT_DOMAIN ); ?></option>' +
					'</select>' +
					'<input type="email" class="custom-email-input" placeholder="<?php esc_attr_e( 'Enter email...', METS_TEXT_DOMAIN ); ?>" style="width: 28%; margin-left: 2%; display: none;">' +
					'<input type="hidden" name="actions[' + actionIndex + '][value]" class="email-value-hidden">' +
					'</div>' +
					'<div class="note-input-container action-value-field" style="display: none; width: 100%;">' +
					'<textarea name="actions[' + actionIndex + '][value]" rows="2" placeholder="<?php esc_attr_e( 'Enter note text...', METS_TEXT_DOMAIN ); ?>" style="width: 100%; resize: vertical;"></textarea>' +
					'<div class="variable-buttons" style="margin-top: 5px;">' +
					'<small><?php _e( 'Quick variables:', METS_TEXT_DOMAIN ); ?></small> ' +
					'<button type="button" class="button button-small variable-btn" data-variable="{customer_name}"><?php _e( 'Customer', METS_TEXT_DOMAIN ); ?></button> ' +
					'<button type="button" class="button button-small variable-btn" data-variable="{ticket_id}"><?php _e( 'Ticket ID', METS_TEXT_DOMAIN ); ?></button> ' +
					'<button type="button" class="button button-small variable-btn" data-variable="{priority}"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></button>' +
					'</div>' +
					'</div>' +
					'<input type="text" name="actions[' + actionIndex + '][value]" class="action-value-field text-input" placeholder="<?php esc_attr_e( 'Enter action value...', METS_TEXT_DOMAIN ); ?>" style="width: 100%; display: none;">' +
					'</div>' +
					'<button type="button" class="button remove-action" style="width: 12%;"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button>' +
					'</div>';
				
				var newRow = $(html);
				$('#actions-container').append(newRow);
				
				// Set values if provided
				if (actionType) {
					newRow.find('.action-type-select').val(actionType);
					updateActionValueField(newRow, actionType);
					
					if (actionValue) {
						// Set the appropriate field value
						switch(actionType) {
							case 'send_email':
								if (['customer', 'assignee', 'manager'].includes(actionValue)) {
									newRow.find('.email-recipient-select').val(actionValue);
								} else {
									newRow.find('.email-recipient-select').val('custom');
									newRow.find('.custom-email-input').val(actionValue).show();
								}
								newRow.find('.email-value-hidden').val(actionValue);
								break;
							default:
								newRow.find('select[name="actions[' + actionIndex + '][value]"]:visible, textarea[name="actions[' + actionIndex + '][value]"]:visible, input[name="actions[' + actionIndex + '][value]"]:visible').val(actionValue);
						}
					}
				}
				
				actionIndex++;
			}
			
			$('#add-action').click(function() {
				addActionRow();
			});
			
			$(document).on('click', '.remove-action', function() {
				$(this).closest('.action-row').remove();
			});
		});
		</script>
		
		<style>
		.required { color: #d63638; }
		.form-table th { width: 200px; }
		
		.automation-templates {
			border: 1px solid #ddd;
			background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
		}
		
		.template-btn {
			background: #fff;
			border: 1px solid #0073aa;
			color: #0073aa;
			transition: all 0.2s ease;
		}
		
		.template-btn:hover {
			background: #0073aa;
			color: #fff;
			transform: translateY(-1px);
			box-shadow: 0 2px 4px rgba(0,115,170,0.2);
		}
		
		.action-row {
			background: #fdfdfd;
			border: 1px solid #e1e1e1;
			border-radius: 6px;
			padding: 15px;
			margin-bottom: 15px !important;
			transition: all 0.2s ease;
		}
		
		.action-row:hover {
			border-color: #0073aa;
			box-shadow: 0 2px 8px rgba(0,115,170,0.1);
		}
		
		.action-value-container {
			position: relative;
		}
		
		.variable-buttons {
			background: #f8f9fa;
			padding: 8px;
			border-radius: 4px;
			border: 1px solid #e1e1e1;
		}
		
		.variable-btn {
			font-size: 11px;
			padding: 2px 8px;
			margin: 0 2px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 3px;
			cursor: pointer;
		}
		
		.variable-btn:hover {
			background: #0073aa;
			color: #fff;
			border-color: #0073aa;
		}
		
		.email-select-container {
			display: flex;
			align-items: center;
		}
		
		.note-input-container textarea {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 8px;
		}
		
		.action-type-select, 
		.action-value-field select,
		.action-value-field input,
		.action-value-field textarea {
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 6px 8px;
			font-size: 14px;
		}
		
		.action-type-select:focus,
		.action-value-field select:focus,
		.action-value-field input:focus,
		.action-value-field textarea:focus {
			border-color: #0073aa;
			box-shadow: 0 0 0 1px #0073aa;
			outline: none;
		}
		
		.remove-action {
			background: #dc3232;
			color: #fff;
			border: none;
			border-radius: 4px;
			padding: 8px 12px;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		
		.remove-action:hover {
			background: #a00;
			transform: translateY(-1px);
		}
		
		#add-action {
			background: #00a32a;
			color: #fff;
			border: none;
			border-radius: 4px;
			padding: 10px 20px;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		
		#add-action:hover {
			background: #008a00;
			transform: translateY(-1px);
			box-shadow: 0 2px 4px rgba(0,163,42,0.3);
		}
		
		.action-help-guide {
			border-left: 4px solid #0073aa;
		}
		
		.action-help-guide ul {
			list-style: none;
			padding-left: 0;
		}
		
		.action-help-guide li {
			padding: 5px 0;
			border-bottom: 1px solid #eee;
		}
		
		.action-help-guide li:last-child {
			border-bottom: none;
		}
		
		/* Responsive adjustments */
		@media (max-width: 768px) {
			.action-row {
				flex-direction: column;
				align-items: stretch;
			}
			
			.action-row > * {
				width: 100% !important;
				margin-bottom: 10px;
			}
			
			.template-btn {
				margin-bottom: 10px;
			}
		}
		</style>
		<?php
	}

	/**
	 * Handle automation form submission
	 *
	 * @since    1.0.0
	 */
	private function handle_automation_form_submission() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}
		
		$action = $_POST['action'] ?? '';
		
		if ( $action === 'add_automation_rule' ) {
			$this->handle_add_automation_rule();
		} elseif ( $action === 'edit_automation_rule' ) {
			$this->handle_edit_automation_rule();
		} elseif ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
			$this->handle_delete_automation_rule();
		}
	}

	/**
	 * Handle add automation rule
	 *
	 * @since    1.0.0
	 */
	private function handle_add_automation_rule() {
		if ( ! wp_verify_nonce( $_POST['automation_rule_nonce'], 'mets_automation_rule_form' ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		$automation_rules = get_option( 'mets_automation_rules', array() );
		
		$rule_data = array(
			'name' => sanitize_text_field( $_POST['rule_name'] ),
			'trigger' => sanitize_text_field( $_POST['trigger_event'] ),
			'actions' => $this->sanitize_actions( $_POST['actions'] ?? array() ),
			'active' => isset( $_POST['rule_active'] ) ? true : false,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);
		
		// Generate unique rule ID
		$rule_id = uniqid( 'rule_' );
		$automation_rules[ $rule_id ] = $rule_data;
		
		if ( update_option( 'mets_automation_rules', $automation_rules ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Automation rule created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create automation rule.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-automation' ) );
		exit;
	}

	/**
	 * Handle edit automation rule
	 *
	 * @since    1.0.0
	 */
	private function handle_edit_automation_rule() {
		if ( ! wp_verify_nonce( $_POST['automation_rule_nonce'], 'mets_automation_rule_form' ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}
		
		$rule_id = sanitize_text_field( $_POST['rule_id'] );
		$automation_rules = get_option( 'mets_automation_rules', array() );
		
		if ( ! isset( $automation_rules[ $rule_id ] ) ) {
			wp_die( __( 'Automation rule not found.', METS_TEXT_DOMAIN ) );
		}
		
		$automation_rules[ $rule_id ] = array_merge( $automation_rules[ $rule_id ], array(
			'name' => sanitize_text_field( $_POST['rule_name'] ),
			'trigger' => sanitize_text_field( $_POST['trigger_event'] ),
			'actions' => $this->sanitize_actions( $_POST['actions'] ?? array() ),
			'active' => isset( $_POST['rule_active'] ) ? true : false,
			'updated_at' => current_time( 'mysql' ),
		) );
		
		if ( update_option( 'mets_automation_rules', $automation_rules ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Automation rule updated successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to update automation rule.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-automation' ) );
		exit;
	}

	/**
	 * Handle delete automation rule
	 *
	 * @since    1.0.0
	 */
	private function handle_delete_automation_rule() {
		$rule_id = sanitize_text_field( $_GET['rule_id'] );
		check_admin_referer( 'delete_automation_rule_' . $rule_id );
		
		$automation_rules = get_option( 'mets_automation_rules', array() );
		
		if ( isset( $automation_rules[ $rule_id ] ) ) {
			unset( $automation_rules[ $rule_id ] );
			
			if ( update_option( 'mets_automation_rules', $automation_rules ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Automation rule deleted successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Failed to delete automation rule.', METS_TEXT_DOMAIN ),
					'type' => 'error'
				), 45 );
			}
		}
		
		wp_redirect( admin_url( 'admin.php?page=mets-automation' ) );
		exit;
	}

	/**
	 * Sanitize actions array
	 *
	 * @since    1.0.0
	 * @param    array    $actions    Raw actions data
	 * @return   array                Sanitized actions
	 */
	private function sanitize_actions( $actions ) {
		$sanitized = array();
		
		foreach ( $actions as $action ) {
			if ( ! empty( $action['type'] ) ) {
				$sanitized[] = array(
					'type' => sanitize_text_field( $action['type'] ),
					'value' => sanitize_text_field( $action['value'] ?? '' ),
				);
			}
		}
		
		return $sanitized;
	}

	/**
	 * Get trigger display name
	 *
	 * @since    1.0.0
	 * @param    string    $trigger    Trigger key
	 * @return   string                Display name
	 */
	private function get_trigger_display_name( $trigger ) {
		$triggers = array(
			'ticket_created' => __( 'Ticket is created', METS_TEXT_DOMAIN ),
			'ticket_updated' => __( 'Ticket is updated', METS_TEXT_DOMAIN ),
			'status_changed' => __( 'Ticket status changes', METS_TEXT_DOMAIN ),
			'ticket_assigned' => __( 'Ticket is assigned', METS_TEXT_DOMAIN ),
			'reply_added' => __( 'Reply is added', METS_TEXT_DOMAIN ),
		);
		
		return $triggers[ $trigger ] ?? $trigger;
	}

	/**
	 * Get action display name
	 *
	 * @since    1.0.0
	 * @param    array    $action    Action data
	 * @return   string             Display name
	 */
	private function get_action_display_name( $action ) {
		$actions = array(
			'assign_to' => __( 'Assign to user', METS_TEXT_DOMAIN ),
			'change_status' => __( 'Change status', METS_TEXT_DOMAIN ),
			'change_priority' => __( 'Change priority', METS_TEXT_DOMAIN ),
			'add_note' => __( 'Add internal note', METS_TEXT_DOMAIN ),
			'send_email' => __( 'Send email notification', METS_TEXT_DOMAIN ),
		);
		
		$action_name = $actions[ $action['type'] ] ?? $action['type'];
		if ( ! empty( $action['value'] ) ) {
			$action_name .= ': ' . $action['value'];
		}
		
		return $action_name;
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
	 * AJAX handler for SLA widget refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_sla_widget() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_admin_nonce' ) ) {
			wp_die( __( 'Security check failed', METS_TEXT_DOMAIN ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-calculator.php';

		$sla_monitor = METS_SLA_Monitor::get_instance();
		$sla_calculator = new METS_SLA_Calculator();

		// Get monitoring metrics
		$metrics = $sla_monitor->get_monitoring_metrics();

		// Get current data
		$approaching_breach = $sla_calculator->get_tickets_approaching_breach( 4 );
		$breached_tickets = $sla_calculator->get_breached_tickets();

		global $wpdb;
		$active_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE status NOT IN ('closed', 'resolved')"
		);

		$sla_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE status NOT IN ('closed', 'resolved') 
			AND (sla_response_due IS NOT NULL OR sla_resolution_due IS NOT NULL)"
		);

		$response_data = array(
			'metrics' => array(
				'breached_count' => count( $breached_tickets ),
				'approaching_count' => count( $approaching_breach ),
				'sla_tickets' => $sla_tickets,
				'active_tickets' => $active_tickets,
			),
			'last_check' => $metrics['last_check'] ?? '',
			'last_check_relative' => ! empty( $metrics['last_check'] ) ? 
				human_time_diff( strtotime( $metrics['last_check'] ) ) . ' ' . __( 'ago', METS_TEXT_DOMAIN ) : '',
		);

		wp_send_json_success( $response_data );
	}

	/**
	 * AJAX handler for tickets widget refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_tickets_widget() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_admin_nonce' ) ) {
			wp_die( __( 'Security check failed', METS_TEXT_DOMAIN ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

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

		// Format data for response
		$status_data = array();
		foreach ( $status_counts as $status ) {
			$status_data[ $status->status ] = $status->count;
		}

		$priority_data = array();
		foreach ( $priority_counts as $priority ) {
			$priority_data[ $priority->priority ] = $priority->count;
		}

		$response_data = array(
			'status_counts' => $status_data,
			'priority_counts' => $priority_data,
			'recent_tickets' => $recent_tickets,
		);

		wp_send_json_success( $response_data );
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
		// Check if we're editing an existing article
		$article_id = isset( $_GET['article_id'] ) ? intval( $_GET['article_id'] ) : 0;
		
		// Use enhanced form if available and enabled
		$use_enhanced = apply_filters( 'mets_kb_display_article_form', false, $article_id );
		
		if ( ! $use_enhanced ) {
			$this->display_edit_article_form( $article_id );
		}
		// If $use_enhanced is true, the enhanced form is displayed by the filter hook
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
		document.getElementById('cb-select-all-1').addEventListener('change', function() {
			var checkboxes = document.querySelectorAll('input[name="articles[]"]');
			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = this.checked;
			}
		});
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

		// Check if KB tables exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'mets_kb_categories';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-kb-activator.php';
			METS_KB_Activator::activate();
		}

		// Handle form submissions
		if ( isset( $_POST['action'] ) ) {
			check_admin_referer( 'mets_kb_categories' );
			$this->handle_category_actions();
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$category_model = new METS_KB_Category_Model();
		$article_model = new METS_KB_Article_Model();
		
		// Get all categories
		$categories = $category_model->get_all();
		
		?>
		<div class="wrap">
			<h1><?php _e( 'Knowledge Base Categories', METS_TEXT_DOMAIN ); ?></h1>
			
			<?php
			// Display admin notices
			$notice = get_transient( 'mets_admin_notice' );
			if ( $notice ) {
				delete_transient( 'mets_admin_notice' );
				?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
					<p><?php echo wp_kses_post( $notice['message'] ); ?></p>
				</div>
				<?php
			}
			?>
			
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
									<label for="category-entity"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></label>
									<select name="entity_id" id="category-entity">
										<option value=""><?php _e( 'Global (All Entities)', METS_TEXT_DOMAIN ); ?></option>
										<?php
										require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
										$entity_model = new METS_Entity_Model();
										$entities = $entity_model->get_all( array( 'parent_id' => 'all' ) );
										foreach ( $entities as $entity ) :
										?>
											<option value="<?php echo esc_attr( $entity->id ); ?>">
												<?php echo esc_html( $entity->name ); ?>
												<?php if ( isset( $entity->parent_name ) && $entity->parent_name ) : ?>
													(<?php echo esc_html( $entity->parent_name ); ?>)
												<?php endif; ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p><?php _e( 'Select which entity this category belongs to, or leave as Global to make it available to all entities.', METS_TEXT_DOMAIN ); ?></p>
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
										<?php $this->display_category_rows( $categories, $article_model ); ?>
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
		document.getElementById('cb-select-all-1').addEventListener('change', function() {
			var checkboxes = document.querySelectorAll('input[name="categories[]"]');
			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = this.checked;
			}
		});
		</script>
		<?php
	}

	/**
	 * Display category rows in hierarchical order
	 *
	 * @since    1.0.0
	 * @param    array    $categories      Categories array
	 * @param    object   $article_model   Article model instance
	 * @param    int      $parent_id       Parent ID for recursion
	 * @param    int      $level           Nesting level
	 */
	private function display_category_rows( $categories, $article_model, $parent_id = 0, $level = 0 ) {
		foreach ( $categories as $category ) {
			if ( $category->parent_id == $parent_id ) {
				$article_count = $article_model->count_articles_by_category( $category->id );
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
				$this->display_category_rows( $categories, $article_model, $category->id, $level + 1 );
			}
		}
	}

	/**
	 * Handle category management actions
	 *
	 * @since    1.0.0
	 */
	private function handle_category_actions() {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}
		
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
		$name = sanitize_text_field( $_POST['name'] ?? '' );
		$slug = sanitize_title( $_POST['slug'] ?? '' );
		$parent_id = intval( $_POST['parent_id'] ?? 0 );
		$description = sanitize_textarea_field( $_POST['description'] ?? '' );
		$color = sanitize_hex_color( $_POST['color'] ?? '#0073aa' );
		$icon = sanitize_text_field( $_POST['icon'] ?? 'dashicons-category' );
		$entity_id = isset( $_POST['entity_id'] ) && ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		
		// Debug logging
		error_log( '[METS-KB] Category creation - Name: ' . $name . ', Parent ID: ' . $parent_id . ', Entity ID: ' . $entity_id );
		
		if ( empty( $name ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category name is required.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		// Convert 0 parent_id to null for database
		if ( $parent_id === 0 ) {
			$parent_id = null;
		}
		
		// Auto-generate slug if empty
		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}
		
		// Set default color if invalid
		if ( ! $color ) {
			$color = '#0073aa';
		}
		
		// Set default icon if empty
		if ( empty( $icon ) ) {
			$icon = 'dashicons-category';
		}
		
		$category_data = array(
			'entity_id' => $entity_id,
			'name' => $name,
			'slug' => $slug,
			'parent_id' => $parent_id,
			'description' => $description,
			'color' => $color,
			'icon' => $icon
		);
		
		error_log( '[METS-KB] Category data before create: ' . print_r( $category_data, true ) );
		
		$result = $category_model->create( $category_data );
		
		error_log( '[METS-KB] Category create result: ' . ( $result ? 'SUCCESS (ID: ' . $result . ')' : 'FAILED' ) );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			global $wpdb;
			$db_error = $wpdb->last_error;
			error_log( '[METS-KB] Database error: ' . $db_error );
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create category.', METS_TEXT_DOMAIN ) . ( $db_error ? ' Error: ' . $db_error : '' ),
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
		
		// Convert 0 parent_id to null for database (same as add_category)
		if ( $parent_id === 0 ) {
			$parent_id = null;
		}
		
		// Prevent setting parent as itself or its child
		if ( $parent_id == $category_id ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'A category cannot be its own parent.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		// Get current category to obtain entity_id for slug regeneration
		$current_category = $category_model->get( $category_id );
		if ( ! $current_category ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category not found.', METS_TEXT_DOMAIN ),
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
			'icon' => $icon,
			'entity_id' => $current_category->entity_id  // Include entity_id for slug regeneration
		);
		
		// Debug logging
		error_log( '[METS-KB] Category update - ID: ' . $category_id . ', Name: ' . $name . ', Parent ID: ' . $parent_id );
		error_log( '[METS-KB] Category update data: ' . print_r( $category_data, true ) );
		
		$result = $category_model->update( $category_id, $category_data );
		
		error_log( '[METS-KB] Category update result: ' . ( $result ? 'SUCCESS' : 'FAILED' ) );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Category updated successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			global $wpdb;
			$db_error = $wpdb->last_error;
			error_log( '[METS-KB] Database error: ' . $db_error );
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to update category.', METS_TEXT_DOMAIN ) . ( $db_error ? ' Error: ' . $db_error : '' ),
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
		
		// Load article model for validation checks
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		// Check if category has articles
		global $wpdb;
		$article_count = $wpdb->get_var( $wpdb->prepare(
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
		$child_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_categories WHERE parent_id = %d",
			$category_id
		) );
		if ( $child_count > 0 ) {
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
		if ( ! isset( $_POST['bulk_action'] ) ) {
			return;
		}
		
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
					$article_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_article_categories WHERE category_id = %d",
						$category_id
					) );
					$child_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_categories WHERE parent_id = %d",
						$category_id
					) );
					
					if ( $article_count > 0 || $child_count > 0 ) {
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
		$name = sanitize_text_field( $_POST['tag_name'] ?? '' );
		$slug = sanitize_title( $_POST['tag_slug'] ?? '' );
		$description = sanitize_textarea_field( $_POST['tag_description'] ?? '' );
		
		// Debug logging
		error_log( '[METS-KB] Tag creation - Name: ' . $name . ', Slug: ' . $slug );
		
		if ( empty( $name ) ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag name is required.', METS_TEXT_DOMAIN ),
				'type' => 'error'
			), 45 );
			return;
		}
		
		// Auto-generate slug if empty
		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}
		
		// Check if KB tables exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'mets_kb_tags';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-kb-activator.php';
			METS_KB_Activator::activate();
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		
		$tag_data = array(
			'name' => $name,
			'slug' => $slug,
			'description' => $description
		);
		
		error_log( '[METS-KB] Tag data before create: ' . print_r( $tag_data, true ) );
		
		$result = $tag_model->create( $tag_data );
		
		error_log( '[METS-KB] Tag create result: ' . ( $result ? 'SUCCESS (ID: ' . $result . ')' : 'FAILED' ) );
		
		if ( $result ) {
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Tag created successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );
		} else {
			$db_error = $wpdb->last_error;
			error_log( '[METS-KB] Tag creation database error: ' . $db_error );
			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Failed to create tag.', METS_TEXT_DOMAIN ) . ( $db_error ? ' Error: ' . $db_error : '' ),
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
		if ( ! current_user_can( 'manage_kb_tags' ) && ! current_user_can( 'manage_tickets' ) && ! current_user_can( 'manage_options' ) ) {
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
			<div id="add-tag-form" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin: 20px 0;">
				<h2><?php _e( 'Add New Tag', METS_TEXT_DOMAIN ); ?></h2>
				<form id="add-tag-ajax-form">
					<input type="hidden" name="action" value="mets_add_kb_tag">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mets_add_kb_tag' ) ); ?>">
					
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
				display: none;
			}
			
			#add-tag-form.show {
				display: block !important;
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
		// Define ajaxurl for AJAX calls
		var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
		
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

			// Add event listener for "Add New Tag" button
			$('#add-new-tag').on('click', function(e) {
				e.preventDefault();
				showAddTagForm();
			});

			// Handle AJAX form submission
			$('#add-tag-ajax-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $submitBtn = $form.find('input[type="submit"]');
				var originalBtnText = $submitBtn.val();
				
				// Client-side validation
				var tagName = $form.find('#tag_name').val().trim();
				if (!tagName) {
					$('<div class="notice notice-error is-dismissible"><p>Tag name is required.</p></div>')
						.insertAfter('.wp-header-end');
					return;
				}
				
				if (tagName.length > 200) {
					$('<div class="notice notice-error is-dismissible"><p>Tag name must be 200 characters or less.</p></div>')
						.insertAfter('.wp-header-end');
					return;
				}
				
				// Show loading state
				$submitBtn.val('Creating Tag...').prop('disabled', true);
				
				var formData = $form.serialize();
				
				$.post(ajaxurl, formData)
					.done(function(response) {
						if (response.success) {
							// Show success message
							$('<div class="notice notice-success is-dismissible"><p>' + 
								(response.data.message || 'Tag created successfully!') + 
								'</p></div>').insertAfter('.wp-header-end');
							hideAddTagForm();
							location.reload(); // Refresh to show new tag
						} else {
							// Show error message
							$('<div class="notice notice-error is-dismissible"><p>' + 
								(response.data.message || 'Error creating tag.') + 
								'</p></div>').insertAfter('.wp-header-end');
						}
					})
					.fail(function() {
						// Show network error
						$('<div class="notice notice-error is-dismissible"><p>Network error. Please try again.</p></div>')
							.insertAfter('.wp-header-end');
					})
					.always(function() {
						// Reset button state
						$submitBtn.val(originalBtnText).prop('disabled', false);
					});
			});
		});

		function showAddTagForm() {
			var $form = jQuery('#add-tag-form');
			if ($form.length > 0) {
				$form.addClass('show');
				jQuery('#tag_name').focus();
			}
		}

		function hideAddTagForm() {
			var $form = jQuery('#add-tag-form');
			$form.removeClass('show');
			var formElement = $form.find('form')[0];
			if (formElement) {
				formElement.reset();
			}
		}

		// Make functions globally available
		window.showAddTagForm = showAddTagForm;
		window.hideAddTagForm = hideAddTagForm;

		function editTag(tagId) {
			// Make AJAX request to get tag data
			jQuery.post(ajaxurl, {
				action: 'mets_get_kb_tag',
				tag_id: tagId,
				nonce: '<?php echo esc_js( wp_create_nonce( 'mets_get_kb_tag' ) ); ?>'
			})
			.done(function(response) {
				if (response.success) {
					var tag = response.data;
					document.getElementById('edit_tag_id').value = tag.id ? String(tag.id) : '';
					document.getElementById('edit_tag_name').value = tag.name ? String(tag.name) : '';
					document.getElementById('edit_tag_slug').value = tag.slug ? String(tag.slug) : '';
					document.getElementById('edit_tag_description').value = tag.description ? String(tag.description) : '';
					document.getElementById('edit-tag-modal').style.display = 'block';
				} else {
					alert('<?php echo esc_js( __( 'Error loading tag data.', METS_TEXT_DOMAIN ) ); ?>');
				}
			})
			.fail(function() {
				alert('<?php echo esc_js( __( 'Network error. Please try again.', METS_TEXT_DOMAIN ) ); ?>');
			});
		}

		function closeEditTagModal() {
			document.getElementById('edit-tag-modal').style.display = 'none';
		}

		function deleteTag(tagId, tagName) {
			if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete this tag?', METS_TEXT_DOMAIN ) ); ?>\n\n' + tagName)) {
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
				nonceInput.value = '<?php echo esc_js( wp_create_nonce( 'mets_delete_kb_tag' ) ); ?>';
				form.appendChild(nonceInput);
				
				document.body.appendChild(form);
				form.submit();
			}
		}

		// Make all functions globally accessible for onclick handlers
		window.editTag = editTag;
		window.closeEditTagModal = closeEditTagModal;
		window.deleteTag = deleteTag;
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

		// Use enhanced form if available and enabled
		$use_enhanced = apply_filters( 'mets_kb_display_article_form', false, $article_id );
		
		if ( $use_enhanced ) {
			// Enhanced form is displayed by the filter hook, so we can return early
			return;
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
					$this->add_admin_notice( __( 'KB Article created successfully! You can now add attachments, edit content, or change settings below.', METS_TEXT_DOMAIN ), 'success' );
					break;
				case 'updated':
					$this->add_admin_notice( __( 'KB Article updated successfully.', METS_TEXT_DOMAIN ), 'success' );
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
				<input type="hidden" name="article_id" value="<?php echo esc_attr( $article_id ); ?>">
				
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
										<select name="entity_id" id="article_entity">
											<option value=""><?php _e( 'Global Article (All Entities)', METS_TEXT_DOMAIN ); ?></option>
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
				var $container = $('#category-checkboxes');
				var $loadingIndicator = $('<div class="mets-loading">Loading categories...</div>');
				
				// Show loading indicator
				$container.html($loadingIndicator);
				
				if (entityId) {
					$.post(ajaxurl, {
						action: 'mets_get_entity_categories',
						entity_id: entityId,
						nonce: $('#mets_kb_nonce').val()
					})
					.done(function(response) {
						if (response.success) {
							$container.html(response.data);
						} else {
							$container.html('<p class="description error"><?php _e( 'Error loading categories. Please try again.', METS_TEXT_DOMAIN ); ?></p>');
						}
					})
					.fail(function() {
						$container.html('<p class="description error"><?php _e( 'Failed to load categories. Please refresh the page and try again.', METS_TEXT_DOMAIN ); ?></p>');
					});
				} else {
					$container.html('<p class="description"><?php _e( 'Select an entity to load categories.', METS_TEXT_DOMAIN ); ?></p>');
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

			// Form validation before submission
			$('#kb-article-form').on('submit', function(e) {
				var $form = $(this);
				var title = $('#title').val().trim();
				var content = '';
				
				// Get content from WordPress editor
				if (typeof tinyMCE !== 'undefined' && tinyMCE.get('article_content')) {
					content = tinyMCE.get('article_content').getContent();
				} else {
					content = $('#article_content').val();
				}
				
				// Remove HTML tags for validation
				var contentText = $('<div>').html(content).text().trim();
				
				if (!title) {
					alert('<?php _e( 'Please enter an article title.', METS_TEXT_DOMAIN ); ?>');
					$('#title').focus();
					e.preventDefault();
					return false;
				}
				
				if (!contentText) {
					alert('<?php _e( 'Please enter article content.', METS_TEXT_DOMAIN ); ?>');
					if (typeof tinyMCE !== 'undefined' && tinyMCE.get('article_content')) {
						tinyMCE.get('article_content').focus();
					} else {
						$('#article_content').focus();
					}
					e.preventDefault();
					return false;
				}

				// Disable submit button to prevent double submission
				$form.find('input[type="submit"]').prop('disabled', true).val('<?php _e( 'Saving...', METS_TEXT_DOMAIN ); ?>');
				
				// Re-enable submit button after 10 seconds as a fallback
				setTimeout(function() {
					$form.find('input[type="submit"]').prop('disabled', false).val($form.find('input[type="submit"]').data('original-value') || '<?php _e( 'Save Article', METS_TEXT_DOMAIN ); ?>');
				}, 10000);
			});

			// Store original submit button value
			$('#publish').each(function() {
				$(this).data('original-value', $(this).val());
			});

			// Add CSS for loading and error states
			$('<style>')
				.prop('type', 'text/css')
				.html(`
					.mets-loading {
						padding: 10px;
						text-align: center;
						color: #666;
						font-style: italic;
					}
					.description.error {
						color: #dc3232;
					}
					#kb-article-form input[type="submit"]:disabled {
						opacity: 0.6;
						cursor: not-allowed;
					}
				`)
				.appendTo('head');
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
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$attachment_model = new METS_KB_Attachment_Model();
		
		$article = $article_model->get( $article_id );
		
		if ( ! $article ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . __( 'Article not found.', METS_TEXT_DOMAIN ) . '</p></div></div>';
			return;
		}
		
		// Increment view count
		$article_model->increment_view_count( $article_id );
		
		// Get attachments
		$attachments = $attachment_model->get_by_article( $article_id );
		
		// Get categories
		global $wpdb;
		$categories = $wpdb->get_results( $wpdb->prepare(
			"SELECT c.*, ac.category_id
			FROM {$wpdb->prefix}mets_kb_categories c
			INNER JOIN {$wpdb->prefix}mets_kb_article_categories ac ON c.id = ac.category_id
			WHERE ac.article_id = %d
			ORDER BY c.name ASC",
			$article_id
		) );
		
		// Get tags
		$tags = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.*, at.tag_id
			FROM {$wpdb->prefix}mets_kb_tags t
			INNER JOIN {$wpdb->prefix}mets_kb_article_tags at ON t.id = at.tag_id
			WHERE at.article_id = %d
			ORDER BY t.name ASC",
			$article_id
		) );
		
		// Calculate reading time (average 200 words per minute)
		$word_count = str_word_count( strip_tags( $article->content ) );
		$reading_time = max( 1, ceil( $word_count / 200 ) );
		
		// Calculate helpful percentage
		$total_feedback = ( $article->helpful_yes ?? 0 ) + ( $article->helpful_no ?? 0 );
		$helpful_percentage = $total_feedback > 0 ? round( ( $article->helpful_yes ?? 0 ) / $total_feedback * 100 ) : 0;
		
		?>
		<div class="wrap">
			<div class="mets-kb-article-view">
				<!-- Article Header -->
				<header class="mets-kb-article-header" role="banner">
					<div class="mets-kb-article-meta-primary">
						<h1 id="article-title" class="mets-kb-article-title"><?php echo esc_html( $article->title ); ?></h1>
						<div class="mets-kb-article-summary" role="complementary" aria-label="Article information">
							<span aria-label="Reading time">
								<span class="dashicons dashicons-clock" style="font-size: 14px;"></span>
								<?php printf( _n( '%d min read', '%d min read', $reading_time, METS_TEXT_DOMAIN ), $reading_time ); ?>
							</span>
							<span aria-label="Article type">
								<span class="dashicons dashicons-category" style="font-size: 14px;"></span>
								<?php echo esc_html( ucfirst( $article->visibility ) ); ?> Article
							</span>
							<span aria-label="Last updated">
								<span class="dashicons dashicons-update" style="font-size: 14px;"></span>
								Updated <?php echo esc_html( human_time_diff( strtotime( $article->updated_at ), current_time( 'timestamp' ) ) ); ?> ago
							</span>
							<?php if ( $article->featured ): ?>
							<span aria-label="Featured article">
								<span class="dashicons dashicons-star-filled" style="font-size: 14px; color: #f39c12;"></span>
								Featured
							</span>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="mets-kb-article-meta-secondary">
						<div class="mets-kb-article-stats" aria-label="Article statistics">
							<span aria-label="View count">
								<span class="dashicons dashicons-visibility" style="font-size: 16px;"></span>
								<?php echo number_format( $article->view_count ?? 0 ); ?> views
							</span>
							<?php if ( $total_feedback > 0 ): ?>
							<span aria-label="Helpfulness rating">
								<span class="dashicons dashicons-thumbs-up" style="font-size: 16px;"></span>
								<?php echo $helpful_percentage; ?>% helpful (<?php echo $total_feedback; ?> votes)
							</span>
							<?php endif; ?>
							<span aria-label="Author">
								<span class="dashicons dashicons-admin-users" style="font-size: 16px;"></span>
								<?php echo esc_html( $article->author_name ?? 'Unknown' ); ?>
							</span>
						</div>
						
						<div class="mets-kb-article-actions">
							<?php if ( $this->can_edit_kb_article( $article ) ): ?>
							<a href="<?php echo admin_url( 'admin.php?page=mets-kb-add-article&article_id=' . $article->id ); ?>" 
							   class="mets-kb-action-btn mets-kb-action-btn--primary">
								<span class="dashicons dashicons-edit" style="font-size: 14px;"></span>
								<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
							</a>
							<?php endif; ?>
							<button class="mets-kb-action-btn" onclick="window.print()">
								<span class="dashicons dashicons-printer" style="font-size: 14px;"></span>
								<?php _e( 'Print', METS_TEXT_DOMAIN ); ?>
							</button>
							<button class="mets-kb-action-btn" onclick="copyArticleLink()" id="share-btn">
								<span class="dashicons dashicons-share" style="font-size: 14px;"></span>
								<?php _e( 'Share', METS_TEXT_DOMAIN ); ?>
							</button>
						</div>
					</div>
				</header>
				
				<!-- Article Content -->
				<main class="mets-kb-article-content" role="main">
					<?php
					// Process content for enhanced display
					$content = $article->content;
					
					// Add content section classes for better visual hierarchy
					$content = preg_replace(
						'/\[tip\](.*?)\[\/tip\]/s',
						'<div class="mets-kb-content-section mets-kb-content-section--tip"><h3>💡 Tip</h3>$1</div>',
						$content
					);
					$content = preg_replace(
						'/\[warning\](.*?)\[\/warning\]/s',
						'<div class="mets-kb-content-section mets-kb-content-section--warning"><h3>⚠️ Warning</h3>$1</div>',
						$content
					);
					$content = preg_replace(
						'/\[important\](.*?)\[\/important\]/s',
						'<div class="mets-kb-content-section mets-kb-content-section--important"><h3>🚨 Important</h3>$1</div>',
						$content
					);
					$content = preg_replace(
						'/\[success\](.*?)\[\/success\]/s',
						'<div class="mets-kb-content-section mets-kb-content-section--success"><h3>✅ Success</h3>$1</div>',
						$content
					);
					
					echo wp_kses_post( $content );
					?>
				</main>
				
				<?php if ( ! empty( $attachments ) ): ?>
				<!-- Article Attachments -->
				<section class="mets-kb-article-attachments" style="padding: 2rem; border-top: 1px solid var(--mets-border);">
					<h3 style="margin: 0 0 1rem 0; font-size: var(--mets-text-lg); font-weight: 600;">📎 Attachments</h3>
					<div class="mets-kb-attachment-list">
						<?php foreach ( $attachments as $attachment ): ?>
						<div class="admin-attachment-item">
							<div class="attachment-icon">
								<span class="dashicons <?php echo esc_attr( $attachment_model->get_file_icon_class( $attachment->mime_type ) ); ?>"></span>
							</div>
							<div class="attachment-info">
								<div class="attachment-name">
									<a href="<?php echo esc_url( $attachment_model->get_download_url( $attachment->id ) ); ?>" target="_blank">
										<?php echo esc_html( $attachment->original_filename ); ?>
									</a>
								</div>
								<div class="attachment-meta">
									<?php echo esc_html( $attachment_model->format_file_size( $attachment->file_size ) ); ?>
									<?php if ( $attachment->download_count > 0 ): ?>
									• <?php echo number_format( $attachment->download_count ); ?> downloads
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>
				
				<?php if ( ! empty( $categories ) || ! empty( $tags ) ): ?>
				<!-- Categories and Tags -->
				<section style="padding: 2rem; border-top: 1px solid var(--mets-border);">
					<?php if ( ! empty( $categories ) ): ?>
					<div class="mets-kb-article-categories">
						<h3>📂 Categories</h3>
						<div class="mets-kb-category-list">
							<?php foreach ( $categories as $category ): ?>
							<span class="mets-kb-category-item">
								<?php echo esc_html( $category->name ); ?>
							</span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $tags ) ): ?>
					<div class="mets-kb-article-tags">
						<h3>🏷️ Tags</h3>
						<div class="mets-kb-tag-list">
							<?php foreach ( $tags as $tag ): ?>
							<span class="mets-kb-tag-item">
								<?php echo esc_html( $tag->name ); ?>
							</span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</section>
				<?php endif; ?>
				
				<!-- Article Metadata -->
				<section class="mets-kb-article-metadata">
					<div class="mets-kb-metadata-grid">
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value">
								<span class="status-badge status-<?php echo esc_attr( $article->status ); ?>">
									<?php echo esc_html( ucfirst( $article->status ) ); ?>
								</span>
							</div>
						</div>
						
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Entity', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value">
								<?php echo $article->entity_name ? esc_html( $article->entity_name ) : __( 'Global', METS_TEXT_DOMAIN ); ?>
							</div>
						</div>
						
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Created', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value">
								<time datetime="<?php echo esc_attr( $article->created_at ); ?>">
									<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $article->created_at ) ) ); ?>
								</time>
							</div>
						</div>
						
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Word Count', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value"><?php echo number_format( $word_count ); ?> words</div>
						</div>
						
						<?php if ( $article->reviewed_by ): ?>
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Reviewed By', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value"><?php echo esc_html( $article->reviewer_name ); ?></div>
						</div>
						<?php endif; ?>
						
						<div class="mets-kb-metadata-item">
							<div class="mets-kb-metadata-label"><?php _e( 'Article ID', METS_TEXT_DOMAIN ); ?></div>
							<div class="mets-kb-metadata-value">#<?php echo esc_html( $article->id ); ?></div>
						</div>
					</div>
				</section>
			</div>
		</div>
		
		<script>
		function copyArticleLink() {
			const url = window.location.href;
			navigator.clipboard.writeText(url).then(function() {
				const btn = document.getElementById('share-btn');
				const originalText = btn.innerHTML;
				btn.innerHTML = '<span class="dashicons dashicons-yes" style="font-size: 14px;"></span> <?php _e( 'Copied!', METS_TEXT_DOMAIN ); ?>';
				setTimeout(function() {
					btn.innerHTML = originalText;
				}, 2000);
			}).catch(function(err) {
				console.error('Could not copy text: ', err);
				// Fallback for older browsers
				const textArea = document.createElement('textarea');
				textArea.value = url;
				document.body.appendChild(textArea);
				textArea.select();
				document.execCommand('copy');
				document.body.removeChild(textArea);
				
				const btn = document.getElementById('share-btn');
				const originalText = btn.innerHTML;
				btn.innerHTML = '<span class="dashicons dashicons-yes" style="font-size: 14px;"></span> <?php _e( 'Copied!', METS_TEXT_DOMAIN ); ?>';
				setTimeout(function() {
					btn.innerHTML = originalText;
				}, 2000);
			});
		}
		</script>
		
		<style>
		.status-badge {
			padding: 0.25rem 0.75rem;
			border-radius: 12px;
			font-size: var(--mets-text-sm);
			font-weight: 500;
			text-transform: capitalize;
		}
		.status-published { background: var(--mets-success-light); color: var(--mets-success); }
		.status-draft { background: var(--mets-border-light); color: var(--mets-text-light); }
		.status-pending { background: var(--mets-warning-light); color: var(--mets-warning); }
		
		@media print {
			.mets-kb-article-actions,
			.wp-admin #wpcontent,
			.wp-admin #wpfooter,
			#adminmenumain { display: none !important; }
			.mets-kb-article-view { box-shadow: none; margin: 0; max-width: none; }
		}
		</style>
		<?php
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
		$entity_id = ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		$status = sanitize_text_field( $_POST['status'] ?? 'draft' );
		$visibility = sanitize_text_field( $_POST['visibility'] ?? 'customer' );
		$is_featured = isset( $_POST['is_featured'] ) ? 1 : 0;
		$meta_title = sanitize_text_field( $_POST['meta_title'] );
		$meta_description = sanitize_textarea_field( $_POST['meta_description'] );
		$categories = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
		$tags = isset( $_POST['tags'] ) ? sanitize_textarea_field( $_POST['tags'] ) : '';

		error_log( '[METS-KB] Parsed - Title: ' . $title . ', Entity: ' . $entity_id . ', Content len: ' . strlen( $content ) );
		
		// Validate required fields
		$content_text = trim( strip_tags( $content ) );
		if ( empty( $title ) || empty( $content_text ) ) {
			error_log( '[METS-KB] Validation FAILED - missing title or content. Title: "' . $title . '", Content: "' . substr( $content_text, 0, 100 ) . '"' );
			$this->add_admin_notice( __( 'Please fill in all required fields (title and content).', METS_TEXT_DOMAIN ), 'error' );
			// Store submitted data in a transient to repopulate the form
			set_transient( 'mets_kb_form_data_' . get_current_user_id(), $_POST, 60 * 5 );
			
			// Maintain context for validation error redirects
			$current_page = $_GET['page'] ?? 'mets-kb-add-article';
			if ( $current_page === 'mets-kb-articles' && $article_id > 0 ) {
				$validation_redirect_url = admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article_id . '&error=validation' );
			} else {
				$validation_redirect_url = admin_url( 'admin.php?page=mets-kb-add-article&error=validation' );
			}
			
			wp_redirect( $validation_redirect_url );
			exit;
		}

		error_log( '[METS-KB] Validation PASSED' );

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
				throw new Exception( __( 'Failed to save article. KB', METS_TEXT_DOMAIN ) );
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

				// Define allowed file types and max size
				$allowed_types = array( 'pdf', 'doc', 'docx', 'odt', 'ods', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp' );
				$max_file_size = 10 * 1024 * 1024; // 10MB

				for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
					// Skip empty files
					if ( empty( $_FILES['attachments']['name'][$i] ) ) {
						continue;
					}

					$filename = $_FILES['attachments']['name'][$i];
					$file_error = $_FILES['attachments']['error'][$i];
					$file_size = $_FILES['attachments']['size'][$i];

					// Check for upload errors
					if ( $file_error !== UPLOAD_ERR_OK ) {
						switch ( $file_error ) {
							case UPLOAD_ERR_INI_SIZE:
							case UPLOAD_ERR_FORM_SIZE:
								$upload_errors[] = sprintf( __( '%s: File size exceeds maximum allowed size.', METS_TEXT_DOMAIN ), $filename );
								break;
							case UPLOAD_ERR_PARTIAL:
								$upload_errors[] = sprintf( __( '%s: File was only partially uploaded.', METS_TEXT_DOMAIN ), $filename );
								break;
							case UPLOAD_ERR_NO_FILE:
								$upload_errors[] = sprintf( __( '%s: No file was uploaded.', METS_TEXT_DOMAIN ), $filename );
								break;
							default:
								$upload_errors[] = sprintf( __( '%s: Upload failed with error code %d.', METS_TEXT_DOMAIN ), $filename, $file_error );
						}
						continue;
					}

					// Validate file size
					if ( $file_size > $max_file_size ) {
						$upload_errors[] = sprintf( __( '%s: File size (%s) exceeds maximum allowed size (10MB).', METS_TEXT_DOMAIN ), $filename, size_format( $file_size ) );
						continue;
					}

					// Validate file type by extension
					$file_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					if ( ! in_array( $file_ext, $allowed_types ) ) {
						$upload_errors[] = sprintf( __( '%s: File type not allowed. Allowed types: %s', METS_TEXT_DOMAIN ), $filename, implode( ', ', $allowed_types ) );
						continue;
					}

					$file = array(
						'name' => $filename,
						'type' => $_FILES['attachments']['type'][$i],
						'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
						'error' => $file_error,
						'size' => $file_size
					);

					$upload_result = $attachment_model->upload_attachment( $saved_article_id, $file );
					if ( is_wp_error( $upload_result ) ) {
						$upload_errors[] = $filename . ': ' . $upload_result->get_error_message();
					} else {
						$upload_count++;
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
				// Redirect to edit page for newly created article
				wp_redirect( admin_url( 'admin.php?page=mets-kb-add-article&article_id=' . $saved_article_id . '&message=created' ) );
				exit;
			} else {
				error_log( '[METS-KB] Redirecting after successful update - Article ID: ' . $article_id );
				
				// Determine where to redirect based on where the user came from
				$current_page = $_GET['page'] ?? 'mets-kb-add-article';
				$redirect_url = '';
				
				if ( $current_page === 'mets-kb-articles' ) {
					// User came from articles list page - redirect back to edit action on that page
					$redirect_url = admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article_id . '&message=updated' );
					error_log( '[METS-KB] Redirecting to articles list edit page' );
				} else {
					// User came from add article page - redirect to add article page with article ID
					$redirect_url = admin_url( 'admin.php?page=mets-kb-add-article&article_id=' . $article_id . '&message=updated' );
					error_log( '[METS-KB] Redirecting to add article page' );
				}
				
				wp_redirect( $redirect_url );
				exit;
			}

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[METS-KB] Exception caught: ' . $e->getMessage() );
			$this->add_admin_notice( $e->getMessage(), 'error' );
			// Store submitted data in a transient to repopulate the form
			set_transient( 'mets_kb_form_data_' . get_current_user_id(), $_POST, 60 * 5 );
			
			// Maintain context for error redirects too
			$current_page = $_GET['page'] ?? 'mets-kb-add-article';
			if ( $current_page === 'mets-kb-articles' && $article_id > 0 ) {
				$error_redirect_url = admin_url( 'admin.php?page=mets-kb-articles&action=edit&article_id=' . $article_id . '&error=exception' );
			} else {
				$error_redirect_url = admin_url( 'admin.php?page=mets-kb-add-article&error=exception' );
			}
			
			wp_redirect( $error_redirect_url );
			exit;
		}
	}

	/**
	 * AJAX handler for getting entity categories
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_entity_categories() {
		check_ajax_referer( 'mets_kb_save_article', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Unauthorized', METS_TEXT_DOMAIN ) );
		}

		$entity_id = isset( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : 0;
		
		// Load category model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		$category_model = new METS_KB_Category_Model();
		
		// Get categories for this entity (including inherited)
		$categories = $category_model->get_by_entity( $entity_id, true );
		
		// Build HTML response
		$html = '';
		if ( $categories && ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$html .= '<label class="kb-category-item">';
				$html .= '<input type="checkbox" name="categories[]" value="' . esc_attr( $category->id ) . '">';
				$html .= esc_html( $category->name );
				if ( $category->entity_name && $category->entity_name !== 'Global' ) {
					$html .= ' <small>(' . esc_html( $category->entity_name ) . ')</small>';
				}
				$html .= '</label>';
			}
		} else {
			$html = '<p class="description">' . __( 'No categories available for this entity.', METS_TEXT_DOMAIN ) . '</p>';
		}
		
		wp_send_json_success( $html );
	}

	/**
	 * AJAX handler for removing KB attachments
	 *
	 * @since    1.0.0
	 */
	public function ajax_remove_kb_attachment() {
		check_ajax_referer( 'mets_kb_save_article', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( __( 'Unauthorized', METS_TEXT_DOMAIN ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
		
		if ( ! $attachment_id ) {
			wp_send_json_error( __( 'Invalid attachment ID.', METS_TEXT_DOMAIN ) );
		}

		// Load attachment model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();
		
		// Get attachment to verify ownership/permissions
		$attachment = $attachment_model->get( $attachment_id );
		if ( ! $attachment ) {
			wp_send_json_error( __( 'Attachment not found.', METS_TEXT_DOMAIN ) );
		}

		// Load article model to check permissions
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get( $attachment->article_id );
		
		if ( ! $article ) {
			wp_send_json_error( __( 'Article not found.', METS_TEXT_DOMAIN ) );
		}

		// Check if user can edit this article
		if ( ! $this->can_edit_kb_article( $article ) ) {
			wp_send_json_error( __( 'You do not have permission to edit this article.', METS_TEXT_DOMAIN ) );
		}

		// Remove the attachment
		$result = $attachment_model->delete( $attachment_id );
		
		if ( $result ) {
			wp_send_json_success( __( 'Attachment removed successfully.', METS_TEXT_DOMAIN ) );
		} else {
			wp_send_json_error( __( 'Failed to remove attachment.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for admin KB article search
	 *
	 * @since    1.0.0
	 */
	public function ajax_admin_search_kb_articles() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}
		
		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		$entity_id = intval( $_POST['entity_id'] ?? 0 );
		$limit = intval( $_POST['limit'] ?? 10 );
		
		if ( strlen( $search_term ) < 2 ) {
			wp_send_json_success( array(
				'articles' => array()
			) );
		}

		// Load KB article model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		// Search for published articles with inheritance
		$args = array(
			'entity_id' => $entity_id > 0 ? $entity_id : null,
			'status' => array( 'published' ),
			'visibility' => array( 'customer', 'staff', 'internal' ),
			'search' => $search_term,
			'per_page' => min( $limit, 20 ), // Cap at 20 for performance
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC',
			'include_parent' => true
		);

		$results = $article_model->get_articles_with_inheritance( $args );
		
		$articles = array();
		foreach ( $results['articles'] as $article ) {
			$articles[] = array(
				'id' => $article->id,
				'title' => $article->title,
				'excerpt' => $article->excerpt ?: wp_trim_words( strip_tags( $article->content ), 20 ),
				'entity_name' => $article->entity_name ?: __( 'General', METS_TEXT_DOMAIN ),
				'url' => $this->get_kb_article_admin_url( $article ),
				'helpful_yes' => $article->helpful_yes ?? 0,
				'helpful_no' => $article->helpful_no ?? 0
			);
		}

		wp_send_json_success( array(
			'articles' => $articles,
			'total' => $results['total']
		) );
	}

	/**
	 * Get KB article admin URL
	 *
	 * @since    1.0.0
	 * @param    object   $article   Article object
	 * @return   string             Article admin URL
	 */
	private function get_kb_article_admin_url( $article ) {
		// For admin, link to edit article page
		return admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id );
	}

	/**
	 * AJAX handler for linking KB article to ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_link_kb_article() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$article_id = intval( $_POST['article_id'] ?? 0 );
		$link_type = sanitize_text_field( $_POST['link_type'] ?? 'related' );
		$agent_notes = sanitize_textarea_field( $_POST['agent_notes'] ?? '' );

		if ( ! $ticket_id || ! $article_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket or article ID', METS_TEXT_DOMAIN ) ) );
		}

		// Verify article exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get_by_id( $article_id );

		if ( ! $article ) {
			wp_send_json_error( array( 'message' => __( 'Article not found', METS_TEXT_DOMAIN ) ) );
		}

		// Verify ticket exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get_by_id( $ticket_id );

		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found', METS_TEXT_DOMAIN ) ) );
		}

		// Create link
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$link_data = array(
			'ticket_id' => $ticket_id,
			'article_id' => $article_id,
			'link_type' => $link_type,
			'suggested_by' => get_current_user_id(),
			'agent_notes' => $agent_notes,
		);

		$link_id = $link_model->create( $link_data );

		if ( $link_id ) {
			wp_send_json_success( array( 
				'message' => __( 'Article linked successfully', METS_TEXT_DOMAIN ),
				'link_id' => $link_id 
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to link article', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler for unlinking KB article from ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_unlink_kb_article() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$link_id = intval( $_POST['link_id'] ?? 0 );

		if ( ! $link_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid link ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$result = $link_model->delete( $link_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Article link removed successfully', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to remove article link', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler for marking KB article as helpful/not helpful
	 *
	 * @since    1.0.0
	 */
	public function ajax_mark_kb_helpful() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$article_id = intval( $_POST['article_id'] ?? 0 );
		$helpful = $_POST['helpful'] === '1' ? true : false;

		if ( ! $ticket_id || ! $article_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket or article ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$result = $link_model->mark_helpful( $ticket_id, $article_id, $helpful );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Feedback updated successfully', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update feedback', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler to get KB tag data
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_kb_tag() {
		check_ajax_referer( 'mets_get_kb_tag', 'nonce' );
		
		if ( ! current_user_can( 'manage_kb_tags' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$tag_id = intval( $_POST['tag_id'] ?? 0 );

		if ( ! $tag_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tag ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		$tag = $tag_model->get( $tag_id );

		if ( ! $tag ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found', METS_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success( $tag );
	}

	/**
	 * AJAX handler to add KB tag
	 *
	 * @since    1.0.0
	 */
	public function ajax_add_kb_tag() {
		try {
			check_ajax_referer( 'mets_add_kb_tag', 'nonce' );
			
			if ( ! current_user_can( 'manage_kb_tags' ) && ! current_user_can( 'manage_tickets' ) ) {
				wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
			}

			$name = sanitize_text_field( $_POST['tag_name'] ?? '' );
			$slug = sanitize_title( $_POST['tag_slug'] ?? '' );
			$description = sanitize_textarea_field( $_POST['tag_description'] ?? '' );

			if ( empty( $name ) ) {
				wp_send_json_error( array( 'message' => __( 'Tag name is required.', METS_TEXT_DOMAIN ) ) );
			}

			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
			$tag_model = new METS_KB_Tag_Model();

			// Auto-generate slug if not provided
			if ( empty( $slug ) ) {
				$slug = sanitize_title( $name );
			}

			// Check if slug already exists
			if ( $tag_model->slug_exists( $slug ) ) {
				wp_send_json_error( array( 'message' => __( 'A tag with this slug already exists.', METS_TEXT_DOMAIN ) ) );
			}

			$tag_data = array(
				'name' => $name,
				'slug' => $slug,
				'description' => $description
			);

			$tag_id = $tag_model->create( $tag_data );

			if ( ! $tag_id ) {
				// Log the error for debugging
				error_log( 'METS KB Tag Creation Failed: ' . print_r( $tag_data, true ) );
				wp_send_json_error( array( 'message' => __( 'Failed to create tag.', METS_TEXT_DOMAIN ) ) );
			}

			wp_send_json_success( array( 
				'message' => __( 'Tag created successfully.', METS_TEXT_DOMAIN ),
				'tag_id' => $tag_id
			) );
		} catch ( Exception $e ) {
			error_log( 'METS KB Tag Creation Exception: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'An unexpected error occurred. Please try again.', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler to update KB tag
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_kb_tag() {
		check_ajax_referer( 'mets_update_kb_tag', 'nonce' );
		
		if ( ! current_user_can( 'manage_kb_tags' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$tag_id = intval( $_POST['tag_id'] ?? 0 );
		$name = sanitize_text_field( $_POST['name'] ?? '' );
		$slug = sanitize_title( $_POST['slug'] ?? '' );
		$description = sanitize_textarea_field( $_POST['description'] ?? '' );
		$color = sanitize_hex_color( $_POST['color'] ?? '#0073aa' );
		$entity_id = isset( $_POST['entity_id'] ) && ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;

		if ( ! $tag_id || empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Tag ID and name are required.', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();

		// Auto-generate slug if not provided
		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		// Check if slug already exists for a different tag
		$existing_tag = $tag_model->get_by_slug( $slug );
		if ( $existing_tag && $existing_tag->id != $tag_id ) {
			wp_send_json_error( array( 'message' => __( 'A tag with this slug already exists.', METS_TEXT_DOMAIN ) ) );
		}

		$tag_data = array(
			'name' => $name,
			'slug' => $slug,
			'description' => $description,
			'color' => $color,
			'entity_id' => $entity_id,
			'updated_at' => current_time( 'mysql' )
		);

		$success = $tag_model->update( $tag_id, $tag_data );

		if ( ! $success ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update tag.', METS_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Tag updated successfully.', METS_TEXT_DOMAIN ) ) );
	}

	/**
	 * AJAX handler to delete KB tag
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_kb_tag() {
		check_ajax_referer( 'mets_delete_kb_tag', 'nonce' );
		
		if ( ! current_user_can( 'manage_kb_tags' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$tag_id = intval( $_POST['tag_id'] ?? 0 );

		if ( ! $tag_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tag ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();

		$success = $tag_model->delete( $tag_id );

		if ( ! $success ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete tag.', METS_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Tag deleted successfully.', METS_TEXT_DOMAIN ) ) );
	}

	/**
	 * Display WooCommerce settings page
	 *
	 * @since    1.0.0
	 */
	public function display_woocommerce_settings_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-woocommerce-settings.php';
		$wc_settings = new METS_WooCommerce_Settings();
		$wc_settings->display_settings_page();
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
	 * AJAX handler for flushing rewrite rules
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_rewrite_rules() {
		check_ajax_referer( 'mets_flush_rewrite_rules', 'nonce' );
		
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		flush_rewrite_rules();
		wp_send_json_success( __( 'Rewrite rules flushed successfully', METS_TEXT_DOMAIN ) );
	}

	/**
	 * AJAX handler for creating WooCommerce entity
	 *
	 * @since    1.0.0
	 */
	public function ajax_create_wc_entity() {
		check_ajax_referer( 'mets_create_wc_entity', 'nonce' );
		
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		// Check if WooCommerce entity already exists
		global $wpdb;
		$existing_entity = $wpdb->get_row(
			"SELECT id FROM {$wpdb->prefix}mets_entities 
			WHERE name = 'WooCommerce' AND type = 'company'"
		);

		if ( $existing_entity ) {
			wp_send_json_error( __( 'WooCommerce entity already exists', METS_TEXT_DOMAIN ) );
		}

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

		if ( $entity_id ) {
			wp_send_json_success( sprintf( __( 'WooCommerce entity created with ID: %d', METS_TEXT_DOMAIN ), $entity_id ) );
		} else {
			wp_send_json_error( __( 'Failed to create WooCommerce entity', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for testing WooCommerce integration
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_wc_integration() {
		check_ajax_referer( 'mets_test_wc_integration', 'nonce' );
		
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		$results = array();

		// Test 1: WooCommerce availability
		if ( class_exists( 'WooCommerce' ) ) {
			$results[] = '✓ WooCommerce is active (v' . WC()->version . ')';
		} else {
			$results[] = '✗ WooCommerce is not active';
			wp_send_json_success( $results );
			return;
		}

		// Test 2: WooCommerce entity
		global $wpdb;
		$wc_entity = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}mets_entities 
			WHERE name = 'WooCommerce' AND type = 'company'"
		);

		if ( $wc_entity ) {
			$results[] = '✓ WooCommerce entity exists (ID: ' . $wc_entity->id . ')';
		} else {
			$results[] = '! WooCommerce entity not found (will be created automatically)';
		}

		// Test 3: Endpoints
		$endpoints = array( 'support-tickets', 'create-ticket' );
		foreach ( $endpoints as $endpoint ) {
			if ( get_option( 'woocommerce_myaccount_' . str_replace( '-', '_', $endpoint ) . '_endpoint', $endpoint ) ) {
				$results[] = '✓ Endpoint "' . $endpoint . '" is configured';
			} else {
				$results[] = '! Endpoint "' . $endpoint . '" uses default settings';
			}
		}

		// Test 4: Database tables
		$tables = array( 'mets_tickets', 'mets_entities', 'mets_ticket_replies' );
		foreach ( $tables as $table ) {
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'" );
			if ( $table_exists ) {
				$results[] = '✓ Table ' . $wpdb->prefix . $table . ' exists';
			} else {
				$results[] = '✗ Table ' . $wpdb->prefix . $table . ' is missing';
			}
		}

		// Test 5: Settings
		$settings = METS_WooCommerce_Integration::get_settings();
		if ( $settings['enabled'] ) {
			$results[] = '✓ WooCommerce integration is enabled';
		} else {
			$results[] = '! WooCommerce integration is disabled';
		}

		// Test 6: Permissions
		if ( current_user_can( 'manage_tickets' ) ) {
			$results[] = '✓ User has manage_tickets capability';
		} else {
			$results[] = '✗ User lacks manage_tickets capability';
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for loading bulk tickets
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_tickets() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();
		
		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		// Apply filters
		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "t.status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		if ( ! empty( $filters['priority'] ) && ! in_array( 'all', $filters['priority'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['priority'] ), '%s' ) );
			$where_clauses[] = "t.priority IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['priority'] );
		}

		if ( ! empty( $filters['entity_id'] ) ) {
			$where_clauses[] = 't.entity_id = %d';
			$where_values[] = intval( $filters['entity_id'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clauses[] = 't.created_at >= %s';
			$where_values[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clauses[] = 't.created_at <= %s';
			$where_values[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}

		if ( ! empty( $filters['assigned_to'] ) ) {
			if ( $filters['assigned_to'] === 'unassigned' ) {
				$where_clauses[] = 't.assigned_to IS NULL';
			} else {
				$where_clauses[] = 't.assigned_to = %d';
				$where_values[] = intval( $filters['assigned_to'] );
			}
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT t.id, t.subject, t.status, t.priority, t.created_at, e.name as entity_name
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			WHERE $where_sql
			ORDER BY t.created_at DESC
			LIMIT 500
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$tickets = $wpdb->get_results( $query );

		wp_send_json_success( $tickets );
	}

	/**
	 * AJAX handler for loading bulk entities
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_entities() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_entities' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();
		
		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $filters['type'] ) && ! in_array( 'all', $filters['type'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['type'] ), '%s' ) );
			$where_clauses[] = "type IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['type'] );
		}

		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT id, name, type, status, created_at,
			       (SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE entity_id = e.id) as ticket_count
			FROM {$wpdb->prefix}mets_entities e
			WHERE $where_sql
			ORDER BY name
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$entities = $wpdb->get_results( $query );

		wp_send_json_success( $entities );
	}

	/**
	 * AJAX handler for loading bulk KB articles
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_kb_articles() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();
		
		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "a.status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		if ( ! empty( $filters['category_id'] ) ) {
			$where_clauses[] = 'a.category_id = %d';
			$where_values[] = intval( $filters['category_id'] );
		}

		if ( isset( $filters['featured'] ) && $filters['featured'] !== '' ) {
			$where_clauses[] = 'a.featured = %d';
			$where_values[] = intval( $filters['featured'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT a.id, a.title, a.status, a.featured, a.view_count, a.created_at,
			       c.name as category_name
			FROM {$wpdb->prefix}mets_kb_articles a
			LEFT JOIN {$wpdb->prefix}mets_kb_categories c ON a.category_id = c.id
			WHERE $where_sql
			ORDER BY a.created_at DESC
			LIMIT 500
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$articles = $wpdb->get_results( $query );

		wp_send_json_success( $articles );
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
	 * AJAX handler for optimizing all database tables
	 *
	 * @since    1.0.0
	 */
	public function ajax_optimize_all_tables() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$database_optimizer = METS_Database_Optimizer::get_instance();
		$results = $database_optimizer->optimize_tables();

		wp_send_json_success( array(
			'message' => sprintf( 
				__( 'Optimized %d tables successfully.', METS_TEXT_DOMAIN ), 
				count( $results['optimized'] ) 
			),
			'results' => $results
		) );
	}

	/**
	 * AJAX handler for creating database indexes
	 *
	 * @since    1.0.0
	 */
	public function ajax_create_indexes() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$database_optimizer = METS_Database_Optimizer::get_instance();
		$success = $database_optimizer->create_optimized_indexes();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Database indexes created successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to create some indexes. Check error logs.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for warming up cache
	 *
	 * @since    1.0.0
	 */
	public function ajax_warm_cache() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->warm_up_cache();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Cache warmed up successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to warm up cache. Check error logs.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for flushing all cache
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_all_cache() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->flush_all();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'All cache flushed successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to flush cache.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for flushing specific cache group
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_cache_group() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$group = sanitize_text_field( $_POST['group'] ?? '' );
		if ( empty( $group ) ) {
			wp_send_json_error( __( 'Invalid cache group.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->flush_group( $group );

		if ( $success ) {
			wp_send_json_success( array(
				'message' => sprintf( __( 'Cache group "%s" flushed successfully.', METS_TEXT_DOMAIN ), $group )
			) );
		} else {
			wp_send_json_error( __( 'Failed to flush cache group.', METS_TEXT_DOMAIN ) );
		}
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
	 * AJAX handler to assign entities to agent
	 *
	 * @since    1.0.0
	 */
	public function ajax_assign_agent_entities() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$entity_ids = isset( $_POST['entity_ids'] ) ? array_map( 'intval', $_POST['entity_ids'] ) : array();
		
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', METS_TEXT_DOMAIN ) ) );
		}
		
		$role_manager = METS_Role_Manager::get_instance();
		$result = $role_manager->assign_entities_to_user( $user_id, $entity_ids );
		
		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Entities assigned successfully.', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to assign entities.', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler to update agent role
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_agent_role() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$new_role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
		
		if ( ! $user_id || ! $new_role ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', METS_TEXT_DOMAIN ) ) );
		}
		
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', METS_TEXT_DOMAIN ) ) );
		}
		
		// Check if current user can manage this role
		$role_manager = METS_Role_Manager::get_instance();
		$current_user_roles = wp_get_current_user()->roles;
		$can_manage = false;
		
		foreach ( $current_user_roles as $current_role ) {
			if ( $role_manager->can_manage_role( $current_role, $new_role ) ) {
				$can_manage = true;
				break;
			}
		}
		
		if ( ! $can_manage && ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot assign this role.', METS_TEXT_DOMAIN ) ) );
		}
		
		// Remove all existing METS roles
		$mets_roles = array_keys( $role_manager->get_roles() );
		foreach ( $mets_roles as $role ) {
			$user->remove_role( $role );
		}
		
		// Add new role
		$user->add_role( $new_role );
		
		wp_send_json_success( array( 'message' => __( 'Role updated successfully.', METS_TEXT_DOMAIN ) ) );
	}

	/**
	 * AJAX handler to get agent performance metrics
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_agent_performance() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'view_agent_performance' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'month';
		
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', METS_TEXT_DOMAIN ) ) );
		}
		
		$role_manager = METS_Role_Manager::get_instance();
		$performance = $role_manager->get_agent_performance( $user_id, $period );
		
		wp_send_json_success( array( 
			'performance' => $performance,
			'message' => __( 'Performance data retrieved successfully.', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * AJAX handler to request article changes
	 *
	 * @since    1.0.0
	 */
	public function ajax_request_article_changes() {
		check_ajax_referer( 'mets_kb_changes', 'nonce' );
		
		if ( ! current_user_can( 'review_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}
		
		$article_id = isset( $_POST['article_id'] ) ? intval( $_POST['article_id'] ) : 0;
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
		
		if ( ! $article_id || ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', METS_TEXT_DOMAIN ) ) );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		
		// Update article status to draft
		$result = $article_model->update( $article_id, array( 'status' => 'draft' ) );
		
		if ( $result ) {
			// Store the feedback message
			$article = $article_model->get_by_id( $article_id );
			if ( $article ) {
				update_post_meta( $article_id, 'mets_review_feedback', $message );
				update_post_meta( $article_id, 'mets_review_date', current_time( 'mysql' ) );
				update_post_meta( $article_id, 'mets_reviewer_id', get_current_user_id() );
				
				// Send notification email to author
				$author = get_userdata( $article->author_id );
				if ( $author ) {
					$subject = sprintf( __( 'Changes Requested: %s', METS_TEXT_DOMAIN ), $article->title );
					$email_message = sprintf(
						__( "Hello %s,\n\nChanges have been requested for your article '%s'.\n\nReviewer feedback:\n%s\n\nPlease make the necessary updates and resubmit for review.\n\nEdit article: %s", METS_TEXT_DOMAIN ),
						$author->display_name,
						$article->title,
						$message,
						admin_url( 'admin.php?page=mets-kb-add-article&edit=' . $article_id )
					);
					
					wp_mail( $author->user_email, $subject, $email_message );
				}
			}
			
			wp_send_json_success( array( 'message' => __( 'Feedback sent to author successfully.', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to process request.', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * Display dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard_page() {
		// Handle legacy URLs: redirect ticket actions from mets-tickets to mets-all-tickets
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'view', 'delete' ) ) ) {
			$redirect_params = $_GET;
			$redirect_params['page'] = 'mets-all-tickets';
			$redirect_url = admin_url( 'admin.php' ) . '?' . http_build_query( $redirect_params );
			wp_redirect( $redirect_url );
			exit;
		}

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
	
	/**
	 * Display AI settings page
	 *
	 * @since    1.0.0
	 */
	public function display_ai_settings_page() {
		// Include AI service class if not already loaded
		if ( ! class_exists( 'METS_AI_Service' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-service.php';
		}
		
		// Include settings page template
		require_once METS_PLUGIN_PATH . 'admin/partials/mets-admin-ai-settings.php';
	}
	
	/**
	 * Display AI Chat Widget settings page
	 *
	 * @since 1.0.0
	 */
	public function display_ai_chat_widget_settings_page() {
		// Include AI service class if not already loaded
		if ( ! class_exists( 'METS_AI_Service' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-service.php';
		}
		
		// Include language manager if available
		if ( ! class_exists( 'METS_Language_Manager' ) && file_exists( METS_PLUGIN_PATH . 'includes/class-mets-language-manager.php' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-language-manager.php';
		}
		
		// Include settings page template
		require_once METS_PLUGIN_PATH . 'admin/partials/mets-admin-ai-chat-widget-settings.php';
	}

	/**
	 * Display Performance KPIs settings page
	 *
	 * @since    1.0.0
	 */
	public function display_performance_kpis_page() {
		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['mets_kpi_settings_nonce'] ) ) {
			if ( wp_verify_nonce( $_POST['mets_kpi_settings_nonce'], 'mets_kpi_settings' ) ) {
				$this->save_performance_kpi_settings( $_POST );
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Performance KPI settings saved successfully!', METS_TEXT_DOMAIN ) . '</p></div>';
			}
		}

		// Get current settings
		$kpi_settings = get_option( 'mets_performance_kpi_settings', $this->get_default_kpi_settings() );
		
		?>
		<div class="wrap">
			<h1><i class="dashicons dashicons-chart-line"></i> <?php _e( 'Performance KPIs Configuration', METS_TEXT_DOMAIN ); ?></h1>
			<p class="description">
				<?php _e( 'Configure Key Performance Indicators (KPIs) and metrics thresholds for your team performance dashboard. These settings control what is considered excellent, good, or poor performance.', METS_TEXT_DOMAIN ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'mets_kpi_settings', 'mets_kpi_settings_nonce' ); ?>
				
				<div class="mets-kpi-settings">
					<!-- Response Time KPIs -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-clock"></i> <?php _e( 'Response Time Targets', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Define target response times for different priority levels and ticket types.', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Excellent Response Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="response_time_excellent" value="<?php echo esc_attr( $kpi_settings['response_time_excellent'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours - Green status threshold', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Good Response Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="response_time_good" value="<?php echo esc_attr( $kpi_settings['response_time_good'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours - Yellow status threshold', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Poor Response Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="response_time_poor" value="<?php echo esc_attr( $kpi_settings['response_time_poor'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours - Red status threshold (anything above this)', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<!-- Resolution Time KPIs -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-yes-alt"></i> <?php _e( 'Resolution Time Targets', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Define target resolution times for tickets.', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Excellent Resolution Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="resolution_time_excellent" value="<?php echo esc_attr( $kpi_settings['resolution_time_excellent'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Good Resolution Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="resolution_time_good" value="<?php echo esc_attr( $kpi_settings['resolution_time_good'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Poor Resolution Time', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="resolution_time_poor" value="<?php echo esc_attr( $kpi_settings['resolution_time_poor'] ); ?>" min="0" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'hours', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<!-- Agent Workload KPIs -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-groups"></i> <?php _e( 'Agent Workload Limits', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Define workload thresholds for agent performance monitoring.', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Low Workload Threshold', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="workload_low" value="<?php echo esc_attr( $kpi_settings['workload_low'] ); ?>" min="0" class="small-text" />
									<span class="description"><?php _e( 'active tickets - Green workload status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Medium Workload Threshold', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="workload_medium" value="<?php echo esc_attr( $kpi_settings['workload_medium'] ); ?>" min="0" class="small-text" />
									<span class="description"><?php _e( 'active tickets - Yellow workload status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'High Workload Threshold', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="workload_high" value="<?php echo esc_attr( $kpi_settings['workload_high'] ); ?>" min="0" class="small-text" />
									<span class="description"><?php _e( 'active tickets - Red workload status (overloaded)', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<!-- Customer Satisfaction KPIs -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-star-filled"></i> <?php _e( 'Customer Satisfaction Targets', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Define customer satisfaction rating thresholds (1-5 scale).', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Excellent Satisfaction', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="satisfaction_excellent" value="<?php echo esc_attr( $kpi_settings['satisfaction_excellent'] ); ?>" min="1" max="5" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'out of 5 - Green satisfaction status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Good Satisfaction', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="satisfaction_good" value="<?php echo esc_attr( $kpi_settings['satisfaction_good'] ); ?>" min="1" max="5" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'out of 5 - Yellow satisfaction status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Poor Satisfaction', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="satisfaction_poor" value="<?php echo esc_attr( $kpi_settings['satisfaction_poor'] ); ?>" min="1" max="5" step="0.1" class="small-text" />
									<span class="description"><?php _e( 'out of 5 - Red satisfaction status (below this)', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<!-- SLA Compliance KPIs -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-shield-alt"></i> <?php _e( 'SLA Compliance Targets', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Define SLA compliance percentage thresholds.', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Excellent SLA Compliance', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="sla_excellent" value="<?php echo esc_attr( $kpi_settings['sla_excellent'] ); ?>" min="0" max="100" class="small-text" />
									<span class="description"><?php _e( '% - Green SLA status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Good SLA Compliance', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="sla_good" value="<?php echo esc_attr( $kpi_settings['sla_good'] ); ?>" min="0" max="100" class="small-text" />
									<span class="description"><?php _e( '% - Yellow SLA status', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Poor SLA Compliance', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="sla_poor" value="<?php echo esc_attr( $kpi_settings['sla_poor'] ); ?>" min="0" max="100" class="small-text" />
									<span class="description"><?php _e( '% - Red SLA status (below this)', METS_TEXT_DOMAIN ); ?></span>
								</td>
							</tr>
						</table>
					</div>

					<!-- Dashboard Display Settings -->
					<div class="mets-settings-section">
						<h2><i class="dashicons dashicons-dashboard"></i> <?php _e( 'Dashboard Display Settings', METS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php _e( 'Configure how metrics are displayed on the performance dashboard.', METS_TEXT_DOMAIN ); ?></p>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Default Time Period', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="default_time_period">
										<option value="today" <?php selected( $kpi_settings['default_time_period'], 'today' ); ?>><?php _e( 'Today', METS_TEXT_DOMAIN ); ?></option>
										<option value="yesterday" <?php selected( $kpi_settings['default_time_period'], 'yesterday' ); ?>><?php _e( 'Yesterday', METS_TEXT_DOMAIN ); ?></option>
										<option value="this_week" <?php selected( $kpi_settings['default_time_period'], 'this_week' ); ?>><?php _e( 'This Week', METS_TEXT_DOMAIN ); ?></option>
										<option value="last_week" <?php selected( $kpi_settings['default_time_period'], 'last_week' ); ?>><?php _e( 'Last Week', METS_TEXT_DOMAIN ); ?></option>
										<option value="this_month" <?php selected( $kpi_settings['default_time_period'], 'this_month' ); ?>><?php _e( 'This Month', METS_TEXT_DOMAIN ); ?></option>
										<option value="last_month" <?php selected( $kpi_settings['default_time_period'], 'last_month' ); ?>><?php _e( 'Last Month', METS_TEXT_DOMAIN ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Show Trend Indicators', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="show_trends" value="1" <?php checked( $kpi_settings['show_trends'], 1 ); ?> />
										<?php _e( 'Display trend arrows and percentage changes', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Auto Refresh Interval', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="auto_refresh_interval">
										<option value="0" <?php selected( $kpi_settings['auto_refresh_interval'], '0' ); ?>><?php _e( 'Disabled', METS_TEXT_DOMAIN ); ?></option>
										<option value="30" <?php selected( $kpi_settings['auto_refresh_interval'], '30' ); ?>><?php _e( '30 seconds', METS_TEXT_DOMAIN ); ?></option>
										<option value="60" <?php selected( $kpi_settings['auto_refresh_interval'], '60' ); ?>><?php _e( '1 minute', METS_TEXT_DOMAIN ); ?></option>
										<option value="300" <?php selected( $kpi_settings['auto_refresh_interval'], '300' ); ?>><?php _e( '5 minutes', METS_TEXT_DOMAIN ); ?></option>
										<option value="600" <?php selected( $kpi_settings['auto_refresh_interval'], '600' ); ?>><?php _e( '10 minutes', METS_TEXT_DOMAIN ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php submit_button( __( 'Save KPI Settings', METS_TEXT_DOMAIN ), 'primary', 'submit', true, array( 'style' => 'margin-top: 20px;' ) ); ?>
			</form>
		</div>

		<style>
		.mets-kpi-settings {
			max-width: 1000px;
		}
		.mets-settings-section {
			background: #fff;
			margin: 20px 0;
			padding: 20px;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			box-shadow: 0 1px 1px rgba(0,0,0,0.04);
		}
		.mets-settings-section h2 {
			margin-top: 0;
			margin-bottom: 10px;
			color: #1d2327;
			border-bottom: 1px solid #e1e1e1;
			padding-bottom: 10px;
		}
		.mets-settings-section .dashicons {
			color: #2271b1;
			margin-right: 5px;
		}
		.mets-settings-section .description {
			color: #646970;
			font-style: italic;
		}
		.form-table th {
			font-weight: 600;
			color: #1d2327;
		}
		.form-table .description {
			margin-left: 10px;
			color: #646970;
		}
		</style>
		<?php
	}
	
	/**
	 * Sanitize AI features array
	 *
	 * @since    1.0.0
	 */
	public function sanitize_ai_features( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		
		$valid_features = array(
			'auto_categorize',
			'priority_prediction',
			'sentiment_analysis',
			'suggested_responses',
			'smart_routing',
			'auto_tagging'
		);
		
		return array_intersect( $input, $valid_features );
	}
	
	/**
	 * AJAX handler for clearing models cache
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_models_cache() {
		check_ajax_referer( 'mets_clear_models_cache', 'nonce' );
		
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'multi-entity-ticket-system' ) );
		}
		
		// Include AI service class if not already loaded
		if ( ! class_exists( 'METS_AI_Service' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-ai-service.php';
		}
		
		$ai_service = METS_AI_Service::get_instance();
		$ai_service->clear_models_cache();
		
		wp_send_json_success( array( 'message' => __( 'Models cache cleared successfully', 'multi-entity-ticket-system' ) ) );
	}

	/**
	 * Display real-time settings page
	 */
	// Removed - WebSocket features deleted
	// public function display_realtime_settings() {
	//	require_once METS_PLUGIN_PATH . 'admin/partials/mets-admin-realtime-settings.php';
	// }


	// WebSocket features have been completely removed from this plugin

	/**
	 * AJAX handler to get entities for mobile form
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_entities_for_mobile() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'mets_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		// Load entity model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		// Get user accessible entities
		$user_entities = $this->get_user_entity_access();
		
		$args = array( 
			'status' => 'active',
			'parent_id' => 'all'
		);

		// Filter by user access if needed
		if ( ! empty( $user_entities ) ) {
			$args['entity_ids'] = $user_entities;
		}

		$entities = $entity_model->get_all( $args );

		// Format entities for mobile display
		$formatted_entities = array();
		foreach ( $entities as $entity ) {
			$formatted_entities[] = array(
				'id' => $entity->id,
				'name' => $entity->name,
				'description' => $entity->description,
				'parent_id' => $entity->parent_id,
				'slug' => $entity->slug
			);
		}

		wp_send_json_success( $formatted_entities );
	}

	/**
	 * AJAX handler for dashboard refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_dashboard() {
		// Load manager dashboard class if not already loaded
		if ( ! class_exists( 'METS_Manager_Dashboard' ) ) {
			require_once METS_PLUGIN_PATH . 'admin/class-mets-manager-dashboard.php';
		}

		$manager_dashboard = new METS_Manager_Dashboard();
		$manager_dashboard->ajax_refresh_dashboard();
	}

	/**
	 * Get user entity access (shared method)
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

		return ! empty( $entity_ids ) ? array_map( 'intval', $entity_ids ) : array( 0 );
	}

	/**
	 * Save Performance KPI settings
	 *
	 * @since    1.0.0
	 * @param    array    $post_data    Form submission data
	 */
	private function save_performance_kpi_settings( $post_data ) {
		$settings = array(
			// Response Time KPIs
			'response_time_excellent' => floatval( $post_data['response_time_excellent'] ?? 1.0 ),
			'response_time_good' => floatval( $post_data['response_time_good'] ?? 4.0 ),
			'response_time_poor' => floatval( $post_data['response_time_poor'] ?? 8.0 ),
			
			// Resolution Time KPIs
			'resolution_time_excellent' => floatval( $post_data['resolution_time_excellent'] ?? 24.0 ),
			'resolution_time_good' => floatval( $post_data['resolution_time_good'] ?? 48.0 ),
			'resolution_time_poor' => floatval( $post_data['resolution_time_poor'] ?? 72.0 ),
			
			// Agent Workload KPIs
			'workload_low' => intval( $post_data['workload_low'] ?? 10 ),
			'workload_medium' => intval( $post_data['workload_medium'] ?? 20 ),
			'workload_high' => intval( $post_data['workload_high'] ?? 30 ),
			
			// Customer Satisfaction KPIs
			'satisfaction_excellent' => floatval( $post_data['satisfaction_excellent'] ?? 4.5 ),
			'satisfaction_good' => floatval( $post_data['satisfaction_good'] ?? 3.5 ),
			'satisfaction_poor' => floatval( $post_data['satisfaction_poor'] ?? 2.5 ),
			
			// SLA Compliance KPIs
			'sla_excellent' => intval( $post_data['sla_excellent'] ?? 95 ),
			'sla_good' => intval( $post_data['sla_good'] ?? 85 ),
			'sla_poor' => intval( $post_data['sla_poor'] ?? 75 ),
			
			// Dashboard Display Settings
			'default_time_period' => sanitize_text_field( $post_data['default_time_period'] ?? 'this_week' ),
			'show_trends' => intval( $post_data['show_trends'] ?? 1 ),
			'auto_refresh_interval' => intval( $post_data['auto_refresh_interval'] ?? 300 ),
		);

		// Validate ranges
		$settings['response_time_excellent'] = max( 0.1, $settings['response_time_excellent'] );
		$settings['response_time_good'] = max( $settings['response_time_excellent'], $settings['response_time_good'] );
		$settings['response_time_poor'] = max( $settings['response_time_good'], $settings['response_time_poor'] );
		
		$settings['resolution_time_excellent'] = max( 0.1, $settings['resolution_time_excellent'] );
		$settings['resolution_time_good'] = max( $settings['resolution_time_excellent'], $settings['resolution_time_good'] );
		$settings['resolution_time_poor'] = max( $settings['resolution_time_good'], $settings['resolution_time_poor'] );
		
		$settings['workload_low'] = max( 1, $settings['workload_low'] );
		$settings['workload_medium'] = max( $settings['workload_low'], $settings['workload_medium'] );
		$settings['workload_high'] = max( $settings['workload_medium'], $settings['workload_high'] );
		
		$settings['satisfaction_excellent'] = max( 1.0, min( 5.0, $settings['satisfaction_excellent'] ) );
		$settings['satisfaction_good'] = max( 1.0, min( $settings['satisfaction_excellent'], $settings['satisfaction_good'] ) );
		$settings['satisfaction_poor'] = max( 1.0, min( $settings['satisfaction_good'], $settings['satisfaction_poor'] ) );
		
		$settings['sla_excellent'] = max( 0, min( 100, $settings['sla_excellent'] ) );
		$settings['sla_good'] = max( 0, min( $settings['sla_excellent'], $settings['sla_good'] ) );
		$settings['sla_poor'] = max( 0, min( $settings['sla_good'], $settings['sla_poor'] ) );

		update_option( 'mets_performance_kpi_settings', $settings );
	}

	/**
	 * Get default KPI settings
	 *
	 * @since    1.0.0
	 * @return   array    Default KPI settings
	 */
	private function get_default_kpi_settings() {
		return array(
			// Response Time KPIs (in hours)
			'response_time_excellent' => 1.0,  // Green: ≤ 1 hour
			'response_time_good' => 4.0,       // Yellow: 1-4 hours
			'response_time_poor' => 8.0,       // Red: > 8 hours
			
			// Resolution Time KPIs (in hours)
			'resolution_time_excellent' => 24.0,  // Green: ≤ 24 hours
			'resolution_time_good' => 48.0,       // Yellow: 24-48 hours
			'resolution_time_poor' => 72.0,       // Red: > 72 hours
			
			// Agent Workload KPIs (number of active tickets)
			'workload_low' => 10,     // Green: ≤ 10 tickets
			'workload_medium' => 20,  // Yellow: 10-20 tickets
			'workload_high' => 30,    // Red: > 30 tickets
			
			// Customer Satisfaction KPIs (1-5 scale)
			'satisfaction_excellent' => 4.5,  // Green: ≥ 4.5
			'satisfaction_good' => 3.5,       // Yellow: 3.5-4.5
			'satisfaction_poor' => 2.5,       // Red: < 2.5
			
			// SLA Compliance KPIs (percentage)
			'sla_excellent' => 95,  // Green: ≥ 95%
			'sla_good' => 85,       // Yellow: 85-95%
			'sla_poor' => 75,       // Red: < 75%
			
			// Dashboard Display Settings
			'default_time_period' => 'this_week',
			'show_trends' => 1,
			'auto_refresh_interval' => 300, // 5 minutes
		);
	}

	/**
	 * Display Email Templates settings page
	 *
	 * @since    1.0.0
	 */
	public function display_email_templates_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Load template manager
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-template-manager.php';
		$template_manager = new METS_Email_Template_Manager();

		// Handle actions
		$action = $_REQUEST['action'] ?? 'list';
		$template_id = intval( $_REQUEST['template_id'] ?? 0 );

		switch ( $action ) {
			case 'edit':
				$this->display_email_template_editor( $template_manager, $template_id );
				break;
			case 'preview':
				$this->display_email_template_preview( $template_manager, $template_id );
				break;
			case 'delete':
				$this->handle_email_template_delete( $template_manager, $template_id );
				$this->display_email_templates_list( $template_manager );
				break;
			case 'migrate':
				$this->handle_email_template_migration( $template_manager );
				$this->display_email_templates_list( $template_manager );
				break;
			default:
				$this->display_email_templates_list( $template_manager );
				break;
		}
	}

	/**
	 * Display email templates list
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 */
	private function display_email_templates_list( $template_manager ) {
		$templates = $template_manager->get_templates();
		$default_templates = $template_manager->get_default_templates();
		
		?>
		<div class="wrap">
			<h1>
				<i class="dashicons dashicons-email-alt"></i> 
				<?php _e( 'Email Templates', METS_TEXT_DOMAIN ); ?>
				<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=edit&template_id=0' ); ?>" class="page-title-action">
					<?php _e( 'Add New Template', METS_TEXT_DOMAIN ); ?>
				</a>
			</h1>
			
			<p class="description">
				<?php _e( 'Manage email templates used for ticket notifications, SLA alerts, and customer communications. Customize the look and content of all automated emails sent by the system.', METS_TEXT_DOMAIN ); ?>
			</p>

			<?php if ( empty( $templates ) ) : ?>
				<div class="notice notice-info">
					<p>
						<?php _e( 'No email templates found. You can migrate existing PHP templates or create new ones.', METS_TEXT_DOMAIN ); ?>
						<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=migrate' ); ?>" class="button button-secondary">
							<?php _e( 'Migrate Existing Templates', METS_TEXT_DOMAIN ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div class="mets-email-templates">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php _e( 'Template Name', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Type', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Last Modified', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Version', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $templates ) ) : ?>
							<?php foreach ( $templates as $template ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $default_templates[ $template->template_name ]['name'] ?? $template->template_name ); ?></strong>
										<?php if ( $template->is_default ) : ?>
											<span class="dashicons dashicons-star-filled" title="<?php _e( 'Default Template', METS_TEXT_DOMAIN ); ?>"></span>
										<?php endif; ?>
									</td>
									<td>
										<?php 
										$template_types = $template_manager->get_template_types();
										echo esc_html( $template_types[ $template->template_type ] ?? $template->template_type );
										?>
									</td>
									<td>
										<?php if ( $template->is_active ) : ?>
											<span class="mets-status-active">✓ <?php _e( 'Active', METS_TEXT_DOMAIN ); ?></span>
										<?php else : ?>
											<span class="mets-status-inactive">○ <?php _e( 'Inactive', METS_TEXT_DOMAIN ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( human_time_diff( strtotime( $template->updated_at ) ) . ' ' . __( 'ago', METS_TEXT_DOMAIN ) ); ?></td>
									<td><?php echo esc_html( $template->version ); ?></td>
									<td>
										<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=edit&template_id=' . $template->id ); ?>" class="button button-small">
											<?php _e( 'Edit', METS_TEXT_DOMAIN ); ?>
										</a>
										<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=preview&template_id=' . $template->id ); ?>" class="button button-small" target="_blank">
											<?php _e( 'Preview', METS_TEXT_DOMAIN ); ?>
										</a>
										<?php if ( ! $template->is_default ) : ?>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-email-templates&action=delete&template_id=' . $template->id ), 'delete_template_' . $template->id ); ?>" 
											   class="button button-small button-link-delete" 
											   onclick="return confirm('<?php _e( 'Are you sure you want to delete this template?', METS_TEXT_DOMAIN ); ?>')">
												<?php _e( 'Delete', METS_TEXT_DOMAIN ); ?>
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" style="text-align: center; padding: 40px;">
									<div class="mets-empty-state">
										<i class="dashicons dashicons-email-alt" style="font-size: 48px; color: #c3c4c7; margin-bottom: 16px;"></i>
										<h3><?php _e( 'No Email Templates Found', METS_TEXT_DOMAIN ); ?></h3>
										<p><?php _e( 'Get started by creating your first email template or migrating existing ones.', METS_TEXT_DOMAIN ); ?></p>
										<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=edit&template_id=0' ); ?>" class="button button-primary">
											<?php _e( 'Create First Template', METS_TEXT_DOMAIN ); ?>
										</a>
										<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=migrate' ); ?>" class="button button-secondary">
											<?php _e( 'Migrate Existing Templates', METS_TEXT_DOMAIN ); ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="mets-template-info" style="margin-top: 30px;">
				<h3><?php _e( 'Available Template Types', METS_TEXT_DOMAIN ); ?></h3>
				<div class="mets-template-types-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 15px;">
					<?php foreach ( $default_templates as $key => $info ) : ?>
						<div class="mets-template-type-card" style="background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">
							<h4 style="margin-top: 0;"><?php echo esc_html( $info['name'] ); ?></h4>
							<p style="margin-bottom: 0; color: #646970;"><?php echo esc_html( $info['description'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<style>
		.mets-status-active { color: #46b450; font-weight: 600; }
		.mets-status-inactive { color: #dc3232; font-weight: 600; }
		.mets-empty-state { text-align: center; }
		.mets-empty-state .dashicons { display: block; margin: 0 auto 16px; }
		.mets-empty-state h3 { margin-bottom: 8px; }
		.mets-empty-state p { color: #646970; margin-bottom: 20px; }
		.mets-email-templates .dashicons-star-filled { color: #f0b849; margin-left: 5px; }
		</style>
		<?php
	}

	/**
	 * Display email template editor
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 * @param    int                           $template_id
	 */
	private function display_email_template_editor( $template_manager, $template_id ) {
		$template = null;
		$is_new = $template_id === 0;

		if ( ! $is_new ) {
			$template = $template_manager->get_template( $template_id );
			if ( ! $template ) {
				wp_die( __( 'Template not found.', METS_TEXT_DOMAIN ) );
			}
		}

		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['mets_template_nonce'] ) ) {
			if ( wp_verify_nonce( $_POST['mets_template_nonce'], 'mets_save_template' ) ) {
				$result = $this->handle_template_save( $template_manager, $template_id, $_POST );
				
				if ( is_wp_error( $result ) ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
				} else {
					echo '<div class="notice notice-success"><p>' . __( 'Template saved successfully!', METS_TEXT_DOMAIN ) . '</p></div>';
					if ( $is_new ) {
						$template_id = $result;
						$template = $template_manager->get_template( $template_id );
						$is_new = false;
					}
				}
			}
		}

		$available_variables = $template_manager->get_available_variables();
		$template_types = $template_manager->get_template_types();
		$default_templates = $template_manager->get_default_templates();

		?>
		<div class="wrap">
			<h1>
				<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates' ); ?>" class="button button-secondary" style="margin-right: 10px;">
					← <?php _e( 'Back to Templates', METS_TEXT_DOMAIN ); ?>
				</a>
				<?php echo $is_new ? __( 'Add New Email Template', METS_TEXT_DOMAIN ) : __( 'Edit Email Template', METS_TEXT_DOMAIN ); ?>
			</h1>

			<div class="mets-template-editor" style="display: flex; gap: 30px; margin-top: 20px;">
				<div class="mets-template-form" style="flex: 2;">
					<form method="post" action="">
						<?php wp_nonce_field( 'mets_save_template', 'mets_template_nonce' ); ?>
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><?php _e( 'Template Name', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></th>
								<td>
									<input type="text" name="template_name" value="<?php echo esc_attr( $template->template_name ?? '' ); ?>" 
										   class="regular-text" required <?php echo ! $is_new ? 'readonly' : ''; ?>>
									<?php if ( ! $is_new ) : ?>
										<p class="description"><?php _e( 'Template name cannot be changed after creation.', METS_TEXT_DOMAIN ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Template Type', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="template_type">
										<?php foreach ( $template_types as $type_key => $type_name ) : ?>
											<option value="<?php echo esc_attr( $type_key ); ?>" 
													<?php selected( $template->template_type ?? 'custom', $type_key ); ?>>
												<?php echo esc_html( $type_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Subject Line', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></th>
								<td>
									<input type="text" name="subject_line" value="<?php echo esc_attr( $template->subject_line ?? '' ); ?>" 
										   class="large-text" required>
									<p class="description"><?php _e( 'Use template variables like {{ticket_number}} and {{customer_name}}.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Email Content', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></th>
								<td>
									<?php
									wp_editor( 
										$template->content ?? '', 
										'template_content',
										array(
											'media_buttons' => false,
											'textarea_name' => 'content',
											'textarea_rows' => 20,
											'teeny' => false,
											'tinymce' => array(
												'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
												'toolbar2' => 'alignleft,aligncenter,alignright,alignjustify,|,forecolor,backcolor,|,removeformat,code,|,fullscreen',
											),
											'quicktags' => array(
												'buttons' => 'strong,em,ul,ol,li,link,code'
											)
										)
									);
									?>
									<p class="description"><?php _e( 'Design your email template using the editor above. Use HTML for advanced formatting.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="is_active" value="1" <?php checked( $template->is_active ?? 1, 1 ); ?>>
										<?php _e( 'Active (template will be used for sending emails)', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>
						</table>

						<div class="mets-template-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #c3c4c7;">
							<input type="submit" name="save_template" class="button button-primary" value="<?php _e( 'Save Template', METS_TEXT_DOMAIN ); ?>">
							<?php if ( ! $is_new ) : ?>
							<button type="button" class="button button-secondary" onclick="metsPreviewTemplate(<?php echo $template_id; ?>)">
								<?php _e( 'Preview', METS_TEXT_DOMAIN ); ?>
							</button>
							<?php endif; ?>
							<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates' ); ?>" class="button button-secondary">
								<?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?>
							</a>
						</div>
					</form>
				</div>

				<div class="mets-template-sidebar" style="flex: 1; background: #f9f9f9; padding: 20px; border-radius: 4px;">
					<h3><?php _e( '🎯 Available Variables', METS_TEXT_DOMAIN ); ?></h3>
					<p class="description"><?php _e( 'Click any variable below to insert it into the editor:', METS_TEXT_DOMAIN ); ?></p>
					
					<div class="mets-variables-list" style="max-height: 400px; overflow-y: auto;">
						<?php foreach ( $available_variables as $var_key => $var_desc ) : ?>
							<div class="mets-variable-item" style="margin-bottom: 8px;">
								<button type="button" class="button button-small" onclick="metsInsertVariable('{{<?php echo esc_js( $var_key ); ?>}}')">
									{{<?php echo esc_html( $var_key ); ?>}}
								</button>
								<p style="margin: 2px 0 0 0; font-size: 11px; color: #646970;"><?php echo esc_html( $var_desc ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>

					<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
						<h4><?php _e( '💡 Tips', METS_TEXT_DOMAIN ); ?></h4>
						<ul style="font-size: 12px; color: #646970;">
							<li><?php _e( 'Variables are case-sensitive', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Test your templates before making them active', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Keep subject lines under 50 characters', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Always provide a plain text fallback', METS_TEXT_DOMAIN ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<script>
		function metsInsertVariable(variable) {
			// Try to insert into TinyMCE editor
			if (typeof tinymce !== 'undefined' && tinymce.get('template_content')) {
				tinymce.get('template_content').execCommand('mceInsertContent', false, variable);
			} else {
				// Fallback to textarea
				const textarea = document.querySelector('textarea[name="content"]');
				if (textarea) {
					const cursorPos = textarea.selectionStart;
					const textBefore = textarea.value.substring(0, cursorPos);
					const textAfter = textarea.value.substring(cursorPos);
					textarea.value = textBefore + variable + textAfter;
					textarea.focus();
					textarea.setSelectionRange(cursorPos + variable.length, cursorPos + variable.length);
				}
			}
		}

		function metsPreviewTemplate(templateId) {
			const previewUrl = '<?php echo admin_url( "admin.php?page=mets-email-templates&action=preview&template_id=" ); ?>' + templateId;
			window.open(previewUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
		}
		</script>

		<style>
		.required { color: #dc3232; }
		.mets-variable-item button { 
			font-family: monospace; 
			font-size: 11px; 
			margin-bottom: 4px;
			background: #fff;
			border: 1px solid #c3c4c7;
		}
		.mets-variable-item button:hover {
			background: #f0f0f1;
			border-color: #8c8f94;
		}
		.mets-variables-list {
			border: 1px solid #ddd;
			padding: 15px;
			background: #fff;
			border-radius: 4px;
		}
		</style>
		<?php
	}

	/**
	 * Display email template preview
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 * @param    int                           $template_id
	 */
	private function display_email_template_preview( $template_manager, $template_id ) {
		// Additional capability check
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_die( __( 'You do not have sufficient permissions to preview templates.', METS_TEXT_DOMAIN ) );
		}

		// Validate template ID
		if ( $template_id <= 0 ) {
			wp_die( __( 'Invalid template ID.', METS_TEXT_DOMAIN ) );
		}

		$template = $template_manager->get_template( $template_id );
		
		if ( ! $template ) {
			wp_die( __( 'Template not found.', METS_TEXT_DOMAIN ) );
		}

		// Generate sample data for preview
		$sample_data = $this->get_sample_template_data();
		
		// Process template variables
		$preview_content = $this->process_template_variables( $template->content, $sample_data );
		$preview_subject = $this->process_template_variables( $template->subject_line, $sample_data );
		
		// Output preview without admin UI
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( $preview_subject ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					line-height: 1.6;
					color: #333;
					margin: 0;
					padding: 20px;
					background: #f0f0f1;
				}
				.preview-container {
					max-width: 800px;
					margin: 0 auto;
					background: #fff;
					border-radius: 8px;
					overflow: hidden;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				}
				.preview-header {
					background: #0073aa;
					color: #fff;
					padding: 20px;
					border-bottom: 1px solid #005a87;
				}
				.preview-header h1 {
					margin: 0;
					font-size: 18px;
					font-weight: 600;
				}
				.preview-meta {
					background: #f6f7f7;
					padding: 15px 20px;
					border-bottom: 1px solid #ddd;
					font-size: 14px;
				}
				.preview-content {
					padding: 0;
				}
				.preview-actions {
					padding: 15px 20px;
					background: #f6f7f7;
					border-top: 1px solid #ddd;
					text-align: center;
				}
				.button {
					display: inline-block;
					padding: 8px 16px;
					margin: 0 5px;
					background: #0073aa;
					color: #fff;
					text-decoration: none;
					border-radius: 4px;
					font-size: 13px;
				}
				.button:hover {
					background: #005a87;
					color: #fff;
				}
				.button-secondary {
					background: #fff;
					color: #2271b1;
					border: 1px solid #2271b1;
				}
				.button-secondary:hover {
					background: #f6f7f7;
				}
			</style>
		</head>
		<body>
			<div class="preview-container">
				<div class="preview-header">
					<h1><?php _e( 'Email Template Preview', METS_TEXT_DOMAIN ); ?></h1>
				</div>
				
				<div class="preview-meta">
					<strong><?php _e( 'Template:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $template->template_name ); ?><br>
					<strong><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $preview_subject ); ?><br>
					<strong><?php _e( 'Type:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $template->template_type ); ?>
				</div>
				
				<div class="preview-content">
					<?php echo wp_kses( $preview_content, wp_kses_allowed_html( 'post' ) ); ?>
				</div>
				
				<div class="preview-actions">
					<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates&action=edit&template_id=' . $template_id ); ?>" class="button">
						<?php _e( 'Edit Template', METS_TEXT_DOMAIN ); ?>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=mets-email-templates' ); ?>" class="button button-secondary">
						<?php _e( 'Back to Templates', METS_TEXT_DOMAIN ); ?>
					</a>
					<a href="javascript:window.close();" class="button button-secondary">
						<?php _e( 'Close Preview', METS_TEXT_DOMAIN ); ?>
					</a>
				</div>
			</div>
		</body>
		</html>
		<?php
		exit; // Stop WordPress from loading the rest of the admin
	}

	/**
	 * Get sample data for template preview
	 *
	 * @since    1.0.0
	 * @return   array    Sample template data
	 */
	private function get_sample_template_data() {
		return array(
			'ticket_number' => '#12345',
			'ticket_number_safe' => '#12345',
			'ticket_subject' => 'Sample support request about billing issue',
			'ticket_subject_safe' => 'Sample support request about billing issue',
			'ticket_content' => 'Hello, I have a question about my recent invoice. Could you please help me understand the charges?',
			'ticket_content_html' => '<p>Hello, I have a question about my recent invoice. Could you please help me understand the charges?</p>',
			'ticket_url' => home_url( '/ticket/12345' ),
			'ticket_url_safe' => home_url( '/ticket/12345' ),
			'admin_ticket_url' => admin_url( 'admin.php?page=mets-tickets&action=view&id=12345' ),
			'admin_ticket_url_safe' => admin_url( 'admin.php?page=mets-tickets&action=view&id=12345' ),
			'customer_name' => 'John Smith',
			'customer_name_safe' => 'John Smith',
			'customer_email' => 'john.smith@example.com',
			'customer_email_safe' => 'john.smith@example.com',
			'customer_phone' => '+1 (555) 123-4567',
			'agent_name' => 'Sarah Johnson',
			'agent_name_safe' => 'Sarah Johnson',
			'agent_email' => 'sarah.johnson@company.com',
			'entity_name' => 'ACME Corporation',
			'entity_name_safe' => 'ACME Corporation',
			'entity_email' => 'support@acme.com',
			'portal_url' => home_url( '/support' ),
			'ticket_status' => 'Open',
			'status' => 'Open',
			'status_safe' => 'Open',
			'priority' => 'High',
			'priority_safe' => 'High',
			'category' => 'Billing',
			'category_safe' => 'Billing',
			'created_date' => date( 'F j, Y \a\t g:i A' ),
			'created_date_safe' => date( 'F j, Y \a\t g:i A' ),
			'updated_date' => date( 'F j, Y \a\t g:i A' ),
			'reply_content' => 'Thank you for contacting us. I will look into your billing question right away.',
			'reply_content_html' => '<p>Thank you for contacting us. I will look into your billing question right away.</p>',
			'reply_date' => date( 'F j, Y \a\t g:i A' ),
			'reply_date_safe' => date( 'F j, Y \a\t g:i A' ),
			'due_date' => date( 'F j, Y \a\t g:i A', strtotime( '+2 days' ) ),
			'response_due' => date( 'F j, Y \a\t g:i A', strtotime( '+4 hours' ) ),
			'resolution_due' => date( 'F j, Y \a\t g:i A', strtotime( '+24 hours' ) ),
			'escalation_due' => date( 'F j, Y \a\t g:i A', strtotime( '+12 hours' ) ),
			'sla_type' => 'High Priority SLA',
			'sla_type_safe' => 'High Priority SLA',
			'time_remaining' => '3 hours 45 minutes',
			'time_remaining_safe' => '3 hours 45 minutes',
			'site_name' => get_bloginfo( 'name' ),
			'site_name_safe' => get_bloginfo( 'name' ),
			'site_url' => home_url(),
			'site_url_safe' => home_url(),
		);
	}

	/**
	 * Process template variables in content
	 *
	 * @since    1.0.0
	 * @param    string    $content    Template content
	 * @param    array     $data       Variable data
	 * @return   string                Processed content
	 */
	private function process_template_variables( $content, $data ) {
		// Replace template variables safely
		foreach ( $data as $key => $value ) {
			// Ensure the value is properly escaped for HTML context
			$escaped_value = esc_html( $value );
			$content = str_replace( '{{' . $key . '}}', $escaped_value, $content );
		}
		
		// Process WordPress translations safely
		$content = $this->process_wordpress_translations( $content );
		
		return $content;
	}

	/**
	 * Process WordPress translation functions safely
	 *
	 * @since    1.0.0
	 * @param    string    $content    Template content
	 * @return   string                Processed content
	 */
	private function process_wordpress_translations( $content ) {
		// Handle common WordPress translation functions
		$patterns = array(
			'/\<\?php\s+_e\(\s*[\'"]([^\'"]*)[\'"][^)]*\)\s*;\s*\?\>/' => '$1',
			'/\<\?php\s+__\(\s*[\'"]([^\'"]*)[\'"][^)]*\)\s*;\s*\?\>/' => '$1',
			'/\<\?php\s+esc_html_e\(\s*[\'"]([^\'"]*)[\'"][^)]*\)\s*;\s*\?\>/' => '$1',
			'/\<\?php\s+esc_html__\(\s*[\'"]([^\'"]*)[\'"][^)]*\)\s*;\s*\?\>/' => '$1',
		);
		
		foreach ( $patterns as $pattern => $replacement ) {
			$content = preg_replace( $pattern, $replacement, $content );
		}
		
		// Remove any remaining PHP tags for security
		$content = preg_replace( '/\<\?php.*?\?\>/', '', $content );
		
		return $content;
	}

	/**
	 * Handle template save
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 * @param    int                           $template_id
	 * @param    array                         $post_data
	 * @return   int|WP_Error                  Template ID or error
	 */
	private function handle_template_save( $template_manager, $template_id, $post_data ) {
		$data = array(
			'template_name' => sanitize_text_field( $post_data['template_name'] ),
			'template_type' => sanitize_text_field( $post_data['template_type'] ),
			'subject_line' => sanitize_text_field( $post_data['subject_line'] ),
			'content' => wp_kses_post( $post_data['content'] ),
			'is_active' => isset( $post_data['is_active'] ) ? 1 : 0,
		);

		if ( $template_id === 0 ) {
			return $template_manager->create_template( $data );
		} else {
			$result = $template_manager->update_template( $template_id, $data );
			return is_wp_error( $result ) ? $result : $template_id;
		}
	}

	/**
	 * Handle template deletion
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 * @param    int                           $template_id
	 */
	private function handle_email_template_delete( $template_manager, $template_id ) {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'delete_template_' . $template_id ) ) {
			wp_die( __( 'Security check failed.', METS_TEXT_DOMAIN ) );
		}

		$result = $template_manager->delete_template( $template_id );
		
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>' . __( 'Template deleted successfully.', METS_TEXT_DOMAIN ) . '</p></div>';
		}
	}

	/**
	 * Handle template migration
	 *
	 * @since    1.0.0
	 * @param    METS_Email_Template_Manager    $template_manager
	 */
	private function handle_email_template_migration( $template_manager ) {
		$results = $template_manager->migrate_existing_templates();
		
		if ( ! empty( $results['success'] ) ) {
			echo '<div class="notice notice-success"><p>' . 
				 sprintf( __( 'Successfully migrated %d templates: %s', METS_TEXT_DOMAIN ), 
					 count( $results['success'] ), 
					 implode( ', ', array_keys( $results['success'] ) ) ) . 
				 '</p></div>';
		}
		
		if ( ! empty( $results['errors'] ) ) {
			echo '<div class="notice notice-error"><p>' . 
				 sprintf( __( 'Migration errors: %s', METS_TEXT_DOMAIN ), 
					 implode( ', ', $results['errors'] ) ) . 
				 '</p></div>';
		}
		
		if ( empty( $results['success'] ) && empty( $results['errors'] ) ) {
			echo '<div class="notice notice-info"><p>' . 
				 __( 'No templates were migrated. All existing templates may already be in the database.', METS_TEXT_DOMAIN ) . 
				 '</p></div>';
		}
	}
}
