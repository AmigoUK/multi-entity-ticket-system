<?php
/**
 * The public knowledgebase class
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @since      1.0.0
 */

/**
 * The public knowledgebase class.
 *
 * Defines the knowledgebase functionality for the public side.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Knowledgebase_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Translation helper method
	 *
	 * @since    1.0.0
	 * @param    string    $text        Text to translate
	 * @param    string    $domain      Text domain
	 * @return   string                 Translated text or original if too early
	 */
	private function safe_translate( $text, $domain = METS_TEXT_DOMAIN ) {
		if ( ! did_action( 'init' ) ) {
			return $text;
		}
		return __( $text, $domain );
	}

	/**
	 * Safe echo translation helper method
	 *
	 * @since    1.0.0
	 * @param    string    $text        Text to translate and echo
	 * @param    string    $domain      Text domain
	 */
	private function safe_echo_translate( $text, $domain = METS_TEXT_DOMAIN ) {
		if ( ! did_action( 'init' ) ) {
			echo esc_html( $text );
			return;
		}
		_e( $text, $domain );
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since    1.0.0
	 */
	public function init() {
		// Add shortcodes
		add_shortcode( 'mets_knowledgebase', array( $this, 'display_knowledgebase_shortcode' ) );
		add_shortcode( 'mets_kb_search', array( $this, 'display_kb_search_shortcode' ) );
		add_shortcode( 'mets_kb_categories', array( $this, 'display_kb_categories_shortcode' ) );
		add_shortcode( 'mets_kb_popular_articles', array( $this, 'display_popular_articles_shortcode' ) );

		// Add rewrite rules for pretty URLs
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );

		// AJAX handlers for public users
		add_action( 'wp_ajax_mets_kb_search', array( $this, 'handle_kb_search_ajax' ) );
		add_action( 'wp_ajax_nopriv_mets_kb_search', array( $this, 'handle_kb_search_ajax' ) );
		add_action( 'wp_ajax_mets_kb_rate_article', array( $this, 'handle_article_rating' ) );
		add_action( 'wp_ajax_nopriv_mets_kb_rate_article', array( $this, 'handle_article_rating' ) );
		add_action( 'wp_ajax_mets_kb_download_attachment', array( $this, 'handle_attachment_download' ) );
		add_action( 'wp_ajax_nopriv_mets_kb_download_attachment', array( $this, 'handle_attachment_download' ) );

		// Enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Ensure rewrite rules are flushed when needed
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 
			$this->plugin_name . '-kb-public', 
			plugin_dir_url( __FILE__ ) . 'css/mets-kb-public.css', 
			array(), 
			$this->version, 
			'all' 
		);
	}

	/**
	 * Register the JavaScript for the public-facing side.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 
			$this->plugin_name . '-kb-public', 
			plugin_dir_url( __FILE__ ) . 'js/mets-kb-public.js', 
			array( 'jquery' ), 
			$this->version, 
			false 
		);

		// Localize script for AJAX
		wp_localize_script( $this->plugin_name . '-kb-public', 'mets_kb_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mets_kb_nonce' ),
			'home_url' => home_url(),
			'strings' => array(
				'search_placeholder' => $this->safe_translate( 'Search knowledgebase...' ),
				'no_results' => $this->safe_translate( 'No articles found.' ),
				'loading' => $this->safe_translate( 'Loading...' ),
				'helpful_yes' => $this->safe_translate( 'Yes' ),
				'helpful_no' => $this->safe_translate( 'No' ),
				'thank_you' => $this->safe_translate( 'Thank you for your feedback!' ),
				'error' => $this->safe_translate( 'An error occurred. Please try again.' )
			)
		) );
	}

	/**
	 * Maybe flush rewrite rules if KB rules were updated
	 *
	 * @since    1.0.0
	 */
	public function maybe_flush_rewrite_rules() {
		$kb_rules_version = get_option( 'mets_kb_rules_version', '1.0.0' );
		$current_version = '1.1.0'; // Increment when rules change
		
		if ( version_compare( $kb_rules_version, $current_version, '<' ) ) {
			flush_rewrite_rules();
			update_option( 'mets_kb_rules_version', $current_version );
		}
	}

	/**
	 * Add rewrite rules for knowledgebase URLs
	 *
	 * @since    1.0.0
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^knowledgebase/?$', 'index.php?mets_kb_page=home', 'top' );
		add_rewrite_rule( '^knowledgebase/search/?$', 'index.php?mets_kb_page=search', 'top' );
		add_rewrite_rule( '^knowledgebase/category/([^/]+)/?$', 'index.php?mets_kb_page=category&kb_category=$matches[1]', 'top' );
		add_rewrite_rule( '^knowledgebase/tag/([^/]+)/?$', 'index.php?mets_kb_page=tag&kb_tag=$matches[1]', 'top' );
		add_rewrite_rule( '^knowledgebase/article/([^/]+)/?$', 'index.php?mets_kb_page=article&kb_article=$matches[1]', 'top' );
		add_rewrite_rule( '^knowledgebase/entity/([^/]+)/?$', 'index.php?mets_kb_page=entity&kb_entity=$matches[1]', 'top' );
	}

	/**
	 * Add query vars for knowledgebase
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Existing query vars
	 * @return   array             Modified query vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'mets_kb_page';
		$vars[] = 'kb_category';
		$vars[] = 'kb_tag';
		$vars[] = 'kb_article';
		$vars[] = 'kb_entity';
		// Legacy query vars for backward compatibility
		$vars[] = 'mets_kb';
		$vars[] = 'article';
		return $vars;
	}

	/**
	 * Handle template redirect for knowledgebase pages
	 *
	 * @since    1.0.0
	 */
	public function template_redirect() {
		$kb_page = get_query_var( 'mets_kb_page' );
		$legacy_kb = get_query_var( 'mets_kb' );
		
		// Handle new URL structure
		if ( ! empty( $kb_page ) ) {
			$this->load_knowledgebase_template( $kb_page );
			exit;
		}
		
		// Handle legacy URL structure for backward compatibility
		if ( ! empty( $legacy_kb ) ) {
			switch ( $legacy_kb ) {
				case 'article':
					$article_slug = get_query_var( 'article' );
					if ( ! empty( $article_slug ) ) {
						// Set the article slug for the template
						global $wp_query;
						$wp_query->set( 'kb_article', $article_slug );
						$this->load_knowledgebase_template( 'article' );
						exit;
					}
					break;
				case 'search':
					$this->load_knowledgebase_template( 'search' );
					exit;
					break;
				case 'category':
					$category_slug = get_query_var( 'category' );
					if ( ! empty( $category_slug ) ) {
						global $wp_query;
						$wp_query->set( 'kb_category', $category_slug );
						$this->load_knowledgebase_template( 'category' );
						exit;
					}
					break;
				default:
					$this->load_knowledgebase_template( 'home' );
					exit;
			}
		}
	}

	/**
	 * Load knowledgebase template
	 *
	 * @since    1.0.0
	 * @param    string    $page    Page type
	 */
	private function load_knowledgebase_template( $page ) {
		// Set up WordPress environment
		get_header();
		
		echo '<div class="mets-kb-container">';
		
		switch ( $page ) {
			case 'home':
				$this->display_kb_home();
				break;
			case 'search':
				$this->display_kb_search_results();
				break;
			case 'category':
				$this->display_kb_category();
				break;
			case 'tag':
				$this->display_kb_tag();
				break;
			case 'article':
				$this->display_kb_article();
				break;
			case 'entity':
				$this->display_kb_entity();
				break;
			default:
				$this->display_kb_home();
				break;
		}
		
		echo '</div>';
		
		get_footer();
	}

	/**
	 * Display knowledgebase shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            Shortcode output
	 */
	public function display_knowledgebase_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'entity_id' => null,
			'category_id' => null,
			'limit' => 10,
			'show_search' => 'yes',
			'show_categories' => 'yes',
			'show_popular' => 'yes',
			'layout' => 'grid' // grid, list, compact
		), $atts, 'mets_knowledgebase' );

		ob_start();
		$this->render_knowledgebase_widget( $atts );
		return ob_get_clean();
	}

	/**
	 * Display knowledgebase search shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            Shortcode output
	 */
	public function display_kb_search_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'placeholder' => $this->safe_translate( 'Search knowledgebase...' ),
			'entity_id' => null,
			'show_suggestions' => 'yes'
		), $atts, 'mets_kb_search' );

		ob_start();
		$this->render_search_form( $atts );
		return ob_get_clean();
	}

	/**
	 * Display knowledgebase categories shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            Shortcode output
	 */
	public function display_kb_categories_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'entity_id' => null,
			'show_count' => 'yes',
			'show_description' => 'yes',
			'layout' => 'grid' // grid, list
		), $atts, 'mets_kb_categories' );

		ob_start();
		$this->render_categories_widget( $atts );
		return ob_get_clean();
	}

	/**
	 * Display popular articles shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            Shortcode output
	 */
	public function display_popular_articles_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'entity_id' => null,
			'limit' => 5,
			'show_views' => 'yes',
			'show_rating' => 'yes'
		), $atts, 'mets_kb_popular_articles' );

		ob_start();
		$this->render_popular_articles( $atts );
		return ob_get_clean();
	}

	/**
	 * Display knowledgebase home page
	 *
	 * @since    1.0.0
	 */
	private function display_kb_home() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';

		$article_model = new METS_KB_Article_Model();
		$category_model = new METS_KB_Category_Model();
		$entity_model = new METS_Entity_Model();

		// Get current entity if specified
		$current_entity_id = isset( $_GET['entity'] ) ? intval( $_GET['entity'] ) : null;
		$entities = $entity_model->get_all( array( 'status' => 'active' ) );

		?>
		<div class="mets-kb-home">
			<header class="mets-kb-header">
				<h1><?php $this->safe_echo_translate( 'Knowledge Base' ); ?></h1>
				<p class="mets-kb-description"><?php $this->safe_echo_translate( 'Find answers to your questions and browse our help articles.' ); ?></p>
			</header>

			<!-- Search Section -->
			<section class="mets-kb-search-section">
				<?php $this->render_search_form( array( 'entity_id' => $current_entity_id ) ); ?>
			</section>

			<!-- Entity Filter -->
			<?php if ( count( $entities ) > 1 ): ?>
			<section class="mets-kb-entity-filter">
				<h3><?php $this->safe_echo_translate( 'Browse by Service Area' ); ?></h3>
				<div class="mets-entity-grid">
					<a href="<?php echo home_url( '/knowledgebase/' ); ?>" class="mets-entity-card <?php echo ! $current_entity_id ? 'active' : ''; ?>">
						<span class="dashicons dashicons-admin-home"></span>
						<h4><?php $this->safe_echo_translate( 'All Areas' ); ?></h4>
						<p><?php $this->safe_echo_translate( 'Browse all available content' ); ?></p>
					</a>
					<?php foreach ( $entities as $entity ): ?>
						<a href="<?php echo home_url( '/knowledgebase/entity/' . $entity->slug . '/' ); ?>" class="mets-entity-card <?php echo $current_entity_id == $entity->id ? 'active' : ''; ?>">
							<span class="dashicons dashicons-building"></span>
							<h4><?php echo esc_html( $entity->name ); ?></h4>
							<p><?php echo esc_html( $entity->description ); ?></p>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<!-- Categories Section -->
			<section class="mets-kb-categories-section">
				<?php $this->render_categories_widget( array( 'entity_id' => $current_entity_id ) ); ?>
			</section>

			<!-- Popular Articles Section -->
			<section class="mets-kb-popular-section">
				<h3><?php $this->safe_echo_translate( 'Popular Articles' ); ?></h3>
				<?php $this->render_popular_articles( array( 'entity_id' => $current_entity_id, 'limit' => 8 ) ); ?>
			</section>

			<!-- Recent Articles Section -->
			<section class="mets-kb-recent-section">
				<h3><?php $this->safe_echo_translate( 'Recently Added' ); ?></h3>
				<?php $this->render_recent_articles( array( 'entity_id' => $current_entity_id, 'limit' => 6 ) ); ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Display knowledgebase search results
	 *
	 * @since    1.0.0
	 */
	private function display_kb_search_results() {
		$search_query = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
		$entity_id = isset( $_GET['entity'] ) ? intval( $_GET['entity'] ) : null;
		$category_id = isset( $_GET['category'] ) ? intval( $_GET['category'] ) : null;
		$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

		?>
		<div class="mets-kb-search-page">
			<header class="mets-kb-header">
				<h1><?php $this->safe_echo_translate( 'Search Results' ); ?></h1>
				<?php if ( ! empty( $search_query ) ): ?>
					<p class="mets-kb-search-query"><?php printf( $this->safe_translate( 'Results for: "%s"' ), esc_html( $search_query ) ); ?></p>
				<?php endif; ?>
			</header>

			<!-- Search Form -->
			<?php $this->render_search_form( array( 
				'entity_id' => $entity_id, 
				'search_query' => $search_query 
			) ); ?>

			<!-- Search Results -->
			<div id="mets-kb-search-results">
				<?php $this->render_search_results( $search_query, $entity_id, $category_id, $page ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display knowledgebase category page
	 *
	 * @since    1.0.0
	 */
	private function display_kb_category() {
		$category_slug = get_query_var( 'kb_category' );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$category_model = new METS_KB_Category_Model();
		$article_model = new METS_KB_Article_Model();
		
		$category = $category_model->get_by_slug( $category_slug );
		
		if ( ! $category ) {
			$this->display_404();
			return;
		}

		// Check visibility permissions
		if ( ! $this->can_view_entity_content( $category->entity_id ) ) {
			$this->display_access_denied();
			return;
		}

		?>
		<div class="mets-kb-category-page">
			<header class="mets-kb-header">
				<nav class="mets-kb-breadcrumb">
					<a href="<?php echo home_url( '/knowledgebase/' ); ?>"><?php $this->safe_echo_translate( 'Knowledge Base' ); ?></a>
					<span class="separator">&gt;</span>
					<span class="current"><?php echo esc_html( $category->name ); ?></span>
				</nav>
				
				<div class="mets-kb-category-header">
					<span class="mets-kb-category-icon dashicons <?php echo esc_attr( $category->icon ); ?>" style="color: <?php echo esc_attr( $category->color ); ?>;"></span>
					<div class="mets-kb-category-info">
						<h1><?php echo esc_html( $category->name ); ?></h1>
						<?php if ( $category->description ): ?>
							<p class="mets-kb-category-description"><?php echo esc_html( $category->description ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</header>

			<div class="mets-kb-category-content">
				<?php $this->render_category_articles( $category->id ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display knowledgebase article page
	 *
	 * @since    1.0.0
	 */
	private function display_kb_article() {
		$article_slug = get_query_var( 'kb_article' );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get_by_slug( $article_slug );
		
		if ( ! $article || $article->status !== 'published' ) {
			$this->display_404();
			return;
		}

		// Check visibility permissions
		if ( ! $this->can_view_article( $article ) ) {
			$this->display_access_denied();
			return;
		}

		// Increment view count
		$article_model->increment_view_count( $article->id );

		// Record analytics event
		$this->record_article_view( $article->id );

		?>
		<div class="mets-kb-article-page">
			<?php $this->render_article_breadcrumb( $article ); ?>
			<?php $this->render_article_content( $article ); ?>
			<?php $this->render_article_feedback( $article ); ?>
			<?php $this->render_related_articles( $article ); ?>
		</div>
		<?php
	}

	/**
	 * Render search form
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Form attributes
	 */
	private function render_search_form( $atts ) {
		$placeholder = isset( $atts['placeholder'] ) ? $atts['placeholder'] : $this->safe_translate( 'Search knowledgebase...' );
		$entity_id = isset( $atts['entity_id'] ) ? $atts['entity_id'] : null;
		$search_query = isset( $atts['search_query'] ) ? $atts['search_query'] : '';

		?>
		<div class="mets-kb-search-form">
			<form action="<?php echo home_url( '/knowledgebase/search/' ); ?>" method="get" id="mets-kb-search">
				<div class="mets-search-input-wrapper">
					<input type="text" name="q" id="mets-kb-search-input" 
						   placeholder="<?php echo esc_attr( $placeholder ); ?>"
						   value="<?php echo esc_attr( $search_query ); ?>"
						   autocomplete="off">
					<button type="submit" class="mets-search-submit">
						<span class="dashicons dashicons-search"></span>
						<span class="screen-reader-text"><?php $this->safe_echo_translate( 'Search' ); ?></span>
					</button>
				</div>
				<?php if ( $entity_id ): ?>
					<input type="hidden" name="entity" value="<?php echo esc_attr( $entity_id ); ?>">
				<?php endif; ?>
			</form>
			
			<div id="mets-kb-search-suggestions" class="mets-kb-search-suggestions" style="display: none;">
				<!-- AJAX search suggestions will be loaded here -->
			</div>
		</div>
		<?php
	}

	/**
	 * Display 404 page
	 *
	 * @since    1.0.0
	 */
	private function display_404() {
		status_header( 404 );
		?>
		<div class="mets-kb-404">
			<h1><?php $this->safe_echo_translate( 'Article Not Found' ); ?></h1>
			<p><?php $this->safe_echo_translate( 'The article you are looking for could not be found.' ); ?></p>
			<a href="<?php echo home_url( '/knowledgebase/' ); ?>" class="mets-kb-button">
				<?php $this->safe_echo_translate( 'Back to Knowledge Base' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Display access denied page
	 *
	 * @since    1.0.0
	 */
	private function display_access_denied() {
		?>
		<div class="mets-kb-access-denied">
			<h1><?php $this->safe_echo_translate( 'Access Denied' ); ?></h1>
			<p><?php $this->safe_echo_translate( 'You do not have permission to view this content.' ); ?></p>
			<a href="<?php echo home_url( '/knowledgebase/' ); ?>" class="mets-kb-button">
				<?php $this->safe_echo_translate( 'Back to Knowledge Base' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Check if user can view entity content
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id    Entity ID
	 * @return   bool                   True if can view
	 */
	private function can_view_entity_content( $entity_id ) {
		// For now, all published content is viewable
		// This can be extended with more complex permission logic
		return true;
	}

	/**
	 * Check if user can view article
	 *
	 * @since    1.0.0
	 * @param    object   $article    Article object
	 * @return   bool                 True if can view
	 */
	private function can_view_article( $article ) {
		// Check visibility settings
		switch ( $article->visibility ) {
			case 'internal':
				return current_user_can( 'edit_tickets' ) || current_user_can( 'manage_kb_articles' );
			case 'staff':
				return is_user_logged_in() && ( 
					current_user_can( 'edit_tickets' ) || 
					current_user_can( 'submit_tickets' ) ||
					current_user_can( 'manage_kb_articles' )
				);
			case 'customer':
			default:
				return true;
		}
	}

	/**
	 * Render categories widget
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Widget attributes
	 */
	private function render_categories_widget( $atts ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		
		$category_model = new METS_KB_Category_Model();
		$entity_id = isset( $atts['entity_id'] ) ? $atts['entity_id'] : null;
		$show_count = isset( $atts['show_count'] ) ? $atts['show_count'] === 'yes' : true;
		$show_description = isset( $atts['show_description'] ) ? $atts['show_description'] === 'yes' : true;
		$layout = isset( $atts['layout'] ) ? $atts['layout'] : 'grid';

		$categories = $category_model->get_by_entity( $entity_id, true, true );

		if ( empty( $categories ) ) {
			echo '<p class="mets-kb-no-content">' . $this->safe_translate( 'No categories found.' ) . '</p>';
			return;
		}

		?>
		<h3><?php $this->safe_echo_translate( 'Browse by Category' ); ?></h3>
		<div class="mets-kb-categories mets-kb-layout-<?php echo esc_attr( $layout ); ?>">
			<?php foreach ( $categories as $category ): ?>
				<a href="<?php echo home_url( '/knowledgebase/category/' . $category->slug . '/' ); ?>" class="mets-kb-category-card">
					<div class="mets-kb-category-icon">
						<span class="dashicons <?php echo esc_attr( $category->icon ); ?>" style="color: <?php echo esc_attr( $category->color ); ?>;"></span>
					</div>
					<div class="mets-kb-category-content">
						<h4><?php echo esc_html( $category->name ); ?></h4>
						<?php if ( $show_description && $category->description ): ?>
							<p><?php echo esc_html( wp_trim_words( $category->description, 15 ) ); ?></p>
						<?php endif; ?>
						<?php if ( $show_count ): ?>
							<span class="mets-kb-category-count"><?php printf( did_action( 'init' ) ? _n( '%s article', '%s articles', $category->article_count, METS_TEXT_DOMAIN ) : '%s articles', number_format( $category->article_count ) ); ?></span>
						<?php endif; ?>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render popular articles
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Widget attributes
	 */
	private function render_popular_articles( $atts ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$entity_id = isset( $atts['entity_id'] ) ? $atts['entity_id'] : null;
		$limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 5;
		$show_views = isset( $atts['show_views'] ) ? $atts['show_views'] === 'yes' : true;
		$show_rating = isset( $atts['show_rating'] ) ? $atts['show_rating'] === 'yes' : true;

		$articles_data = $article_model->get_articles_with_inheritance( array(
			'entity_id' => $entity_id,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'orderby' => 'view_count',
			'order' => 'DESC',
			'per_page' => $limit
		) );

		$articles = $articles_data['articles'];

		if ( empty( $articles ) ) {
			echo '<p class="mets-kb-no-content">' . $this->safe_translate( 'No articles found.' ) . '</p>';
			return;
		}

		?>
		<div class="mets-kb-articles-list mets-kb-popular-articles">
			<?php foreach ( $articles as $article ): ?>
				<article class="mets-kb-article-item">
					<h4><a href="<?php echo home_url( '/knowledgebase/article/' . $article->slug . '/' ); ?>"><?php echo esc_html( $article->title ); ?></a></h4>
					<div class="mets-kb-article-excerpt">
						<?php echo esc_html( wp_trim_words( $article->excerpt, 20 ) ); ?>
					</div>
					<div class="mets-kb-article-meta">
						<?php if ( $show_views ): ?>
							<span class="mets-kb-views">
								<span class="dashicons dashicons-visibility"></span>
								<?php echo number_format( $article->view_count ); ?> <?php $this->safe_echo_translate( 'views' ); ?>
							</span>
						<?php endif; ?>
						<?php if ( $show_rating && $article->helpful_yes + $article->helpful_no > 0 ): ?>
							<span class="mets-kb-rating">
								<?php 
								$total_votes = $article->helpful_yes + $article->helpful_no;
								$rating_percentage = round( ( $article->helpful_yes / $total_votes ) * 100 );
								?>
								<span class="dashicons dashicons-thumbs-up"></span>
								<?php echo $rating_percentage; ?>% <?php $this->safe_echo_translate( 'helpful' ); ?>
							</span>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render recent articles
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Widget attributes
	 */
	private function render_recent_articles( $atts ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$entity_id = isset( $atts['entity_id'] ) ? $atts['entity_id'] : null;
		$limit = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 6;

		$articles_data = $article_model->get_articles_with_inheritance( array(
			'entity_id' => $entity_id,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'orderby' => 'created_at',
			'order' => 'DESC',
			'per_page' => $limit
		) );

		$articles = $articles_data['articles'];

		if ( empty( $articles ) ) {
			echo '<p class="mets-kb-no-content">' . $this->safe_translate( 'No recent articles found.' ) . '</p>';
			return;
		}

		?>
		<div class="mets-kb-articles-grid">
			<?php foreach ( $articles as $article ): ?>
				<article class="mets-kb-article-card">
					<div class="mets-kb-article-header">
						<h4><a href="<?php echo home_url( '/knowledgebase/article/' . $article->slug . '/' ); ?>"><?php echo esc_html( $article->title ); ?></a></h4>
						<time class="mets-kb-article-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></time>
					</div>
					<div class="mets-kb-article-excerpt">
						<?php echo esc_html( wp_trim_words( $article->excerpt, 15 ) ); ?>
					</div>
					<?php if ( $article->is_featured ): ?>
						<span class="mets-kb-featured-badge"><?php $this->safe_echo_translate( 'Featured' ); ?></span>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render search results
	 *
	 * @since    1.0.0
	 * @param    string   $search_query    Search query
	 * @param    int      $entity_id       Entity ID
	 * @param    int      $category_id     Category ID
	 * @param    int      $page            Page number
	 */
	private function render_search_results( $search_query, $entity_id = null, $category_id = null, $page = 1 ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$per_page = 10;

		$args = array(
			'entity_id' => $entity_id,
			'category_id' => $category_id,
			'search' => $search_query,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'per_page' => $per_page,
			'page' => $page,
			'orderby' => 'relevance'
		);

		$results = $article_model->get_articles_with_inheritance( $args );
		$articles = $results['articles'];
		$total = $results['total'];
		$total_pages = $results['pages'];

		// Record search analytics (only on first page and if search query exists)
		if ( $page === 1 && ! empty( $search_query ) ) {
			$this->record_search_analytics( $search_query, $total, $entity_id );
		}

		?>
		<div class="mets-kb-search-results">
			<?php if ( ! empty( $search_query ) ): ?>
				<div class="mets-kb-results-header">
					<p class="mets-kb-results-count">
						<?php printf( did_action( 'init' ) ? _n( '%s result found', '%s results found', $total, METS_TEXT_DOMAIN ) : '%s results found', number_format( $total ) ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $articles ) ): ?>
				<div class="mets-kb-search-articles">
					<?php foreach ( $articles as $article ): ?>
						<article class="mets-kb-search-result">
							<h3><a href="<?php echo home_url( '/knowledgebase/article/' . $article->slug . '/' ); ?>"><?php echo esc_html( $article->title ); ?></a></h3>
							<div class="mets-kb-search-excerpt">
								<?php 
								// Highlight search terms in excerpt
								$excerpt = wp_trim_words( strip_tags( $article->content ), 30 );
								if ( ! empty( $search_query ) ) {
									$excerpt = $this->highlight_search_terms( $excerpt, $search_query );
								}
								echo $excerpt;
								?>
							</div>
							<div class="mets-kb-search-meta">
								<?php if ( $article->entity_name ): ?>
									<span class="mets-kb-entity-tag"><?php echo esc_html( $article->entity_name ); ?></span>
								<?php endif; ?>
								<span class="mets-kb-article-views"><?php echo number_format( $article->view_count ); ?> <?php $this->safe_echo_translate( 'views' ); ?></span>
								<time><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></time>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ( $total_pages > 1 ): ?>
					<div class="mets-kb-pagination">
						<?php $this->render_pagination( $page, $total_pages, array( 'q' => $search_query, 'entity' => $entity_id, 'category' => $category_id ) ); ?>
					</div>
				<?php endif; ?>
			<?php else: ?>
				<div class="mets-kb-no-results">
					<h3><?php $this->safe_echo_translate( 'No results found' ); ?></h3>
					<p><?php $this->safe_echo_translate( 'Try adjusting your search terms or browse our categories below.' ); ?></p>
					
					<?php 
					// Show popular categories as suggestions
					$this->render_categories_widget( array( 'entity_id' => $entity_id, 'layout' => 'list' ) );
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render article content
	 *
	 * @since    1.0.0
	 * @param    object   $article    Article object
	 */
	private function render_article_content( $article ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		
		$attachment_model = new METS_KB_Attachment_Model();
		$tag_model = new METS_KB_Tag_Model();
		
		$attachments = $attachment_model->get_by_article( $article->id );
		$tags = $tag_model->get_article_tags( $article->id );

		?>
		<article class="mets-kb-article">
			<header class="mets-kb-article-header">
				<h1><?php echo esc_html( $article->title ); ?></h1>
				<div class="mets-kb-article-meta">
					<time><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></time>
					<span class="mets-kb-views"><?php echo number_format( $article->view_count ); ?> <?php $this->safe_echo_translate( 'views' ); ?></span>
					<?php if ( $article->entity_name ): ?>
						<span class="mets-kb-entity"><?php echo esc_html( $article->entity_name ); ?></span>
					<?php endif; ?>
				</div>
			</header>

			<div class="mets-kb-article-content">
				<?php echo wp_kses_post( $article->content ); ?>
			</div>

			<?php if ( ! empty( $attachments ) ): ?>
				<section class="mets-kb-article-attachments">
					<h3><?php $this->safe_echo_translate( 'Downloads' ); ?></h3>
					<div class="mets-kb-attachments-list">
						<?php foreach ( $attachments as $attachment ): ?>
							<a href="<?php echo $attachment_model->get_download_url( $attachment->id ); ?>" class="mets-kb-attachment" target="_blank">
								<span class="dashicons <?php echo $attachment_model->get_file_icon_class( $attachment->mime_type ); ?>"></span>
								<span class="mets-kb-attachment-name"><?php echo esc_html( $attachment->original_filename ); ?></span>
								<span class="mets-kb-attachment-size">(<?php echo $attachment_model->format_file_size( $attachment->file_size ); ?>)</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $tags ) ): ?>
				<section class="mets-kb-article-tags">
					<h4><?php $this->safe_echo_translate( 'Tags:' ); ?></h4>
					<div class="mets-kb-tags-list">
						<?php foreach ( $tags as $tag ): ?>
							<a href="<?php echo home_url( '/knowledgebase/tag/' . $tag->slug . '/' ); ?>" class="mets-kb-tag"><?php echo esc_html( $tag->name ); ?></a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Render article breadcrumb
	 *
	 * @since    1.0.0
	 * @param    object   $article    Article object
	 */
	private function render_article_breadcrumb( $article ) {
		?>
		<nav class="mets-kb-breadcrumb">
			<a href="<?php echo home_url( '/knowledgebase/' ); ?>"><?php $this->safe_echo_translate( 'Knowledge Base' ); ?></a>
			<span class="separator">&gt;</span>
			<?php if ( $article->entity_name ): ?>
				<a href="<?php echo home_url( '/knowledgebase/entity/' . $article->entity_slug . '/' ); ?>"><?php echo esc_html( $article->entity_name ); ?></a>
				<span class="separator">&gt;</span>
			<?php endif; ?>
			<span class="current"><?php echo esc_html( wp_trim_words( $article->title, 5 ) ); ?></span>
		</nav>
		<?php
	}

	/**
	 * Render article feedback form
	 *
	 * @since    1.0.0
	 * @param    object   $article    Article object
	 */
	private function render_article_feedback( $article ) {
		$total_votes = $article->helpful_yes + $article->helpful_no;
		$helpful_percentage = $total_votes > 0 ? round( ( $article->helpful_yes / $total_votes ) * 100 ) : 0;

		?>
		<section class="mets-kb-article-feedback">
			<div class="mets-kb-feedback-header">
				<h3><?php $this->safe_echo_translate( 'Was this article helpful?' ); ?></h3>
				<?php if ( $total_votes > 0 ): ?>
					<p class="mets-kb-feedback-stats">
						<?php printf( $this->safe_translate( '%d%% of users found this helpful (%d votes)' ), $helpful_percentage, $total_votes ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="mets-kb-feedback-buttons" data-article-id="<?php echo $article->id; ?>">
				<button type="button" class="mets-kb-feedback-btn mets-kb-helpful-yes" data-vote="yes">
					<span class="dashicons dashicons-thumbs-up"></span>
					<?php $this->safe_echo_translate( 'Yes' ); ?>
				</button>
				<button type="button" class="mets-kb-feedback-btn mets-kb-helpful-no" data-vote="no">
					<span class="dashicons dashicons-thumbs-down"></span>
					<?php $this->safe_echo_translate( 'No' ); ?>
				</button>
			</div>

			<div class="mets-kb-feedback-message" style="display: none;"></div>
		</section>
		<?php
	}

	/**
	 * Get user visibility levels
	 *
	 * @since    1.0.0
	 * @return   array    Allowed visibility levels
	 */
	private function get_user_visibility_levels() {
		$levels = array( 'customer' );

		if ( is_user_logged_in() ) {
			$levels[] = 'staff';
			
			if ( current_user_can( 'edit_tickets' ) || current_user_can( 'manage_kb_articles' ) ) {
				$levels[] = 'internal';
			}
		}

		return $levels;
	}

	/**
	 * Highlight search terms in text
	 *
	 * @since    1.0.0
	 * @param    string   $text     Text to highlight
	 * @param    string   $query    Search query
	 * @return   string             Highlighted text
	 */
	private function highlight_search_terms( $text, $query ) {
		$terms = explode( ' ', $query );
		
		foreach ( $terms as $term ) {
			$term = trim( $term );
			if ( strlen( $term ) > 2 ) {
				$text = preg_replace( '/(' . preg_quote( $term, '/' ) . ')/i', '<mark>$1</mark>', $text );
			}
		}
		
		return $text;
	}

	/**
	 * Render pagination
	 *
	 * @since    1.0.0
	 * @param    int      $current_page    Current page number
	 * @param    int      $total_pages     Total pages
	 * @param    array    $args            URL arguments
	 */
	private function render_pagination( $current_page, $total_pages, $args = array() ) {
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url = home_url( '/knowledgebase/search/' );
		
		?>
		<div class="mets-kb-pagination">
			<?php if ( $current_page > 1 ): ?>
				<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paged' => $current_page - 1 ) ), $base_url ) ); ?>" class="mets-kb-page-btn mets-kb-prev">
					&laquo; <?php $this->safe_echo_translate( 'Previous' ); ?>
				</a>
			<?php endif; ?>

			<?php for ( $i = max( 1, $current_page - 2 ); $i <= min( $total_pages, $current_page + 2 ); $i++ ): ?>
				<?php if ( $i === $current_page ): ?>
					<span class="mets-kb-page-btn mets-kb-current"><?php echo $i; ?></span>
				<?php else: ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paged' => $i ) ), $base_url ) ); ?>" class="mets-kb-page-btn"><?php echo $i; ?></a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if ( $current_page < $total_pages ): ?>
				<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paged' => $current_page + 1 ) ), $base_url ) ); ?>" class="mets-kb-page-btn mets-kb-next">
					<?php $this->safe_echo_translate( 'Next' ); ?> &raquo;
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle AJAX search
	 *
	 * @since    1.0.0
	 */
	public function handle_kb_search_ajax() {
		check_ajax_referer( 'mets_kb_nonce', 'nonce' );

		$query = sanitize_text_field( $_POST['query'] );
		$entity_id = isset( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 5;

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$results = $article_model->get_articles_with_inheritance( array(
			'entity_id' => $entity_id,
			'search' => $query,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'per_page' => $limit,
			'orderby' => 'relevance'
		) );

		wp_send_json_success( array(
			'articles' => $results['articles'],
			'total' => $results['total']
		) );
	}

	/**
	 * Handle article rating
	 *
	 * @since    1.0.0
	 */
	public function handle_article_rating() {
		check_ajax_referer( 'mets_kb_nonce', 'nonce' );

		$article_id = intval( $_POST['article_id'] );
		$vote = sanitize_text_field( $_POST['vote'] );

		if ( ! in_array( $vote, array( 'yes', 'no' ) ) ) {
			wp_send_json_error( $this->safe_translate( 'Invalid vote.' ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();

		$result = $article_model->add_helpfulness_vote( $article_id, $vote );

		if ( $result ) {
			// Record analytics event for feedback
			$this->record_feedback_analytics( $article_id, $vote );
			
			wp_send_json_success( array(
				'message' => $this->safe_translate( 'Thank you for your feedback!' )
			) );
		} else {
			wp_send_json_error( $this->safe_translate( 'Failed to record your vote.' ) );
		}
	}

	/**
	 * Handle attachment download
	 *
	 * @since    1.0.0
	 */
	public function handle_attachment_download() {
		$attachment_id = intval( $_GET['attachment_id'] );
		$nonce = sanitize_text_field( $_GET['nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'mets_kb_download_' . $attachment_id ) ) {
			wp_die( $this->safe_translate( 'Security check failed.' ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();

		$attachment = $attachment_model->get( $attachment_id );
		if ( ! $attachment ) {
			wp_die( $this->safe_translate( 'Attachment not found.' ) );
		}

		// Check if user can download this attachment
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		$article_model = new METS_KB_Article_Model();
		$article = $article_model->get( $attachment->article_id );

		if ( ! $article || ! $this->can_view_article( $article ) ) {
			wp_die( $this->safe_translate( 'Access denied.' ) );
		}

		// Increment download count
		$attachment_model->increment_download_count( $attachment_id );

		// Send file
		if ( file_exists( $attachment->file_path ) ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . $attachment->mime_type );
			header( 'Content-Disposition: attachment; filename="' . basename( $attachment->original_filename ) . '"' );
			header( 'Content-Length: ' . filesize( $attachment->file_path ) );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			
			ob_clean();
			flush();
			readfile( $attachment->file_path );
			exit;
		} else {
			wp_die( $this->safe_translate( 'File not found.' ) );
		}
	}

	/**
	 * Render knowledgebase widget
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Widget attributes
	 */
	private function render_knowledgebase_widget( $atts ) {
		?>
		<div class="mets-kb-widget mets-kb-layout-<?php echo esc_attr( $atts['layout'] ); ?>">
			<?php if ( $atts['show_search'] === 'yes' ): ?>
				<?php $this->render_search_form( array( 'entity_id' => $atts['entity_id'] ) ); ?>
			<?php endif; ?>

			<?php if ( $atts['show_categories'] === 'yes' ): ?>
				<?php $this->render_categories_widget( array( 'entity_id' => $atts['entity_id'], 'layout' => $atts['layout'] ) ); ?>
			<?php endif; ?>

			<?php if ( $atts['show_popular'] === 'yes' ): ?>
				<?php $this->render_popular_articles( array( 'entity_id' => $atts['entity_id'], 'limit' => $atts['limit'] ) ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render category articles
	 *
	 * @since    1.0.0
	 * @param    int    $category_id    Category ID
	 */
	private function render_category_articles( $category_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();
		$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 15;

		$results = $article_model->get_articles_with_inheritance( array(
			'category_id' => $category_id,
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'per_page' => $per_page,
			'page' => $page,
			'orderby' => 'created_at',
			'order' => 'DESC'
		) );

		$articles = $results['articles'];
		$total_pages = $results['pages'];

		if ( empty( $articles ) ) {
			echo '<p class="mets-kb-no-content">' . $this->safe_translate( 'No articles found in this category.' ) . '</p>';
			return;
		}

		?>
		<div class="mets-kb-category-articles">
			<?php foreach ( $articles as $article ): ?>
				<article class="mets-kb-article-item">
					<h3><a href="<?php echo home_url( '/knowledgebase/article/' . $article->slug . '/' ); ?>"><?php echo esc_html( $article->title ); ?></a></h3>
					<div class="mets-kb-article-excerpt">
						<?php echo esc_html( wp_trim_words( $article->excerpt, 25 ) ); ?>
					</div>
					<div class="mets-kb-article-meta">
						<time><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></time>
						<span class="mets-kb-views"><?php echo number_format( $article->view_count ); ?> <?php $this->safe_echo_translate( 'views' ); ?></span>
						<?php if ( $article->is_featured ): ?>
							<span class="mets-kb-featured-badge"><?php $this->safe_echo_translate( 'Featured' ); ?></span>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $total_pages > 1 ): ?>
			<div class="mets-kb-pagination">
				<?php $this->render_pagination( $page, $total_pages, array() ); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render related articles
	 *
	 * @since    1.0.0
	 * @param    object   $article    Article object
	 */
	private function render_related_articles( $article ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$article_model = new METS_KB_Article_Model();

		// Get related articles based on entity and categories
		$related = $article_model->get_related_articles( $article->id, 4 );

		if ( empty( $related ) ) {
			return;
		}

		?>
		<section class="mets-kb-related-articles">
			<h3><?php $this->safe_echo_translate( 'Related Articles' ); ?></h3>
			<div class="mets-kb-related-grid">
				<?php foreach ( $related as $related_article ): ?>
					<article class="mets-kb-related-item">
						<h4><a href="<?php echo home_url( '/knowledgebase/article/' . $related_article->slug . '/' ); ?>"><?php echo esc_html( $related_article->title ); ?></a></h4>
						<div class="mets-kb-article-excerpt">
							<?php echo esc_html( wp_trim_words( $related_article->excerpt, 15 ) ); ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Display knowledgebase tag page
	 *
	 * @since    1.0.0
	 */
	private function display_kb_tag() {
		$tag_slug = get_query_var( 'kb_tag' );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		
		$tag_model = new METS_KB_Tag_Model();
		$article_model = new METS_KB_Article_Model();
		
		$tag = $tag_model->get_by_slug( $tag_slug );
		
		if ( ! $tag ) {
			$this->display_404();
			return;
		}

		$page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 15;

		// Get articles with this tag
		$results = $article_model->get_articles_by_tag( $tag->id, array(
			'status' => array( 'published' ),
			'visibility' => $this->get_user_visibility_levels(),
			'per_page' => $per_page,
			'page' => $page
		) );

		?>
		<div class="mets-kb-tag-page">
			<header class="mets-kb-header">
				<nav class="mets-kb-breadcrumb">
					<a href="<?php echo home_url( '/knowledgebase/' ); ?>"><?php $this->safe_echo_translate( 'Knowledge Base' ); ?></a>
					<span class="separator">&gt;</span>
					<span class="current"><?php printf( $this->safe_translate( 'Tag: %s' ), esc_html( $tag->name ) ); ?></span>
				</nav>
				
				<h1><?php printf( $this->safe_translate( 'Articles tagged "%s"' ), esc_html( $tag->name ) ); ?></h1>
				<?php if ( $tag->description ): ?>
					<p class="mets-kb-tag-description"><?php echo esc_html( $tag->description ); ?></p>
				<?php endif; ?>
			</header>

			<div class="mets-kb-tag-content">
				<?php if ( ! empty( $results['articles'] ) ): ?>
					<div class="mets-kb-tag-articles">
						<?php foreach ( $results['articles'] as $article ): ?>
							<article class="mets-kb-article-item">
								<h3><a href="<?php echo home_url( '/knowledgebase/article/' . $article->slug . '/' ); ?>"><?php echo esc_html( $article->title ); ?></a></h3>
								<div class="mets-kb-article-excerpt">
									<?php echo esc_html( wp_trim_words( $article->excerpt, 25 ) ); ?>
								</div>
								<div class="mets-kb-article-meta">
									<time><?php echo date_i18n( get_option( 'date_format' ), strtotime( $article->created_at ) ); ?></time>
									<span class="mets-kb-views"><?php echo number_format( $article->view_count ); ?> <?php $this->safe_echo_translate( 'views' ); ?></span>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ( $results['pages'] > 1 ): ?>
						<div class="mets-kb-pagination">
							<?php $this->render_pagination( $page, $results['pages'], array() ); ?>
						</div>
					<?php endif; ?>
				<?php else: ?>
					<p class="mets-kb-no-content"><?php $this->safe_echo_translate( 'No articles found with this tag.' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display knowledgebase entity page
	 *
	 * @since    1.0.0
	 */
	private function display_kb_entity() {
		$entity_slug = get_query_var( 'kb_entity' );
		
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get_by_slug( $entity_slug );
		
		if ( ! $entity || $entity->status !== 'active' ) {
			$this->display_404();
			return;
		}

		// Redirect to home page with entity filter
		wp_redirect( home_url( '/knowledgebase/?entity=' . $entity->id ) );
		exit;
	}

	/**
	 * Record article view analytics
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 */
	private function record_article_view( $article_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();

		$analytics_model->record_event( array(
			'article_id' => $article_id,
			'action' => 'view',
			'search_query' => isset( $_GET['from_search'] ) ? sanitize_text_field( $_GET['search'] ?? '' ) : '',
		) );
	}

	/**
	 * Record search analytics
	 *
	 * @since    1.0.0
	 * @param    string   $query         Search query
	 * @param    int      $results_count Number of results
	 * @param    int      $entity_id     Entity ID (optional)
	 */
	public function record_search_analytics( $query, $results_count, $entity_id = null ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();

		$analytics_model->log_search( array(
			'query' => $query,
			'results_count' => $results_count,
			'entity_id' => $entity_id,
		) );
	}

	/**
	 * Record article feedback analytics
	 *
	 * @since    1.0.0
	 * @param    int     $article_id   Article ID
	 * @param    string  $vote         Vote type ('yes' or 'no')
	 */
	public function record_feedback_analytics( $article_id, $vote ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();

		$action = ( $vote === 'yes' ) ? 'helpful' : 'not_helpful';
		$analytics_model->record_event( array(
			'article_id' => $article_id,
			'action' => $action,
		) );
	}

	/**
	 * Record download analytics
	 *
	 * @since    1.0.0
	 * @param    int    $article_id    Article ID
	 */
	public function record_download_analytics( $article_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$analytics_model = new METS_KB_Analytics_Model();

		$analytics_model->record_event( array(
			'article_id' => $article_id,
			'action' => 'download',
		) );
	}
}