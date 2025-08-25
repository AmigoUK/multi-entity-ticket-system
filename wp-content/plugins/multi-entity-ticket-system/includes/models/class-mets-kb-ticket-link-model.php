<?php
/**
 * KB Ticket Link Model
 *
 * Handles all database operations for ticket-article links
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @since      1.0.0
 */

/**
 * The KB Ticket Link Model class.
 *
 * This class defines the model for managing relationships between tickets and KB articles.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/models
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Ticket_Link_Model {

	/**
	 * The database table name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $table_name    The database table name
	 */
	private $table_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'mets_kb_ticket_links';
	}

	/**
	 * Create a new ticket-article link
	 *
	 * @since    1.0.0
	 * @param    array    $data    Link data
	 * @return   int|false         Link ID on success, false on failure
	 */
	public function create( $data ) {
		global $wpdb;

		$defaults = array(
			'ticket_id' => 0,
			'article_id' => 0,
			'link_type' => 'related',
			'suggested_by' => get_current_user_id(),
			'helpful' => null,
			'agent_notes' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields
		if ( empty( $data['ticket_id'] ) || empty( $data['article_id'] ) ) {
			return false;
		}

		// Check if link already exists
		$existing = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM {$this->table_name} WHERE ticket_id = %d AND article_id = %d",
			$data['ticket_id'],
			$data['article_id']
		) );

		if ( $existing ) {
			// Update existing link
			return $this->update( $existing, $data );
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'ticket_id' => absint( $data['ticket_id'] ),
				'article_id' => absint( $data['article_id'] ),
				'link_type' => sanitize_text_field( $data['link_type'] ),
				'suggested_by' => $data['suggested_by'] ? absint( $data['suggested_by'] ) : null,
				'helpful' => $data['helpful'] !== null ? (bool) $data['helpful'] : null,
				'agent_notes' => sanitize_textarea_field( $data['agent_notes'] ),
			),
			array(
				'%d', // ticket_id
				'%d', // article_id
				'%s', // link_type
				'%d', // suggested_by
				'%d', // helpful
				'%s', // agent_notes
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing ticket-article link
	 *
	 * @since    1.0.0
	 * @param    int      $id      Link ID
	 * @param    array    $data    Update data
	 * @return   bool              True on success, false on failure
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$update_data = array();
		$format = array();

		// Only update allowed fields
		$allowed_fields = array( 'link_type', 'helpful', 'agent_notes' );

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( $field === 'helpful' ) {
					$update_data[ $field ] = $data[ $field ] !== null ? (bool) $data[ $field ] : null;
					$format[] = '%d';
				} elseif ( $field === 'agent_notes' ) {
					$update_data[ $field ] = sanitize_textarea_field( $data[ $field ] );
					$format[] = '%s';
				} else {
					$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
					$format[] = '%s';
				}
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => absint( $id ) ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete a ticket-article link
	 *
	 * @since    1.0.0
	 * @param    int    $id    Link ID
	 * @return   bool          True on success, false on failure
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get links by ticket ID
	 *
	 * @since    1.0.0
	 * @param    int      $ticket_id    Ticket ID
	 * @param    string   $link_type    Optional. Filter by link type
	 * @return   array                  Array of link objects
	 */
	public function get_by_ticket( $ticket_id, $link_type = '' ) {
		global $wpdb;

		$sql = "SELECT tl.*, 
				       a.title as article_title,
				       a.slug as article_slug,
				       a.excerpt as article_excerpt,
				       a.status as article_status,
				       u.display_name as suggested_by_name
				FROM {$this->table_name} tl
				LEFT JOIN {$wpdb->prefix}mets_kb_articles a ON tl.article_id = a.id
				LEFT JOIN {$wpdb->prefix}users u ON tl.suggested_by = u.ID
				WHERE tl.ticket_id = %d";

		$params = array( absint( $ticket_id ) );

		if ( ! empty( $link_type ) ) {
			$sql .= " AND tl.link_type = %s";
			$params[] = sanitize_text_field( $link_type );
		}

		$sql .= " ORDER BY tl.created_at DESC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Get links by article ID
	 *
	 * @since    1.0.0
	 * @param    int      $article_id    Article ID
	 * @param    string   $link_type     Optional. Filter by link type
	 * @return   array                   Array of link objects
	 */
	public function get_by_article( $article_id, $link_type = '' ) {
		global $wpdb;

		$sql = "SELECT tl.*, 
				       t.ticket_number,
				       t.subject as ticket_subject,
				       t.status as ticket_status,
				       t.priority as ticket_priority,
				       u.display_name as suggested_by_name
				FROM {$this->table_name} tl
				LEFT JOIN {$wpdb->prefix}mets_tickets t ON tl.ticket_id = t.id
				LEFT JOIN {$wpdb->prefix}users u ON tl.suggested_by = u.ID
				WHERE tl.article_id = %d";

		$params = array( absint( $article_id ) );

		if ( ! empty( $link_type ) ) {
			$sql .= " AND tl.link_type = %s";
			$params[] = sanitize_text_field( $link_type );
		}

		$sql .= " ORDER BY tl.created_at DESC";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Get a specific link by ID
	 *
	 * @since    1.0.0
	 * @param    int    $id    Link ID
	 * @return   object|null   Link object or null if not found
	 */
	public function get_by_id( $id ) {
		global $wpdb;

		$sql = "SELECT tl.*, 
				       a.title as article_title,
				       a.slug as article_slug,
				       t.ticket_number,
				       t.subject as ticket_subject,
				       u.display_name as suggested_by_name
				FROM {$this->table_name} tl
				LEFT JOIN {$wpdb->prefix}mets_kb_articles a ON tl.article_id = a.id
				LEFT JOIN {$wpdb->prefix}mets_tickets t ON tl.ticket_id = t.id
				LEFT JOIN {$wpdb->prefix}users u ON tl.suggested_by = u.ID
				WHERE tl.id = %d";

		return $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ) );
	}

	/**
	 * Mark a link as helpful or not helpful
	 *
	 * @since    1.0.0
	 * @param    int     $ticket_id    Ticket ID
	 * @param    int     $article_id   Article ID
	 * @param    bool    $helpful      Whether the article was helpful
	 * @return   bool                  True on success, false on failure
	 */
	public function mark_helpful( $ticket_id, $article_id, $helpful ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table_name,
			array( 'helpful' => (bool) $helpful ),
			array( 
				'ticket_id' => absint( $ticket_id ),
				'article_id' => absint( $article_id )
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get helpful statistics for an article
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 * @return   array                 Array with helpful and not_helpful counts
	 */
	public function get_helpful_stats( $article_id ) {
		global $wpdb;

		$sql = "SELECT 
				    SUM(CASE WHEN helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
				    SUM(CASE WHEN helpful = 0 THEN 1 ELSE 0 END) as not_helpful_count,
				    COUNT(*) as total_links
				FROM {$this->table_name} 
				WHERE article_id = %d AND helpful IS NOT NULL";

		$stats = $wpdb->get_row( $wpdb->prepare( $sql, absint( $article_id ) ) );

		return array(
			'helpful' => intval( $stats->helpful_count ?? 0 ),
			'not_helpful' => intval( $stats->not_helpful_count ?? 0 ),
			'total_links' => intval( $stats->total_links ?? 0 ),
		);
	}

	/**
	 * Get most linked articles
	 *
	 * @since    1.0.0
	 * @param    int    $limit         Number of articles to return
	 * @param    int    $entity_id     Optional. Filter by entity
	 * @return   array                 Array of article objects with link counts
	 */
	public function get_most_linked_articles( $limit = 10, $entity_id = null ) {
		global $wpdb;

		$sql = "SELECT a.*, 
				       COUNT(tl.id) as link_count,
				       SUM(CASE WHEN tl.helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
				       e.name as entity_name
				FROM {$wpdb->prefix}mets_kb_articles a
				LEFT JOIN {$this->table_name} tl ON a.id = tl.article_id
				LEFT JOIN {$wpdb->prefix}mets_entities e ON a.entity_id = e.id
				WHERE a.status = 'published'";

		$params = array();

		if ( $entity_id ) {
			$sql .= " AND a.entity_id = %d";
			$params[] = absint( $entity_id );
		}

		$sql .= " GROUP BY a.id 
				  HAVING link_count > 0
				  ORDER BY link_count DESC, helpful_count DESC 
				  LIMIT %d";

		$params[] = absint( $limit );

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Bulk create links from an array
	 *
	 * @since    1.0.0
	 * @param    array    $links    Array of link data
	 * @return   array             Array of created link IDs
	 */
	public function bulk_create( $links ) {
		$created_ids = array();

		foreach ( $links as $link_data ) {
			$link_id = $this->create( $link_data );
			if ( $link_id ) {
				$created_ids[] = $link_id;
			}
		}

		return $created_ids;
	}
}