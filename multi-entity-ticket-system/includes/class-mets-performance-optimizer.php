<?php
/**
 * Performance Optimization System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Performance_Optimizer {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Performance_Optimizer    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Cache manager instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Cache_Manager    $cache_manager
	 */
	private $cache_manager;

	/**
	 * Performance metrics
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metrics
	 */
	private $metrics = array();

	/**
	 * Query optimization flags
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $optimization_flags
	 */
	private $optimization_flags = array(
		'enable_query_cache' => true,
		'enable_object_cache' => true,
		'enable_transient_cache' => true,
		'enable_database_optimization' => true,
		'enable_asset_optimization' => true,
		'enable_lazy_loading' => true
	);

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Performance_Optimizer    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->cache_manager = METS_Cache_Manager::get_instance();
		$this->init_optimizations();
	}

	/**
	 * Initialize performance optimizations
	 *
	 * @since    1.0.0
	 */
	private function init_optimizations() {
		// Database query optimization
		add_filter( 'posts_clauses', array( $this, 'optimize_wp_queries' ), 10, 2 );
		
		// Asset optimization
		add_action( 'wp_enqueue_scripts', array( $this, 'optimize_frontend_assets' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'optimize_admin_assets' ), 99 );
		
		// AJAX optimization
		add_filter( 'wp_doing_ajax', array( $this, 'optimize_ajax_requests' ) );
		
		// Database optimization
		add_action( 'mets_database_optimization', array( $this, 'optimize_database_tables' ) );
		
		// Memory optimization
		add_action( 'wp_loaded', array( $this, 'optimize_memory_usage' ) );
		
		// Performance monitoring
		add_action( 'shutdown', array( $this, 'record_performance_metrics' ) );
	}

	/**
	 * Optimize database queries
	 *
	 * @since    1.0.0
	 * @param    string    $sql     SQL query
	 * @param    string    $table   Table name
	 * @return   string             Optimized SQL query
	 */
	public function optimize_ticket_queries( $sql, $table = '' ) {
		global $wpdb;
		
		// Add query hints for ticket searches
		if ( strpos( $sql, $wpdb->prefix . 'mets_tickets' ) !== false ) {
			// Add USE INDEX hints for common queries
			if ( strpos( $sql, 'WHERE status' ) !== false ) {
				$sql = str_replace( 
					$wpdb->prefix . 'mets_tickets', 
					$wpdb->prefix . 'mets_tickets USE INDEX (idx_status)', 
					$sql 
				);
			}
			
			if ( strpos( $sql, 'WHERE entity_id' ) !== false ) {
				$sql = str_replace( 
					$wpdb->prefix . 'mets_tickets', 
					$wpdb->prefix . 'mets_tickets USE INDEX (idx_entity_id)', 
					$sql 
				);
			}
			
			if ( strpos( $sql, 'ORDER BY created_at' ) !== false ) {
				$sql = str_replace( 
					$wpdb->prefix . 'mets_tickets', 
					$wpdb->prefix . 'mets_tickets USE INDEX (idx_created_at)', 
					$sql 
				);
			}
		}
		
		return $sql;
	}

	/**
	 * Optimize WordPress queries
	 *
	 * @since    1.0.0
	 * @param    array      $clauses    Query clauses
	 * @param    WP_Query   $query      Query object
	 * @return   array                  Optimized clauses
	 */
	public function optimize_wp_queries( $clauses, $query ) {
		// Skip optimization for main queries on frontend
		if ( ! is_admin() && $query->is_main_query() ) {
			return $clauses;
		}
		
		// Add LIMIT to prevent runaway queries
		if ( empty( $clauses['limits'] ) && ! $query->is_singular() ) {
			$clauses['limits'] = 'LIMIT 0, 100';
		}
		
		return $clauses;
	}

	/**
	 * Optimize frontend assets
	 *
	 * @since    1.0.0
	 */
	public function optimize_frontend_assets() {
		if ( ! $this->optimization_flags['enable_asset_optimization'] ) {
			return;
		}
		
		// Defer non-critical JavaScript
		$this->defer_non_critical_scripts();
		
		// Optimize CSS delivery
		$this->optimize_css_delivery();
		
		// Enable asset minification
		$this->enable_asset_minification();
	}

	/**
	 * Optimize admin assets
	 *
	 * @since    1.0.0
	 */
	public function optimize_admin_assets() {
		if ( ! $this->optimization_flags['enable_asset_optimization'] ) {
			return;
		}
		
		// Only load assets on METS admin pages
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'mets' ) === false ) {
			return;
		}
		
		// Combine admin scripts
		$this->combine_admin_scripts();
		
		// Optimize admin CSS
		$this->optimize_admin_css();
	}

	/**
	 * Defer non-critical JavaScript files
	 *
	 * @since    1.0.0
	 */
	private function defer_non_critical_scripts() {
		global $wp_scripts;
		
		$defer_scripts = array(
			'mets-analytics',
			'mets-charts',
			'mets-export'
		);
		
		foreach ( $defer_scripts as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$wp_scripts->registered[ $handle ]->extra['defer'] = true;
			}
		}
	}

	/**
	 * Optimize CSS delivery
	 *
	 * @since    1.0.0
	 */
	private function optimize_css_delivery() {
		// This would typically involve critical CSS extraction
		// and non-critical CSS lazy loading
		
		// For now, we'll just ensure proper CSS dependencies
		global $wp_styles;
		
		// Combine METS stylesheets
		$mets_styles = array();
		foreach ( $wp_styles->registered as $handle => $style ) {
			if ( strpos( $handle, 'mets-' ) === 0 ) {
				$mets_styles[] = $handle;
			}
		}
		
		// Set dependencies to ensure proper loading order
		if ( count( $mets_styles ) > 1 ) {
			for ( $i = 1; $i < count( $mets_styles ); $i++ ) {
				$wp_styles->registered[ $mets_styles[ $i ] ]->deps[] = $mets_styles[ $i - 1 ];
			}
		}
	}

	/**
	 * Enable asset minification
	 *
	 * @since    1.0.0
	 */
	private function enable_asset_minification() {
		// Add minification filter for METS assets
		add_filter( 'script_loader_src', array( $this, 'use_minified_assets' ), 10, 2 );
		add_filter( 'style_loader_src', array( $this, 'use_minified_assets' ), 10, 2 );
	}

	/**
	 * Use minified versions of assets in production
	 *
	 * @since    1.0.0
	 * @param    string    $src     Asset source URL
	 * @param    string    $handle  Asset handle
	 * @return   string             Potentially minified asset URL
	 */
	public function use_minified_assets( $src, $handle ) {
		// Only minify METS assets
		if ( strpos( $handle, 'mets-' ) !== 0 ) {
			return $src;
		}
		
		// In production, use .min versions
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			$src = str_replace( array( '.js', '.css' ), array( '.min.js', '.min.css' ), $src );
		}
		
		return $src;
	}

	/**
	 * Combine admin scripts for better performance
	 *
	 * @since    1.0.0
	 */
	private function combine_admin_scripts() {
		// This is a simplified version - in production you'd want proper bundling
		$combined_handle = 'mets-admin-combined';
		
		if ( ! wp_script_is( $combined_handle, 'registered' ) ) {
			wp_register_script(
				$combined_handle,
				METS_PLUGIN_URL . 'admin/js/mets-admin-combined.min.js',
				array( 'jquery' ),
				METS_VERSION,
				true
			);
		}
	}

	/**
	 * Optimize admin CSS
	 *
	 * @since    1.0.0
	 */
	private function optimize_admin_css() {
		$combined_handle = 'mets-admin-styles-combined';
		
		if ( ! wp_style_is( $combined_handle, 'registered' ) ) {
			wp_register_style(
				$combined_handle,
				METS_PLUGIN_URL . 'admin/css/mets-admin-combined.min.css',
				array(),
				METS_VERSION
			);
		}
	}

	/**
	 * Optimize AJAX requests
	 *
	 * @since    1.0.0
	 * @param    bool    $doing_ajax    Whether doing AJAX
	 * @return   bool                   Whether doing AJAX
	 */
	public function optimize_ajax_requests( $doing_ajax ) {
		if ( $doing_ajax && isset( $_POST['action'] ) && strpos( $_POST['action'], 'mets_' ) === 0 ) {
			// Disable unnecessary WordPress features for METS AJAX requests
			remove_action( 'wp_head', 'wp_generator' );
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'rsd_link' );
			
			// Enable output compression
			if ( ! ob_get_level() && ini_get( 'output_buffering' ) ) {
				ob_start( 'ob_gzhandler' );
			}
		}
		
		return $doing_ajax;
	}

	/**
	 * Optimize database tables
	 *
	 * @since    1.0.0
	 */
	public function optimize_database_tables() {
		global $wpdb;
		
		$tables = array(
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_ticket_replies',
			$wpdb->prefix . 'mets_entities',
			$wpdb->prefix . 'mets_kb_articles',
			$wpdb->prefix . 'mets_kb_categories'
		);
		
		foreach ( $tables as $table ) {
			// Check if table exists
			$table_exists = $wpdb->get_var( $wpdb->prepare( 
				"SHOW TABLES LIKE %s", 
				$table 
			) );
			
			if ( $table_exists ) {
				// Optimize table
				$wpdb->query( "OPTIMIZE TABLE $table" );
				
				// Analyze table for better query planning
				$wpdb->query( "ANALYZE TABLE $table" );
			}
		}
		
		// Clean up orphaned records
		$this->cleanup_orphaned_records();
	}

	/**
	 * Clean up orphaned database records
	 *
	 * @since    1.0.0
	 */
	private function cleanup_orphaned_records() {
		global $wpdb;
		
		// Clean up ticket replies without parent tickets
		$wpdb->query( "
			DELETE tr FROM {$wpdb->prefix}mets_ticket_replies tr 
			LEFT JOIN {$wpdb->prefix}mets_tickets t ON tr.ticket_id = t.id 
			WHERE t.id IS NULL
		" );
		
		// Clean up tickets without valid entities
		$wpdb->query( "
			DELETE t FROM {$wpdb->prefix}mets_tickets t 
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id 
			WHERE e.id IS NULL AND t.entity_id IS NOT NULL
		" );
		
		// Clean up KB articles without valid categories
		$wpdb->query( "
			UPDATE {$wpdb->prefix}mets_kb_articles a 
			LEFT JOIN {$wpdb->prefix}mets_kb_categories c ON a.category_id = c.id 
			SET a.category_id = NULL 
			WHERE c.id IS NULL AND a.category_id IS NOT NULL
		" );
	}

	/**
	 * Optimize memory usage
	 *
	 * @since    1.0.0
	 */
	public function optimize_memory_usage() {
		// Increase memory limit for heavy operations
		if ( $this->is_heavy_operation() ) {
			$current_limit = ini_get( 'memory_limit' );
			$current_bytes = $this->convert_to_bytes( $current_limit );
			$required_bytes = 256 * 1024 * 1024; // 256MB
			
			if ( $current_bytes < $required_bytes ) {
				ini_set( 'memory_limit', '256M' );
			}
		}
		
		// Clean up unnecessary data
		$this->cleanup_memory();
	}

	/**
	 * Check if current request is a heavy operation
	 *
	 * @since    1.0.0
	 * @return   bool    Whether current operation is heavy
	 */
	private function is_heavy_operation() {
		$heavy_actions = array(
			'mets_bulk_operation',
			'mets_export_data',
			'mets_import_data',
			'mets_generate_report'
		);
		
		return isset( $_POST['action'] ) && in_array( $_POST['action'], $heavy_actions );
	}

	/**
	 * Convert memory limit string to bytes
	 *
	 * @since    1.0.0
	 * @param    string    $limit    Memory limit string
	 * @return   int                 Memory limit in bytes
	 */
	private function convert_to_bytes( $limit ) {
		$limit = strtolower( $limit );
		$bytes = intval( $limit );
		
		if ( strpos( $limit, 'k' ) !== false ) {
			$bytes *= 1024;
		} elseif ( strpos( $limit, 'm' ) !== false ) {
			$bytes *= 1024 * 1024;
		} elseif ( strpos( $limit, 'g' ) !== false ) {
			$bytes *= 1024 * 1024 * 1024;
		}
		
		return $bytes;
	}

	/**
	 * Clean up memory usage
	 *
	 * @since    1.0.0
	 */
	private function cleanup_memory() {
		// Force garbage collection
		if ( function_exists( 'gc_collect_cycles' ) ) {
			gc_collect_cycles();
		}
		
		// Clear unnecessary variables
		if ( isset( $GLOBALS['wp_object_cache'] ) && is_object( $GLOBALS['wp_object_cache'] ) ) {
			$GLOBALS['wp_object_cache']->flush();
		}
	}

	/**
	 * Record performance metrics
	 *
	 * @since    1.0.0
	 */
	public function record_performance_metrics() {
		if ( ! defined( 'METS_PERFORMANCE_MONITORING' ) || ! METS_PERFORMANCE_MONITORING ) {
			return;
		}
		
		$this->metrics['memory_peak'] = memory_get_peak_usage( true );
		$this->metrics['memory_current'] = memory_get_usage( true );
		$this->metrics['execution_time'] = microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];
		$this->metrics['queries'] = get_num_queries();
		$this->metrics['timestamp'] = time();
		
		// Store metrics in transient for analysis
		$stored_metrics = get_transient( 'mets_performance_metrics' );
		if ( ! $stored_metrics ) {
			$stored_metrics = array();
		}
		
		$stored_metrics[] = $this->metrics;
		
		// Keep only last 100 entries
		if ( count( $stored_metrics ) > 100 ) {
			$stored_metrics = array_slice( $stored_metrics, -100 );
		}
		
		set_transient( 'mets_performance_metrics', $stored_metrics, DAY_IN_SECONDS );
	}

	/**
	 * Get performance metrics
	 *
	 * @since    1.0.0
	 * @return   array    Performance metrics
	 */
	public function get_performance_metrics() {
		return get_transient( 'mets_performance_metrics' ) ?: array();
	}

	/**
	 * Get current performance snapshot
	 *
	 * @since    1.0.0
	 * @return   array    Current performance data
	 */
	public function get_current_performance() {
		return array(
			'memory_usage' => memory_get_usage( true ),
			'memory_peak' => memory_get_peak_usage( true ),
			'memory_limit' => ini_get( 'memory_limit' ),
			'execution_time' => microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'],
			'queries_count' => get_num_queries(),
			'cache_stats' => $this->cache_manager->get_stats(),
			'php_version' => PHP_VERSION,
			'mysql_version' => $this->get_mysql_version()
		);
	}

	/**
	 * Get MySQL version
	 *
	 * @since    1.0.0
	 * @return   string    MySQL version
	 */
	private function get_mysql_version() {
		global $wpdb;
		return $wpdb->get_var( "SELECT VERSION()" );
	}

	/**
	 * Enable lazy loading for heavy components
	 *
	 * @since    1.0.0
	 * @param    string    $component    Component to lazy load
	 * @return   bool                    Success status
	 */
	public function enable_lazy_loading( $component ) {
		if ( ! $this->optimization_flags['enable_lazy_loading'] ) {
			return false;
		}
		
		$lazy_components = array(
			'reports' => array( $this, 'lazy_load_reports' ),
			'analytics' => array( $this, 'lazy_load_analytics' ),
			'bulk_operations' => array( $this, 'lazy_load_bulk_operations' )
		);
		
		if ( isset( $lazy_components[ $component ] ) ) {
			add_action( 'wp_ajax_mets_lazy_load_' . $component, $lazy_components[ $component ] );
			return true;
		}
		
		return false;
	}

	/**
	 * Lazy load reports component
	 *
	 * @since    1.0.0
	 */
	public function lazy_load_reports() {
		// Only load when specifically requested
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( 'Unauthorized' );
		}
		
		require_once METS_PLUGIN_PATH . 'admin/class-mets-reporting-dashboard.php';
		$reports = new METS_Reporting_Dashboard();
		
		wp_send_json_success( array(
			'html' => $reports->get_dashboard_content()
		) );
	}

	/**
	 * Lazy load analytics component
	 *
	 * @since    1.0.0
	 */
	public function lazy_load_analytics() {
		// Only load when specifically requested
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( 'Unauthorized' );
		}
		
		require_once METS_PLUGIN_PATH . 'admin/kb/class-mets-kb-analytics.php';
		$analytics = new METS_KB_Analytics();
		
		wp_send_json_success( array(
			'html' => $analytics->get_analytics_content()
		) );
	}

	/**
	 * Lazy load bulk operations component
	 *
	 * @since    1.0.0
	 */
	public function lazy_load_bulk_operations() {
		// Only load when specifically requested
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( 'Unauthorized' );
		}
		
		require_once METS_PLUGIN_PATH . 'includes/class-mets-bulk-operations.php';
		$bulk_ops = METS_Bulk_Operations::get_instance();
		
		wp_send_json_success( array(
			'status' => 'ready',
			'operations_count' => $bulk_ops->get_available_operations_count()
		) );
	}

	/**
	 * Schedule performance optimization tasks
	 *
	 * @since    1.0.0
	 */
	public function schedule_optimization_tasks() {
		// Schedule database optimization
		if ( ! wp_next_scheduled( 'mets_database_optimization' ) ) {
			wp_schedule_event( time(), 'daily', 'mets_database_optimization' );
		}
		
		// Schedule cache cleanup
		if ( ! wp_next_scheduled( 'mets_cache_cleanup' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'mets_cache_cleanup' );
		}
		
		// Schedule performance metrics cleanup
		if ( ! wp_next_scheduled( 'mets_metrics_cleanup' ) ) {
			wp_schedule_event( time(), 'weekly', 'mets_metrics_cleanup' );
		}
	}

	/**
	 * Clean up old performance metrics
	 *
	 * @since    1.0.0
	 */
	public function cleanup_performance_metrics() {
		delete_transient( 'mets_performance_metrics' );
	}
}