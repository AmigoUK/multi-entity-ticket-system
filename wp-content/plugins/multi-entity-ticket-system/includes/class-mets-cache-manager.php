<?php
/**
 * Cache Management System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Cache_Manager {

	/**
	 * Cache groups and their default expiration times (in seconds)
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $cache_groups    Cache configuration
	 */
	private static $cache_groups = array(
		'entities' => array(
			'expiration' => 3600, // 1 hour
			'group' => 'mets_entities'
		),
		'tickets' => array(
			'expiration' => 900, // 15 minutes
			'group' => 'mets_tickets'
		),
		'kb_articles' => array(
			'expiration' => 1800, // 30 minutes
			'group' => 'mets_kb'
		),
		'statistics' => array(
			'expiration' => 600, // 10 minutes
			'group' => 'mets_stats'
		),
		'search_results' => array(
			'expiration' => 300, // 5 minutes
			'group' => 'mets_search'
		),
		'user_permissions' => array(
			'expiration' => 3600, // 1 hour
			'group' => 'mets_permissions'
		),
		'reports' => array(
			'expiration' => 1800, // 30 minutes
			'group' => 'mets_reports'
		)
	);

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Cache_Manager    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Cache_Manager    Single instance
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
		// Initialize cache groups
		$this->init_cache_groups();
	}

	/**
	 * Initialize WordPress cache groups
	 *
	 * @since    1.0.0
	 */
	private function init_cache_groups() {
		foreach ( self::$cache_groups as $config ) {
			wp_cache_add_non_persistent_groups( $config['group'] );
		}
	}

	/**
	 * Get cached data with enhanced performance tracking
	 *
	 * @since    1.0.0
	 * @param    string    $key        Cache key
	 * @param    string    $group      Cache group
	 * @return   mixed                 Cached data or false if not found
	 */
	public function get( $key, $group = 'default' ) {
		$cache_key = $this->generate_cache_key( $key );
		$cache_group = $this->get_cache_group( $group );
		
		$start_time = microtime( true );
		$found = false;
		$result = wp_cache_get( $cache_key, $cache_group, false, $found );
		$execution_time = microtime( true ) - $start_time;
		
		// Track cache performance
		$this->track_cache_operation( 'get', $group, $found, $execution_time );
		
		return $result;
	}

	/**
	 * Set cache data
	 *
	 * @since    1.0.0
	 * @param    string    $key           Cache key
	 * @param    mixed     $data          Data to cache
	 * @param    string    $group         Cache group
	 * @param    int       $expiration    Expiration time (optional)
	 * @return   bool                     Success status
	 */
	public function set( $key, $data, $group = 'default', $expiration = null ) {
		$cache_key = $this->generate_cache_key( $key );
		$cache_group = $this->get_cache_group( $group );
		
		if ( null === $expiration ) {
			$expiration = $this->get_cache_expiration( $group );
		}
		
		return wp_cache_set( $cache_key, $data, $cache_group, $expiration );
	}

	/**
	 * Delete cached data
	 *
	 * @since    1.0.0
	 * @param    string    $key      Cache key
	 * @param    string    $group    Cache group
	 * @return   bool                Success status
	 */
	public function delete( $key, $group = 'default' ) {
		$cache_key = $this->generate_cache_key( $key );
		$cache_group = $this->get_cache_group( $group );
		
		return wp_cache_delete( $cache_key, $cache_group );
	}

	/**
	 * Flush cache group
	 *
	 * @since    1.0.0
	 * @param    string    $group    Cache group to flush
	 * @return   bool                Success status
	 */
	public function flush_group( $group ) {
		$cache_group = $this->get_cache_group( $group );
		
		// WordPress doesn't have a native flush group function, so we increment a version
		$version_key = $cache_group . '_version';
		$current_version = wp_cache_get( $version_key, 'mets_versions' );
		$new_version = $current_version ? $current_version + 1 : 1;
		
		return wp_cache_set( $version_key, $new_version, 'mets_versions', 0 );
	}

	/**
	 * Flush all METS caches
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function flush_all() {
		$success = true;
		
		foreach ( array_keys( self::$cache_groups ) as $group ) {
			if ( ! $this->flush_group( $group ) ) {
				$success = false;
			}
		}
		
		return $success;
	}

	/**
	 * Generate cache key with versioning
	 *
	 * @since    1.0.0
	 * @param    string    $key    Original cache key
	 * @return   string            Versioned cache key
	 */
	private function generate_cache_key( $key ) {
		return 'mets_' . md5( $key );
	}

	/**
	 * Get cache group name
	 *
	 * @since    1.0.0
	 * @param    string    $group    Group identifier
	 * @return   string              Cache group name
	 */
	private function get_cache_group( $group ) {
		if ( isset( self::$cache_groups[ $group ] ) ) {
			$cache_group = self::$cache_groups[ $group ]['group'];
			
			// Add versioning for cache invalidation
			$version_key = $cache_group . '_version';
			$version = wp_cache_get( $version_key, 'mets_versions' );
			if ( false === $version ) {
				$version = 1;
				wp_cache_set( $version_key, $version, 'mets_versions', 0 );
			}
			
			return $cache_group . '_v' . $version;
		}
		
		return 'mets_default';
	}

	/**
	 * Get cache expiration time
	 *
	 * @since    1.0.0
	 * @param    string    $group    Group identifier
	 * @return   int                 Expiration time in seconds
	 */
	private function get_cache_expiration( $group ) {
		if ( isset( self::$cache_groups[ $group ] ) ) {
			return self::$cache_groups[ $group ]['expiration'];
		}
		
		return 3600; // Default 1 hour
	}

	/**
	 * Cache wrapper for expensive database queries
	 *
	 * @since    1.0.0
	 * @param    string    $key         Cache key
	 * @param    callable  $callback    Function to execute if cache miss
	 * @param    string    $group       Cache group
	 * @param    int       $expiration  Cache expiration
	 * @return   mixed                  Cached or fresh data
	 */
	public function remember( $key, $callback, $group = 'default', $expiration = null ) {
		$data = $this->get( $key, $group );
		
		if ( false === $data ) {
			$data = call_user_func( $callback );
			$this->set( $key, $data, $group, $expiration );
		}
		
		return $data;
	}

	/**
	 * Get cache statistics
	 *
	 * @since    1.0.0
	 * @return   array    Cache statistics
	 */
	public function get_stats() {
		$stats = array(
			'groups' => array(),
			'total_keys' => 0,
			'memory_usage' => 0
		);
		
		foreach ( self::$cache_groups as $group_key => $config ) {
			$cache_group = $this->get_cache_group( $group_key );
			$group_stats = $this->get_group_stats( $cache_group );
			
			$stats['groups'][ $group_key ] = array(
				'name' => $group_key,
				'group' => $cache_group,
				'expiration' => $config['expiration'],
				'keys' => $group_stats['keys'],
				'memory' => $group_stats['memory']
			);
			
			$stats['total_keys'] += $group_stats['keys'];
			$stats['memory_usage'] += $group_stats['memory'];
		}
		
		return $stats;
	}

	/**
	 * Get statistics for a specific cache group
	 *
	 * @since    1.0.0
	 * @param    string    $cache_group    Cache group name
	 * @return   array                     Group statistics
	 */
	private function get_group_stats( $cache_group ) {
		// This is a simplified implementation as WordPress doesn't provide
		// native cache statistics. In production, you might want to integrate
		// with specific cache backends like Redis or Memcached for detailed stats.
		
		return array(
			'keys' => 0,    // Would need cache backend integration
			'memory' => 0   // Would need cache backend integration
		);
	}

	/**
	 * Warm up cache with frequently accessed data
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function warm_up_cache() {
		global $wpdb;
		
		try {
			// Cache active entities
			$entities = $wpdb->get_results( 
				"SELECT * FROM {$wpdb->prefix}mets_entities WHERE status = 'active' ORDER BY name" 
			);
			$this->set( 'active_entities', $entities, 'entities' );
			
			// Cache entity hierarchy
			$entity_hierarchy = $this->build_entity_hierarchy( $entities );
			$this->set( 'entity_hierarchy', $entity_hierarchy, 'entities' );
			
			// Cache recent tickets count
			$recent_tickets_count = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)" 
			);
			$this->set( 'recent_tickets_count', $recent_tickets_count, 'statistics' );
			
			// Cache KB categories
			$kb_categories = $wpdb->get_results( 
				"SELECT * FROM {$wpdb->prefix}mets_kb_categories ORDER BY name" 
			);
			$this->set( 'kb_categories', $kb_categories, 'kb_articles' );
			
			// Cache published KB articles count
			$published_articles_count = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_articles WHERE status = 'published'" 
			);
			$this->set( 'published_articles_count', $published_articles_count, 'statistics' );
			
			return true;
			
		} catch ( Exception $e ) {
			error_log( 'METS Cache Warm-up Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Build entity hierarchy for caching
	 *
	 * @since    1.0.0
	 * @param    array    $entities    Array of entity objects
	 * @return   array                 Hierarchical entity structure
	 */
	private function build_entity_hierarchy( $entities ) {
		$hierarchy = array();
		$entity_map = array();
		
		// First pass: create entity map
		foreach ( $entities as $entity ) {
			$entity_map[ $entity->id ] = array(
				'entity' => $entity,
				'children' => array()
			);
		}
		
		// Second pass: build hierarchy
		foreach ( $entities as $entity ) {
			if ( $entity->parent_id && isset( $entity_map[ $entity->parent_id ] ) ) {
				$entity_map[ $entity->parent_id ]['children'][] = $entity->id;
			} else {
				$hierarchy[] = $entity->id;
			}
		}
		
		return array(
			'map' => $entity_map,
			'roots' => $hierarchy
		);
	}

	/**
	 * Schedule cache warm-up with advanced scheduling
	 *
	 * @since    1.0.0
	 */
	public function schedule_cache_warmup() {
		// Schedule intelligent cache warmup
		if ( ! wp_next_scheduled( 'mets_intelligent_cache_warmup' ) ) {
			wp_schedule_event( time(), 'hourly', 'mets_intelligent_cache_warmup' );
		}
		
		// Schedule critical cache warmup (more frequent)
		if ( ! wp_next_scheduled( 'mets_critical_cache_warmup' ) ) {
			wp_schedule_event( time(), 'mets_15min', 'mets_critical_cache_warmup' );
		}
		
		// Schedule user-based cache warmup (daily)
		if ( ! wp_next_scheduled( 'mets_user_cache_warmup' ) ) {
			wp_schedule_event( time(), 'daily', 'mets_user_cache_warmup' );
		}
		
		// Add custom cron intervals
		add_filter( 'cron_schedules', array( $this, 'add_cache_cron_intervals' ) );
		
		// Hook the warmup functions
		add_action( 'mets_intelligent_cache_warmup', array( $this, 'intelligent_cache_warmup' ) );
		add_action( 'mets_critical_cache_warmup', array( $this, 'critical_cache_warmup' ) );
		add_action( 'mets_user_cache_warmup', array( $this, 'user_based_cache_warmup' ) );
	}

	/**
	 * Add custom cron intervals for cache warming
	 *
	 * @since    1.0.0
	 * @param    array    $schedules    Existing schedules
	 * @return   array                  Modified schedules
	 */
	public function add_cache_cron_intervals( $schedules ) {
		$schedules['mets_15min'] = array(
			'interval' => 900, // 15 minutes
			'display' => __( 'Every 15 Minutes', METS_TEXT_DOMAIN )
		);
		
		$schedules['mets_5min'] = array(
			'interval' => 300, // 5 minutes
			'display' => __( 'Every 5 Minutes', METS_TEXT_DOMAIN )
		);
		
		return $schedules;
	}

	/**
	 * Critical cache warmup for high-priority data
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function critical_cache_warmup() {
		global $wpdb;
		
		try {
			$start_time = microtime( true );
			$warmed_items = 0;
			
			// Warm up dashboard statistics (most frequently accessed)
			$critical_stats = array(
				'open_tickets_count' => "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE status = 'open'",
				'pending_tickets_count' => "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE status = 'pending'",
				'critical_tickets_count' => "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE priority = 'critical' AND status IN ('open', 'pending')",
				'sla_breached_count' => "SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE (sla_response_breached = 1 OR sla_resolution_breached = 1)"
			);
			
			foreach ( $critical_stats as $stat_key => $query ) {
				$cache_key = 'critical_' . $stat_key;
				if ( $this->get( $cache_key, 'statistics' ) === false ) {
					$result = $wpdb->get_var( $query );
					$this->set( $cache_key, $result, 'statistics', 300 ); // 5 minute cache
					$warmed_items++;
				}
			}
			
			// Warm up active entities (frequently accessed)
			$entities_cache_key = 'active_entities_critical';
			if ( $this->get( $entities_cache_key, 'entities' ) === false ) {
				$entities = $wpdb->get_results( 
					"SELECT id, name, status FROM {$wpdb->prefix}mets_entities WHERE status = 'active' ORDER BY name LIMIT 50" 
				);
				$this->set( $entities_cache_key, $entities, 'entities', 900 ); // 15 minute cache
				$warmed_items++;
			}
			
			// Warm up recent tickets for quick access
			$recent_tickets_key = 'recent_tickets_critical';
			if ( $this->get( $recent_tickets_key, 'tickets' ) === false ) {
				$recent_tickets = $wpdb->get_results( 
					"SELECT id, subject, status, priority, created_at FROM {$wpdb->prefix}mets_tickets 
					ORDER BY created_at DESC LIMIT 20"
				);
				$this->set( $recent_tickets_key, $recent_tickets, 'tickets', 300 ); // 5 minute cache
				$warmed_items++;
			}
			
			$execution_time = microtime( true ) - $start_time;
			
			// Log warmup performance
			$this->log_warmup_performance( 'critical', $warmed_items, $execution_time );
			
			return true;
			
		} catch ( Exception $e ) {
			error_log( 'METS Critical Cache Warmup Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * User-based cache warmup for personalized data
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function user_based_cache_warmup() {
		try {
			$start_time = microtime( true );
			$warmed_items = 0;
			
			// Get users active in the last 7 days
			$active_users = get_users( array(
				'meta_key' => 'last_activity',
				'meta_value' => strtotime( '-7 days' ),
				'meta_compare' => '>',
				'number' => 100
			) );
			
			// If no last_activity meta, fallback to users with session tokens
			if ( empty( $active_users ) ) {
				$active_users = get_users( array(
					'meta_key' => 'session_tokens',
					'meta_compare' => 'EXISTS',
					'number' => 50
				) );
			}
			
			foreach ( $active_users as $user ) {
				$warmed_items += $this->warm_user_specific_cache( $user->ID );
				
				// Prevent overwhelming the system
				if ( $warmed_items > 200 ) {
					break;
				}
				
				// Small delay between users
				usleep( 5000 ); // 5ms delay
			}
			
			$execution_time = microtime( true ) - $start_time;
			$this->log_warmup_performance( 'user_based', $warmed_items, $execution_time );
			
			return true;
			
		} catch ( Exception $e ) {
			error_log( 'METS User-based Cache Warmup Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Warm cache for specific user
	 *
	 * @since    1.0.0
	 * @param    int      $user_id    User ID
	 * @return   int                  Number of items warmed
	 */
	private function warm_user_specific_cache( $user_id ) {
		global $wpdb;
		$warmed = 0;
		
		// Warm user entities
		$user_entities_key = 'user_entities_' . $user_id;
		if ( $this->get( $user_entities_key, 'user_permissions' ) === false ) {
			$user_entities = $wpdb->get_results( $wpdb->prepare(
				"SELECT e.* FROM {$wpdb->prefix}mets_entities e 
				INNER JOIN {$wpdb->prefix}mets_user_entities ue ON e.id = ue.entity_id 
				WHERE ue.user_id = %d AND e.status = 'active'",
				$user_id
			) );
			$this->set( $user_entities_key, $user_entities, 'user_permissions', 3600 );
			$warmed++;
		}
		
		// Warm user tickets
		$user_tickets_key = 'user_tickets_' . $user_id;
		if ( $this->get( $user_tickets_key, 'tickets' ) === false ) {
			$user_tickets = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, subject, status, priority, created_at FROM {$wpdb->prefix}mets_tickets 
				WHERE assigned_to = %d ORDER BY created_at DESC LIMIT 50",
				$user_id
			) );
			$this->set( $user_tickets_key, $user_tickets, 'tickets', 1800 );
			$warmed++;
		}
		
		// Warm user statistics
		$user_stats_key = 'user_stats_' . $user_id;
		if ( $this->get( $user_stats_key, 'statistics' ) === false ) {
			$user_stats = $wpdb->get_row( $wpdb->prepare(
				"SELECT 
					COUNT(*) as total_assigned,
					COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
					COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
					AVG(CASE WHEN resolved_at IS NOT NULL 
						THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_time
				FROM {$wpdb->prefix}mets_tickets WHERE assigned_to = %d",
				$user_id
			), ARRAY_A );
			$this->set( $user_stats_key, $user_stats, 'statistics', 1800 );
			$warmed++;
		}
		
		return $warmed;
	}

	/**
	 * Log cache warmup performance
	 *
	 * @since    1.0.0
	 * @param    string   $type            Warmup type
	 * @param    int      $items_warmed    Number of items warmed
	 * @param    float    $execution_time  Execution time in seconds
	 */
	private function log_warmup_performance( $type, $items_warmed, $execution_time ) {
		$log_entry = array(
			'type' => $type,
			'items_warmed' => $items_warmed,
			'execution_time' => $execution_time,
			'timestamp' => current_time( 'mysql' ),
			'memory_usage' => memory_get_usage( true ),
			'peak_memory' => memory_get_peak_usage( true )
		);
		
		// Store in transient for monitoring
		$warmup_logs = get_transient( 'mets_cache_warmup_logs' ) ?: array();
		array_unshift( $warmup_logs, $log_entry );
		
		// Keep only last 50 entries
		$warmup_logs = array_slice( $warmup_logs, 0, 50 );
		set_transient( 'mets_cache_warmup_logs', $warmup_logs, DAY_IN_SECONDS );
		
		// Log significant performance issues
		if ( $execution_time > 5.0 ) {
			error_log( "METS Cache Warmup: Slow {$type} warmup - {$execution_time}s for {$items_warmed} items" );
		}
	}

	/**
	 * Get cache warmup statistics
	 *
	 * @since    1.0.0
	 * @return   array    Warmup statistics
	 */
	public function get_warmup_stats() {
		$warmup_logs = get_transient( 'mets_cache_warmup_logs' ) ?: array();
		
		if ( empty( $warmup_logs ) ) {
			return array(
				'total_warmups' => 0,
				'avg_items_per_warmup' => 0,
				'avg_execution_time' => 0,
				'types' => array(),
				'recent_activity' => array()
			);
		}
		
		$stats = array(
			'total_warmups' => count( $warmup_logs ),
			'total_items_warmed' => array_sum( array_column( $warmup_logs, 'items_warmed' ) ),
			'avg_items_per_warmup' => 0,
			'avg_execution_time' => 0,
			'types' => array(),
			'recent_activity' => array_slice( $warmup_logs, 0, 10 ),
			'performance_trend' => $this->calculate_warmup_trend( $warmup_logs )
		);
		
		if ( $stats['total_warmups'] > 0 ) {
			$stats['avg_items_per_warmup'] = $stats['total_items_warmed'] / $stats['total_warmups'];
			$stats['avg_execution_time'] = array_sum( array_column( $warmup_logs, 'execution_time' ) ) / $stats['total_warmups'];
		}
		
		// Calculate type breakdown
		foreach ( $warmup_logs as $log ) {
			$type = $log['type'];
			if ( ! isset( $stats['types'][ $type ] ) ) {
				$stats['types'][ $type ] = array(
					'count' => 0,
					'total_items' => 0,
					'total_time' => 0
				);
			}
			
			$stats['types'][ $type ]['count']++;
			$stats['types'][ $type ]['total_items'] += $log['items_warmed'];
			$stats['types'][ $type ]['total_time'] += $log['execution_time'];
		}
		
		// Calculate averages for each type
		foreach ( $stats['types'] as $type => &$type_stats ) {
			$type_stats['avg_items'] = $type_stats['total_items'] / $type_stats['count'];
			$type_stats['avg_time'] = $type_stats['total_time'] / $type_stats['count'];
		}
		
		return $stats;
	}

	/**
	 * Calculate warmup performance trend
	 *
	 * @since    1.0.0
	 * @param    array    $logs    Warmup logs
	 * @return   array             Trend data
	 */
	private function calculate_warmup_trend( $logs ) {
		if ( count( $logs ) < 10 ) {
			return array( 'trend' => 'insufficient_data' );
		}
		
		// Compare recent performance to historical
		$recent_logs = array_slice( $logs, 0, 5 );
		$historical_logs = array_slice( $logs, 5, 10 );
		
		$recent_avg_time = array_sum( array_column( $recent_logs, 'execution_time' ) ) / count( $recent_logs );
		$historical_avg_time = array_sum( array_column( $historical_logs, 'execution_time' ) ) / count( $historical_logs );
		
		$performance_change = ( $recent_avg_time - $historical_avg_time ) / $historical_avg_time * 100;
		
		if ( $performance_change > 20 ) {
			$trend = 'degrading';
		} elseif ( $performance_change < -20 ) {
			$trend = 'improving';
		} else {
			$trend = 'stable';
		}
		
		return array(
			'trend' => $trend,
			'change_percentage' => round( $performance_change, 2 ),
			'recent_avg_time' => $recent_avg_time,
			'historical_avg_time' => $historical_avg_time
		);
	}

	/**
	 * Clear expired cache entries
	 *
	 * @since    1.0.0
	 */
	public function clear_expired_cache() {
		// WordPress handles cache expiration automatically,
		// but we can clean up our version tracking
		foreach ( array_keys( self::$cache_groups ) as $group ) {
			$cache_group = self::$cache_groups[ $group ]['group'];
			$version_key = $cache_group . '_version';
			
			// Reset version if it gets too high (prevent integer overflow)
			$version = wp_cache_get( $version_key, 'mets_versions' );
			if ( $version && $version > 1000000 ) {
				wp_cache_set( $version_key, 1, 'mets_versions', 0 );
			}
		}
	}

	/**
	 * Track cache operation performance
	 *
	 * @since    1.0.0
	 * @param    string    $operation      Operation type (get, set, delete)
	 * @param    string    $group          Cache group
	 * @param    bool      $hit            Whether it was a cache hit
	 * @param    float     $execution_time Execution time in seconds
	 */
	private function track_cache_operation( $operation, $group, $hit, $execution_time ) {
		$stats_key = 'mets_cache_performance_' . date( 'Y-m-d-H' );
		$stats = wp_cache_get( $stats_key, 'mets_performance' );
		
		if ( ! $stats ) {
			$stats = array(
				'operations' => array(),
				'hit_ratio' => array(),
				'avg_time' => array()
			);
		}
		
		// Track operation
		if ( ! isset( $stats['operations'][ $operation ] ) ) {
			$stats['operations'][ $operation ] = 0;
		}
		$stats['operations'][ $operation ]++;
		
		// Track hit ratio for get operations
		if ( $operation === 'get' ) {
			if ( ! isset( $stats['hit_ratio'][ $group ] ) ) {
				$stats['hit_ratio'][ $group ] = array( 'hits' => 0, 'misses' => 0 );
			}
			
			if ( $hit ) {
				$stats['hit_ratio'][ $group ]['hits']++;
			} else {
				$stats['hit_ratio'][ $group ]['misses']++;
			}
		}
		
		// Track average execution time
		if ( ! isset( $stats['avg_time'][ $operation ] ) ) {
			$stats['avg_time'][ $operation ] = array( 'total' => 0, 'count' => 0 );
		}
		$stats['avg_time'][ $operation ]['total'] += $execution_time;
		$stats['avg_time'][ $operation ]['count']++;
		
		// Cache for 1 hour
		wp_cache_set( $stats_key, $stats, 'mets_performance', 3600 );
	}

	/**
	 * Enhanced database query caching with intelligent invalidation
	 *
	 * @since    1.0.0
	 * @param    string    $sql           SQL query
	 * @param    string    $cache_key     Cache key
	 * @param    string    $group         Cache group
	 * @param    int       $expiration    Cache expiration
	 * @return   mixed                    Query results
	 */
	public function cache_query( $sql, $cache_key = null, $group = 'database', $expiration = null ) {
		global $wpdb;
		
		// Generate cache key if not provided
		if ( ! $cache_key ) {
			$cache_key = 'query_' . md5( $sql . serialize( $wpdb->last_query_time ) );
		}
		
		// Try to get from cache first
		$cached_result = $this->get( $cache_key, $group );
		if ( $cached_result !== false ) {
			return $cached_result;
		}
		
		// Execute query and time it
		$start_time = microtime( true );
		$result = $wpdb->get_results( $sql );
		$execution_time = microtime( true ) - $start_time;
		
		// Log slow queries
		if ( $execution_time > 0.5 ) {
			error_log( "METS Slow Query ({$execution_time}s): " . substr( $sql, 0, 200 ) . '...' );
		}
		
		// Cache the result
		if ( $result !== false && ! $wpdb->last_error ) {
			$this->set( $cache_key, $result, $group, $expiration );
		}
		
		return $result;
	}

	/**
	 * Batch cache operations for better performance
	 *
	 * @since    1.0.0
	 * @param    array     $operations    Array of cache operations
	 * @return   array                    Results of operations
	 */
	public function batch_operations( $operations ) {
		$results = array();
		$get_keys = array();
		$set_operations = array();
		
		// Separate get and set operations
		foreach ( $operations as $index => $operation ) {
			if ( $operation['type'] === 'get' ) {
				$get_keys[ $index ] = array(
					'key' => $this->generate_cache_key( $operation['key'] ),
					'group' => $this->get_cache_group( $operation['group'] )
				);
			} else {
				$set_operations[ $index ] = $operation;
			}
		}
		
		// Perform batch get operations if supported
		if ( ! empty( $get_keys ) && function_exists( 'wp_cache_get_multiple' ) ) {
			$keys_by_group = array();
			foreach ( $get_keys as $index => $key_info ) {
				$keys_by_group[ $key_info['group'] ][ $index ] = $key_info['key'];
			}
			
			foreach ( $keys_by_group as $group => $keys ) {
				$group_results = wp_cache_get_multiple( array_values( $keys ), $group );
				foreach ( $keys as $index => $cache_key ) {
					$original_key = array_search( $cache_key, $keys );
					$results[ $index ] = isset( $group_results[ $cache_key ] ) ? $group_results[ $cache_key ] : false;
				}
			}
		} else {
			// Fallback to individual gets
			foreach ( $get_keys as $index => $key_info ) {
				$results[ $index ] = wp_cache_get( $key_info['key'], $key_info['group'] );
			}
		}
		
		// Perform set operations
		foreach ( $set_operations as $index => $operation ) {
			$results[ $index ] = $this->set( 
				$operation['key'], 
				$operation['data'], 
				$operation['group'], 
				$operation['expiration'] ?? null 
			);
		}
		
		return $results;
	}

	/**
	 * Smart cache warming based on usage patterns
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function intelligent_cache_warmup() {
		global $wpdb;
		
		try {
			// Get most frequently accessed data based on logs
			$popular_queries = $this->get_popular_queries();
			
			foreach ( $popular_queries as $query_info ) {
				$cache_key = 'popular_' . md5( $query_info['query'] );
				
				// Check if already cached
				if ( $this->get( $cache_key, 'database' ) === false ) {
					// Warm up the cache
					$this->cache_query( $query_info['query'], $cache_key, 'database' );
					
					// Add small delay to prevent overwhelming the database
					usleep( 10000 ); // 10ms delay
				}
			}
			
			// Warm up user-specific caches for active users
			$this->warmup_user_caches();
			
			// Pre-calculate dashboard statistics
			$this->warmup_dashboard_stats();
			
			return true;
			
		} catch ( Exception $e ) {
			error_log( 'METS Intelligent Cache Warmup Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get popular queries for cache warming
	 *
	 * @since    1.0.0
	 * @return   array    Popular queries
	 */
	private function get_popular_queries() {
		// In a production environment, you would track query frequency
		// For now, return commonly used queries
		return array(
			array( 'query' => "SELECT * FROM {$GLOBALS['wpdb']->prefix}mets_entities WHERE status = 'active'" ),
			array( 'query' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}mets_tickets WHERE status = 'open'" ),
			array( 'query' => "SELECT * FROM {$GLOBALS['wpdb']->prefix}mets_kb_articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 10" )
		);
	}

	/**
	 * Warm up user-specific caches
	 *
	 * @since    1.0.0
	 */
	private function warmup_user_caches() {
		// Get active users (logged in within last 24 hours)
		$active_users = get_users( array(
			'meta_key' => 'session_tokens',
			'meta_compare' => 'EXISTS',
			'number' => 50 // Limit to top 50 active users
		) );
		
		foreach ( $active_users as $user ) {
			// Warm up user permissions
			$user_entities_key = 'user_entities_' . $user->ID;
			if ( $this->get( $user_entities_key, 'user_permissions' ) === false ) {
				// This would typically call a method to get user entities
				$user_entities = array(); // Placeholder
				$this->set( $user_entities_key, $user_entities, 'user_permissions' );
			}
		}
	}

	/**
	 * Warm up dashboard statistics
	 *
	 * @since    1.0.0
	 */
	private function warmup_dashboard_stats() {
		$dashboard_stats = array(
			'total_tickets' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}mets_tickets",
			'open_tickets' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}mets_tickets WHERE status = 'open'",
			'resolved_today' => "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}mets_tickets WHERE status = 'resolved' AND DATE(resolved_at) = CURDATE()"
		);
		
		foreach ( $dashboard_stats as $stat_key => $query ) {
			$cache_key = 'dashboard_' . $stat_key;
			if ( $this->get( $cache_key, 'statistics' ) === false ) {
				$this->cache_query( $query, $cache_key, 'statistics', 600 ); // 10 minute cache
			}
		}
	}

	/**
	 * Get comprehensive cache performance report
	 *
	 * @since    1.0.0
	 * @return   array    Performance report
	 */
	public function get_performance_report() {
		global $wp_object_cache;
		
		$report = array(
			'backend_info' => array(),
			'hit_ratios' => array(),
			'operation_stats' => array(),
			'memory_usage' => array(),
			'recommendations' => array()
		);
		
		// Get backend information
		if ( isset( $wp_object_cache ) && method_exists( $wp_object_cache, 'stats' ) ) {
			$report['backend_info'] = $wp_object_cache->stats();
		}
		
		// Calculate hit ratios per group
		$current_hour_stats = wp_cache_get( 'mets_cache_performance_' . date( 'Y-m-d-H' ), 'mets_performance' );
		if ( $current_hour_stats && isset( $current_hour_stats['hit_ratio'] ) ) {
			foreach ( $current_hour_stats['hit_ratio'] as $group => $stats ) {
				$total = $stats['hits'] + $stats['misses'];
				$report['hit_ratios'][ $group ] = $total > 0 ? ( $stats['hits'] / $total ) * 100 : 0;
			}
		}
		
		// Add performance recommendations
		$report['recommendations'] = $this->generate_performance_recommendations( $report );
		
		return $report;
	}

	/**
	 * Generate performance recommendations
	 *
	 * @since    1.0.0
	 * @param    array     $report    Current performance report
	 * @return   array                Recommendations
	 */
	private function generate_performance_recommendations( $report ) {
		$recommendations = array();
		
		// Check hit ratios
		if ( ! empty( $report['hit_ratios'] ) ) {
			foreach ( $report['hit_ratios'] as $group => $ratio ) {
				if ( $ratio < 70 ) {
					$recommendations[] = "Consider increasing cache expiration time for '{$group}' group (current hit ratio: {$ratio}%)";
				}
			}
		}
		
		// Check backend type
		if ( isset( $report['backend_info']['backend'] ) && $report['backend_info']['backend'] === 'default' ) {
			$recommendations[] = 'Consider setting up Redis or Memcached for better caching performance';
		}
		
		return $recommendations;
	}
}