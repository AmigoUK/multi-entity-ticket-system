<?php
/**
 * KB Analytics Model
 *
 * Handles all database operations for KB analytics and performance tracking
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * The KB Analytics Model class.
 *
 * This class defines the model for managing KB analytics data and generating performance reports.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Analytics_Model {

	/**
	 * The analytics database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $analytics_table    The analytics database table name
	 */
	private $analytics_table;

	/**
	 * The search log database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $search_table    The search log database table name
	 */
	private $search_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->analytics_table = $wpdb->prefix . 'mets_kb_analytics';
		$this->search_table = $wpdb->prefix . 'mets_kb_search_log';
	}

	/**
	 * Record an analytics event
	 *
	 * @since    1.0.0
	 * @param    array    $data    Event data
	 * @return   int|false         Event ID on success, false on failure
	 */
	public function record_event( $data ) {
		global $wpdb;

		$defaults = array(
			'article_id' => 0,
			'user_id' => get_current_user_id() ?: null,
			'session_id' => $this->get_session_id(),
			'action' => 'view',
			'ip_address' => $this->get_client_ip(),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
			'search_query' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields
		if ( empty( $data['article_id'] ) || empty( $data['action'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->analytics_table,
			array(
				'article_id' => absint( $data['article_id'] ),
				'user_id' => $data['user_id'] ? absint( $data['user_id'] ) : null,
				'session_id' => sanitize_text_field( $data['session_id'] ),
				'action' => sanitize_text_field( $data['action'] ),
				'ip_address' => sanitize_text_field( $data['ip_address'] ),
				'user_agent' => sanitize_text_field( $data['user_agent'] ),
				'referrer' => esc_url_raw( $data['referrer'] ),
				'search_query' => sanitize_text_field( $data['search_query'] ),
			),
			array(
				'%d', // article_id
				'%d', // user_id
				'%s', // session_id
				'%s', // action
				'%s', // ip_address
				'%s', // user_agent
				'%s', // referrer
				'%s', // search_query
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Log a search query
	 *
	 * @since    1.0.0
	 * @param    array    $data    Search data
	 * @return   int|false         Log ID on success, false on failure
	 */
	public function log_search( $data ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'user_id' => get_current_user_id() ?: null,
			'query' => '',
			'results_count' => 0,
			'clicked_article_id' => null,
			'session_id' => $this->get_session_id(),
			'ip_address' => $this->get_client_ip(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['query'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->search_table,
			array(
				'entity_id' => $data['entity_id'] ? absint( $data['entity_id'] ) : null,
				'user_id' => $data['user_id'] ? absint( $data['user_id'] ) : null,
				'query' => sanitize_text_field( $data['query'] ),
				'results_count' => absint( $data['results_count'] ),
				'clicked_article_id' => $data['clicked_article_id'] ? absint( $data['clicked_article_id'] ) : null,
				'session_id' => sanitize_text_field( $data['session_id'] ),
				'ip_address' => sanitize_text_field( $data['ip_address'] ),
			),
			array(
				'%d', // entity_id
				'%d', // user_id
				'%s', // query
				'%d', // results_count
				'%d', // clicked_article_id
				'%s', // session_id
				'%s', // ip_address
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get article performance statistics
	 *
	 * @since    1.0.0
	 * @param    int      $article_id    Article ID
	 * @param    string   $period        Period (30days, 7days, 24hours, all)
	 * @return   array                   Performance statistics
	 */
	public function get_article_performance( $article_id, $period = '30days' ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );

		// Get basic stats
		$sql = "SELECT 
				    action,
				    COUNT(*) as count,
				    COUNT(DISTINCT session_id) as unique_sessions,
				    COUNT(DISTINCT user_id) as unique_users
				FROM {$this->analytics_table} 
				WHERE article_id = %d {$where_date}
				GROUP BY action";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $article_id ) );

		$stats = array(
			'views' => 0,
			'helpful' => 0,
			'not_helpful' => 0,
			'downloads' => 0,
			'unique_sessions' => 0,
			'unique_users' => 0,
			'helpfulness_ratio' => 0,
		);

		foreach ( $results as $result ) {
			switch ( $result->action ) {
				case 'view':
					$stats['views'] = intval( $result->count );
					$stats['unique_sessions'] = intval( $result->unique_sessions );
					$stats['unique_users'] = intval( $result->unique_users );
					break;
				case 'helpful':
					$stats['helpful'] = intval( $result->count );
					break;
				case 'not_helpful':
					$stats['not_helpful'] = intval( $result->count );
					break;
				case 'download':
					$stats['downloads'] = intval( $result->count );
					break;
			}
		}

		// Calculate helpfulness ratio
		$total_feedback = $stats['helpful'] + $stats['not_helpful'];
		if ( $total_feedback > 0 ) {
			$stats['helpfulness_ratio'] = round( ( $stats['helpful'] / $total_feedback ) * 100, 1 );
		}

		// Get search-to-view data
		$search_sql = "SELECT COUNT(*) as search_views
					   FROM {$this->analytics_table} 
					   WHERE article_id = %d AND action = 'view' AND search_query != '' {$where_date}";

		$search_views = $wpdb->get_var( $wpdb->prepare( $search_sql, $article_id ) );
		$stats['search_views'] = intval( $search_views );
		$stats['direct_views'] = $stats['views'] - $stats['search_views'];

		return $stats;
	}

	/**
	 * Get top performing articles
	 *
	 * @since    1.0.0
	 * @param    int      $limit         Number of articles to return
	 * @param    string   $period        Period (30days, 7days, 24hours, all)
	 * @param    int      $entity_id     Optional. Filter by entity
	 * @param    string   $metric        Metric to sort by (views, helpful, helpfulness_ratio)
	 * @return   array                   Top performing articles
	 */
	public function get_top_articles( $limit = 10, $period = '30days', $entity_id = null, $metric = 'views' ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period );
		$entity_where = $entity_id ? $wpdb->prepare( " AND a.entity_id = %d", $entity_id ) : "";

		$order_by = "views DESC";
		switch ( $metric ) {
			case 'helpful':
				$order_by = "helpful DESC";
				break;
			case 'helpfulness_ratio':
				$order_by = "helpfulness_ratio DESC";
				break;
		}

		$sql = "SELECT 
				    a.id,
				    a.title,
				    a.slug,
				    a.entity_id,
				    e.name as entity_name,
				    COUNT(CASE WHEN an.action = 'view' THEN 1 END) as views,
				    COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) as helpful,
				    COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END) as not_helpful,
				    COUNT(CASE WHEN an.action = 'download' THEN 1 END) as downloads,
				    COUNT(DISTINCT an.session_id) as unique_sessions,
				    CASE 
				        WHEN (COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) + COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END)) > 0 
				        THEN ROUND((COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) / (COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) + COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END))) * 100, 1)
				        ELSE 0 
				    END as helpfulness_ratio
				FROM {$wpdb->prefix}mets_kb_articles a
				LEFT JOIN {$this->analytics_table} an ON a.id = an.article_id {$where_date}
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE a.status = 'published' {$entity_where}
				GROUP BY a.id, a.title, a.slug, a.entity_id, e.name
				HAVING views > 0
				ORDER BY {$order_by}
				LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );
	}

	/**
	 * Get search analytics
	 *
	 * @since    1.0.0
	 * @param    string   $period        Period (30days, 7days, 24hours, all)
	 * @param    int      $entity_id     Optional. Filter by entity
	 * @return   array                   Search analytics data
	 */
	public function get_search_analytics( $period = '30days', $entity_id = null ) {
		global $wpdb;

		$where_date = $this->get_date_where_clause( $period, 'sl.created_at' );
		$entity_where = $entity_id ? $wpdb->prepare( " AND sl.entity_id = %d", $entity_id ) : "";

		// Top search queries
		$top_queries_sql = "SELECT 
							    sl.query,
							    COUNT(*) as search_count,
							    AVG(sl.results_count) as avg_results,
							    COUNT(CASE WHEN sl.clicked_article_id IS NOT NULL THEN 1 END) as click_count,
							    CASE 
							        WHEN COUNT(*) > 0 
							        THEN ROUND((COUNT(CASE WHEN sl.clicked_article_id IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 1)
							        ELSE 0 
							    END as click_through_rate
							FROM {$this->search_table} sl
							WHERE 1=1 {$where_date} {$entity_where}
							GROUP BY sl.query
							ORDER BY search_count DESC
							LIMIT 20";

		$top_queries = $wpdb->get_results( $top_queries_sql );

		// Zero-result queries
		$zero_results_sql = "SELECT 
							     sl.query,
							     COUNT(*) as search_count
							 FROM {$this->search_table} sl
							 WHERE sl.results_count = 0 {$where_date} {$entity_where}
							 GROUP BY sl.query
							 ORDER BY search_count DESC
							 LIMIT 10";

		$zero_results = $wpdb->get_results( $zero_results_sql );

		// Overall search stats
		$overall_sql = "SELECT 
						    COUNT(*) as total_searches,
						    COUNT(DISTINCT sl.query) as unique_queries,
						    AVG(sl.results_count) as avg_results_per_search,
						    COUNT(CASE WHEN sl.clicked_article_id IS NOT NULL THEN 1 END) as total_clicks,
						    COUNT(CASE WHEN sl.results_count = 0 THEN 1 END) as zero_result_searches
						FROM {$this->search_table} sl
						WHERE 1=1 {$where_date} {$entity_where}";

		$overall_stats = $wpdb->get_row( $overall_sql );

		return array(
			'top_queries' => $top_queries,
			'zero_results' => $zero_results,
			'total_searches' => intval( $overall_stats->total_searches ?? 0 ),
			'unique_queries' => intval( $overall_stats->unique_queries ?? 0 ),
			'avg_results_per_search' => round( floatval( $overall_stats->avg_results_per_search ?? 0 ), 1 ),
			'total_clicks' => intval( $overall_stats->total_clicks ?? 0 ),
			'zero_result_searches' => intval( $overall_stats->zero_result_searches ?? 0 ),
			'overall_ctr' => $overall_stats->total_searches > 0 ? round( ( $overall_stats->total_clicks / $overall_stats->total_searches ) * 100, 1 ) : 0,
		);
	}

	/**
	 * Get trending articles (articles with increasing views)
	 *
	 * @since    1.0.0
	 * @param    int      $limit         Number of articles to return
	 * @param    int      $entity_id     Optional. Filter by entity
	 * @return   array                   Trending articles
	 */
	public function get_trending_articles( $limit = 10, $entity_id = null ) {
		global $wpdb;

		$entity_where = $entity_id ? $wpdb->prepare( " AND a.entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
				    a.id,
				    a.title,
				    a.slug,
				    a.entity_id,
				    e.name as entity_name,
				    COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_views,
				    COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND an.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as previous_views,
				    CASE 
				        WHEN COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND an.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) > 0 
				        THEN ROUND(((COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) - COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND an.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END)) / COUNT(CASE WHEN an.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND an.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END)) * 100, 1)
				        ELSE 100 
				    END as growth_rate
				FROM {$wpdb->prefix}mets_kb_articles a
				LEFT JOIN {$this->analytics_table} an ON a.id = an.article_id AND an.action = 'view'
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE a.status = 'published' {$entity_where}
				GROUP BY a.id, a.title, a.slug, a.entity_id, e.name
				HAVING recent_views > 0 AND growth_rate > 0
				ORDER BY growth_rate DESC, recent_views DESC
				LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );
	}

	/**
	 * Get article performance over time
	 *
	 * @since    1.0.0
	 * @param    int      $article_id    Article ID
	 * @param    string   $period        Period (30days, 7days)
	 * @param    string   $interval      Interval (day, hour)
	 * @return   array                   Time series data
	 */
	public function get_article_timeline( $article_id, $period = '30days', $interval = 'day' ) {
		global $wpdb;

		$date_format = $interval === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
		$where_date = $this->get_date_where_clause( $period );

		$sql = "SELECT 
				    DATE_FORMAT(analytics.created_at, '{$date_format}') as period,
				    COUNT(CASE WHEN analytics.action = 'view' THEN 1 END) as views,
				    COUNT(CASE WHEN analytics.action = 'helpful' THEN 1 END) as helpful,
				    COUNT(CASE WHEN analytics.action = 'not_helpful' THEN 1 END) as not_helpful,
				    COUNT(DISTINCT analytics.session_id) as unique_sessions
				FROM {$this->analytics_table} analytics
				WHERE analytics.article_id = %d {$where_date}
				GROUP BY period
				ORDER BY period ASC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $article_id ) );
	}

	/**
	 * Get articles that need attention (low performance indicators)
	 *
	 * @since    1.0.0
	 * @param    int      $limit         Number of articles to return
	 * @param    int      $entity_id     Optional. Filter by entity
	 * @return   array                   Articles needing attention
	 */
	public function get_articles_needing_attention( $limit = 10, $entity_id = null ) {
		global $wpdb;

		$entity_where = $entity_id ? $wpdb->prepare( " AND a.entity_id = %d", $entity_id ) : "";

		$sql = "SELECT 
				    a.id,
				    a.title,
				    a.slug,
				    a.entity_id,
				    e.name as entity_name,
				    COUNT(CASE WHEN an.action = 'view' THEN 1 END) as views,
				    COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) as helpful,
				    COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END) as not_helpful,
				    CASE 
				        WHEN (COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) + COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END)) > 5
				        THEN ROUND((COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) / (COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) + COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END))) * 100, 1)
				        ELSE NULL
				    END as helpfulness_ratio,
				    'low_helpfulness' as reason
				FROM {$wpdb->prefix}mets_kb_articles a
				LEFT JOIN {$this->analytics_table} an ON a.id = an.article_id 
				    AND an.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE a.status = 'published' {$entity_where}
				GROUP BY a.id, a.title, a.slug, a.entity_id, e.name
				HAVING (helpfulness_ratio IS NOT NULL AND helpfulness_ratio < 40) 
				    OR (views > 0 AND (COUNT(CASE WHEN an.action = 'helpful' THEN 1 END) + COUNT(CASE WHEN an.action = 'not_helpful' THEN 1 END)) = 0)
				ORDER BY helpfulness_ratio ASC, views DESC
				LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );
	}

	/**
	 * Get date WHERE clause for SQL queries
	 *
	 * @since    1.0.0
	 * @param    string   $period        Period (30days, 7days, 24hours, all)
	 * @param    string   $column        Column name (default: created_at)
	 * @return   string                  WHERE clause
	 */
	private function get_date_where_clause( $period, $column = 'created_at' ) {
		switch ( $period ) {
			case '24hours':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
			case '7days':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			case '30days':
				return " AND {$column} >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
			case 'all':
			default:
				return "";
		}
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string   Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				// Handle comma-separated IPs (forwarded)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	/**
	 * Get or create session ID
	 *
	 * @since    1.0.0
	 * @return   string   Session ID
	 */
	private function get_session_id() {
		if ( ! session_id() ) {
			session_start();
		}
		return session_id();
	}
}