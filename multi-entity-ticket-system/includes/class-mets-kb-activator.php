<?php
/**
 * Knowledgebase Activator
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Fired during plugin activation for knowledgebase components.
 *
 * This class defines all code necessary to run during the plugin's activation
 * specifically for knowledgebase functionality.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Activator {

	/**
	 * Create knowledgebase database tables.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// KB Articles table
		$table_articles = $wpdb->prefix . 'mets_kb_articles';
		$sql_articles = "CREATE TABLE $table_articles (
			id int(11) NOT NULL AUTO_INCREMENT,
			entity_id int(11) DEFAULT NULL,
			title varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			content longtext,
			excerpt text,
			author_id bigint(20) unsigned NOT NULL,
			status enum('draft','pending_review','approved','published','rejected','archived') NOT NULL DEFAULT 'draft',
			visibility enum('internal','staff','customer') NOT NULL DEFAULT 'customer',
			featured tinyint(1) NOT NULL DEFAULT 0,
			view_count int(11) NOT NULL DEFAULT 0,
			helpful_count int(11) NOT NULL DEFAULT 0,
			not_helpful_count int(11) NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			meta_title varchar(255) DEFAULT NULL,
			meta_description text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			reviewed_by bigint(20) unsigned DEFAULT NULL,
			reviewed_at datetime DEFAULT NULL,
			published_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY entity_id (entity_id),
			KEY author_id (author_id),
			KEY status (status),
			KEY visibility (visibility),
			KEY slug (slug),
			KEY featured (featured),
			KEY created_at (created_at),
			UNIQUE KEY unique_slug_entity (slug, entity_id),
			FOREIGN KEY (entity_id) REFERENCES {$wpdb->prefix}mets_entities(id) ON DELETE CASCADE,
			FOREIGN KEY (author_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
			FOREIGN KEY (reviewed_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
		) $charset_collate;";

		// KB Categories table
		$table_categories = $wpdb->prefix . 'mets_kb_categories';
		$sql_categories = "CREATE TABLE $table_categories (
			id int(11) NOT NULL AUTO_INCREMENT,
			entity_id int(11) DEFAULT NULL,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			parent_id int(11) DEFAULT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			icon varchar(50) DEFAULT NULL,
			color varchar(7) DEFAULT NULL,
			article_count int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity_id (entity_id),
			KEY parent_id (parent_id),
			KEY slug (slug),
			KEY sort_order (sort_order),
			UNIQUE KEY unique_slug_entity (slug, entity_id),
			FOREIGN KEY (entity_id) REFERENCES {$wpdb->prefix}mets_entities(id) ON DELETE CASCADE,
			FOREIGN KEY (parent_id) REFERENCES $table_categories(id) ON DELETE CASCADE
		) $charset_collate;";

		// KB Tags table
		$table_tags = $wpdb->prefix . 'mets_kb_tags';
		$sql_tags = "CREATE TABLE $table_tags (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			usage_count int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY usage_count (usage_count),
			KEY name (name)
		) $charset_collate;";

		// KB Article-Tags relationship table
		$table_article_tags = $wpdb->prefix . 'mets_kb_article_tags';
		$sql_article_tags = "CREATE TABLE $table_article_tags (
			article_id int(11) NOT NULL,
			tag_id int(11) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (article_id, tag_id),
			KEY tag_id (tag_id),
			FOREIGN KEY (article_id) REFERENCES $table_articles(id) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES $table_tags(id) ON DELETE CASCADE
		) $charset_collate;";

		// KB Article-Categories relationship table
		$table_article_categories = $wpdb->prefix . 'mets_kb_article_categories';
		$sql_article_categories = "CREATE TABLE $table_article_categories (
			article_id int(11) NOT NULL,
			category_id int(11) NOT NULL,
			is_primary tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (article_id, category_id),
			KEY category_id (category_id),
			KEY is_primary (is_primary),
			FOREIGN KEY (article_id) REFERENCES $table_articles(id) ON DELETE CASCADE,
			FOREIGN KEY (category_id) REFERENCES $table_categories(id) ON DELETE CASCADE
		) $charset_collate;";

		// KB Attachments table
		$table_attachments = $wpdb->prefix . 'mets_kb_attachments';
		$sql_attachments = "CREATE TABLE $table_attachments (
			id int(11) NOT NULL AUTO_INCREMENT,
			article_id int(11) NOT NULL,
			filename varchar(255) NOT NULL,
			original_filename varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_size bigint(20) NOT NULL,
			mime_type varchar(255) NOT NULL,
			file_hash varchar(64) NOT NULL,
			download_count int(11) NOT NULL DEFAULT 0,
			uploaded_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY article_id (article_id),
			KEY uploaded_by (uploaded_by),
			KEY mime_type (mime_type),
			KEY file_hash (file_hash),
			FOREIGN KEY (article_id) REFERENCES $table_articles(id) ON DELETE CASCADE,
			FOREIGN KEY (uploaded_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
		) $charset_collate;";

		// KB Analytics table for tracking views and interactions
		$table_analytics = $wpdb->prefix . 'mets_kb_analytics';
		$sql_analytics = "CREATE TABLE $table_analytics (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			article_id int(11) NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			session_id varchar(255) DEFAULT NULL,
			action enum('view','helpful','not_helpful','search','download') NOT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text,
			referrer varchar(500) DEFAULT NULL,
			search_query varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY article_id (article_id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at),
			KEY session_id (session_id),
			FOREIGN KEY (article_id) REFERENCES $table_articles(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
		) $charset_collate;";

		// KB Search Log table for tracking search queries
		$table_search_log = $wpdb->prefix . 'mets_kb_search_log';
		$sql_search_log = "CREATE TABLE $table_search_log (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			entity_id int(11) DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			query varchar(255) NOT NULL,
			results_count int(11) NOT NULL DEFAULT 0,
			clicked_article_id int(11) DEFAULT NULL,
			session_id varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY entity_id (entity_id),
			KEY user_id (user_id),
			KEY query (query),
			KEY created_at (created_at),
			KEY clicked_article_id (clicked_article_id),
			FOREIGN KEY (entity_id) REFERENCES {$wpdb->prefix}mets_entities(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL,
			FOREIGN KEY (clicked_article_id) REFERENCES $table_articles(id) ON DELETE SET NULL
		) $charset_collate;";

		// KB Ticket-Article Links table for linking tickets to helpful articles
		$table_ticket_links = $wpdb->prefix . 'mets_kb_ticket_links';
		$sql_ticket_links = "CREATE TABLE $table_ticket_links (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			article_id int(11) NOT NULL,
			link_type enum('suggested','resolved','related','referenced') NOT NULL DEFAULT 'related',
			suggested_by bigint(20) unsigned DEFAULT NULL,
			helpful tinyint(1) DEFAULT NULL,
			agent_notes text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ticket_id (ticket_id),
			KEY article_id (article_id),
			KEY link_type (link_type),
			KEY suggested_by (suggested_by),
			KEY helpful (helpful),
			KEY created_at (created_at),
			UNIQUE KEY unique_ticket_article (ticket_id, article_id),
			FOREIGN KEY (ticket_id) REFERENCES {$wpdb->prefix}mets_tickets(id) ON DELETE CASCADE,
			FOREIGN KEY (article_id) REFERENCES $table_articles(id) ON DELETE CASCADE,
			FOREIGN KEY (suggested_by) REFERENCES {$wpdb->prefix}users(ID) ON DELETE SET NULL
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create tables
		dbDelta( $sql_articles );
		dbDelta( $sql_categories );
		dbDelta( $sql_tags );
		dbDelta( $sql_article_tags );
		dbDelta( $sql_article_categories );
		dbDelta( $sql_attachments );
		dbDelta( $sql_analytics );
		dbDelta( $sql_search_log );
		dbDelta( $sql_ticket_links );

		// Add version option
		add_option( 'mets_kb_db_version', '1.0.0' );

		// Create default categories for each entity
		self::create_default_categories();

		// Add capabilities
		self::add_capabilities();
	}

	/**
	 * Create default categories for all entities
	 *
	 * @since    1.0.0
	 */
	private static function create_default_categories() {
		global $wpdb;

		// Get all entities
		$entities = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}mets_entities" );

		$default_categories = array(
			array(
				'name' => 'Getting Started',
				'slug' => 'getting-started',
				'description' => 'Basic information and first steps',
				'icon' => 'dashicons-welcome-learn-more',
				'color' => '#0073aa',
				'sort_order' => 1
			),
			array(
				'name' => 'Account Management',
				'slug' => 'account-management',
				'description' => 'Managing your account settings and profile',
				'icon' => 'dashicons-admin-users',
				'color' => '#00a32a',
				'sort_order' => 2
			),
			array(
				'name' => 'Troubleshooting',
				'slug' => 'troubleshooting',
				'description' => 'Solutions to common problems',
				'icon' => 'dashicons-sos',
				'color' => '#d63638',
				'sort_order' => 3
			),
			array(
				'name' => 'Policies & Procedures',
				'slug' => 'policies-procedures',
				'description' => 'Important policies and standard procedures',
				'icon' => 'dashicons-admin-page',
				'color' => '#135e96',
				'sort_order' => 4
			),
			array(
				'name' => 'Advanced Features',
				'slug' => 'advanced-features',
				'description' => 'Advanced functionality and features',
				'icon' => 'dashicons-admin-tools',
				'color' => '#996600',
				'sort_order' => 5
			)
		);

		foreach ( $entities as $entity ) {
			foreach ( $default_categories as $category ) {
				$wpdb->insert(
					$wpdb->prefix . 'mets_kb_categories',
					array(
						'entity_id' => $entity->id,
						'name' => $category['name'],
						'slug' => $category['slug'],
						'description' => $category['description'],
						'icon' => $category['icon'],
						'color' => $category['color'],
						'sort_order' => $category['sort_order']
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
				);
			}
		}

		// Create global categories (entity_id = NULL)
		foreach ( $default_categories as $category ) {
			$wpdb->insert(
				$wpdb->prefix . 'mets_kb_categories',
				array(
					'entity_id' => null,
					'name' => $category['name'] . ' (Global)',
					'slug' => $category['slug'] . '-global',
					'description' => $category['description'] . ' - Available to all entities',
					'icon' => $category['icon'],
					'color' => $category['color'],
					'sort_order' => $category['sort_order']
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Add KB capabilities to existing users (public method for dynamic calls)
	 *
	 * @since    1.0.0
	 */
	public static function add_kb_capabilities_to_existing_users() {
		self::add_capabilities();
	}

	/**
	 * Add knowledgebase capabilities to user roles
	 *
	 * @since    1.0.0
	 */
	private static function add_capabilities() {
		// Get roles
		$admin = get_role( 'administrator' );
		$manager = get_role( 'mets_manager' );
		$agent = get_role( 'mets_agent' );

		// Define capabilities
		$kb_caps = array(
			// Article management
			'read_kb_articles',
			'create_kb_articles', 
			'edit_kb_articles',
			'edit_others_kb_articles',
			'delete_kb_articles',
			'delete_others_kb_articles',
			'publish_kb_articles',
			
			// Review capabilities
			'review_kb_articles',
			'approve_kb_articles',
			
			// Category management
			'manage_kb_categories',
			
			// Tag management  
			'manage_kb_tags',
			
			// Analytics access
			'view_kb_analytics',
			
			// Global KB management
			'manage_kb_global',
			
			// Attachment management
			'upload_kb_attachments',
			'delete_kb_attachments'
		);

		// Administrators get all capabilities
		if ( $admin ) {
			foreach ( $kb_caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		// Managers get most capabilities
		if ( $manager ) {
			$manager_caps = array(
				'read_kb_articles',
				'create_kb_articles',
				'edit_kb_articles',
				'edit_others_kb_articles',
				'delete_kb_articles',
				'delete_others_kb_articles',
				'publish_kb_articles',
				'review_kb_articles',
				'approve_kb_articles',
				'manage_kb_categories',
				'manage_kb_tags',
				'view_kb_analytics',
				'upload_kb_attachments',
				'delete_kb_attachments'
			);
			
			foreach ( $manager_caps as $cap ) {
				$manager->add_cap( $cap );
			}
		}

		// Agents get basic capabilities
		if ( $agent ) {
			$agent_caps = array(
				'read_kb_articles',
				'create_kb_articles',
				'edit_kb_articles',
				'upload_kb_attachments'
			);
			
			foreach ( $agent_caps as $cap ) {
				$agent->add_cap( $cap );
			}
		}
	}
}