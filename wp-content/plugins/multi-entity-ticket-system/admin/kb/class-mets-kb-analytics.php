<?php
/**
 * KB Analytics Admin Page
 *
 * Handles the knowledge base analytics dashboard
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @since      1.0.0
 */

/**
 * The KB Analytics admin class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Analytics {

	/**
	 * Analytics model instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_KB_Analytics_Model    $analytics_model    Analytics model instance
	 */
	private $analytics_model;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-analytics-model.php';
		$this->analytics_model = new METS_KB_Analytics_Model();
	}

	/**
	 * Display analytics dashboard
	 *
	 * @since    1.0.0
	 */
	public function display_analytics_page() {
		// Check user capabilities
		if ( ! current_user_can( 'read_kb_articles' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		$period = sanitize_text_field( $_GET['period'] ?? '30days' );
		$entity_id = isset( $_GET['entity_id'] ) ? intval( $_GET['entity_id'] ) : null;

		// Get entities for filter
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'status' => 'active', 'parent_id' => 'all' ) );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Knowledge Base Analytics', METS_TEXT_DOMAIN ); ?></h1>
			
			<!-- Filters -->
			<div class="mets-analytics-filters" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
				<form method="get" action="">
					<input type="hidden" name="page" value="mets-kb-analytics">
					
					<label for="period" style="margin-right: 15px;">
						<strong><?php _e( 'Period:', METS_TEXT_DOMAIN ); ?></strong>
						<select name="period" id="period" style="margin-left: 5px;">
							<option value="24hours" <?php selected( $period, '24hours' ); ?>><?php _e( 'Last 24 Hours', METS_TEXT_DOMAIN ); ?></option>
							<option value="7days" <?php selected( $period, '7days' ); ?>><?php _e( 'Last 7 Days', METS_TEXT_DOMAIN ); ?></option>
							<option value="30days" <?php selected( $period, '30days' ); ?>><?php _e( 'Last 30 Days', METS_TEXT_DOMAIN ); ?></option>
							<option value="all" <?php selected( $period, 'all' ); ?>><?php _e( 'All Time', METS_TEXT_DOMAIN ); ?></option>
						</select>
					</label>
					
					<label for="entity_id" style="margin-right: 15px;">
						<strong><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></strong>
						<select name="entity_id" id="entity_id" style="margin-left: 5px;">
							<option value=""><?php _e( 'All Entities', METS_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $entities as $entity ) : ?>
								<option value="<?php echo esc_attr( $entity->id ); ?>" <?php selected( $entity_id, $entity->id ); ?>>
									<?php echo esc_html( $entity->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Apply Filters', METS_TEXT_DOMAIN ); ?>">
				</form>
			</div>

			<!-- Analytics Grid -->
			<div class="mets-analytics-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
				<!-- Top Articles -->
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Top Performing Articles', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<?php $this->render_top_articles( $period, $entity_id ); ?>
					</div>
				</div>

				<!-- Trending Articles -->
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Trending Articles', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<?php $this->render_trending_articles( $entity_id ); ?>
					</div>
				</div>
			</div>

			<!-- Search Analytics -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Search Analytics', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php $this->render_search_analytics( $period, $entity_id ); ?>
				</div>
			</div>

			<!-- Articles Needing Attention -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Articles Needing Attention', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php $this->render_articles_needing_attention( $entity_id ); ?>
				</div>
			</div>
		</div>

		<style>
		.mets-analytics-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}
		
		@media (max-width: 1200px) {
			.mets-analytics-grid {
				grid-template-columns: 1fr;
			}
		}
		
		.mets-stat-card {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 15px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 5px;
			margin-bottom: 10px;
		}
		
		.mets-stat-value {
			font-size: 24px;
			font-weight: bold;
			color: #0073aa;
		}
		
		.mets-article-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 10px;
			border-bottom: 1px solid #eee;
		}
		
		.mets-article-item:last-child {
			border-bottom: none;
		}
		
		.mets-article-title {
			font-weight: 600;
			color: #0073aa;
			text-decoration: none;
		}
		
		.mets-article-title:hover {
			color: #005a87;
		}
		
		.mets-article-meta {
			font-size: 12px;
			color: #666;
			margin-top: 4px;
		}
		
		.mets-article-stats {
			text-align: right;
			font-size: 12px;
			color: #666;
		}
		
		.mets-stat-highlight {
			font-weight: bold;
			color: #0073aa;
		}
		
		.mets-attention-reason {
			color: #d63638;
			font-size: 11px;
			font-weight: 500;
		}
		
		.mets-search-query {
			background: #f0f0f1;
			padding: 2px 6px;
			border-radius: 3px;
			font-family: monospace;
			font-size: 11px;
		}
		</style>
		<?php
	}

	/**
	 * Render top articles section
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_top_articles( $period, $entity_id ) {
		$articles = $this->analytics_model->get_top_articles( 10, $period, $entity_id, 'views' );

		if ( empty( $articles ) ) {
			echo '<p>' . __( 'No data available for the selected period.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		foreach ( $articles as $article ) {
			?>
			<div class="mets-article-item">
				<div class="mets-article-info">
					<div>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id ); ?>" 
							class="mets-article-title" target="_blank">
							<?php echo esc_html( $article->title ); ?>
						</a>
					</div>
					<div class="mets-article-meta">
						<?php echo esc_html( $article->entity_name ); ?>
						<?php if ( $article->helpfulness_ratio > 0 ) : ?>
							• <?php printf( __( '%s%% helpful', METS_TEXT_DOMAIN ), $article->helpfulness_ratio ); ?>
						<?php endif; ?>
					</div>
				</div>
				<div class="mets-article-stats">
					<div class="mets-stat-highlight"><?php echo number_format( $article->views ); ?></div>
					<div><?php _e( 'views', METS_TEXT_DOMAIN ); ?></div>
					<?php if ( $article->unique_sessions > 0 ) : ?>
						<div style="margin-top: 4px;"><?php echo number_format( $article->unique_sessions ); ?> <?php _e( 'sessions', METS_TEXT_DOMAIN ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render trending articles section
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_trending_articles( $entity_id ) {
		$articles = $this->analytics_model->get_trending_articles( 10, $entity_id );

		if ( empty( $articles ) ) {
			echo '<p>' . __( 'No trending articles found.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		foreach ( $articles as $article ) {
			?>
			<div class="mets-article-item">
				<div class="mets-article-info">
					<div>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id ); ?>" 
							class="mets-article-title" target="_blank">
							<?php echo esc_html( $article->title ); ?>
						</a>
					</div>
					<div class="mets-article-meta">
						<?php echo esc_html( $article->entity_name ); ?>
					</div>
				</div>
				<div class="mets-article-stats">
					<div class="mets-stat-highlight" style="color: #00a32a;">+<?php echo number_format( $article->growth_rate, 1 ); ?>%</div>
					<div><?php _e( 'growth', METS_TEXT_DOMAIN ); ?></div>
					<div style="margin-top: 4px;"><?php echo number_format( $article->recent_views ); ?> <?php _e( 'recent views', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render search analytics section
	 *
	 * @since    1.0.0
	 * @param    string   $period      Time period
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_search_analytics( $period, $entity_id ) {
		$search_data = $this->analytics_model->get_search_analytics( $period, $entity_id );

		?>
		<!-- Overall Search Stats -->
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
			<div class="mets-stat-card">
				<div>
					<div class="mets-stat-value"><?php echo number_format( $search_data['total_searches'] ); ?></div>
					<div><?php _e( 'Total Searches', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
			<div class="mets-stat-card">
				<div>
					<div class="mets-stat-value"><?php echo $search_data['overall_ctr']; ?>%</div>
					<div><?php _e( 'Click-through Rate', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
			<div class="mets-stat-card">
				<div>
					<div class="mets-stat-value"><?php echo number_format( $search_data['zero_result_searches'] ); ?></div>
					<div><?php _e( 'Zero Results', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
			<div class="mets-stat-card">
				<div>
					<div class="mets-stat-value"><?php echo $search_data['avg_results_per_search']; ?></div>
					<div><?php _e( 'Avg Results', METS_TEXT_DOMAIN ); ?></div>
				</div>
			</div>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
			<!-- Top Search Queries -->
			<div>
				<h4><?php _e( 'Top Search Queries', METS_TEXT_DOMAIN ); ?></h4>
				<?php if ( ! empty( $search_data['top_queries'] ) ) : ?>
					<?php foreach ( array_slice( $search_data['top_queries'], 0, 10 ) as $query ) : ?>
						<div class="mets-article-item">
							<div>
								<span class="mets-search-query"><?php echo esc_html( $query->query ); ?></span>
								<div class="mets-article-meta">
									<?php printf( __( '%s searches • %s%% CTR', METS_TEXT_DOMAIN ), 
										number_format( $query->search_count ), 
										$query->click_through_rate 
									); ?>
								</div>
							</div>
							<div class="mets-article-stats">
								<div><?php echo number_format( $query->avg_results, 1 ); ?></div>
								<div><?php _e( 'avg results', METS_TEXT_DOMAIN ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php _e( 'No search data available.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Zero Result Queries -->
			<div>
				<h4><?php _e( 'Queries with No Results', METS_TEXT_DOMAIN ); ?></h4>
				<?php if ( ! empty( $search_data['zero_results'] ) ) : ?>
					<?php foreach ( array_slice( $search_data['zero_results'], 0, 10 ) as $query ) : ?>
						<div class="mets-article-item">
							<div>
								<span class="mets-search-query"><?php echo esc_html( $query->query ); ?></span>
								<div class="mets-article-meta">
									<?php printf( __( '%s searches with no results', METS_TEXT_DOMAIN ), 
										number_format( $query->search_count ) 
									); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php _e( 'No zero-result queries found.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render articles needing attention section
	 *
	 * @since    1.0.0
	 * @param    int      $entity_id   Entity filter
	 */
	private function render_articles_needing_attention( $entity_id ) {
		$articles = $this->analytics_model->get_articles_needing_attention( 10, $entity_id );

		if ( empty( $articles ) ) {
			echo '<p style="color: #00a32a;">' . __( 'Great! No articles currently need attention.', METS_TEXT_DOMAIN ) . '</p>';
			return;
		}

		foreach ( $articles as $article ) {
			?>
			<div class="mets-article-item">
				<div class="mets-article-info">
					<div>
						<a href="<?php echo admin_url( 'admin.php?page=mets-kb-edit-article&article_id=' . $article->id ); ?>" 
							class="mets-article-title" target="_blank">
							<?php echo esc_html( $article->title ); ?>
						</a>
					</div>
					<div class="mets-article-meta">
						<?php echo esc_html( $article->entity_name ); ?>
						<span class="mets-attention-reason">
							<?php if ( $article->helpfulness_ratio !== null && $article->helpfulness_ratio < 40 ) : ?>
								• <?php printf( __( 'Low helpfulness: %s%%', METS_TEXT_DOMAIN ), $article->helpfulness_ratio ); ?>
							<?php else : ?>
								• <?php _e( 'No feedback received', METS_TEXT_DOMAIN ); ?>
							<?php endif; ?>
						</span>
					</div>
				</div>
				<div class="mets-article-stats">
					<div><?php echo number_format( $article->views ); ?></div>
					<div><?php _e( 'views', METS_TEXT_DOMAIN ); ?></div>
					<?php if ( $article->helpful > 0 || $article->not_helpful > 0 ) : ?>
						<div style="margin-top: 4px;">
							<?php printf( __( '%d helpful, %d not helpful', METS_TEXT_DOMAIN ), $article->helpful, $article->not_helpful ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}
}