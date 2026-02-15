<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Core {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Core    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Whether hooks have been registered
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool    $hooks_registered    Hooks registration status.
	 */
	private static $hooks_registered = false;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      METS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Core    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'METS_VERSION' ) ) {
			$this->version = METS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'multi-entity-ticket-system';

		$this->load_dependencies();
		$this->set_locale();
		
		// Only register hooks once
		if ( ! self::$hooks_registered ) {
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->define_sla_hooks();
			$this->define_role_management_hooks();
			$this->initialize_email_system();
			$this->initialize_rest_api();
			$this->initialize_woocommerce_integration();
			$this->initialize_bulk_operations();
			// Temporarily disable advanced features for dashboard access
			// $this->initialize_advanced_automation();
			// $this->initialize_performance_optimization();
			// $this->initialize_security_system();
			// $this->initialize_csp_handler();
			self::$hooks_registered = true;
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - METS_Loader. Orchestrates the hooks of the plugin.
	 * - METS_I18n. Defines internationalization functionality.
	 * - METS_Admin. Defines all hooks for the admin area.
	 * - METS_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once METS_PLUGIN_PATH . 'admin/class-mets-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once METS_PLUGIN_PATH . 'public/class-mets-public.php';

		/**
		 * SLA and automation classes
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-calculator.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-template-engine.php';

		/**
		 * SMTP classes
		 */
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-providers.php';
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-manager.php';
		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-mailer.php';

		/**
		 * Email queue system
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-smtp-logger.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-queue.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-notifications.php';

		/**
		 * Performance optimization system
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-cache-manager.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-performance-optimizer.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-database-optimizer.php';

		/**
		 * Security system
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-security-manager.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-security-audit.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-security-database.php';

		/**
		 * CSP Handler for n8n chat integration
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-csp-handler.php';

		/**
		 * Role and Agent Management system
		 */
		require_once METS_PLUGIN_PATH . 'includes/class-mets-role-manager.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-agent-profile.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-agent-status-widget.php';

		$this->loader = new METS_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the METS_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new METS_I18n();
		$plugin_i18n->load_plugin_textdomain();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new METS_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'admin_init' );

		// AJAX hooks for admin functionality
		$this->loader->add_action( 'wp_ajax_mets_search_entities', $plugin_admin, 'ajax_search_entities' );
		$this->loader->add_action( 'wp_ajax_mets_get_entity_agents', $plugin_admin, 'ajax_get_entity_agents' );
		$this->loader->add_action( 'wp_ajax_mets_assign_ticket', $plugin_admin, 'ajax_assign_ticket' );
		$this->loader->add_action( 'wp_ajax_mets_change_ticket_status', $plugin_admin, 'ajax_change_ticket_status' );
		$this->loader->add_action( 'wp_ajax_mets_check_workflow_transition', $plugin_admin, 'ajax_check_workflow_transition' );
		$this->loader->add_action( 'wp_ajax_mets_get_allowed_transitions', $plugin_admin, 'ajax_get_allowed_transitions' );

		// Agent Management AJAX hooks
		$this->loader->add_action( 'wp_ajax_mets_assign_agent_entities', $plugin_admin, 'ajax_assign_agent_entities' );
		$this->loader->add_action( 'wp_ajax_mets_update_agent_role', $plugin_admin, 'ajax_update_agent_role' );
		$this->loader->add_action( 'wp_ajax_mets_get_agent_performance', $plugin_admin, 'ajax_get_agent_performance' );

		// KB Review AJAX hooks
		$this->loader->add_action( 'wp_ajax_mets_request_article_changes', $plugin_admin, 'ajax_request_article_changes' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new METS_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX hooks for public functionality
		$this->loader->add_action( 'wp_ajax_mets_submit_ticket', $plugin_public, 'ajax_submit_ticket' );
		$this->loader->add_action( 'wp_ajax_nopriv_mets_submit_ticket', $plugin_public, 'ajax_submit_ticket' );
		$this->loader->add_action( 'wp_ajax_mets_upload_file', $plugin_public, 'ajax_upload_file' );
		$this->loader->add_action( 'wp_ajax_nopriv_mets_upload_file', $plugin_public, 'ajax_upload_file' );
		$this->loader->add_action( 'wp_ajax_mets_customer_reply', $plugin_public, 'ajax_customer_reply' );
		$this->loader->add_action( 'wp_ajax_nopriv_mets_customer_reply', $plugin_public, 'ajax_customer_reply' );
		$this->loader->add_action( 'wp_ajax_mets_search_entities_public', $plugin_public, 'ajax_search_entities' );
		$this->loader->add_action( 'wp_ajax_nopriv_mets_search_entities_public', $plugin_public, 'ajax_search_entities' );
		$this->loader->add_action( 'wp_ajax_mets_search_kb_articles', $plugin_public, 'ajax_search_kb_articles' );
		$this->loader->add_action( 'wp_ajax_nopriv_mets_search_kb_articles', $plugin_public, 'ajax_search_kb_articles' );
	}

	/**
	 * Register SLA and automation hooks
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_sla_hooks() {
		// Add custom cron schedules
		$this->loader->add_filter( 'cron_schedules', $this, 'add_cron_schedules' );
		
		// SLA monitoring hooks
		$this->loader->add_action( 'mets_sla_monitoring', $this, 'run_sla_monitoring' );
		$this->loader->add_action( 'mets_sla_breach_check', $this, 'run_breach_check' );
		$this->loader->add_action( 'mets_process_email_queue', $this, 'process_email_queue' );
		$this->loader->add_action( 'mets_clean_email_queue', $this, 'clean_email_queue' );
		
		// Initialize SLA monitoring schedule on WordPress init
		$this->loader->add_action( 'init', $this, 'initialize_sla_monitoring' );
		
		// Ticket lifecycle hooks for SLA calculation
		$this->loader->add_action( 'mets_ticket_created', $this, 'calculate_ticket_sla', 10, 2 );
		$this->loader->add_action( 'mets_ticket_replied', $this, 'update_response_time', 10, 2 );
		$this->loader->add_action( 'mets_ticket_status_changed', $this, 'update_resolution_time', 10, 4 );
		
		// SMTP integration hooks
		$smtp_mailer = new METS_SMTP_Mailer();
		$this->loader->add_action( 'phpmailer_init', $smtp_mailer, 'phpmailer_init' );
	}

	/**
	 * Register role and agent management hooks
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_role_management_hooks() {
		// Initialize role manager instance
		$role_manager = METS_Role_Manager::get_instance();
		
		// Hook into WordPress initialization to create/update roles
		$this->loader->add_action( 'init', $role_manager, 'maybe_create_roles' );
		
		// Hook into user registration to assign default capabilities
		$this->loader->add_action( 'user_register', $role_manager, 'assign_default_capabilities' );
		
		// Hook into capability checking for entity-specific permissions
		$this->loader->add_filter( 'user_has_cap', $role_manager, 'check_entity_specific_capabilities', 10, 4 );
		
		// Initialize agent profile system
		$agent_profile = METS_Agent_Profile::get_instance();
		// The agent profile hooks are initialized in its constructor
		
		// Initialize agent status widget
		$agent_status_widget = METS_Agent_Status_Widget::get_instance();
		// The status widget hooks are initialized in its constructor
	}

	/**
	 * Add custom cron schedules
	 *
	 * @since    1.0.0
	 * @param    array    $schedules    WordPress cron schedules
	 * @return   array                  Modified schedules
	 */
	public function add_cron_schedules( $schedules ) {
		// Add general intervals
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', METS_TEXT_DOMAIN ),
		);

		$schedules['fifteen_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes', METS_TEXT_DOMAIN ),
		);

		$schedules['thirty_minutes'] = array(
			'interval' => 1800,
			'display'  => __( 'Every 30 Minutes', METS_TEXT_DOMAIN ),
		);

		$schedules['two_hours'] = array(
			'interval' => 7200,
			'display'  => __( 'Every 2 Hours', METS_TEXT_DOMAIN ),
		);

		// Add SLA specific intervals
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		$sla_monitor = METS_SLA_Monitor::get_instance();
		return $sla_monitor->add_cron_intervals( $schedules );
	}

	/**
	 * Initialize SLA monitoring schedule
	 *
	 * @since    1.0.0
	 */
	public function initialize_sla_monitoring() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		$sla_monitor = METS_SLA_Monitor::get_instance();
		$sla_monitor->schedule_monitoring();
	}

	/**
	 * Run SLA monitoring
	 *
	 * @since    1.0.0
	 */
	public function run_sla_monitoring() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		$sla_monitor = METS_SLA_Monitor::get_instance();
		$sla_monitor->monitor_sla_compliance();
	}

	/**
	 * Run SLA breach check
	 *
	 * @since    1.0.0
	 */
	public function run_breach_check() {
		// More frequent check for urgent SLA breaches
		$this->run_sla_monitoring();
	}

	/**
	 * Process email queue
	 *
	 * @since    1.0.0
	 */
	public function process_email_queue() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-queue.php';
		$email_queue = METS_Email_Queue::get_instance();
		$email_queue->process_queue();
	}

	/**
	 * Clean email queue
	 *
	 * @since    1.0.0
	 */
	public function clean_email_queue() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-email-queue.php';
		$email_queue = METS_Email_Queue::get_instance();
		$email_queue->clean_queue();
	}

	/**
	 * Calculate SLA for newly created ticket
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @param    array    $ticket_data  Ticket data
	 */
	public function calculate_ticket_sla( $ticket_id, $ticket_data ) {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		$sla_monitor = METS_SLA_Monitor::get_instance();
		
		// Trigger SLA calculation for new ticket
		$sla_monitor->check_single_ticket( $ticket_id );
	}

	/**
	 * Update response time when ticket is replied to
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @param    array    $reply_data   Reply data
	 */
	public function update_response_time( $ticket_id, $reply_data ) {
		// If this is an agent response, mark response SLA as met
		if ( ! empty( $reply_data['user_id'] ) && ! $reply_data['is_internal_note'] ) {
			$user = get_userdata( $reply_data['user_id'] );
			if ( $user && ( user_can( $user, 'manage_tickets' ) || user_can( $user, 'reply_to_tickets' ) ) ) {
				global $wpdb;
				$wpdb->update(
					$wpdb->prefix . 'mets_tickets',
					array( 'sla_status' => 'response_met' ),
					array( 'id' => $ticket_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Update resolution time when ticket status changes
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id     Ticket ID
	 * @param    string   $old_status    Previous status
	 * @param    string   $new_status    New status
	 * @param    int      $user_id       User who changed status
	 */
	public function update_resolution_time( $ticket_id, $old_status, $new_status, $user_id = null ) {
		// If ticket is resolved or closed, mark resolution SLA as met
		if ( in_array( $new_status, array( 'resolved', 'closed' ) ) ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'mets_tickets',
				array( 'sla_status' => 'resolution_met' ),
				array( 'id' => $ticket_id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Initialize email system
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_email_system() {
		// Initialize email notifications
		METS_Email_Notifications::get_instance();
	}

	/**
	 * Initialize REST API
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_rest_api() {
		require_once METS_PLUGIN_PATH . 'includes/api/class-mets-rest-api.php';
		require_once METS_PLUGIN_PATH . 'includes/api/class-mets-rest-api-endpoints.php';
		new METS_REST_API_Endpoints();
	}

	/**
	 * Initialize WooCommerce integration
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_woocommerce_integration() {
		// Only initialize if WooCommerce is active
		if ( class_exists( 'WooCommerce' ) ) {
			require_once METS_PLUGIN_PATH . 'includes/class-mets-woocommerce-integration.php';
			METS_WooCommerce_Integration::get_instance();
		}
	}

	/**
	 * Initialize bulk operations
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_bulk_operations() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-bulk-operations.php';
		METS_Bulk_Operations::get_instance();
		
		// Schedule cleanup tasks
		$this->loader->add_action( 'mets_bulk_cleanup', $this, 'run_bulk_cleanup' );
		$this->loader->add_action( 'mets_archive_old_tickets', $this, 'run_archive_tickets' );
		
		// Initialize cleanup schedule
		$this->loader->add_action( 'init', $this, 'initialize_bulk_cleanup' );
	}

	/**
	 * Initialize advanced automation
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_advanced_automation() {
		require_once METS_PLUGIN_PATH . 'includes/class-mets-advanced-automation.php';
		$automation = METS_Advanced_Automation::get_instance();
		
		// Hook into ticket lifecycle events
		$this->loader->add_action( 'mets_ticket_created', $automation, 'process_ticket_created', 10, 2 );
		$this->loader->add_action( 'mets_ticket_status_changed', $automation, 'process_status_changed', 10, 4 );
		$this->loader->add_action( 'mets_ticket_assigned', $automation, 'process_ticket_assigned', 10, 3 );
		$this->loader->add_action( 'mets_ticket_replied', $automation, 'process_ticket_replied', 10, 2 );
		
		// Schedule automation tasks
		$this->loader->add_action( 'mets_run_automation', $automation, 'run_scheduled_automation' );
		
		// Initialize automation schedule
		$this->loader->add_action( 'init', $this, 'initialize_automation_schedule' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    METS_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
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
	 * Get configured ticket portal URL
	 *
	 * @since    1.0.0
	 * @return   string    Portal URL or empty string if not configured
	 */
	public static function get_ticket_portal_url() {
		return self::get_general_setting( 'ticket_portal_url' );
	}

	/**
	 * Get configured terms and conditions URL
	 *
	 * @since    1.0.0
	 * @return   string    Terms URL or empty string if not configured
	 */
	public static function get_terms_conditions_url() {
		return self::get_general_setting( 'terms_conditions_url' );
	}

	/**
	 * Initialize bulk cleanup schedule
	 *
	 * @since    1.0.0
	 */
	public function initialize_bulk_cleanup() {
		// Schedule bulk cleanup (daily)
		if ( ! wp_next_scheduled( 'mets_bulk_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'mets_bulk_cleanup' );
		}

		// Schedule archive cleanup (weekly)
		if ( ! wp_next_scheduled( 'mets_archive_old_tickets' ) ) {
			wp_schedule_event( time(), 'weekly', 'mets_archive_old_tickets' );
		}
	}

	/**
	 * Initialize automation schedule
	 *
	 * @since    1.0.0
	 */
	public function initialize_automation_schedule() {
		// Schedule automation checks every 30 minutes
		if ( ! wp_next_scheduled( 'mets_run_automation' ) ) {
			wp_schedule_event( time(), 'thirty_minutes', 'mets_run_automation' );
		}
	}

	/**
	 * Run bulk cleanup tasks
	 *
	 * @since    1.0.0
	 */
	public function run_bulk_cleanup() {
		$bulk_operations = METS_Bulk_Operations::get_instance();
		$bulk_operations->cleanup_old_files();
		$bulk_operations->cleanup_logs();
	}

	/**
	 * Run ticket archival
	 *
	 * @since    1.0.0
	 */
	public function run_archive_tickets() {
		$bulk_operations = METS_Bulk_Operations::get_instance();
		$bulk_operations->auto_archive_tickets();
	}

	/**
	 * Initialize performance optimization system
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_performance_optimization() {
		// Initialize performance optimizer
		$performance_optimizer = METS_Performance_Optimizer::get_instance();
		
		// Initialize cache manager
		$cache_manager = METS_Cache_Manager::get_instance();
		
		// Initialize database optimizer
		$database_optimizer = METS_Database_Optimizer::get_instance();
		
		// Schedule performance tasks
		$this->loader->add_action( 'mets_cache_warmup', $cache_manager, 'warm_up_cache' );
		$this->loader->add_action( 'mets_cache_cleanup', $cache_manager, 'clear_expired_cache' );
		$this->loader->add_action( 'mets_weekly_db_optimization', $database_optimizer, 'run_scheduled_optimization' );
		$this->loader->add_action( 'mets_monthly_db_cleanup', $database_optimizer, 'run_scheduled_cleanup' );
		$this->loader->add_action( 'mets_metrics_cleanup', $performance_optimizer, 'cleanup_performance_metrics' );
		
		// Initialize performance schedules
		$this->loader->add_action( 'init', $this, 'initialize_performance_schedules' );
		
		// Enable performance monitoring if debug is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			define( 'METS_PERFORMANCE_MONITORING', true );
		}
	}

	/**
	 * Initialize security system
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_security_system() {
		// Initialize security manager
		$security_manager = METS_Security_Manager::get_instance();
		
		// Initialize security audit system
		$security_audit = METS_Security_Audit::get_instance();
		
		// Create security database tables if they don't exist (non-blocking)
		try {
			METS_Security_Database::create_security_tables();
		} catch ( Exception $e ) {
			error_log( 'METS Security: Non-fatal error creating security tables: ' . $e->getMessage() );
		}
		
		// Schedule security tasks
		$this->loader->add_action( 'mets_security_audit', $security_audit, 'run_security_audit' );
		$this->loader->add_action( 'mets_security_cleanup', $this, 'run_security_cleanup' );
		
		// Initialize security schedules
		$this->loader->add_action( 'init', $this, 'initialize_security_schedules' );
		
		// Add AJAX handlers for security actions
		$this->loader->add_action( 'wp_ajax_mets_run_security_audit', $this, 'ajax_run_security_audit' );
		$this->loader->add_action( 'wp_ajax_mets_save_security_config', $this, 'ajax_save_security_config' );
		$this->loader->add_action( 'wp_ajax_mets_clear_security_logs', $this, 'ajax_clear_security_logs' );
		$this->loader->add_action( 'wp_ajax_mets_export_security_report', $this, 'ajax_export_security_report' );
		$this->loader->add_action( 'wp_ajax_mets_reset_rate_limits', $this, 'ajax_reset_rate_limits' );
	}

	/**
	 * Initialize performance-related scheduled tasks
	 *
	 * @since    1.0.0
	 */
	public function initialize_performance_schedules() {
		$cache_manager = METS_Cache_Manager::get_instance();
		$performance_optimizer = METS_Performance_Optimizer::get_instance();
		
		// Schedule cache warm-up
		$cache_manager->schedule_cache_warmup();
		
		// Schedule performance optimization tasks
		$performance_optimizer->schedule_optimization_tasks();
		
		// Create database indexes on first run
		if ( ! get_option( 'mets_database_indexes_created' ) ) {
			$database_optimizer = METS_Database_Optimizer::get_instance();
			$database_optimizer->create_optimized_indexes();
		}
	}

	/**
	 * Initialize security-related scheduled tasks
	 *
	 * @since    1.0.0
	 */
	public function initialize_security_schedules() {
		$security_audit = METS_Security_Audit::get_instance();
		
		// Schedule weekly security audits
		$security_audit->schedule_security_audits();
		
		// Schedule security cleanup (monthly)
		if ( ! wp_next_scheduled( 'mets_security_cleanup' ) ) {
			wp_schedule_event( time(), 'monthly', 'mets_security_cleanup' );
		}
		
		// Update security database tables if needed
		METS_Security_Database::update_security_tables();
	}

	/**
	 * Run security cleanup tasks
	 *
	 * @since    1.0.0
	 */
	public function run_security_cleanup() {
		// Clean up old security logs (keep last 3 months)
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}mets_security_log 
			WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-3 months' ) )
		) );
		
		// Clean up old rate limits
		METS_Security_Database::cleanup_rate_limits( 30 );
		
		// Clean up old audit trail entries (keep last 6 months)
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}mets_audit_trail 
			WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-6 months' ) )
		) );
	}

	/**
	 * AJAX handler for running security audit
	 *
	 * @since    1.0.0
	 */
	public function ajax_run_security_audit() {
		// Check nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_security_action' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) );
		}

		$security_audit = METS_Security_Audit::get_instance();
		$audit_report = $security_audit->run_security_audit();

		wp_send_json_success( $audit_report );
	}

	/**
	 * AJAX handler for saving security configuration
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_security_config() {
		// Check nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_security_action' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) );
		}

		parse_str( $_POST['config'], $config_data );

		$security_config = array(
			'enable_rate_limiting' => isset( $config_data['enable_rate_limiting'] ) ? 1 : 0,
			'enable_input_validation' => isset( $config_data['enable_input_validation'] ) ? 1 : 0,
			'enable_file_upload_security' => isset( $config_data['enable_file_upload_security'] ) ? 1 : 0,
			'max_login_attempts' => intval( $config_data['max_login_attempts'] ),
			'lockout_duration' => intval( $config_data['lockout_duration'] ) * 60 // Convert to seconds
		);

		$security_manager = METS_Security_Manager::get_instance();
		$result = $security_manager->update_security_config( $security_config );

		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * AJAX handler for clearing security logs
	 *
	 * @since    1.0.0
	 */
	public function ajax_clear_security_logs() {
		// Check nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_security_action' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) );
		}

		global $wpdb;
		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}mets_security_log
			WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-90 days' ) )
		) );

		wp_send_json_success( array( 'message' => sprintf( __( 'Deleted %d old security log entries', METS_TEXT_DOMAIN ), $deleted ) ) );
	}

	/**
	 * AJAX handler for exporting security report
	 *
	 * @since    1.0.0
	 */
	public function ajax_export_security_report() {
		// Check nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_security_action' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) );
		}

		$security_audit = METS_Security_Audit::get_instance();
		$audit_report = $security_audit->get_latest_audit_report();

		if ( ! $audit_report ) {
			wp_send_json_error( array( 'message' => __( 'No audit report available', METS_TEXT_DOMAIN ) ) );
		}
		
		// Generate filename
		$filename = 'mets-security-report-' . date( 'Y-m-d-H-i-s' ) . '.json';
		
		// Set headers for download
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( wp_json_encode( $audit_report ) ) );
		
		echo wp_json_encode( $audit_report, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * AJAX handler for resetting rate limits
	 *
	 * @since    1.0.0
	 */
	public function ajax_reset_rate_limits() {
		// Check nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_security_action' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', METS_TEXT_DOMAIN ) ) );
		}

		global $wpdb;
		$deleted = $wpdb->query( "DELETE FROM {$wpdb->prefix}mets_rate_limits" );

		wp_send_json_success( array( 'message' => sprintf( __( 'Reset %d rate limit entries', METS_TEXT_DOMAIN ), $deleted ) ) );
	}

	/**
	 * Initialize CSP handler for n8n chat integration
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_csp_handler() {
		// Initialize CSP handler for n8n chat
		// METS_CSP_Handler::init(); // Temporarily disabled for testing
	}

}