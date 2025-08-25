<?php
/**
 * Advanced Cache Invalidation Strategies
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Cache_Invalidation {

	/**
	 * Cache dependency map
	 */
	private $dependency_map = array();
	
	/**
	 * Invalidation queue for batch processing
	 */
	private $invalidation_queue = array();
	
	/**
	 * Cache manager instance
	 */
	private $cache_manager;
	
	/**
	 * CDN manager instance
	 */
	private $cdn_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cache_manager = METS_Cache_Manager::get_instance();
		$this->cdn_manager = new METS_CDN_Manager();
		$this->init_dependency_map();
		$this->init_hooks();
	}

	/**
	 * Initialize dependency mapping
	 */
	private function init_dependency_map() {
		$this->dependency_map = array(
			'ticket' => array(
				'cache_groups' => array( 'tickets', 'statistics', 'search_results' ),
				'cache_keys' => array(
					'tickets_*',
					'dashboard_*',
					'user_tickets_*',
					'entity_tickets_*',
					'ticket_stats_*'
				),
				'cdn_paths' => array(
					'/tickets/',
					'/dashboard/',
					'/api/tickets/',
					'/search/'
				),
				'dependent_entities' => array( 'entity', 'user', 'sla_rule' )
			),
			'entity' => array(
				'cache_groups' => array( 'entities', 'user_permissions', 'tickets', 'statistics' ),
				'cache_keys' => array(
					'entities_*',
					'entity_hierarchy_*',
					'user_entities_*',
					'entity_tickets_*'
				),
				'cdn_paths' => array(
					'/entities/',
					'/dashboard/',
					'/api/entities/'
				),
				'dependent_entities' => array( 'ticket', 'user', 'kb_article' )
			),
			'kb_article' => array(
				'cache_groups' => array( 'kb_articles', 'search_results', 'statistics' ),
				'cache_keys' => array(
					'kb_articles_*',
					'kb_categories_*',
					'published_articles_*',
					'article_search_*'
				),
				'cdn_paths' => array(
					'/knowledge-base/',
					'/kb/',
					'/api/kb/',
					'/search/'
				),
				'dependent_entities' => array( 'entity', 'category' )
			),
			'user' => array(
				'cache_groups' => array( 'user_permissions', 'tickets', 'statistics' ),
				'cache_keys' => array(
					'user_entities_*',
					'user_tickets_*',
					'user_permissions_*',
					'agent_stats_*'
				),
				'cdn_paths' => array(
					'/dashboard/',
					'/profile/',
					'/api/users/'
				),
				'dependent_entities' => array( 'ticket', 'entity' )
			),
			'sla_rule' => array(
				'cache_groups' => array( 'tickets', 'statistics', 'reports' ),
				'cache_keys' => array(
					'sla_rules_*',
					'sla_calculations_*',
					'sla_reports_*'
				),
				'cdn_paths' => array(
					'/reports/',
					'/api/sla/'
				),
				'dependent_entities' => array( 'ticket', 'entity' )
			),
			'automation_rule' => array(
				'cache_groups' => array( 'tickets', 'entities' ),
				'cache_keys' => array(
					'automation_rules_*',
					'rule_conditions_*'
				),
				'cdn_paths' => array(
					'/automation/',
					'/api/automation/'
				),
				'dependent_entities' => array( 'ticket', 'entity' )
			)
		);
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Ticket invalidation hooks
		add_action( 'mets_ticket_created', array( $this, 'invalidate_ticket_cache' ), 10, 2 );
		add_action( 'mets_ticket_updated', array( $this, 'invalidate_ticket_cache' ), 10, 3 );
		add_action( 'mets_ticket_deleted', array( $this, 'invalidate_ticket_cache' ), 10, 1 );
		add_action( 'mets_ticket_status_changed', array( $this, 'invalidate_status_change_cache' ), 10, 4 );
		
		// Entity invalidation hooks
		add_action( 'mets_entity_created', array( $this, 'invalidate_entity_cache' ), 10, 2 );
		add_action( 'mets_entity_updated', array( $this, 'invalidate_entity_cache' ), 10, 3 );
		add_action( 'mets_entity_deleted', array( $this, 'invalidate_entity_cache' ), 10, 1 );
		
		// KB Article invalidation hooks
		add_action( 'mets_kb_article_published', array( $this, 'invalidate_kb_cache' ), 10, 2 );
		add_action( 'mets_kb_article_updated', array( $this, 'invalidate_kb_cache' ), 10, 3 );
		add_action( 'mets_kb_article_deleted', array( $this, 'invalidate_kb_cache' ), 10, 1 );
		
		// User permission changes
		add_action( 'mets_user_entity_added', array( $this, 'invalidate_user_cache' ), 10, 2 );
		add_action( 'mets_user_entity_removed', array( $this, 'invalidate_user_cache' ), 10, 2 );
		add_action( 'mets_user_role_changed', array( $this, 'invalidate_user_cache' ), 10, 3 );
		
		// SLA and automation rule changes
		add_action( 'mets_sla_rule_updated', array( $this, 'invalidate_sla_cache' ), 10, 2 );
		add_action( 'mets_automation_rule_updated', array( $this, 'invalidate_automation_cache' ), 10, 2 );
		
		// Batch processing
		add_action( 'wp_shutdown', array( $this, 'process_invalidation_queue' ) );
		
		// Scheduled cleanup
		add_action( 'mets_cache_cleanup', array( $this, 'cleanup_expired_invalidation_logs' ) );
	}

	/**
	 * Invalidate ticket-related cache
	 */
	public function invalidate_ticket_cache( $ticket_id, $ticket_data = null, $old_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'ticket',
			'entity_id' => $ticket_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => array(
				'new' => $ticket_data,
				'old' => $old_data
			)
		);
		
		// Add to queue for batch processing
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Immediate critical cache clearing
		$this->clear_critical_ticket_cache( $ticket_id, $ticket_data );
	}

	/**
	 * Clear critical ticket cache immediately
	 */
	private function clear_critical_ticket_cache( $ticket_id, $ticket_data = null ) {
		// Clear specific ticket cache
		$this->cache_manager->delete( "ticket_{$ticket_id}", 'tickets' );
		
		// Clear user-specific caches if we have user data
		if ( $ticket_data ) {
			$assigned_to = is_array( $ticket_data ) ? $ticket_data['assigned_to'] ?? null : $ticket_data->assigned_to ?? null;
			$entity_id = is_array( $ticket_data ) ? $ticket_data['entity_id'] ?? null : $ticket_data->entity_id ?? null;
			
			if ( $assigned_to ) {
				$this->cache_manager->delete( "user_tickets_{$assigned_to}", 'tickets' );
			}
			
			if ( $entity_id ) {
				$this->cache_manager->delete( "entity_tickets_{$entity_id}", 'tickets' );
			}
		}
		
		// Clear dashboard statistics
		$this->cache_manager->flush_group( 'statistics' );
	}

	/**
	 * Handle status change cache invalidation
	 */
	public function invalidate_status_change_cache( $ticket_id, $old_status, $new_status, $user_id ) {
		$invalidation_data = array(
			'entity_type' => 'ticket_status_change',
			'entity_id' => $ticket_id,
			'trigger' => 'status_change',
			'timestamp' => current_time( 'timestamp' ),
			'data' => array(
				'old_status' => $old_status,
				'new_status' => $new_status,
				'user_id' => $user_id
			)
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Immediate status-specific cache clearing
		$this->clear_status_specific_cache( $old_status, $new_status );
	}

	/**
	 * Clear status-specific cache
	 */
	private function clear_status_specific_cache( $old_status, $new_status ) {
		$statuses_to_clear = array_unique( array( $old_status, $new_status ) );
		
		foreach ( $statuses_to_clear as $status ) {
			$this->cache_manager->delete( "tickets_status_{$status}", 'tickets' );
			$this->cache_manager->delete( "dashboard_stats_{$status}", 'statistics' );
		}
		
		// Clear reports that depend on status
		$this->cache_manager->flush_group( 'reports' );
	}

	/**
	 * Invalidate entity-related cache
	 */
	public function invalidate_entity_cache( $entity_id, $entity_data = null, $old_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'entity',
			'entity_id' => $entity_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => array(
				'new' => $entity_data,
				'old' => $old_data
			)
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Clear entity hierarchy cache immediately
		$this->cache_manager->delete( 'entity_hierarchy', 'entities' );
		$this->cache_manager->delete( 'active_entities', 'entities' );
		
		// Clear user permissions that depend on this entity
		$this->clear_entity_dependent_permissions( $entity_id );
	}

	/**
	 * Clear entity-dependent permissions
	 */
	private function clear_entity_dependent_permissions( $entity_id ) {
		global $wpdb;
		
		// Get all users associated with this entity
		$users = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$wpdb->prefix}mets_user_entities WHERE entity_id = %d",
			$entity_id
		) );
		
		foreach ( $users as $user_id ) {
			$this->cache_manager->delete( "user_entities_{$user_id}", 'user_permissions' );
		}
	}

	/**
	 * Invalidate KB article cache
	 */
	public function invalidate_kb_cache( $article_id, $article_data = null, $old_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'kb_article',
			'entity_id' => $article_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => array(
				'new' => $article_data,
				'old' => $old_data
			)
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Clear KB-specific caches
		$this->cache_manager->delete( "kb_article_{$article_id}", 'kb_articles' );
		$this->cache_manager->delete( 'published_articles_count', 'statistics' );
		
		// Clear search caches
		$this->cache_manager->flush_group( 'search_results' );
	}

	/**
	 * Invalidate user-related cache
	 */
	public function invalidate_user_cache( $user_id, $entity_id = null, $role_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'user',
			'entity_id' => $user_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => array(
				'entity_id' => $entity_id,
				'role_data' => $role_data
			)
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Clear user-specific caches immediately
		$this->cache_manager->delete( "user_entities_{$user_id}", 'user_permissions' );
		$this->cache_manager->delete( "user_tickets_{$user_id}", 'tickets' );
		$this->cache_manager->delete( "agent_stats_{$user_id}", 'statistics' );
	}

	/**
	 * Invalidate SLA rule cache
	 */
	public function invalidate_sla_cache( $rule_id, $rule_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'sla_rule',
			'entity_id' => $rule_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => $rule_data
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Clear SLA-related caches
		$this->cache_manager->delete( "sla_rule_{$rule_id}", 'tickets' );
		$this->cache_manager->flush_group( 'reports' );
		
		// SLA changes can affect many tickets, so clear ticket statistics
		$this->cache_manager->flush_group( 'statistics' );
	}

	/**
	 * Invalidate automation rule cache
	 */
	public function invalidate_automation_cache( $rule_id, $rule_data = null ) {
		$invalidation_data = array(
			'entity_type' => 'automation_rule',
			'entity_id' => $rule_id,
			'trigger' => current_action(),
			'timestamp' => current_time( 'timestamp' ),
			'data' => $rule_data
		);
		
		$this->add_to_invalidation_queue( $invalidation_data );
		
		// Clear automation-related caches
		$this->cache_manager->delete( "automation_rule_{$rule_id}", 'tickets' );
		$this->cache_manager->delete( 'active_automation_rules', 'tickets' );
	}

	/**
	 * Add invalidation to queue for batch processing
	 */
	private function add_to_invalidation_queue( $invalidation_data ) {
		$this->invalidation_queue[] = $invalidation_data;
	}

	/**
	 * Process the invalidation queue
	 */
	public function process_invalidation_queue() {
		if ( empty( $this->invalidation_queue ) ) {
			return;
		}
		
		// Group invalidations by entity type for efficient processing
		$grouped_invalidations = array();
		foreach ( $this->invalidation_queue as $invalidation ) {
			$entity_type = $invalidation['entity_type'];
			$grouped_invalidations[ $entity_type ][] = $invalidation;
		}
		
		// Process each entity type
		foreach ( $grouped_invalidations as $entity_type => $invalidations ) {
			$this->process_entity_invalidations( $entity_type, $invalidations );
		}
		
		// Clear the queue
		$this->invalidation_queue = array();
		
		// Log the batch invalidation
		$this->log_invalidation_batch( $grouped_invalidations );
	}

	/**
	 * Process invalidations for a specific entity type
	 */
	private function process_entity_invalidations( $entity_type, $invalidations ) {
		if ( ! isset( $this->dependency_map[ $entity_type ] ) ) {
			return;
		}
		
		$config = $this->dependency_map[ $entity_type ];
		$entity_ids = array_unique( array_column( $invalidations, 'entity_id' ) );
		
		// Clear cache groups
		foreach ( $config['cache_groups'] as $group ) {
			$this->clear_group_patterns( $group, $config['cache_keys'] );
		}
		
		// Clear CDN cache
		if ( ! empty( $config['cdn_paths'] ) ) {
			$this->cdn_manager->purge_cdn_cache( $config['cdn_paths'] );
		}
		
		// Handle dependent entities
		$this->process_dependent_entities( $entity_type, $entity_ids, $config['dependent_entities'] ?? array() );
	}

	/**
	 * Clear cache patterns for a group
	 */
	private function clear_group_patterns( $group, $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( strpos( $pattern, '*' ) !== false ) {
				// Pattern-based clearing (simplified - in production you'd need more sophisticated pattern matching)
				$base_pattern = str_replace( '*', '', $pattern );
				$this->clear_pattern_cache( $base_pattern, $group );
			} else {
				// Direct key clearing
				$this->cache_manager->delete( $pattern, $group );
			}
		}
	}

	/**
	 * Clear cache using pattern matching
	 */
	private function clear_pattern_cache( $pattern, $group ) {
		// This is a simplified implementation
		// In production, you'd need a way to enumerate cache keys or use Redis/Memcached pattern deletion
		$common_variations = array(
			$pattern . 'list',
			$pattern . 'count',
			$pattern . 'active',
			$pattern . 'recent',
			$pattern . 'summary'
		);
		
		foreach ( $common_variations as $key ) {
			$this->cache_manager->delete( $key, $group );
		}
	}

	/**
	 * Process dependent entity invalidations
	 */
	private function process_dependent_entities( $source_type, $entity_ids, $dependent_types ) {
		foreach ( $dependent_types as $dependent_type ) {
			$this->invalidate_dependent_cache( $source_type, $entity_ids, $dependent_type );
		}
	}

	/**
	 * Invalidate cache for dependent entities
	 */
	private function invalidate_dependent_cache( $source_type, $entity_ids, $dependent_type ) {
		// This would contain logic to find dependent entities and invalidate their cache
		// For example, if a ticket is updated, we might need to invalidate entity statistics
		
		switch ( $source_type ) {
			case 'ticket':
				if ( $dependent_type === 'entity' ) {
					$this->invalidate_entity_statistics_for_tickets( $entity_ids );
				}
				break;
				
			case 'entity':
				if ( $dependent_type === 'ticket' ) {
					$this->invalidate_ticket_caches_for_entities( $entity_ids );
				}
				break;
		}
	}

	/**
	 * Invalidate entity statistics when tickets change
	 */
	private function invalidate_entity_statistics_for_tickets( $ticket_ids ) {
		global $wpdb;
		
		// Get affected entities
		$placeholders = implode( ',', array_fill( 0, count( $ticket_ids ), '%d' ) );
		$entity_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT entity_id FROM {$wpdb->prefix}mets_tickets WHERE id IN ($placeholders)",
			...$ticket_ids
		) );
		
		// Clear entity statistics
		foreach ( $entity_ids as $entity_id ) {
			$this->cache_manager->delete( "entity_stats_{$entity_id}", 'statistics' );
		}
	}

	/**
	 * Invalidate ticket caches when entities change
	 */
	private function invalidate_ticket_caches_for_entities( $entity_ids ) {
		foreach ( $entity_ids as $entity_id ) {
			$this->cache_manager->delete( "entity_tickets_{$entity_id}", 'tickets' );
		}
	}

	/**
	 * Log invalidation batch for debugging and monitoring
	 */
	private function log_invalidation_batch( $grouped_invalidations ) {
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'batch_size' => array_sum( array_map( 'count', $grouped_invalidations ) ),
			'entity_types' => array_keys( $grouped_invalidations ),
			'processing_time' => microtime( true ) - ( $_SERVER['REQUEST_TIME_FLOAT'] ?? time() )
		);
		
		// Store in transient for monitoring dashboard
		$recent_invalidations = get_transient( 'mets_recent_invalidations' ) ?: array();
		array_unshift( $recent_invalidations, $log_entry );
		
		// Keep only last 100 entries
		$recent_invalidations = array_slice( $recent_invalidations, 0, 100 );
		set_transient( 'mets_recent_invalidations', $recent_invalidations, DAY_IN_SECONDS );
		
		// Log significant batches
		if ( $log_entry['batch_size'] > 10 ) {
			error_log( "METS Cache: Large invalidation batch processed - {$log_entry['batch_size']} items in {$log_entry['processing_time']}s" );
		}
	}

	/**
	 * Get invalidation statistics
	 */
	public function get_invalidation_stats() {
		$recent_invalidations = get_transient( 'mets_recent_invalidations' ) ?: array();
		
		$stats = array(
			'total_batches' => count( $recent_invalidations ),
			'total_invalidations' => array_sum( array_column( $recent_invalidations, 'batch_size' ) ),
			'avg_batch_size' => 0,
			'avg_processing_time' => 0,
			'entity_type_breakdown' => array(),
			'recent_activity' => array_slice( $recent_invalidations, 0, 10 )
		);
		
		if ( $stats['total_batches'] > 0 ) {
			$stats['avg_batch_size'] = $stats['total_invalidations'] / $stats['total_batches'];
			$stats['avg_processing_time'] = array_sum( array_column( $recent_invalidations, 'processing_time' ) ) / $stats['total_batches'];
		}
		
		// Calculate entity type breakdown
		foreach ( $recent_invalidations as $batch ) {
			foreach ( $batch['entity_types'] as $type ) {
				$stats['entity_type_breakdown'][ $type ] = ( $stats['entity_type_breakdown'][ $type ] ?? 0 ) + 1;
			}
		}
		
		return $stats;
	}

	/**
	 * Cleanup expired invalidation logs
	 */
	public function cleanup_expired_invalidation_logs() {
		// This runs daily to prevent the invalidation log from growing too large
		$recent_invalidations = get_transient( 'mets_recent_invalidations' ) ?: array();
		
		// Remove entries older than 7 days
		$cutoff_time = strtotime( '-7 days' );
		$recent_invalidations = array_filter( $recent_invalidations, function( $entry ) use ( $cutoff_time ) {
			return strtotime( $entry['timestamp'] ) > $cutoff_time;
		} );
		
		set_transient( 'mets_recent_invalidations', $recent_invalidations, DAY_IN_SECONDS );
	}

	/**
	 * Force invalidate all cache for specific entity type
	 */
	public function force_invalidate_entity_type( $entity_type ) {
		if ( ! isset( $this->dependency_map[ $entity_type ] ) ) {
			return false;
		}
		
		$config = $this->dependency_map[ $entity_type ];
		
		// Clear all cache groups
		foreach ( $config['cache_groups'] as $group ) {
			$this->cache_manager->flush_group( $group );
		}
		
		// Clear CDN cache
		if ( ! empty( $config['cdn_paths'] ) ) {
			$this->cdn_manager->purge_cdn_cache( $config['cdn_paths'] );
		}
		
		return true;
	}

	/**
	 * Get cache invalidation recommendations
	 */
	public function get_invalidation_recommendations() {
		$stats = $this->get_invalidation_stats();
		$recommendations = array();
		
		// Check for excessive invalidations
		if ( $stats['avg_batch_size'] > 50 ) {
			$recommendations[] = array(
				'type' => 'warning',
				'message' => 'High cache invalidation volume detected. Consider optimizing cache dependencies.',
				'metric' => 'avg_batch_size',
				'value' => $stats['avg_batch_size']
			);
		}
		
		// Check processing time
		if ( $stats['avg_processing_time'] > 0.1 ) {
			$recommendations[] = array(
				'type' => 'info',
				'message' => 'Cache invalidation processing time is elevated. Consider batch optimization.',
				'metric' => 'avg_processing_time',
				'value' => $stats['avg_processing_time']
			);
		}
		
		// Check entity type distribution
		if ( ! empty( $stats['entity_type_breakdown'] ) ) {
			$max_type = array_keys( $stats['entity_type_breakdown'], max( $stats['entity_type_breakdown'] ) )[0];
			$max_count = $stats['entity_type_breakdown'][ $max_type ];
			
			if ( $max_count > $stats['total_batches'] * 0.7 ) {
				$recommendations[] = array(
					'type' => 'optimization',
					'message' => "Most invalidations are for '{$max_type}' entities. Consider optimizing this cache pattern.",
					'metric' => 'entity_distribution',
					'value' => array( 'type' => $max_type, 'percentage' => ( $max_count / $stats['total_batches'] ) * 100 )
				);
			}
		}
		
		return $recommendations;
	}
}