<?php
/**
 * Entity model class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * Entity model class.
 *
 * This class handles all database operations for entities including
 * CRUD operations and hierarchical relationships.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Entity_Model {

	/**
	 * Database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The database table name.
	 */
	private $table_name;

	/**
	 * Initialize the model
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_entities';
	}

	/**
	 * Create a new entity
	 *
	 * @since    1.0.0
	 * @param    array    $data    Entity data
	 * @return   int|WP_Error      Entity ID on success, WP_Error on failure
	 */
	public function create( $data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Entity name is required.', METS_TEXT_DOMAIN ) );
		}

		// Generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_title( $data['name'] );
		}

		// Check if slug already exists
		if ( $this->slug_exists( $data['slug'] ) ) {
			return new WP_Error( 'slug_exists', __( 'Entity slug already exists.', METS_TEXT_DOMAIN ) );
		}

		// Prepare data for insertion
		$insert_data = array(
			'parent_id'       => ! empty( $data['parent_id'] ) ? intval( $data['parent_id'] ) : null,
			'name'            => sanitize_text_field( $data['name'] ),
			'slug'            => sanitize_title( $data['slug'] ),
			'description'     => ! empty( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'logo_url'        => ! empty( $data['logo_url'] ) ? esc_url( $data['logo_url'] ) : '',
			'primary_color'   => ! empty( $data['primary_color'] ) ? sanitize_hex_color( $data['primary_color'] ) : '',
			'secondary_color' => ! empty( $data['secondary_color'] ) ? sanitize_hex_color( $data['secondary_color'] ) : '',
			'email_template'  => ! empty( $data['email_template'] ) ? wp_kses_post( $data['email_template'] ) : '',
			'settings'        => ! empty( $data['settings'] ) ? maybe_serialize( $data['settings'] ) : '',
			'status'          => ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ) ) ? $data['status'] : 'active',
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to create entity.', METS_TEXT_DOMAIN ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get entity by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Entity ID
	 * @return   object|null   Entity object or null if not found
	 */
	public function get( $id ) {
		global $wpdb;

		$entity = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		) );

		if ( $entity && ! empty( $entity->settings ) ) {
			$entity->settings = maybe_unserialize( $entity->settings );
		}

		return $entity;
	}

	/**
	 * Get entity by slug
	 *
	 * @since    1.0.0
	 * @param    string    $slug    Entity slug
	 * @return   object|null        Entity object or null if not found
	 */
	public function get_by_slug( $slug ) {
		global $wpdb;

		$entity = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE slug = %s",
			$slug
		) );

		if ( $entity && ! empty( $entity->settings ) ) {
			$entity->settings = maybe_unserialize( $entity->settings );
		}

		return $entity;
	}

	/**
	 * Update entity
	 *
	 * @since    1.0.0
	 * @param    int      $id      Entity ID
	 * @param    array    $data    Entity data
	 * @return   bool|WP_Error     True on success, WP_Error on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		// Check if entity exists
		if ( ! $this->get( $id ) ) {
			return new WP_Error( 'entity_not_found', __( 'Entity not found.', METS_TEXT_DOMAIN ) );
		}

		// Check slug uniqueness if provided
		if ( ! empty( $data['slug'] ) && $this->slug_exists( $data['slug'], $id ) ) {
			return new WP_Error( 'slug_exists', __( 'Entity slug already exists.', METS_TEXT_DOMAIN ) );
		}

		// Prepare data for update
		$update_data = array(
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array( '%s' );

		// Update allowed fields
		if ( isset( $data['parent_id'] ) ) {
			$update_data['parent_id'] = ! empty( $data['parent_id'] ) ? intval( $data['parent_id'] ) : null;
			$format[] = '%d';
		}

		if ( ! empty( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
			$format[] = '%s';
		}

		if ( ! empty( $data['slug'] ) ) {
			$update_data['slug'] = sanitize_title( $data['slug'] );
			$format[] = '%s';
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = wp_kses_post( $data['description'] );
			$format[] = '%s';
		}

		if ( isset( $data['logo_url'] ) ) {
			$update_data['logo_url'] = esc_url( $data['logo_url'] );
			$format[] = '%s';
		}

		if ( isset( $data['primary_color'] ) ) {
			$update_data['primary_color'] = sanitize_hex_color( $data['primary_color'] );
			$format[] = '%s';
		}

		if ( isset( $data['secondary_color'] ) ) {
			$update_data['secondary_color'] = sanitize_hex_color( $data['secondary_color'] );
			$format[] = '%s';
		}

		if ( isset( $data['email_template'] ) ) {
			$update_data['email_template'] = wp_kses_post( $data['email_template'] );
			$format[] = '%s';
		}

		if ( isset( $data['settings'] ) ) {
			$update_data['settings'] = maybe_serialize( $data['settings'] );
			$format[] = '%s';
		}

		if ( ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ) ) ) {
			$update_data['status'] = $data['status'];
			$format[] = '%s';
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to update entity.', METS_TEXT_DOMAIN ) );
		}

		return true;
	}

	/**
	 * Delete entity
	 *
	 * @since    1.0.0
	 * @param    int    $id    Entity ID
	 * @return   bool|WP_Error  True on success, WP_Error on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		// Check if entity exists
		if ( ! $this->get( $id ) ) {
			return new WP_Error( 'entity_not_found', __( 'Entity not found.', METS_TEXT_DOMAIN ) );
		}

		// Check if entity has children
		$children = $this->get_children( $id );
		if ( ! empty( $children ) ) {
			return new WP_Error( 'has_children', __( 'Cannot delete entity with child entities.', METS_TEXT_DOMAIN ) );
		}

		// Check if entity has tickets
		$ticket_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE entity_id = %d",
			$id
		) );

		if ( $ticket_count > 0 ) {
			return new WP_Error( 'has_tickets', __( 'Cannot delete entity with existing tickets.', METS_TEXT_DOMAIN ) );
		}

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', __( 'Failed to delete entity.', METS_TEXT_DOMAIN ) );
		}

		return true;
	}

	/**
	 * Get all entities
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   array             Array of entity objects
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'     => 'active',
			'parent_id'  => null,
			'search'     => '',
			'orderby'    => 'name',
			'order'      => 'ASC',
			'limit'      => null,
			'offset'     => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		// Determine if we're doing a search (affects column prefixes and JOIN)
		$has_join = ! empty( $args['search'] );
		$table_prefix = $has_join ? 'e.' : '';

		// Status filter
		if ( ! empty( $args['status'] ) && $args['status'] !== 'all' ) {
			$where_clauses[] = $table_prefix . 'status = %s';
			$where_values[] = $args['status'];
		}

		// Parent ID filter
		if ( $args['parent_id'] === null ) {
			$where_clauses[] = $table_prefix . 'parent_id IS NULL';
		} elseif ( $args['parent_id'] !== 'all' ) {
			$where_clauses[] = $table_prefix . 'parent_id = %d';
			$where_values[] = intval( $args['parent_id'] );
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(e.name LIKE %s OR e.description LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Build WHERE clause
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Order by - Add parent_id to sort parents first, then by name
		$allowed_orderby = array( 'name', 'created_at', 'updated_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'name';
		$order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		
		// Build ORDER BY clause with proper table prefixes
		if ( $has_join ) {
			$order_sql = "ORDER BY e.parent_id IS NULL DESC, e.parent_id ASC, e.{$orderby} {$order}";
		} else {
			$order_sql = "ORDER BY parent_id IS NULL DESC, parent_id ASC, {$orderby} {$order}";
		}

		// Limit and offset
		$limit_sql = '';
		if ( ! empty( $args['limit'] ) ) {
			$limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', intval( $args['limit'] ), intval( $args['offset'] ) );
		}

		// Include parent name if we're doing a search
		if ( $has_join ) {
			$sql = "SELECT e.*, p.name as parent_name 
			        FROM {$this->table_name} e 
			        LEFT JOIN {$this->table_name} p ON e.parent_id = p.id 
			        {$where_sql} {$order_sql} {$limit_sql}";
		} else {
			$sql = "SELECT * FROM {$this->table_name} {$where_sql} {$order_sql} {$limit_sql}";
		}

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		$entities = $wpdb->get_results( $sql );

		// Unserialize settings for each entity
		foreach ( $entities as $entity ) {
			if ( ! empty( $entity->settings ) ) {
				$entity->settings = maybe_unserialize( $entity->settings );
			}
		}

		return $entities;
	}

	/**
	 * Get child entities
	 *
	 * @since    1.0.0
	 * @param    int    $parent_id    Parent entity ID
	 * @return   array                Array of child entity objects
	 */
	public function get_children( $parent_id ) {
		return $this->get_all( array(
			'parent_id' => $parent_id,
			'status'    => 'all',
		) );
	}

	/**
	 * Check if slug exists
	 *
	 * @since    1.0.0
	 * @param    string    $slug       Entity slug
	 * @param    int       $exclude_id Entity ID to exclude from check
	 * @return   bool                  True if slug exists, false otherwise
	 */
	public function slug_exists( $slug, $exclude_id = null ) {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s";
		$values = array( $slug );

		if ( ! empty( $exclude_id ) ) {
			$sql .= ' AND id != %d';
			$values[] = intval( $exclude_id );
		}

		$count = $wpdb->get_var( $wpdb->prepare( $sql, $values ) );

		return $count > 0;
	}

	/**
	 * Get entity count
	 *
	 * @since    1.0.0
	 * @param    array    $args    Query arguments
	 * @return   int               Number of entities
	 */
	public function get_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'    => 'active',
			'parent_id' => null,
			'search'    => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		// Status filter
		if ( ! empty( $args['status'] ) && $args['status'] !== 'all' ) {
			$where_clauses[] = 'status = %s';
			$where_values[] = $args['status'];
		}

		// Parent ID filter
		if ( $args['parent_id'] === null ) {
			$where_clauses[] = 'parent_id IS NULL';
		} elseif ( $args['parent_id'] !== 'all' ) {
			$where_clauses[] = 'parent_id = %d';
			$where_values[] = intval( $args['parent_id'] );
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR description LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Build WHERE clause
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return intval( $wpdb->get_var( $sql ) );
	}

	/**
	 * Search entities
	 *
	 * @since    1.0.0
	 * @param    string    $search_term    Search term
	 * @param    int       $limit          Number of results to return
	 * @return   array                     Array of entity objects
	 */
	public function search( $search_term, $limit = 10 ) {
		return $this->get_all( array(
			'search'    => $search_term,
			'limit'     => $limit,
			'status'    => 'active',
			'parent_id' => 'all', // Include both parent and child entities
		) );
	}
}