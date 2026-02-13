<?php
/**
 * Knowledgebase Article Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Knowledgebase Article Model class.
 *
 * This class handles all CRUD operations for knowledgebase articles.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Article_Model {

	/**
	 * Table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The table name.
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_kb_articles';
	}

	/**
	 * Create a new article
	 *
	 * @since    1.0.0
	 * @param    array    $data    Article data
	 * @return   int|false         Article ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'title' => '',
			'slug' => '',
			'content' => '',
			'excerpt' => '',
			'author_id' => get_current_user_id(),
			'status' => 'draft',
			'visibility' => 'customer',
			'featured' => 0,
			'sort_order' => 0,
			'meta_title' => '',
			'meta_description' => ''
		);

		$data = wp_parse_args( $data, $defaults );

		// Auto-generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = $this->generate_unique_slug( $data['title'], $data['entity_id'] );
		}

		// Validate required fields
		if ( empty( $data['title'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'entity_id' => $data['entity_id'],
				'title' => $data['title'],
				'slug' => $data['slug'],
				'content' => $data['content'],
				'excerpt' => $data['excerpt'],
				'author_id' => $data['author_id'],
				'status' => $data['status'],
				'visibility' => $data['visibility'],
				'featured' => $data['featured'],
				'sort_order' => $data['sort_order'],
				'meta_title' => $data['meta_title'],
				'meta_description' => $data['meta_description']
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update an article
	 *
	 * @since    1.0.0
	 * @param    int      $id      Article ID
	 * @param    array    $data    Updated data
	 * @return   bool              True on success, false on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		$allowed_fields = array(
			'entity_id' => '%d',
			'title' => '%s',
			'slug' => '%s',
			'content' => '%s',
			'excerpt' => '%s',
			'status' => '%s',
			'visibility' => '%s',
			'featured' => '%d',
			'sort_order' => '%d',
			'meta_title' => '%s',
			'meta_description' => '%s',
			'reviewed_by' => '%d',
			'reviewed_at' => '%s',
			'published_at' => '%s'
		);

		foreach ( $allowed_fields as $field => $format ) {
			if ( array_key_exists( $field, $data ) ) {
				$update_data[ $field ] = $data[ $field ];
				$update_format[] = $format;
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		// Handle slug regeneration if title changed
		if ( isset( $data['title'] ) && isset( $data['entity_id'] ) ) {
			$current = $this->get( $id );
			if ( $current && $current->title !== $data['title'] ) {
				$update_data['slug'] = $this->generate_unique_slug( $data['title'], $data['entity_id'], $id );
				$update_format[] = '%s';
			}
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$update_format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get article by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Article ID
	 * @return   object|null   Article object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, u.display_name as author_name, 
				        ur.display_name as reviewer_name,
				        e.name as entity_name
				FROM {$this->table_name} a
				LEFT JOIN {$wpdb->prefix}users u ON a.author_id = u.ID
				LEFT JOIN {$wpdb->prefix}users ur ON a.reviewed_by = ur.ID
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE a.id = %d",
				$id
			)
		);
	}

	/**
	 * Get article by slug and entity
	 *
	 * @since    1.0.0
	 * @param    string   $slug       Article slug
	 * @param    int      $entity_id  Entity ID
	 * @return   object|null          Article object or null if not found
	 */
	public function get_by_slug( $slug, $entity_id = null ) {
		global $wpdb;

		if ( is_null( $entity_id ) ) {
			$where_clause = "a.slug = %s AND a.entity_id IS NULL";
			$params = array( $slug );
		} else {
			$where_clause = "a.slug = %s AND a.entity_id = %d";
			$params = array( $slug, $entity_id );
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, u.display_name as author_name, 
				        ur.display_name as reviewer_name,
				        e.name as entity_name
				FROM {$this->table_name} a
				LEFT JOIN {$wpdb->prefix}users u ON a.author_id = u.ID
				LEFT JOIN {$wpdb->prefix}users ur ON a.reviewed_by = ur.ID
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE {$where_clause}",
				$params
			)
		);
	}

	/**
	 * Get articles with inheritance (entity + parent articles)
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Query results array
	 */
	public function get_articles_with_inheritance( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'status' => array( 'published' ),
			'visibility' => array( 'customer', 'staff', 'internal' ),
			'featured' => null,
			'category_id' => null,
			'tag_id' => null,
			'search' => '',
			'per_page' => 20,
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC',
			'include_parent' => true
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause
		$where_conditions = array();
		$params = array();

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$status_placeholders = implode( ',', array_fill( 0, count( $args['status'] ), '%s' ) );
			$where_conditions[] = "a.status IN ($status_placeholders)";
			$params = array_merge( $params, $args['status'] );
		}

		// Visibility filter
		if ( ! empty( $args['visibility'] ) ) {
			$visibility_placeholders = implode( ',', array_fill( 0, count( $args['visibility'] ), '%s' ) );
			$where_conditions[] = "a.visibility IN ($visibility_placeholders)";
			$params = array_merge( $params, $args['visibility'] );
		}

		// Entity inheritance logic
		if ( ! is_null( $args['entity_id'] ) && $args['include_parent'] ) {
			// Get parent entity ID if this is a child entity
			$entity = $wpdb->get_row( $wpdb->prepare(
				"SELECT parent_id FROM {$wpdb->prefix}mets_entities WHERE id = %d",
				$args['entity_id']
			) );

			if ( $entity && $entity->parent_id ) {
				// Child entity: include own articles + parent articles + global articles
				$where_conditions[] = "(a.entity_id = %d OR a.entity_id = %d OR a.entity_id IS NULL)";
				$params[] = $args['entity_id'];
				$params[] = $entity->parent_id;
			} else {
				// Parent entity: include own articles + global articles
				$where_conditions[] = "(a.entity_id = %d OR a.entity_id IS NULL)";
				$params[] = $args['entity_id'];
			}
		} elseif ( ! is_null( $args['entity_id'] ) ) {
			// Only specific entity
			$where_conditions[] = "a.entity_id = %d";
			$params[] = $args['entity_id'];
		} elseif ( $args['entity_id'] === 'global' ) {
			// Only global articles
			$where_conditions[] = "a.entity_id IS NULL";
		}

		// Featured filter
		if ( ! is_null( $args['featured'] ) ) {
			$where_conditions[] = "a.featured = %d";
			$params[] = $args['featured'];
		}

		// Category filter
		if ( ! is_null( $args['category_id'] ) ) {
			$where_conditions[] = "EXISTS (
				SELECT 1 FROM {$wpdb->prefix}mets_kb_article_categories ac 
				WHERE ac.article_id = a.id AND ac.category_id = %d
			)";
			$params[] = $args['category_id'];
		}

		// Tag filter
		if ( ! is_null( $args['tag_id'] ) ) {
			$where_conditions[] = "EXISTS (
				SELECT 1 FROM {$wpdb->prefix}mets_kb_article_tags at 
				WHERE at.article_id = a.id AND at.tag_id = %d
			)";
			$params[] = $args['tag_id'];
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$where_conditions[] = "(a.title LIKE %s OR a.content LIKE %s OR a.excerpt LIKE %s)";
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $search_term;
			$params[] = $search_term;
			$params[] = $search_term;
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		// Build ORDER BY clause
		$allowed_orderby = array( 'title', 'created_at', 'updated_at', 'view_count', 'sort_order', 'featured' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'created_at';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$order_clause = "ORDER BY a.{$orderby} {$order}";

		// Featured articles first if not ordering by featured specifically
		if ( $orderby !== 'featured' ) {
			$order_clause = "ORDER BY a.featured DESC, a.{$orderby} {$order}";
		}

		// Pagination
		$limit = absint( $args['per_page'] );
		$offset = ( absint( $args['page'] ) - 1 ) * $limit;
		$limit_clause = "LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		// Main query
		$sql = "SELECT a.*, u.display_name as author_name, 
		               ur.display_name as reviewer_name,
		               e.name as entity_name
		        FROM {$this->table_name} a
		        LEFT JOIN {$wpdb->prefix}users u ON a.author_id = u.ID
		        LEFT JOIN {$wpdb->prefix}users ur ON a.reviewed_by = ur.ID
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
		        {$where_clause}
		        {$order_clause}
		        {$limit_clause}";

		$articles = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		// Count query for pagination
		$count_params = array_slice( $params, 0, -2 ); // Remove limit and offset
		$count_sql = "SELECT COUNT(*) 
		              FROM {$this->table_name} a
		              {$where_clause}";

		$total = $wpdb->get_var( 
			empty( $count_params ) ? $count_sql : $wpdb->prepare( $count_sql, $count_params )
		);

		return array(
			'articles' => $articles,
			'total' => (int) $total,
			'pages' => ceil( $total / $limit ),
			'current_page' => absint( $args['page'] )
		);
	}

	/**
	 * Delete an article
	 *
	 * @since    1.0.0
	 * @param    int    $id    Article ID
	 * @return   bool          True on success, false on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Update article view count
	 *
	 * @since    1.0.0
	 * @param    int    $id    Article ID
	 * @return   bool          True on success, false on failure
	 */
	public function increment_view_count( $id ) {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET view_count = view_count + 1 WHERE id = %d",
				$id
			)
		);

		return $result !== false;
	}

	/**
	 * Update helpful/not helpful counts
	 *
	 * @since    1.0.0
	 * @param    int     $id         Article ID
	 * @param    bool    $helpful    True for helpful, false for not helpful
	 * @return   bool                True on success, false on failure
	 */
	public function update_helpful_count( $id, $helpful = true ) {
		global $wpdb;

		$allowed_fields = array( 'helpful_count', 'not_helpful_count' );
		$field = $helpful ? 'helpful_count' : 'not_helpful_count';

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return false;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET {$field} = {$field} + 1 WHERE id = %d",
				$id
			)
		);

		return $result !== false;
	}

	/**
	 * Generate unique slug for article
	 *
	 * @since    1.0.0
	 * @param    string   $title      Article title
	 * @param    int      $entity_id  Entity ID
	 * @param    int      $exclude_id Article ID to exclude from uniqueness check
	 * @return   string               Unique slug
	 */
	private function generate_unique_slug( $title, $entity_id = null, $exclude_id = null ) {
		global $wpdb;

		$base_slug = sanitize_title( $title );
		$slug = $base_slug;
		$counter = 1;

		while ( $this->slug_exists( $slug, $entity_id, $exclude_id ) ) {
			$slug = $base_slug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * Check if slug exists for entity
	 *
	 * @since    1.0.0
	 * @param    string   $slug       Slug to check
	 * @param    int      $entity_id  Entity ID
	 * @param    int      $exclude_id Article ID to exclude from check
	 * @return   bool                 True if slug exists, false otherwise
	 */
	private function slug_exists( $slug, $entity_id = null, $exclude_id = null ) {
		global $wpdb;

		$where_conditions = array( "slug = %s" );
		$params = array( $slug );

		if ( is_null( $entity_id ) ) {
			$where_conditions[] = "entity_id IS NULL";
		} else {
			$where_conditions[] = "entity_id = %d";
			$params[] = $entity_id;
		}

		if ( ! is_null( $exclude_id ) ) {
			$where_conditions[] = "id != %d";
			$params[] = $exclude_id;
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
				$params
			)
		);

		return $exists > 0;
	}

	/**
	 * Get articles by status
	 *
	 * @since    1.0.0
	 * @param    string   $status     Article status
	 * @param    int      $entity_id  Entity ID (optional)
	 * @return   array                Array of articles
	 */
	public function get_by_status( $status, $entity_id = null ) {
		global $wpdb;

		$where_conditions = array( "a.status = %s" );
		$params = array( $status );

		if ( ! is_null( $entity_id ) ) {
			$where_conditions[] = "a.entity_id = %d";
			$params[] = $entity_id;
		}

		$where_clause = implode( ' AND ', $where_conditions );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, u.display_name as author_name, e.name as entity_name
				FROM {$this->table_name} a
				LEFT JOIN {$wpdb->prefix}users u ON a.author_id = u.ID
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE {$where_clause}
				ORDER BY a.created_at DESC",
				$params
			)
		);
	}


	/**
	 * Add helpfulness vote for an article
	 *
	 * @since    1.0.0
	 * @param    int      $id      Article ID
	 * @param    string   $vote    Vote type ('yes' or 'no')
	 * @return   bool             True on success, false on failure
	 */
	public function add_helpfulness_vote( $id, $vote ) {
		global $wpdb;

		$field = $vote === 'yes' ? 'helpful_yes' : 'helpful_no';

		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET {$field} = {$field} + 1 WHERE id = %d",
				$id
			)
		) !== false;
	}

	/**
	 * Get related articles
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 * @param    int    $limit         Number of related articles to return
	 * @return   array                 Array of related articles
	 */
	public function get_related_articles( $article_id, $limit = 4 ) {
		global $wpdb;

		// Get the current article's entity and categories
		$article = $this->get( $article_id );
		if ( ! $article ) {
			return array();
		}

		// Get categories for this article
		$categories = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT category_id FROM {$wpdb->prefix}mets_kb_article_categories WHERE article_id = %d",
				$article_id
			)
		);

		$where_conditions = array( "a.id != %d", "a.status = 'published'" );
		$params = array( $article_id );

		// Prioritize articles from same entity
		if ( $article->entity_id ) {
			$where_conditions[] = "a.entity_id = %d";
			$params[] = $article->entity_id;
		}

		// If we have categories, find articles with shared categories
		if ( ! empty( $categories ) ) {
			$category_placeholders = implode( ', ', array_fill( 0, count( $categories ), '%d' ) );
			$where_conditions[] = "ac.category_id IN ({$category_placeholders})";
			$params = array_merge( $params, $categories );
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$sql = "SELECT DISTINCT a.*, e.name as entity_name, 
		               COUNT(ac.category_id) as shared_categories
		        FROM {$this->table_name} a
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id";

		if ( ! empty( $categories ) ) {
			$sql .= " LEFT JOIN {$wpdb->prefix}mets_kb_article_categories ac ON a.id = ac.article_id";
		}

		$sql .= " WHERE {$where_clause}
		         GROUP BY a.id
		         ORDER BY shared_categories DESC, a.view_count DESC, a.created_at DESC
		         LIMIT %d";

		$params[] = $limit;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Get articles by tag
	 *
	 * @since    1.0.0
	 * @param    int      $tag_id    Tag ID
	 * @param    array    $args      Query arguments
	 * @return   array               Array with articles and pagination info
	 */
	public function get_articles_by_tag( $tag_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status' => array( 'published' ),
			'visibility' => array( 'customer', 'staff', 'internal' ),
			'per_page' => 10,
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		$where_conditions = array();
		$params = array( $tag_id );

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) );
			$where_conditions[] = "a.status IN ({$status_placeholders})";
			$params = array_merge( $params, $args['status'] );
		}

		// Visibility filter
		if ( ! empty( $args['visibility'] ) ) {
			$visibility_placeholders = implode( ', ', array_fill( 0, count( $args['visibility'] ), '%s' ) );
			$where_conditions[] = "a.visibility IN ({$visibility_placeholders})";
			$params = array_merge( $params, $args['visibility'] );
		}

		$where_clause = ! empty( $where_conditions ) ? 'AND ' . implode( ' AND ', $where_conditions ) : '';

		// Count total results
		$count_sql = "SELECT COUNT(DISTINCT a.id) 
		              FROM {$this->table_name} a
		              JOIN {$wpdb->prefix}mets_kb_article_tags at ON a.id = at.article_id
		              LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
		              WHERE at.tag_id = %d {$where_clause}";

		$total = $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

		// Calculate pagination
		$per_page = max( 1, intval( $args['per_page'] ) );
		$page = max( 1, intval( $args['page'] ) );
		$offset = ( $page - 1 ) * $per_page;
		$total_pages = ceil( $total / $per_page );

		// Get articles
		$allowed_orderby = array( 'title', 'created_at', 'updated_at', 'view_count' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'created_at';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT DISTINCT a.*, e.name as entity_name, e.slug as entity_slug,
		               u.display_name as author_name
		        FROM {$this->table_name} a
		        JOIN {$wpdb->prefix}mets_kb_article_tags at ON a.id = at.article_id
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
		        LEFT JOIN {$wpdb->prefix}users u ON a.author_id = u.ID
		        WHERE at.tag_id = %d {$where_clause}
		        ORDER BY a.{$orderby} {$order}
		        LIMIT %d OFFSET %d";

		$params[] = $per_page;
		$params[] = $offset;

		$articles = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		return array(
			'articles' => $articles,
			'total' => intval( $total ),
			'pages' => intval( $total_pages ),
			'current_page' => $page,
			'per_page' => $per_page
		);
	}
}