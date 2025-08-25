<?php
/**
 * Knowledgebase Category Model
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Knowledgebase Category Model class.
 *
 * This class handles all CRUD operations for knowledgebase categories.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Category_Model {

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
		$this->table_name = $wpdb->prefix . 'mets_kb_categories';
	}

	/**
	 * Create a new category
	 *
	 * @since    1.0.0
	 * @param    array    $data    Category data
	 * @return   int|false         Category ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'name' => '',
			'slug' => '',
			'description' => '',
			'parent_id' => null,
			'sort_order' => 0,
			'icon' => 'dashicons-category',
			'color' => '#0073aa'
		);

		$data = wp_parse_args( $data, $defaults );

		// Auto-generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = $this->generate_unique_slug( $data['name'], $data['entity_id'] );
		}

		// Validate required fields
		if ( empty( $data['name'] ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'entity_id' => $data['entity_id'],
				'name' => $data['name'],
				'slug' => $data['slug'],
				'description' => $data['description'],
				'parent_id' => $data['parent_id'],
				'sort_order' => $data['sort_order'],
				'icon' => $data['icon'],
				'color' => $data['color']
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		return $result !== false ? $wpdb->insert_id : false;
	}

	/**
	 * Update a category
	 *
	 * @since    1.0.0
	 * @param    int      $id      Category ID
	 * @param    array    $data    Updated data
	 * @return   bool              True on success, false on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$update_format = array();

		$allowed_fields = array(
			'entity_id' => '%d',
			'name' => '%s',
			'slug' => '%s',
			'description' => '%s',
			'parent_id' => '%d',
			'sort_order' => '%d',
			'icon' => '%s',
			'color' => '%s'
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
				// Use entity_id from data if provided, otherwise use current category's entity_id
				$entity_id = isset( $data['entity_id'] ) ? $data['entity_id'] : $current->entity_id;
				$update_data['slug'] = $this->generate_unique_slug( $data['name'], $entity_id, $id );
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

		// Update article count
		$this->update_article_count( $id );

		return $result !== false;
	}

	/**
	 * Get category by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Category ID
	 * @return   object|null   Category object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*, e.name as entity_name, pc.name as parent_name
				FROM {$this->table_name} c
				LEFT JOIN {$wpdb->prefix}mets_entities e ON c.entity_id = e.id
				LEFT JOIN {$this->table_name} pc ON c.parent_id = pc.id
				WHERE c.id = %d",
				$id
			)
		);
	}

	/**
	 * Get categories by entity with inheritance
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id         Entity ID
	 * @param    bool     $include_parent    Include parent entity categories
	 * @param    bool     $hierarchical      Return hierarchically organized
	 * @return   array                       Array of categories
	 */
	public function get_by_entity( $entity_id, $include_parent = true, $hierarchical = false ) {
		global $wpdb;

		$where_conditions = array();
		$params = array();

		if ( $include_parent && ! is_null( $entity_id ) ) {
			// Get parent entity ID if this is a child entity
			$entity = $wpdb->get_row( $wpdb->prepare(
				"SELECT parent_id FROM {$wpdb->prefix}mets_entities WHERE id = %d",
				$entity_id
			) );

			if ( $entity && $entity->parent_id ) {
				// Child entity: include own categories + parent categories + global categories
				$where_conditions[] = "(c.entity_id = %d OR c.entity_id = %d OR c.entity_id IS NULL)";
				$params[] = $entity_id;
				$params[] = $entity->parent_id;
			} else {
				// Parent entity: include own categories + global categories
				$where_conditions[] = "(c.entity_id = %d OR c.entity_id IS NULL)";
				$params[] = $entity_id;
			}
		} elseif ( ! is_null( $entity_id ) ) {
			// Only specific entity
			$where_conditions[] = "c.entity_id = %d";
			$params[] = $entity_id;
		} else {
			// Global categories only
			$where_conditions[] = "c.entity_id IS NULL";
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		$sql = "SELECT c.*, e.name as entity_name, pc.name as parent_name
		        FROM {$this->table_name} c
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON c.entity_id = e.id
		        LEFT JOIN {$this->table_name} pc ON c.parent_id = pc.id
		        {$where_clause}
		        ORDER BY c.sort_order ASC, c.name ASC";

		$categories = $wpdb->get_results( 
			empty( $params ) ? $sql : $wpdb->prepare( $sql, $params )
		);

		if ( $hierarchical ) {
			return $this->organize_hierarchically( $categories );
		}

		return $categories;
	}

	/**
	 * Get all categories
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of categories
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'entity_id' => null,
			'parent_id' => null,
			'hierarchical' => false,
			'orderby' => 'sort_order',
			'order' => 'ASC'
		);

		$args = wp_parse_args( $args, $defaults );

		$where_conditions = array();
		$params = array();

		if ( ! is_null( $args['entity_id'] ) ) {
			if ( $args['entity_id'] === 'all' ) {
				// No entity filter
			} else {
				$where_conditions[] = "c.entity_id = %d";
				$params[] = $args['entity_id'];
			}
		}

		if ( ! is_null( $args['parent_id'] ) ) {
			if ( $args['parent_id'] === 'none' ) {
				$where_conditions[] = "c.parent_id IS NULL";
			} else {
				$where_conditions[] = "c.parent_id = %d";
				$params[] = $args['parent_id'];
			}
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		$allowed_orderby = array( 'name', 'sort_order', 'created_at', 'article_count' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'sort_order';
		$order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		$sql = "SELECT c.*, e.name as entity_name, pc.name as parent_name
		        FROM {$this->table_name} c
		        LEFT JOIN {$wpdb->prefix}mets_entities e ON c.entity_id = e.id
		        LEFT JOIN {$this->table_name} pc ON c.parent_id = pc.id
		        {$where_clause}
		        ORDER BY c.{$orderby} {$order}";

		$categories = $wpdb->get_results( 
			empty( $params ) ? $sql : $wpdb->prepare( $sql, $params )
		);

		if ( $args['hierarchical'] ) {
			return $this->organize_hierarchically( $categories );
		}

		return $categories;
	}

	/**
	 * Delete a category
	 *
	 * @since    1.0.0
	 * @param    int    $id    Category ID
	 * @return   bool          True on success, false on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// First, update any child categories to have no parent
		$wpdb->update(
			$this->table_name,
			array( 'parent_id' => null ),
			array( 'parent_id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		// Remove category associations from articles
		$wpdb->delete(
			$wpdb->prefix . 'mets_kb_article_categories',
			array( 'category_id' => $id ),
			array( '%d' )
		);

		// Delete the category
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Update article count for a category
	 *
	 * @since    1.0.0
	 * @param    int    $category_id    Category ID
	 * @return   bool                   True on success, false on failure
	 */
	public function update_article_count( $category_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$wpdb->prefix}mets_kb_article_categories ac
				JOIN {$wpdb->prefix}mets_kb_articles a ON ac.article_id = a.id
				WHERE ac.category_id = %d AND a.status = 'published'",
				$category_id
			)
		);

		$result = $wpdb->update(
			$this->table_name,
			array( 'article_count' => $count ),
			array( 'id' => $category_id ),
			array( '%d' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Organize categories hierarchically
	 *
	 * @since    1.0.0
	 * @param    array    $categories    Flat array of categories
	 * @return   array                   Hierarchically organized categories
	 */
	private function organize_hierarchically( $categories ) {
		$parents = array();
		$children = array();

		// Separate parents and children
		foreach ( $categories as $category ) {
			if ( empty( $category->parent_id ) ) {
				$parents[] = $category;
			} else {
				if ( ! isset( $children[ $category->parent_id ] ) ) {
					$children[ $category->parent_id ] = array();
				}
				$children[ $category->parent_id ][] = $category;
			}
		}

		// Combine parents with their children
		$organized = array();
		foreach ( $parents as $parent ) {
			$parent->children = isset( $children[ $parent->id ] ) ? $children[ $parent->id ] : array();
			$organized[] = $parent;
		}

		return $organized;
	}

	/**
	 * Generate unique slug for category
	 *
	 * @since    1.0.0
	 * @param    string   $name       Category name
	 * @param    int      $entity_id  Entity ID
	 * @param    int      $exclude_id Category ID to exclude from uniqueness check
	 * @return   string               Unique slug
	 */
	private function generate_unique_slug( $name, $entity_id = null, $exclude_id = null ) {
		$base_slug = sanitize_title( $name );
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
	 * @param    int      $exclude_id Category ID to exclude from check
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
	 * Get category tree for select dropdown
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id     Entity ID
	 * @param    int      $exclude_id    Category ID to exclude
	 * @return   array                   Array of categories formatted for dropdown
	 */
	public function get_category_tree( $entity_id = null, $exclude_id = null ) {
		$categories = $this->get_by_entity( $entity_id, true, true );
		$tree = array();

		foreach ( $categories as $category ) {
			if ( $exclude_id && $category->id == $exclude_id ) {
				continue;
			}

			$tree[] = array(
				'id' => $category->id,
				'name' => $category->name,
				'level' => 0
			);

			if ( ! empty( $category->children ) ) {
				foreach ( $category->children as $child ) {
					if ( $exclude_id && $child->id == $exclude_id ) {
						continue;
					}

					$tree[] = array(
						'id' => $child->id,
						'name' => $child->name,
						'level' => 1
					);
				}
			}
		}

		return $tree;
	}
}