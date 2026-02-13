<?php
/**
 * AJAX handlers for admin operations
 *
 * Extracted from METS_Admin to reduce God class complexity.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.1.0
 */

class METS_Admin_Ajax {

	/**
	 * @var string Plugin name
	 */
	private $plugin_name;

	/**
	 * @var string Plugin version
	 */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * AJAX search entities
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_entities() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		// Placeholder for entity search
		wp_send_json_success( array() );
	}

	/**
	 * AJAX get entity agents
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_entity_agents() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$entity_id = isset( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : 0;

		if ( ! $entity_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entity ID.', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$agents = $ticket_model->get_available_agents( $entity_id );

		wp_send_json_success( $agents );
	}

	/**
	 * AJAX assign ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_assign_ticket() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		// Placeholder for ticket assignment
		wp_send_json_success( array() );
	}

	/**
	 * AJAX change ticket status
	 *
	 * @since    1.0.0
	 */
	public function ajax_change_ticket_status() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		// Placeholder for status change
		wp_send_json_success( array() );
	}

	/**
	 * AJAX check workflow transition
	 *
	 * @since    1.0.0
	 */
	public function ajax_check_workflow_transition() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$from_status = isset( $_POST['from_status'] ) ? sanitize_text_field( $_POST['from_status'] ) : '';
		$to_status = isset( $_POST['to_status'] ) ? sanitize_text_field( $_POST['to_status'] ) : '';
		$ticket_data = isset( $_POST['ticket_data'] ) ? $_POST['ticket_data'] : array();

		if ( empty( $from_status ) || empty( $to_status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status parameters.', METS_TEXT_DOMAIN ) ) );
		}

		// Sanitize ticket data
		$sanitized_ticket_data = array();
		if ( isset( $ticket_data['priority'] ) ) {
			$sanitized_ticket_data['priority'] = sanitize_text_field( $ticket_data['priority'] );
		}
		if ( isset( $ticket_data['category'] ) ) {
			$sanitized_ticket_data['category'] = sanitize_text_field( $ticket_data['category'] );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();

		$result = $workflow_model->is_transition_allowed( $from_status, $to_status, get_current_user_id(), $sanitized_ticket_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
				'allowed' => false
			) );
		}

		// Check if note is required
		$requires_note = $workflow_model->requires_note( $from_status, $to_status );

		wp_send_json_success( array(
			'allowed' => true,
			'requires_note' => $requires_note,
			'message' => __( 'Transition allowed.', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * AJAX get allowed status transitions
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_allowed_transitions() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$from_status = isset( $_POST['from_status'] ) ? sanitize_text_field( $_POST['from_status'] ) : '';
		$ticket_data = isset( $_POST['ticket_data'] ) ? $_POST['ticket_data'] : array();

		if ( empty( $from_status ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status parameter.', METS_TEXT_DOMAIN ) ) );
		}

		// Sanitize ticket data
		$sanitized_ticket_data = array();
		if ( isset( $ticket_data['priority'] ) ) {
			$sanitized_ticket_data['priority'] = sanitize_text_field( $ticket_data['priority'] );
		}
		if ( isset( $ticket_data['category'] ) ) {
			$sanitized_ticket_data['category'] = sanitize_text_field( $ticket_data['category'] );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();

		$allowed_statuses = $workflow_model->get_allowed_transitions( $from_status, get_current_user_id(), $sanitized_ticket_data );

		// Get status labels
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$status_options = array();

		foreach ( $allowed_statuses as $status_key ) {
			$status_options[] = array(
				'key' => $status_key,
				'label' => isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ]['label'] : ucfirst( $status_key ),
				'color' => isset( $statuses[ $status_key ] ) ? $statuses[ $status_key ]['color'] : '#007cba'
			);
		}

		wp_send_json_success( array(
			'allowed_statuses' => $allowed_statuses,
			'status_options' => $status_options
		) );
	}

	/**
	 * AJAX handler for SLA widget refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_sla_widget() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_admin_nonce' ) ) {
			wp_die( __( 'Security check failed', METS_TEXT_DOMAIN ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-monitor.php';
		require_once METS_PLUGIN_PATH . 'includes/class-mets-sla-calculator.php';

		$sla_monitor = METS_SLA_Monitor::get_instance();
		$sla_calculator = new METS_SLA_Calculator();

		// Get monitoring metrics
		$metrics = $sla_monitor->get_monitoring_metrics();

		// Get current data
		$approaching_breach = $sla_calculator->get_tickets_approaching_breach( 4 );
		$breached_tickets = $sla_calculator->get_breached_tickets();

		global $wpdb;
		$active_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets
			WHERE status NOT IN ('closed', 'resolved')"
		);

		$sla_tickets = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets
			WHERE status NOT IN ('closed', 'resolved')
			AND (sla_response_due IS NOT NULL OR sla_resolution_due IS NOT NULL)"
		);

		$response_data = array(
			'metrics' => array(
				'breached_count' => count( $breached_tickets ),
				'approaching_count' => count( $approaching_breach ),
				'sla_tickets' => $sla_tickets,
				'active_tickets' => $active_tickets,
			),
			'last_check' => $metrics['last_check'] ?? '',
			'last_check_relative' => ! empty( $metrics['last_check'] ) ?
				human_time_diff( strtotime( $metrics['last_check'] ) ) . ' ' . __( 'ago', METS_TEXT_DOMAIN ) : '',
		);

		wp_send_json_success( $response_data );
	}

	/**
	 * AJAX handler for tickets widget refresh
	 *
	 * @since    1.0.0
	 */
	public function ajax_refresh_tickets_widget() {
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'mets_admin_nonce' ) ) {
			wp_die( __( 'Security check failed', METS_TEXT_DOMAIN ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		global $wpdb;

		// Get ticket counts by status
		$status_counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count
			FROM {$wpdb->prefix}mets_tickets
			GROUP BY status"
		);

		// Get recent tickets (last 7 days)
		$recent_tickets = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets
				WHERE created_at >= %s",
				date( 'Y-m-d H:i:s', strtotime( '-7 days' ) )
			)
		);

		// Get tickets by priority
		$priority_counts = $wpdb->get_results(
			"SELECT priority, COUNT(*) as count
			FROM {$wpdb->prefix}mets_tickets
			WHERE status NOT IN ('closed', 'resolved')
			GROUP BY priority"
		);

		// Format data for response
		$status_data = array();
		foreach ( $status_counts as $status ) {
			$status_data[ $status->status ] = $status->count;
		}

		$priority_data = array();
		foreach ( $priority_counts as $priority ) {
			$priority_data[ $priority->priority ] = $priority->count;
		}

		$response_data = array(
			'status_counts' => $status_data,
			'priority_counts' => $priority_data,
			'recent_tickets' => $recent_tickets,
		);

		wp_send_json_success( $response_data );
	}

	/**
	 * AJAX handler for getting entity categories
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_entity_categories() {
		check_ajax_referer( 'mets_kb_save_article', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Unauthorized', METS_TEXT_DOMAIN ) );
		}

		$entity_id = isset( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : 0;

		// Load category model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		$category_model = new METS_KB_Category_Model();

		// Get categories for this entity (including inherited)
		$categories = $category_model->get_by_entity( $entity_id, true );

		// Build HTML response
		$html = '';
		if ( $categories && ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$html .= '<label class="kb-category-item">';
				$html .= '<input type="checkbox" name="categories[]" value="' . esc_attr( $category->id ) . '">';
				$html .= esc_html( $category->name );
				if ( $category->entity_name && $category->entity_name !== 'Global' ) {
					$html .= ' <small>(' . esc_html( $category->entity_name ) . ')</small>';
				}
				$html .= '</label>';
			}
		} else {
			$html = '<p class="description">' . __( 'No categories available for this entity.', METS_TEXT_DOMAIN ) . '</p>';
		}

		wp_send_json_success( $html );
	}

	/**
	 * AJAX handler for admin KB article search
	 *
	 * @since    1.0.0
	 */
	public function ajax_admin_search_kb_articles() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		$entity_id = intval( $_POST['entity_id'] ?? 0 );
		$limit = intval( $_POST['limit'] ?? 10 );

		if ( strlen( $search_term ) < 2 ) {
			wp_send_json_success( array(
				'articles' => array()
			) );
		}

		// Load KB article model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		// Search for published articles with inheritance
		$args = array(
			'entity_id' => $entity_id > 0 ? $entity_id : null,
			'status' => array( 'published' ),
			'visibility' => array( 'customer', 'staff', 'internal' ),
			'search' => $search_term,
			'per_page' => min( $limit, 20 ), // Cap at 20 for performance
			'page' => 1,
			'orderby' => 'created_at',
			'order' => 'DESC',
			'include_parent' => true
		);

		$results = $article_model->get_articles_with_inheritance( $args );

		$articles = array();
		foreach ( $results['articles'] as $article ) {
			$articles[] = array(
				'id' => $article->id,
				'title' => $article->title,
				'excerpt' => $article->excerpt ?: wp_trim_words( strip_tags( $article->content ), 20 ),
				'entity_name' => $article->entity_name ?: __( 'General', METS_TEXT_DOMAIN ),
				'url' => $this->get_kb_article_admin_url( $article ),
				'helpful_yes' => $article->helpful_yes ?? 0,
				'helpful_no' => $article->helpful_no ?? 0
			);
		}

		wp_send_json_success( array(
			'articles' => $articles,
			'total' => $results['total']
		) );
	}

	/**
	 * Get KB article admin URL
	 *
	 * @since    1.0.0
	 * @param    object   $article   Article object
	 * @return   string             Article admin URL
	 */
	private function get_kb_article_admin_url( $article ) {
		// For admin, link to edit article page
		return admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id );
	}

	/**
	 * AJAX handler for linking KB article to ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_link_kb_article() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$article_id = intval( $_POST['article_id'] ?? 0 );
		$link_type = sanitize_text_field( $_POST['link_type'] ?? 'related' );
		$agent_notes = sanitize_textarea_field( $_POST['agent_notes'] ?? '' );

		if ( ! $ticket_id || ! $article_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket or article ID', METS_TEXT_DOMAIN ) ) );
		}

		// Verify article exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get_by_id( $article_id );

		if ( ! $article ) {
			wp_send_json_error( array( 'message' => __( 'Article not found', METS_TEXT_DOMAIN ) ) );
		}

		// Verify ticket exists
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get_by_id( $ticket_id );

		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found', METS_TEXT_DOMAIN ) ) );
		}

		// Create link
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$link_data = array(
			'ticket_id' => $ticket_id,
			'article_id' => $article_id,
			'link_type' => $link_type,
			'suggested_by' => get_current_user_id(),
			'agent_notes' => $agent_notes,
		);

		$link_id = $link_model->create( $link_data );

		if ( $link_id ) {
			wp_send_json_success( array(
				'message' => __( 'Article linked successfully', METS_TEXT_DOMAIN ),
				'link_id' => $link_id
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to link article', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler for unlinking KB article from ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_unlink_kb_article() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$link_id = intval( $_POST['link_id'] ?? 0 );

		if ( ! $link_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid link ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$result = $link_model->delete( $link_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Article link removed successfully', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to remove article link', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler for marking KB article as helpful/not helpful
	 *
	 * @since    1.0.0
	 */
	public function ajax_mark_kb_helpful() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$article_id = intval( $_POST['article_id'] ?? 0 );
		$helpful = $_POST['helpful'] === '1' ? true : false;

		if ( ! $ticket_id || ! $article_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ticket or article ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-ticket-link-model.php';
		$link_model = new METS_KB_Ticket_Link_Model();

		$result = $link_model->mark_helpful( $ticket_id, $article_id, $helpful );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Feedback updated successfully', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update feedback', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler to get KB tag data
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_kb_tag() {
		check_ajax_referer( 'mets_get_kb_tag', 'nonce' );

		if ( ! current_user_can( 'manage_kb_categories' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', METS_TEXT_DOMAIN ) ) );
		}

		$tag_id = intval( $_POST['tag_id'] ?? 0 );

		if ( ! $tag_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid tag ID', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		$tag_model = new METS_KB_Tag_Model();
		$tag = $tag_model->get( $tag_id );

		if ( ! $tag ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found', METS_TEXT_DOMAIN ) ) );
		}

		wp_send_json_success( $tag );
	}

	/**
	 * AJAX handler for flushing rewrite rules
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_rewrite_rules() {
		check_ajax_referer( 'mets_flush_rewrite_rules', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		flush_rewrite_rules();
		wp_send_json_success( __( 'Rewrite rules flushed successfully', METS_TEXT_DOMAIN ) );
	}

	/**
	 * AJAX handler for creating WooCommerce entity
	 *
	 * @since    1.0.0
	 */
	public function ajax_create_wc_entity() {
		check_ajax_referer( 'mets_create_wc_entity', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();

		// Check if WooCommerce entity already exists
		global $wpdb;
		$existing_entity = $wpdb->get_row(
			"SELECT id FROM {$wpdb->prefix}mets_entities
			WHERE name = 'WooCommerce' AND type = 'company'"
		);

		if ( $existing_entity ) {
			wp_send_json_error( __( 'WooCommerce entity already exists', METS_TEXT_DOMAIN ) );
		}

		// Create WooCommerce entity
		$entity_data = array(
			'name' => 'WooCommerce',
			'type' => 'company',
			'description' => __( 'WooCommerce Store Support', METS_TEXT_DOMAIN ),
			'contact_email' => get_option( 'admin_email' ),
			'status' => 'active',
			'metadata' => json_encode( array(
				'auto_created' => true,
				'integration' => 'woocommerce'
			) )
		);

		$entity_id = $entity_model->create( $entity_data );

		if ( $entity_id ) {
			wp_send_json_success( sprintf( __( 'WooCommerce entity created with ID: %d', METS_TEXT_DOMAIN ), $entity_id ) );
		} else {
			wp_send_json_error( __( 'Failed to create WooCommerce entity', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for testing WooCommerce integration
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_wc_integration() {
		check_ajax_referer( 'mets_test_wc_integration', 'nonce' );

		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', METS_TEXT_DOMAIN ) );
		}

		$results = array();

		// Test 1: WooCommerce availability
		if ( class_exists( 'WooCommerce' ) ) {
			$results[] = '✓ WooCommerce is active (v' . WC()->version . ')';
		} else {
			$results[] = '✗ WooCommerce is not active';
			wp_send_json_success( $results );
			return;
		}

		// Test 2: WooCommerce entity
		global $wpdb;
		$wc_entity = $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}mets_entities
			WHERE name = 'WooCommerce' AND type = 'company'"
		);

		if ( $wc_entity ) {
			$results[] = '✓ WooCommerce entity exists (ID: ' . $wc_entity->id . ')';
		} else {
			$results[] = '! WooCommerce entity not found (will be created automatically)';
		}

		// Test 3: Endpoints
		$endpoints = array( 'support-tickets', 'create-ticket' );
		foreach ( $endpoints as $endpoint ) {
			if ( get_option( 'woocommerce_myaccount_' . str_replace( '-', '_', $endpoint ) . '_endpoint', $endpoint ) ) {
				$results[] = '✓ Endpoint "' . $endpoint . '" is configured';
			} else {
				$results[] = '! Endpoint "' . $endpoint . '" uses default settings';
			}
		}

		// Test 4: Database tables
		$tables = array( 'mets_tickets', 'mets_entities', 'mets_ticket_replies' );
		foreach ( $tables as $table ) {
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'" );
			if ( $table_exists ) {
				$results[] = '✓ Table ' . $wpdb->prefix . $table . ' exists';
			} else {
				$results[] = '✗ Table ' . $wpdb->prefix . $table . ' is missing';
			}
		}

		// Test 5: Settings
		$settings = METS_WooCommerce_Integration::get_settings();
		if ( $settings['enabled'] ) {
			$results[] = '✓ WooCommerce integration is enabled';
		} else {
			$results[] = '! WooCommerce integration is disabled';
		}

		// Test 6: Permissions
		if ( current_user_can( 'manage_tickets' ) ) {
			$results[] = '✓ User has manage_tickets capability';
		} else {
			$results[] = '✗ User lacks manage_tickets capability';
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for loading bulk tickets
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_tickets() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();

		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		// Apply filters
		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "t.status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		if ( ! empty( $filters['priority'] ) && ! in_array( 'all', $filters['priority'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['priority'] ), '%s' ) );
			$where_clauses[] = "t.priority IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['priority'] );
		}

		if ( ! empty( $filters['entity_id'] ) ) {
			$where_clauses[] = 't.entity_id = %d';
			$where_values[] = intval( $filters['entity_id'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clauses[] = 't.created_at >= %s';
			$where_values[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clauses[] = 't.created_at <= %s';
			$where_values[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}

		if ( ! empty( $filters['assigned_to'] ) ) {
			if ( $filters['assigned_to'] === 'unassigned' ) {
				$where_clauses[] = 't.assigned_to IS NULL';
			} else {
				$where_clauses[] = 't.assigned_to = %d';
				$where_values[] = intval( $filters['assigned_to'] );
			}
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT t.id, t.subject, t.status, t.priority, t.created_at, e.name as entity_name
			FROM {$wpdb->prefix}mets_tickets t
			LEFT JOIN {$wpdb->prefix}mets_entities e ON t.entity_id = e.id
			WHERE $where_sql
			ORDER BY t.created_at DESC
			LIMIT 500
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$tickets = $wpdb->get_results( $query );

		wp_send_json_success( $tickets );
	}

	/**
	 * AJAX handler for loading bulk entities
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_entities() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_entities' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();

		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $filters['type'] ) && ! in_array( 'all', $filters['type'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['type'] ), '%s' ) );
			$where_clauses[] = "type IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['type'] );
		}

		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT id, name, type, status, created_at,
			       (SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets WHERE entity_id = e.id) as ticket_count
			FROM {$wpdb->prefix}mets_entities e
			WHERE $where_sql
			ORDER BY name
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$entities = $wpdb->get_results( $query );

		wp_send_json_success( $entities );
	}

	/**
	 * AJAX handler for loading bulk KB articles
	 *
	 * @since    1.0.0
	 */
	public function ajax_load_bulk_kb_articles() {
		check_ajax_referer( 'mets_bulk_action', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$filters = $_POST['filters'] ?? array();

		global $wpdb;
		$where_clauses = array( '1=1' );
		$where_values = array();

		if ( ! empty( $filters['status'] ) && ! in_array( 'all', $filters['status'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['status'] ), '%s' ) );
			$where_clauses[] = "a.status IN ($placeholders)";
			$where_values = array_merge( $where_values, $filters['status'] );
		}

		if ( ! empty( $filters['category_id'] ) ) {
			$where_clauses[] = 'a.category_id = %d';
			$where_values[] = intval( $filters['category_id'] );
		}

		if ( isset( $filters['featured'] ) && $filters['featured'] !== '' ) {
			$where_clauses[] = 'a.featured = %d';
			$where_values[] = intval( $filters['featured'] );
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$query = "
			SELECT a.id, a.title, a.status, a.featured, a.view_count, a.created_at,
			       c.name as category_name
			FROM {$wpdb->prefix}mets_kb_articles a
			LEFT JOIN {$wpdb->prefix}mets_kb_categories c ON a.category_id = c.id
			WHERE $where_sql
			ORDER BY a.created_at DESC
			LIMIT 500
		";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare( $query, $where_values );
		}

		$articles = $wpdb->get_results( $query );

		wp_send_json_success( $articles );
	}

	/**
	 * AJAX handler for optimizing all database tables
	 *
	 * @since    1.0.0
	 */
	public function ajax_optimize_all_tables() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$database_optimizer = METS_Database_Optimizer::get_instance();
		$results = $database_optimizer->optimize_tables();

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'Optimized %d tables successfully.', METS_TEXT_DOMAIN ),
				count( $results['optimized'] )
			),
			'results' => $results
		) );
	}

	/**
	 * AJAX handler for creating database indexes
	 *
	 * @since    1.0.0
	 */
	public function ajax_create_indexes() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$database_optimizer = METS_Database_Optimizer::get_instance();
		$success = $database_optimizer->create_optimized_indexes();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Database indexes created successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to create some indexes. Check error logs.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for warming up cache
	 *
	 * @since    1.0.0
	 */
	public function ajax_warm_cache() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->warm_up_cache();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Cache warmed up successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to warm up cache. Check error logs.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for flushing all cache
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_all_cache() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->flush_all();

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'All cache flushed successfully.', METS_TEXT_DOMAIN )
			) );
		} else {
			wp_send_json_error( __( 'Failed to flush cache.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler for flushing specific cache group
	 *
	 * @since    1.0.0
	 */
	public function ajax_flush_cache_group() {
		check_ajax_referer( 'mets_performance_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', METS_TEXT_DOMAIN ) );
		}

		$group = sanitize_text_field( $_POST['group'] ?? '' );
		if ( empty( $group ) ) {
			wp_send_json_error( __( 'Invalid cache group.', METS_TEXT_DOMAIN ) );
		}

		$cache_manager = METS_Cache_Manager::get_instance();
		$success = $cache_manager->flush_group( $group );

		if ( $success ) {
			wp_send_json_success( array(
				'message' => sprintf( __( 'Cache group "%s" flushed successfully.', METS_TEXT_DOMAIN ), $group )
			) );
		} else {
			wp_send_json_error( __( 'Failed to flush cache group.', METS_TEXT_DOMAIN ) );
		}
	}

	/**
	 * AJAX handler to assign entities to agent
	 *
	 * @since    1.0.0
	 */
	public function ajax_assign_agent_entities() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$entity_ids = isset( $_POST['entity_ids'] ) ? array_map( 'intval', $_POST['entity_ids'] ) : array();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', METS_TEXT_DOMAIN ) ) );
		}

		$role_manager = METS_Role_Manager::get_instance();
		$result = $role_manager->assign_entities_to_user( $user_id, $entity_ids );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Entities assigned successfully.', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to assign entities.', METS_TEXT_DOMAIN ) ) );
		}
	}

	/**
	 * AJAX handler to update agent role
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_agent_role() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_agents' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$new_role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';

		if ( ! $user_id || ! $new_role ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', METS_TEXT_DOMAIN ) ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', METS_TEXT_DOMAIN ) ) );
		}

		// Check if current user can manage this role
		$role_manager = METS_Role_Manager::get_instance();
		$current_user_roles = wp_get_current_user()->roles;
		$can_manage = false;

		foreach ( $current_user_roles as $current_role ) {
			if ( $role_manager->can_manage_role( $current_role, $new_role ) ) {
				$can_manage = true;
				break;
			}
		}

		if ( ! $can_manage && ! current_user_can( 'manage_ticket_system' ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot assign this role.', METS_TEXT_DOMAIN ) ) );
		}

		// Remove all existing METS roles
		$mets_roles = array_keys( $role_manager->get_roles() );
		foreach ( $mets_roles as $role ) {
			$user->remove_role( $role );
		}

		// Add new role
		$user->add_role( $new_role );

		wp_send_json_success( array( 'message' => __( 'Role updated successfully.', METS_TEXT_DOMAIN ) ) );
	}

	/**
	 * AJAX handler to get agent performance metrics
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_agent_performance() {
		check_ajax_referer( 'mets_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'view_agent_performance' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'month';

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', METS_TEXT_DOMAIN ) ) );
		}

		$role_manager = METS_Role_Manager::get_instance();
		$performance = $role_manager->get_agent_performance( $user_id, $period );

		wp_send_json_success( array(
			'performance' => $performance,
			'message' => __( 'Performance data retrieved successfully.', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * AJAX handler to request article changes
	 *
	 * @since    1.0.0
	 */
	public function ajax_request_article_changes() {
		check_ajax_referer( 'mets_kb_changes', 'nonce' );

		if ( ! current_user_can( 'review_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', METS_TEXT_DOMAIN ) ) );
		}

		$article_id = isset( $_POST['article_id'] ) ? intval( $_POST['article_id'] ) : 0;
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

		if ( ! $article_id || ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', METS_TEXT_DOMAIN ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		// Update article status to draft
		$result = $article_model->update( $article_id, array( 'status' => 'draft' ) );

		if ( $result ) {
			// Store the feedback message
			$article = $article_model->get_by_id( $article_id );
			if ( $article ) {
				update_post_meta( $article_id, 'mets_review_feedback', $message );
				update_post_meta( $article_id, 'mets_review_date', current_time( 'mysql' ) );
				update_post_meta( $article_id, 'mets_reviewer_id', get_current_user_id() );

				// Send notification email to author
				$author = get_userdata( $article->author_id );
				if ( $author ) {
					$subject = sprintf( __( 'Changes Requested: %s', METS_TEXT_DOMAIN ), $article->title );
					$email_message = sprintf(
						__( "Hello %s,\n\nChanges have been requested for your article '%s'.\n\nReviewer feedback:\n%s\n\nPlease make the necessary updates and resubmit for review.\n\nEdit article: %s", METS_TEXT_DOMAIN ),
						$author->display_name,
						$article->title,
						$message,
						admin_url( 'admin.php?page=mets-kb-add-article&edit=' . $article_id )
					);

					wp_mail( $author->user_email, $subject, $email_message );
				}
			}

			wp_send_json_success( array( 'message' => __( 'Feedback sent to author successfully.', METS_TEXT_DOMAIN ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to process request.', METS_TEXT_DOMAIN ) ) );
		}
	}
}
