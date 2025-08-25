<?php
/**
 * Performance Monitoring System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Performance_Monitor {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Performance_Monitor    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Performance metrics storage
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $metrics
	 */
	private $metrics = array();

	/**
	 * Start time for performance tracking
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      float    $start_time
	 */
	private $start_time = 0;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Performance_Monitor    Single instance
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
		$this->start_time = microtime( true );
		$this->init_performance_monitoring();
	}

	/**
	 * Initialize performance monitoring
	 *
	 * @since    1.0.0
	 */
	private function init_performance_monitoring() {
		// Only monitor if WP_DEBUG is enabled or METS_PERFORMANCE_MONITORING is defined
		if ( ! $this->should_monitor_performance() ) {
			return;
		}

		// Hook into WordPress lifecycle
		add_action( 'init', array( $this, 'start_request_tracking' ), 1 );
		add_action( 'wp_footer', array( $this, 'end_request_tracking' ), 999 );
		add_action( 'admin_footer', array( $this, 'end_request_tracking' ), 999 );

		// Monitor database queries
		add_filter( 'query', array( $this, 'track_database_query' ) );

		// Monitor memory usage
		add_action( 'wp_loaded', array( $this, 'track_memory_usage' ) );
	}

	/**
	 * Check if performance monitoring should be enabled
	 *
	 * @since    1.0.0
	 * @return   bool    Whether to monitor performance
	 */
	private function should_monitor_performance() {
		return defined( 'METS_PERFORMANCE_MONITORING' ) && METS_PERFORMANCE_MONITORING ||
			   ( defined( 'WP_DEBUG' ) && WP_DEBUG );
	}

	/**
	 * Start request tracking
	 *
	 * @since    1.0.0
	 */
	public function start_request_tracking() {
		$this->metrics['request_start'] = microtime( true );
		$this->metrics['memory_start'] = memory_get_usage( true );
		$this->metrics['queries_start'] = get_num_queries();
	}

	/**
	 * End request tracking and log metrics
	 *
	 * @since    1.0.0
	 */
	public function end_request_tracking() {
		if ( ! isset( $this->metrics['request_start'] ) ) {
			return;
		}

		$end_time = microtime( true );
		$end_memory = memory_get_usage( true );
		$end_queries = get_num_queries();

		$this->metrics['request_duration'] = $end_time - $this->metrics['request_start'];
		$this->metrics['memory_usage'] = $end_memory - $this->metrics['memory_start'];
		$this->metrics['query_count'] = $end_queries - $this->metrics['queries_start'];
		$this->metrics['peak_memory'] = memory_get_peak_usage( true );

		// Log performance metrics
		$this->log_performance_metrics();
	}

	/**
	 * Track database query performance
	 *
	 * @since    1.0.0
	 * @param    string    $query    SQL query
	 * @return   string              Original query
	 */
	public function track_database_query( $query ) {
		// Only track METS-related queries
		if ( strpos( $query, 'mets_' ) === false ) {
			return $query;
		}

		$start_time = microtime( true );
		
		// Register shutdown hook to measure query time
		add_action( 'shutdown', function() use ( $query, $start_time ) {
			$execution_time = microtime( true ) - $start_time;
			
			// Track slow METS queries
			if ( $execution_time > 1.0 ) { // More than 1 second
				$this->log_slow_query( $query, $execution_time );
			}
		}, 999 );

		return $query;
	}

	/**
	 * Track memory usage at key points
	 *
	 * @since    1.0.0
	 */
	public function track_memory_usage() {
		$this->metrics['wp_loaded_memory'] = memory_get_usage( true );
	}

	/**
	 * Log performance metrics to database
	 *
	 * @since    1.0.0
	 */
	private function log_performance_metrics() {
		global $wpdb;

		// Create performance metrics table if it doesn't exist
		$this->maybe_create_performance_table();

		$metrics_data = array(
			'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
			'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
			'request_duration' => $this->metrics['request_duration'] ?? 0,
			'memory_usage' => $this->metrics['memory_usage'] ?? 0,
			'peak_memory' => $this->metrics['peak_memory'] ?? 0,
			'query_count' => $this->metrics['query_count'] ?? 0,
			'user_id' => get_current_user_id(),
			'is_admin' => is_admin() ? 1 : 0,
			'timestamp' => current_time( 'mysql' )
		);

		$wpdb->insert(
			$wpdb->prefix . 'mets_performance_metrics',
			$metrics_data,
			array( '%s', '%s', '%f', '%d', '%d', '%d', '%d', '%d', '%s' )
		);
	}

	/**
	 * Log slow query for analysis
	 *
	 * @since    1.0.0
	 * @param    string    $query           SQL query
	 * @param    float     $execution_time  Execution time in seconds
	 */
	private function log_slow_query( $query, $execution_time ) {
		$slow_queries = get_transient( 'mets_slow_queries_detailed' ) ?: array();
		
		$slow_queries[] = array(
			'query' => substr( $query, 0, 1000 ), // Limit query length
			'execution_time' => $execution_time,
			'timestamp' => time(),
			'url' => $_SERVER['REQUEST_URI'] ?? '',
			'user_id' => get_current_user_id(),
			'memory_usage' => memory_get_usage( true ),
			'backtrace' => wp_debug_backtrace_summary()
		);

		// Keep only last 50 slow queries
		if ( count( $slow_queries ) > 50 ) {
			$slow_queries = array_slice( $slow_queries, -50 );
		}

		set_transient( 'mets_slow_queries_detailed', $slow_queries, DAY_IN_SECONDS );

		// Log critical slow queries to error log
		if ( $execution_time > 5.0 ) { // More than 5 seconds
			error_log( sprintf( 
				'METS Critical Slow Query: %.2fs - %s', 
				$execution_time, 
				substr( $query, 0, 200 ) 
			) );
		}
	}

	/**
	 * Create performance metrics table
	 *
	 * @since    1.0.0
	 */
	private function maybe_create_performance_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_performance_metrics';
		
		// Check if table already exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			request_uri varchar(500) NOT NULL,
			request_method varchar(10) NOT NULL DEFAULT 'GET',
			request_duration decimal(10,6) NOT NULL DEFAULT 0,
			memory_usage bigint(20) NOT NULL DEFAULT 0,
			peak_memory bigint(20) NOT NULL DEFAULT 0,
			query_count int(11) NOT NULL DEFAULT 0,
			user_id bigint(20) DEFAULT 0,
			is_admin tinyint(1) NOT NULL DEFAULT 0,
			timestamp datetime NOT NULL,
			PRIMARY KEY (id),
			KEY idx_timestamp (timestamp),
			KEY idx_request_duration (request_duration),
			KEY idx_user_id (user_id),
			KEY idx_is_admin (is_admin)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Get performance statistics
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Performance statistics
	 */
	public function get_performance_statistics( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'days' => 7,
			'limit' => 100,
			'order_by' => 'timestamp',
			'order' => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'mets_performance_metrics';
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$args['days']} days" ) );

		// Get basic statistics
		$stats = $wpdb->get_row( $wpdb->prepare( "
			SELECT 
				COUNT(*) as total_requests,
				AVG(request_duration) as avg_duration,
				MAX(request_duration) as max_duration,
				AVG(memory_usage) as avg_memory,
				MAX(peak_memory) as max_memory,
				AVG(query_count) as avg_queries,
				MAX(query_count) as max_queries
			FROM $table_name 
			WHERE timestamp >= %s
		", $date_threshold ) );

		// Get slowest requests
		$slowest_requests = $wpdb->get_results( $wpdb->prepare( "
			SELECT request_uri, request_duration, memory_usage, query_count, timestamp
			FROM $table_name 
			WHERE timestamp >= %s
			ORDER BY request_duration DESC 
			LIMIT %d
		", $date_threshold, $args['limit'] ) );

		// Get requests with highest memory usage
		$memory_intensive = $wpdb->get_results( $wpdb->prepare( "
			SELECT request_uri, request_duration, memory_usage, query_count, timestamp
			FROM $table_name 
			WHERE timestamp >= %s
			ORDER BY memory_usage DESC 
			LIMIT %d
		", $date_threshold, $args['limit'] ) );

		// Get requests with most database queries
		$query_intensive = $wpdb->get_results( $wpdb->prepare( "
			SELECT request_uri, request_duration, memory_usage, query_count, timestamp
			FROM $table_name 
			WHERE timestamp >= %s
			ORDER BY query_count DESC 
			LIMIT %d
		", $date_threshold, $args['limit'] ) );

		return array(
			'statistics' => $stats,
			'slowest_requests' => $slowest_requests,
			'memory_intensive_requests' => $memory_intensive,
			'query_intensive_requests' => $query_intensive,
			'slow_queries' => get_transient( 'mets_slow_queries_detailed' ) ?: array(),
			'period_days' => $args['days']
		);
	}

	/**
	 * Generate performance report
	 *
	 * @since    1.0.0
	 * @return   array    Performance report
	 */
	public function generate_performance_report() {
		$stats = $this->get_performance_statistics();
		$database_optimizer = METS_Database_Optimizer::get_instance();
		$db_metrics = $database_optimizer->get_performance_metrics();

		$report = array(
			'timestamp' => time(),
			'performance_statistics' => $stats['statistics'],
			'database_metrics' => $db_metrics,
			'problem_areas' => $this->identify_performance_problems( $stats ),
			'recommendations' => $this->generate_performance_recommendations( $stats, $db_metrics ),
			'top_issues' => array(
				'slowest_requests' => array_slice( $stats['slowest_requests'], 0, 10 ),
				'memory_intensive' => array_slice( $stats['memory_intensive_requests'], 0, 10 ),
				'query_intensive' => array_slice( $stats['query_intensive_requests'], 0, 10 ),
				'slow_queries' => array_slice( $stats['slow_queries'], 0, 10 )
			)
		);

		return $report;
	}

	/**
	 * Identify performance problems
	 *
	 * @since    1.0.0
	 * @param    array    $stats    Performance statistics
	 * @return   array              Identified problems
	 */
	private function identify_performance_problems( $stats ) {
		$problems = array();

		$statistics = $stats['statistics'];

		// Check for slow average response time
		if ( $statistics->avg_duration > 2.0 ) {
			$problems[] = array(
				'type' => 'slow_response_time',
				'severity' => 'high',
				'message' => sprintf( 
					'Average response time is %.2f seconds (should be under 1 second)', 
					$statistics->avg_duration 
				),
				'value' => $statistics->avg_duration
			);
		}

		// Check for high memory usage
		if ( $statistics->avg_memory > 50 * 1024 * 1024 ) { // 50MB
			$problems[] = array(
				'type' => 'high_memory_usage',
				'severity' => 'medium',
				'message' => sprintf( 
					'Average memory usage is %s (should be under 50MB)', 
					size_format( $statistics->avg_memory ) 
				),
				'value' => $statistics->avg_memory
			);
		}

		// Check for too many database queries
		if ( $statistics->avg_queries > 50 ) {
			$problems[] = array(
				'type' => 'too_many_queries',
				'severity' => 'high',
				'message' => sprintf( 
					'Average %.1f queries per request (should be under 30)', 
					$statistics->avg_queries 
				),
				'value' => $statistics->avg_queries
			);
		}

		// Check for N+1 query issues
		$n1_detections = get_transient( 'mets_n1_queries' ) ?: array();
		if ( count( $n1_detections ) > 0 ) {
			$problems[] = array(
				'type' => 'n1_queries',
				'severity' => 'high',
				'message' => sprintf( 
					'%d N+1 query patterns detected', 
					count( $n1_detections ) 
				),
				'value' => count( $n1_detections )
			);
		}

		return $problems;
	}

	/**
	 * Generate performance recommendations
	 *
	 * @since    1.0.0
	 * @param    array    $stats       Performance statistics
	 * @param    array    $db_metrics  Database metrics
	 * @return   array                 Recommendations
	 */
	private function generate_performance_recommendations( $stats, $db_metrics ) {
		$recommendations = array();

		// Check cache hit ratio
		if ( isset( $db_metrics['cache_hit_ratio'] ) && $db_metrics['cache_hit_ratio'] < 80 ) {
			$recommendations[] = array(
				'type' => 'improve_caching',
				'priority' => 'high',
				'message' => 'Query cache hit ratio is below 80%. Consider implementing Redis or Memcached.',
				'action' => 'Install object caching plugin'
			);
		}

		// Check for slow queries
		if ( count( $stats['slow_queries'] ) > 5 ) {
			$recommendations[] = array(
				'type' => 'optimize_queries',
				'priority' => 'high',
				'message' => 'Multiple slow queries detected. Review and optimize database queries.',
				'action' => 'Analyze slow query log and add indexes'
			);
		}

		// Check for N+1 queries
		if ( isset( $db_metrics['n1_detections_today'] ) && $db_metrics['n1_detections_today'] > 0 ) {
			$recommendations[] = array(
				'type' => 'fix_n1_queries',
				'priority' => 'critical',
				'message' => 'N+1 query patterns detected. Implement eager loading or batch queries.',
				'action' => 'Review code for N+1 patterns and implement batch loading'
			);
		}

		// Check table sizes
		$table_stats = $db_metrics['optimized_tables'] ?? array();
		foreach ( $table_stats as $table => $stats ) {
			if ( isset( $stats['size_mb'] ) && $stats['size_mb'] > 500 ) {
				$recommendations[] = array(
					'type' => 'archive_large_tables',
					'priority' => 'medium',
					'message' => "Table {$table} is {$stats['size_mb']}MB. Consider archiving old data.",
					'action' => 'Implement data archival strategy'
				);
			}
		}

		return $recommendations;
	}

	/**
	 * Clean up old performance data
	 *
	 * @since    1.0.0
	 * @param    int    $days_to_keep    Days to keep performance data
	 */
	public function cleanup_old_performance_data( $days_to_keep = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'mets_performance_metrics';
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-$days_to_keep days" ) );

		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM $table_name WHERE timestamp < %s",
			$cutoff_date
		) );

		return $deleted;
	}
}