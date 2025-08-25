<?php
/**
 * Database Optimization System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Database_Optimizer {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Database_Optimizer    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Database optimization settings
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $optimization_settings
	 */
	private $optimization_settings = array(
		'enable_query_cache' => true,
		'enable_index_optimization' => true,
		'enable_table_optimization' => true,
		'enable_query_analysis' => true,
		'slow_query_threshold' => 2.0, // seconds
		'batch_size' => 1000
	);

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Database_Optimizer    Single instance
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
		$this->init_optimization();
	}

	/**
	 * Initialize database optimization
	 *
	 * @since    1.0.0
	 */
	private function init_optimization() {
		// Create database indexes on plugin activation
		add_action( 'mets_create_database_indexes', array( $this, 'create_optimized_indexes' ) );
		
		// Monitor slow queries if enabled
		if ( $this->optimization_settings['enable_query_analysis'] ) {
			add_filter( 'query', array( $this, 'analyze_query_performance' ) );
		}
		
		// Schedule regular maintenance
		$this->schedule_maintenance_tasks();
	}

	/**
	 * Create optimized database indexes
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function create_optimized_indexes() {
		global $wpdb;
		
		$indexes = array(
			// Tickets table indexes
			array(
				'table' => $wpdb->prefix . 'mets_tickets',
				'indexes' => array(
					'idx_status' => 'status',
					'idx_priority' => 'priority',
					'idx_entity_id' => 'entity_id',
					'idx_assigned_to' => 'assigned_to',
					'idx_created_at' => 'created_at',
					'idx_updated_at' => 'updated_at',
					'idx_status_priority' => 'status, priority',
					'idx_entity_status' => 'entity_id, status',
					'idx_assigned_status' => 'assigned_to, status',
					'idx_created_status' => 'created_at, status'
				)
			),
			
			// Ticket replies table indexes
			array(
				'table' => $wpdb->prefix . 'mets_ticket_replies',
				'indexes' => array(
					'idx_ticket_id' => 'ticket_id',
					'idx_user_id' => 'user_id',
					'idx_created_at' => 'created_at',
					'idx_is_internal' => 'is_internal_note',
					'idx_ticket_created' => 'ticket_id, created_at',
					'idx_user_created' => 'user_id, created_at'
				)
			),
			
			// Entities table indexes
			array(
				'table' => $wpdb->prefix . 'mets_entities',
				'indexes' => array(
					'idx_status' => 'status',
					'idx_parent_id' => 'parent_id',
					'idx_entity_type' => 'entity_type',
					'idx_created_at' => 'created_at',
					'idx_name' => 'name(50)', // Partial index for name searches
					'idx_status_type' => 'status, entity_type',
					'idx_parent_status' => 'parent_id, status'
				)
			),
			
			// KB Articles table indexes
			array(
				'table' => $wpdb->prefix . 'mets_kb_articles',
				'indexes' => array(
					'idx_status' => 'status',
					'idx_category_id' => 'category_id',
					'idx_author_id' => 'author_id',
					'idx_created_at' => 'created_at',
					'idx_updated_at' => 'updated_at',
					'idx_is_featured' => 'is_featured',
					'idx_slug' => 'slug',
					'idx_category_status' => 'category_id, status',
					'idx_status_featured' => 'status, is_featured',
					'idx_title' => 'title(100)' // Partial index for title searches
				)
			),
			
			// KB Categories table indexes
			array(
				'table' => $wpdb->prefix . 'mets_kb_categories',
				'indexes' => array(
					'idx_parent_id' => 'parent_id',
					'idx_slug' => 'slug',
					'idx_name' => 'name(50)'
				)
			),

			// KB Analytics table indexes
			array(
				'table' => $wpdb->prefix . 'mets_kb_analytics',
				'indexes' => array(
					'idx_article_id' => 'article_id',
					'idx_event_type' => 'event_type',
					'idx_created_at' => 'created_at',
					'idx_article_event' => 'article_id, event_type',
					'idx_event_date' => 'event_type, created_at'
				)
			),

			// Ticket metadata table (if exists)
			array(
				'table' => $wpdb->prefix . 'mets_ticket_meta',
				'indexes' => array(
					'idx_ticket_id' => 'ticket_id',
					'idx_meta_key' => 'meta_key',
					'idx_ticket_key' => 'ticket_id, meta_key'
				)
			)
		);

		$created_indexes = 0;
		$errors = array();

		foreach ( $indexes as $table_config ) {
			$table = $table_config['table'];
			
			// Check if table exists
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
			if ( ! $table_exists ) {
				continue;
			}

			foreach ( $table_config['indexes'] as $index_name => $columns ) {
				// Check if index already exists
				$existing_index = $wpdb->get_var( $wpdb->prepare( 
					"SHOW INDEX FROM $table WHERE Key_name = %s", 
					$index_name 
				) );

				if ( ! $existing_index ) {
					$sql = "CREATE INDEX $index_name ON $table ($columns)";
					$result = $wpdb->query( $sql );
					
					if ( $result === false ) {
						$errors[] = "Failed to create index $index_name on $table: " . $wpdb->last_error;
					} else {
						$created_indexes++;
					}
				}
			}
		}

		// Log results
		if ( ! empty( $errors ) ) {
			error_log( 'METS Database Index Errors: ' . implode( '; ', $errors ) );
		}

		update_option( 'mets_database_indexes_created', array(
			'count' => $created_indexes,
			'timestamp' => time(),
			'errors' => $errors
		) );

		return empty( $errors );
	}

	/**
	 * Analyze query performance
	 *
	 * @since    1.0.0
	 * @param    string    $query    SQL query
	 * @return   string              Original query
	 */
	public function analyze_query_performance( $query ) {
		// Only analyze METS-related queries
		if ( strpos( $query, 'mets_' ) === false ) {
			return $query;
		}

		$start_time = microtime( true );
		
		// Execute the query and measure time
		add_filter( 'query', array( $this, 'measure_query_time' ), 999 );
		
		return $query;
	}

	/**
	 * Measure query execution time
	 *
	 * @since    1.0.0
	 * @param    string    $query    SQL query
	 * @return   string              Original query
	 */
	public function measure_query_time( $query ) {
		static $start_time;
		
		if ( ! $start_time ) {
			$start_time = microtime( true );
			return $query;
		}

		$execution_time = microtime( true ) - $start_time;
		
		// Log slow queries
		if ( $execution_time > $this->optimization_settings['slow_query_threshold'] ) {
			$this->log_slow_query( $query, $execution_time );
		}

		$start_time = null;
		remove_filter( 'query', array( $this, 'measure_query_time' ), 999 );
		
		return $query;
	}

	/**
	 * Log slow query for analysis
	 *
	 * @since    1.0.0
	 * @param    string    $query           SQL query
	 * @param    float     $execution_time  Execution time in seconds
	 */
	private function log_slow_query( $query, $execution_time ) {
		$slow_queries = get_transient( 'mets_slow_queries' );
		if ( ! $slow_queries ) {
			$slow_queries = array();
		}

		$slow_queries[] = array(
			'query' => $query,
			'execution_time' => $execution_time,
			'timestamp' => time(),
			'url' => $_SERVER['REQUEST_URI'] ?? '',
			'user_id' => get_current_user_id()
		);

		// Keep only last 50 slow queries
		if ( count( $slow_queries ) > 50 ) {
			$slow_queries = array_slice( $slow_queries, -50 );
		}

		set_transient( 'mets_slow_queries', $slow_queries, DAY_IN_SECONDS );
	}

	/**
	 * Optimize database tables
	 *
	 * @since    1.0.0
	 * @return   array    Optimization results
	 */
	public function optimize_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_ticket_replies',
			$wpdb->prefix . 'mets_entities',
			$wpdb->prefix . 'mets_kb_articles',
			$wpdb->prefix . 'mets_kb_categories',
			$wpdb->prefix . 'mets_kb_analytics'
		);

		$results = array(
			'optimized' => array(),
			'analyzed' => array(),
			'errors' => array()
		);

		foreach ( $tables as $table ) {
			// Check if table exists
			$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
			if ( ! $table_exists ) {
				continue;
			}

			// Optimize table
			$optimize_result = $wpdb->query( "OPTIMIZE TABLE $table" );
			if ( $optimize_result !== false ) {
				$results['optimized'][] = $table;
			} else {
				$results['errors'][] = "Failed to optimize $table: " . $wpdb->last_error;
			}

			// Analyze table
			$analyze_result = $wpdb->query( "ANALYZE TABLE $table" );
			if ( $analyze_result !== false ) {
				$results['analyzed'][] = $table;
			} else {
				$results['errors'][] = "Failed to analyze $table: " . $wpdb->last_error;
			}
		}

		// Update last optimization timestamp
		update_option( 'mets_last_db_optimization', time() );

		return $results;
	}

	/**
	 * Get table statistics
	 *
	 * @since    1.0.0
	 * @return   array    Table statistics
	 */
	public function get_table_statistics() {
		global $wpdb;

		$tables = array(
			'tickets' => $wpdb->prefix . 'mets_tickets',
			'ticket_replies' => $wpdb->prefix . 'mets_ticket_replies',
			'entities' => $wpdb->prefix . 'mets_entities',
			'kb_articles' => $wpdb->prefix . 'mets_kb_articles',
			'kb_categories' => $wpdb->prefix . 'mets_kb_categories',
			'kb_analytics' => $wpdb->prefix . 'mets_kb_analytics'
		);

		$statistics = array();

		foreach ( $tables as $key => $table ) {
			$table_stats = $wpdb->get_row( $wpdb->prepare( 
				"SELECT 
					COUNT(*) as row_count,
					ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
					ROUND((data_free / 1024 / 1024), 2) as free_mb
				FROM information_schema.TABLES 
				WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$table
			) );

			if ( $table_stats ) {
				$statistics[ $key ] = array(
					'table' => $table,
					'rows' => $table_stats->row_count,
					'size_mb' => $table_stats->size_mb,
					'free_mb' => $table_stats->free_mb
				);
			}
		}

		return $statistics;
	}

	/**
	 * Get slow queries log
	 *
	 * @since    1.0.0
	 * @return   array    Slow queries
	 */
	public function get_slow_queries() {
		return get_transient( 'mets_slow_queries' ) ?: array();
	}

	/**
	 * Clean up old data
	 *
	 * @since    1.0.0
	 * @param    int    $days_old    Delete data older than X days
	 * @return   array               Cleanup results
	 */
	public function cleanup_old_data( $days_old = 90 ) {
		global $wpdb;

		$results = array(
			'deleted_records' => 0,
			'tables_cleaned' => array(),
			'errors' => array()
		);

		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-$days_old days" ) );

		// Clean up old analytics data
		$deleted = $wpdb->query( $wpdb->prepare( 
			"DELETE FROM {$wpdb->prefix}mets_kb_analytics WHERE created_at < %s",
			$cutoff_date
		) );

		if ( $deleted !== false ) {
			$results['deleted_records'] += $deleted;
			$results['tables_cleaned'][] = 'mets_kb_analytics';
		} else {
			$results['errors'][] = 'Failed to clean analytics data: ' . $wpdb->last_error;
		}

		// Clean up old closed tickets (if enabled)
		$cleanup_tickets = get_option( 'mets_cleanup_old_tickets', false );
		if ( $cleanup_tickets ) {
			$deleted = $wpdb->query( $wpdb->prepare( 
				"DELETE FROM {$wpdb->prefix}mets_tickets 
				WHERE status = 'closed' AND updated_at < %s",
				$cutoff_date
			) );

			if ( $deleted !== false ) {
				$results['deleted_records'] += $deleted;
				$results['tables_cleaned'][] = 'mets_tickets (closed)';
			} else {
				$results['errors'][] = 'Failed to clean old tickets: ' . $wpdb->last_error;
			}
		}

		return $results;
	}

	/**
	 * Repair corrupted tables
	 *
	 * @since    1.0.0
	 * @return   array    Repair results
	 */
	public function repair_tables() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'mets_tickets',
			$wpdb->prefix . 'mets_ticket_replies',
			$wpdb->prefix . 'mets_entities',
			$wpdb->prefix . 'mets_kb_articles',
			$wpdb->prefix . 'mets_kb_categories'
		);

		$results = array(
			'repaired' => array(),
			'errors' => array()
		);

		foreach ( $tables as $table ) {
			// Check table status
			$check_result = $wpdb->get_row( "CHECK TABLE $table" );
			
			if ( $check_result && $check_result->Msg_text !== 'OK' ) {
				// Table needs repair
				$repair_result = $wpdb->query( "REPAIR TABLE $table" );
				
				if ( $repair_result !== false ) {
					$results['repaired'][] = $table;
				} else {
					$results['errors'][] = "Failed to repair $table: " . $wpdb->last_error;
				}
			}
		}

		return $results;
	}

	/**
	 * Generate database optimization report
	 *
	 * @since    1.0.0
	 * @return   array    Optimization report
	 */
	public function generate_optimization_report() {
		$report = array(
			'timestamp' => time(),
			'table_statistics' => $this->get_table_statistics(),
			'slow_queries' => $this->get_slow_queries(),
			'last_optimization' => get_option( 'mets_last_db_optimization', 0 ),
			'indexes_created' => get_option( 'mets_database_indexes_created', array() ),
			'recommendations' => $this->get_optimization_recommendations()
		);

		return $report;
	}

	/**
	 * Get optimization recommendations
	 *
	 * @since    1.0.0
	 * @return   array    Optimization recommendations
	 */
	private function get_optimization_recommendations() {
		$recommendations = array();
		$stats = $this->get_table_statistics();
		$slow_queries = $this->get_slow_queries();

		// Check for large tables
		foreach ( $stats as $key => $table_stats ) {
			if ( $table_stats['size_mb'] > 100 ) {
				$recommendations[] = array(
					'type' => 'warning',
					'message' => "Table {$table_stats['table']} is large ({$table_stats['size_mb']} MB). Consider archiving old data.",
					'action' => 'archive_old_data'
				);
			}

			if ( $table_stats['free_mb'] > 10 ) {
				$recommendations[] = array(
					'type' => 'info',
					'message' => "Table {$table_stats['table']} has {$table_stats['free_mb']} MB of free space. Consider optimizing.",
					'action' => 'optimize_table'
				);
			}
		}

		// Check for slow queries
		if ( count( $slow_queries ) > 10 ) {
			$recommendations[] = array(
				'type' => 'error',
				'message' => 'Multiple slow queries detected. Review query optimization and indexing.',
				'action' => 'review_indexes'
			);
		}

		// Check last optimization date
		$last_optimization = get_option( 'mets_last_db_optimization', 0 );
		if ( $last_optimization && ( time() - $last_optimization ) > ( 7 * DAY_IN_SECONDS ) ) {
			$recommendations[] = array(
				'type' => 'info',
				'message' => 'Database tables have not been optimized in over a week.',
				'action' => 'optimize_tables'
			);
		}

		return $recommendations;
	}

	/**
	 * Schedule maintenance tasks
	 *
	 * @since    1.0.0
	 */
	private function schedule_maintenance_tasks() {
		// Schedule weekly table optimization
		if ( ! wp_next_scheduled( 'mets_weekly_db_optimization' ) ) {
			wp_schedule_event( time(), 'weekly', 'mets_weekly_db_optimization' );
		}

		// Schedule monthly cleanup
		if ( ! wp_next_scheduled( 'mets_monthly_db_cleanup' ) ) {
			wp_schedule_event( time(), 'monthly', 'mets_monthly_db_cleanup' );
		}
	}

	/**
	 * Run scheduled optimization
	 *
	 * @since    1.0.0
	 */
	public function run_scheduled_optimization() {
		$this->optimize_tables();
		$this->cleanup_old_data( 90 );
	}

	/**
	 * Run scheduled cleanup
	 *
	 * @since    1.0.0
	 */
	public function run_scheduled_cleanup() {
		$this->cleanup_old_data( 180 );
		delete_transient( 'mets_slow_queries' );
	}
}