<?php
/**
 * Knowledgebase Tag Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Knowledgebase Tag Model class.
 *
 * This class handles all CRUD operations for knowledgebase tags.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Tag_Model {

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
		$this->table_name = $wpdb->prefix . 'mets_kb_tags';
	}

	/**
	 * Create a new tag
	 *
	 * @since    1.0.0
	 * @param    array    $data    Tag data
	 * @return   int|false         Tag ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'name' => '',
			'slug' => '',
			'description' => ''
		);

		$data = wp_parse_args( $data, $defaults );

		// Auto-generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = $this->generate_unique_slug( $data['name'] );
		}

		// Validate required fields
		if ( empty( $data['name'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'name' => $data['name'],
				'slug' => $data['slug'],
				'description' => $data['description']
			),
			array( '%s', '%s', '%s' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update a tag
	 *
	 * @since    1.0.0
	 * @param    int      $id      Tag ID
	 * @param    array    $data    Updated data
	 * @return   bool              True on success, false on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		$allowed_fields = array(
			'name' => '%s',
			'slug' => '%s',
			'description' => '%s'
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

		// Handle slug regeneration if name changed
		if ( isset( $data['name'] ) ) {
			$current = $this->get( $id );
			if ( $current && $current->name !== $data['name'] ) {
				$update_data['slug'] = $this->generate_unique_slug( $data['name'], $id );
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

		// Update usage count
		$this->update_usage_count( $id );

		return $result !== false;
	}

	/**
	 * Get tag by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Tag ID
	 * @return   object|null   Tag object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Get tag by slug
	 *
	 * @since    1.0.0
	 * @param    string   $slug    Tag slug
	 * @return   object|null       Tag object or null if not found
	 */
	public function get_by_slug( $slug ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE slug = %s",
				$slug
			)
		);
	}

	/**
	 * Get all tags
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of tags
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby' => 'usage_count',
			'order' => 'DESC',
			'limit' => null,
			'search' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		$where_conditions = array();
		$params = array();

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$where_conditions[] = "(name LIKE %s OR description LIKE %s)";
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $search_term;
			$params[] = $search_term;
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		$allowed_orderby = array( 'name', 'usage_count', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'usage_count';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$limit_clause = '';
		if ( ! is_null( $args['limit'] ) ) {
			$limit_clause = "LIMIT " . absint( $args['limit'] );
		}

		$sql = "SELECT * FROM {$this->table_name} 
		        {$where_clause}
		        ORDER BY {$orderby} {$order}
		        {$limit_clause}";

		return $wpdb->get_results( 
			empty( $params ) ? $sql : $wpdb->prepare( $sql, $params )
		);
	}

	/**
	 * Delete a tag
	 *
	 * @since    1.0.0
	 * @param    int    $id    Tag ID
	 * @return   bool          True on success, false on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Remove tag associations from articles
		$wpdb->delete(
			$wpdb->prefix . 'mets_kb_article_tags',
			array( 'tag_id' => $id ),
			array( '%d' )
		);

		// Delete the tag
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Update usage count for a tag
	 *
	 * @since    1.0.0
	 * @param    int    $tag_id    Tag ID
	 * @return   bool              True on success, false on failure
	 */
	public function update_usage_count( $tag_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$wpdb->prefix}mets_kb_article_tags at
				JOIN {$wpdb->prefix}mets_kb_articles a ON at.article_id = a.id
				WHERE at.tag_id = %d AND a.status = 'published'",
				$tag_id
			)
		);

		$result = $wpdb->update(
			$this->table_name,
			array( 'usage_count' => $count ),
			array( 'id' => $tag_id ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Find or create tag by name
	 *
	 * @since    1.0.0
	 * @param    string   $name    Tag name
	 * @return   int|false         Tag ID on success, false on failure
	 */
	public function find_or_create( $name ) {
		global $wpdb;

		$name = trim( $name );
		if ( empty( $name ) ) {
			return false;
		}

		// Try to find existing tag
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE name = %s",
				$name
			)
		);

		if ( $existing ) {
			return $existing->id;
		}

		// Create new tag
		return $this->create( array( 'name' => $name ) );
	}

	/**
	 * Get tags for an article
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 * @return   array                 Array of tags
	 */
	public function get_article_tags( $article_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* 
				FROM {$this->table_name} t
				JOIN {$wpdb->prefix}mets_kb_article_tags at ON t.id = at.tag_id
				WHERE at.article_id = %d
				ORDER BY t.name ASC",
				$article_id
			)
		);
	}

	/**
	 * Set tags for an article
	 *
	 * @since    1.0.0
	 * @param    int      $article_id    Article ID
	 * @param    array    $tag_names     Array of tag names
	 * @return   bool                    True on success, false on failure
	 */
	public function set_article_tags( $article_id, $tag_names ) {
		global $wpdb;

		// Start transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Remove existing tag associations
			$wpdb->delete(
				$wpdb->prefix . 'mets_kb_article_tags',
				array( 'article_id' => $article_id ),
				array( '%d' )
			);

			// Add new tag associations
			foreach ( $tag_names as $tag_name ) {
				$tag_name = trim( $tag_name );
				if ( empty( $tag_name ) ) {
					continue;
				}

				$tag_id = $this->find_or_create( $tag_name );
				if ( $tag_id ) {
					$wpdb->insert(
						$wpdb->prefix . 'mets_kb_article_tags',
						array(
							'article_id' => $article_id,
							'tag_id' => $tag_id
						),
						array( '%d', '%d' )
					);
				}
			}

			// Update usage counts for all affected tags
			$all_tag_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT tag_id FROM {$wpdb->prefix}mets_kb_article_tags WHERE article_id = %d",
					$article_id
				)
			);

			foreach ( $all_tag_ids as $tag_id ) {
				$this->update_usage_count( $tag_id );
			}

			$wpdb->query( 'COMMIT' );
			return true;

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
	}

	/**
	 * Get popular tags
	 *
	 * @since    1.0.0
	 * @param    int    $limit    Number of tags to return
	 * @return   array            Array of popular tags
	 */
	public function get_popular_tags( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE usage_count > 0 
				ORDER BY usage_count DESC, name ASC 
				LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Search tags by name
	 *
	 * @since    1.0.0
	 * @param    string   $query    Search query
	 * @param    int      $limit    Number of results to return
	 * @return   array              Array of matching tags
	 */
	public function search( $query, $limit = 10 ) {
		global $wpdb;

		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE name LIKE %s 
				ORDER BY usage_count DESC, name ASC 
				LIMIT %d",
				$search_term,
				$limit
			)
		);
	}

	/**
	 * Generate unique slug for tag
	 *
	 * @since    1.0.0
	 * @param    string   $name       Tag name
	 * @param    int      $exclude_id Tag ID to exclude from uniqueness check
	 * @return   string               Unique slug
	 */
	private function generate_unique_slug( $name, $exclude_id = null ) {
		$base_slug = sanitize_title( $name );
		$slug = $base_slug;
		$counter = 1;

		while ( $this->slug_exists( $slug, $exclude_id ) ) {
			$slug = $base_slug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * Check if slug exists
	 *
	 * @since    1.0.0
	 * @param    string   $slug       Slug to check
	 * @param    int      $exclude_id Tag ID to exclude from check
	 * @return   bool                 True if slug exists, false otherwise
	 */
	public function slug_exists( $slug, $exclude_id = null ) {
		global $wpdb;

		$where_conditions = array( "slug = %s" );
		$params = array( $slug );

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
}