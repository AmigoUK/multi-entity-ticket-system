<?php
/**
 * Enhanced KB Article Manager
 *
 * Handles KB article editing with modern WordPress practices and AJAX support
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @since      2.0.0
 */

/**
 * Enhanced KB Article Manager class.
 *
 * This class provides a modern, robust interface for managing KB articles
 * with AJAX auto-save, proper error handling, and real-time feedback.
 *
 * @since      2.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Article_Manager {

	/**
	 * Article model instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      METS_KB_Article_Model    $article_model    Article model instance.
	 */
	private $article_model;

	/**
	 * Category model instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      METS_KB_Category_Model    $category_model    Category model instance.
	 */
	private $category_model;

	/**
	 * Tag model instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      METS_KB_Tag_Model    $tag_model    Tag model instance.
	 */
	private $tag_model;

	/**
	 * Entity model instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      METS_Entity_Model    $entity_model    Entity model instance.
	 */
	private $entity_model;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		$this->load_models();
		$this->init_hooks();
	}

	/**
	 * Load required model classes
	 *
	 * @since    2.0.0
	 */
	private function load_models() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-category-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		
		$this->article_model = new METS_KB_Article_Model();
		$this->category_model = new METS_KB_Category_Model();
		$this->tag_model = new METS_KB_Tag_Model();
		$this->entity_model = new METS_Entity_Model();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since    2.0.0
	 */
	private function init_hooks() {
		// AJAX hooks for authenticated users
		add_action( 'wp_ajax_mets_kb_auto_save', array( $this, 'ajax_auto_save' ) );
		add_action( 'wp_ajax_mets_kb_get_categories', array( $this, 'ajax_get_categories' ) );
		add_action( 'wp_ajax_mets_kb_upload_file', array( $this, 'ajax_upload_file' ) );
		add_action( 'wp_ajax_mets_kb_delete_attachment', array( $this, 'ajax_delete_attachment' ) );
		add_action( 'wp_ajax_mets_kb_validate_title', array( $this, 'ajax_validate_title' ) );
		
		// Admin form processing
		add_action( 'admin_post_mets_kb_save_article', array( $this, 'handle_form_submit' ) );
		
		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since    2.0.0
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on KB article pages
		if ( ! in_array( $hook, array( 'mets-kb_page_mets-kb-add-article', 'toplevel_page_mets-kb' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'mets-kb-article-manager',
			METS_PLUGIN_URL . 'admin/js/mets-kb-article-manager.js',
			array( 'jquery', 'wp-util', 'heartbeat' ),
			METS_VERSION,
			true
		);

		wp_localize_script( 'mets-kb-article-manager', 'metsKbArticle', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'mets_kb_article_nonce' ),
			'autosaveInterval' => 30000, // 30 seconds
			'i18n' => array(
				'saving' => __( 'Saving...', 'multi-entity-ticket-system' ),
				'saved' => __( 'Saved', 'multi-entity-ticket-system' ),
				'saveError' => __( 'Save failed', 'multi-entity-ticket-system' ),
				'confirmLeave' => __( 'You have unsaved changes. Are you sure you want to leave?', 'multi-entity-ticket-system' ),
				'titleRequired' => __( 'Title is required', 'multi-entity-ticket-system' ),
				'contentRequired' => __( 'Content is required', 'multi-entity-ticket-system' ),
			)
		) );

		wp_enqueue_style(
			'mets-kb-article-manager',
			METS_PLUGIN_URL . 'admin/css/mets-kb-article-manager.css',
			array( 'wp-admin' ),
			METS_VERSION
		);
	}

	/**
	 * Display the enhanced article form
	 *
	 * @since    2.0.0
	 * @param    int    $article_id    Article ID (0 for new article)
	 */
	public function display_article_form( $article_id = 0 ) {
		// Check permissions
		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'multi-entity-ticket-system' ) );
		}

		$is_edit = $article_id > 0;
		$article = null;

		// Get article data if editing
		if ( $is_edit ) {
			$article = $this->article_model->get( $article_id );
			if ( ! $article ) {
				wp_die( __( 'Article not found.', 'multi-entity-ticket-system' ) );
			}

			// Check edit permissions
			if ( ! $this->can_edit_article( $article ) ) {
				wp_die( __( 'You do not have permission to edit this article.', 'multi-entity-ticket-system' ) );
			}
		}

		// Get form data
		$entities = $this->entity_model->get_all( array( 'parent_id' => 'all' ) );
		$current_entity_id = $article ? $article->entity_id : ( isset( $_GET['entity'] ) ? intval( $_GET['entity'] ) : null );
		$categories = $this->get_categories_for_entity( $current_entity_id );
		$current_tags = $is_edit ? $this->tag_model->get_article_tags( $article_id ) : array();
		$popular_tags = $this->tag_model->get_popular_tags( 20 );

		// Get current article categories
		$current_categories = array();
		if ( $is_edit ) {
			global $wpdb;
			$current_categories = $wpdb->get_col( $wpdb->prepare(
				"SELECT category_id FROM {$wpdb->prefix}mets_kb_article_categories WHERE article_id = %d",
				$article_id
			) );
		}

		$this->render_article_form( $article, $entities, $categories, $current_categories, $current_tags, $popular_tags );
	}

	/**
	 * Get categories for specific entity with inheritance
	 *
	 * @since    2.0.0
	 * @param    int|null    $entity_id    Entity ID
	 * @return   array                     Array of categories
	 */
	private function get_categories_for_entity( $entity_id ) {
		if ( $entity_id !== null ) {
			return $this->category_model->get_by_entity( $entity_id, true );
		} else {
			return $this->category_model->get_by_entity( null );
		}
	}

	/**
	 * Check if current user can edit the article
	 *
	 * @since    2.0.0
	 * @param    object    $article    Article object
	 * @return   bool                  True if user can edit, false otherwise
	 */
	private function can_edit_article( $article ) {
		// System administrators and managers can edit any article
		if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_tickets' ) ) {
			return true;
		}

		// Authors can edit their own articles
		if ( current_user_can( 'edit_kb_articles' ) && $article->author_id == get_current_user_id() ) {
			return true;
		}

		// Entity-specific editors can edit articles for their entities
		if ( current_user_can( 'edit_entity_kb_articles' ) ) {
			// Get user's entities and check if article belongs to one of them
			$user_entities = $this->get_user_entities( get_current_user_id() );
			return in_array( $article->entity_id, $user_entities );
		}

		return false;
	}

	/**
	 * Get entities that user can manage
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    User ID
	 * @return   array              Array of entity IDs
	 */
	private function get_user_entities( $user_id ) {
		// This would be implemented based on your entity-user relationship logic
		// For now, return empty array as placeholder
		return array();
	}

	/**
	 * Render the article form HTML
	 *
	 * @since    2.0.0
	 */
	private function render_article_form( $article, $entities, $categories, $current_categories, $current_tags, $popular_tags ) {
		$is_edit = ! empty( $article );
		$article_id = $is_edit ? $article->id : 0;
		
		?>
		<div class="wrap mets-kb-article-wrap">
			<h1 class="wp-heading-inline">
				<?php echo $is_edit ? __( 'Edit Article', 'multi-entity-ticket-system' ) : __( 'Add New Article', 'multi-entity-ticket-system' ); ?>
			</h1>
			
			<?php if ( ! $is_edit ): ?>
				<a href="<?php echo admin_url( 'admin.php?page=mets-kb-articles' ); ?>" class="page-title-action">
					<?php _e( 'View All Articles', 'multi-entity-ticket-system' ); ?>
				</a>
			<?php endif; ?>

			<!-- Status indicator -->
			<div id="mets-kb-status" class="mets-kb-status">
				<span class="status-text"><?php _e( 'Ready', 'multi-entity-ticket-system' ); ?></span>
				<span class="spinner"></span>
			</div>

			<form method="post" id="mets-kb-article-form" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data" data-article-id="<?php echo esc_attr( $article_id ); ?>">
				<?php wp_nonce_field( 'mets_kb_save_article', 'mets_kb_nonce' ); ?>
				<input type="hidden" name="action" value="mets_kb_save_article">
				<input type="hidden" name="article_id" value="<?php echo esc_attr( $article_id ); ?>">
				<input type="hidden" name="auto_save" value="0" id="auto_save_field">
				
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<!-- Main content area -->
						<div id="post-body-content">
							<?php $this->render_title_section( $article ); ?>
							<?php $this->render_content_section( $article ); ?>
							<?php $this->render_excerpt_section( $article ); ?>
						</div>

						<!-- Sidebar -->
						<div id="postbox-container-1" class="postbox-container">
							<?php $this->render_publish_box( $article ); ?>
							<?php $this->render_entity_categories_box( $article, $entities, $categories, $current_categories ); ?>
							<?php $this->render_tags_box( $current_tags, $popular_tags ); ?>
							<?php $this->render_attachments_box( $article ); ?>
							<?php $this->render_seo_box( $article ); ?>
						</div>
					</div>
				</div>
			</form>
		</div>

		<?php $this->render_javascript_templates(); ?>
		<?php
	}

	/**
	 * Render title section
	 *
	 * @since    2.0.0
	 */
	private function render_title_section( $article ) {
		?>
		<div id="titlediv">
			<div id="titlewrap">
				<input type="text" 
					   name="title" 
					   id="title" 
					   value="<?php echo $article ? esc_attr( $article->title ) : ''; ?>" 
					   placeholder="<?php esc_attr_e( 'Enter article title', 'multi-entity-ticket-system' ); ?>"
					   autocomplete="off" 
					   required
					   data-autosave="true">
				<div id="title-validation" class="validation-message"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render content section
	 *
	 * @since    2.0.0
	 */
	private function render_content_section( $article ) {
		?>
		<div id="postdivrich" class="postarea">
			<?php
			$content = $article ? $article->content : '';
			$editor_id = 'article_content';
			$settings = array(
				'textarea_name' => 'content',
				'media_buttons' => true,
				'teeny' => false,
				'textarea_rows' => 20,
				'tabindex' => 2,
				'editor_class' => 'mets-kb-content-editor',
				'quicktags' => array(
					'buttons' => 'strong,em,ul,ol,li,link,code,close'
				)
			);
			wp_editor( $content, $editor_id, $settings );
			?>
			<div id="content-validation" class="validation-message"></div>
		</div>
		<?php
	}

	/**
	 * Render excerpt section
	 *
	 * @since    2.0.0
	 */
	private function render_excerpt_section( $article ) {
		?>
		<div id="postexcerpt" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'Excerpt', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<textarea name="excerpt" 
						  id="excerpt" 
						  rows="3" 
						  cols="40" 
						  data-autosave="true"
						  placeholder="<?php esc_attr_e( 'Optional. Brief description for search results and article previews.', 'multi-entity-ticket-system' ); ?>"><?php echo $article ? esc_textarea( $article->excerpt ) : ''; ?></textarea>
				<p class="description">
					<?php _e( 'Excerpts are optional hand-crafted summaries of your content. If left blank, one will be generated automatically.', 'multi-entity-ticket-system' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render publish box
	 *
	 * @since    2.0.0
	 */
	private function render_publish_box( $article ) {
		?>
		<div id="submitdiv" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'Publish', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<div class="submitbox" id="submitpost">
					<div id="minor-publishing">
						<!-- Status -->
						<div class="misc-pub-section">
							<label for="article_status"><?php _e( 'Status:', 'multi-entity-ticket-system' ); ?></label>
							<select name="status" id="article_status" data-autosave="true">
								<option value="draft" <?php selected( $article ? $article->status : 'draft', 'draft' ); ?>>
									<?php _e( 'Draft', 'multi-entity-ticket-system' ); ?>
								</option>
								<?php if ( current_user_can( 'publish_kb_articles' ) ): ?>
									<option value="pending" <?php selected( $article ? $article->status : '', 'pending' ); ?>>
										<?php _e( 'Pending Review', 'multi-entity-ticket-system' ); ?>
									</option>
									<option value="published" <?php selected( $article ? $article->status : '', 'published' ); ?>>
										<?php _e( 'Published', 'multi-entity-ticket-system' ); ?>
									</option>
								<?php endif; ?>
							</select>
						</div>

						<!-- Visibility -->
						<div class="misc-pub-section">
							<label for="article_visibility"><?php _e( 'Visibility:', 'multi-entity-ticket-system' ); ?></label>
							<select name="visibility" id="article_visibility" data-autosave="true">
								<option value="internal" <?php selected( $article ? $article->visibility : 'internal', 'internal' ); ?>>
									<?php _e( 'Internal Only', 'multi-entity-ticket-system' ); ?>
								</option>
								<option value="staff" <?php selected( $article ? $article->visibility : '', 'staff' ); ?>>
									<?php _e( 'Staff & Agents', 'multi-entity-ticket-system' ); ?>
								</option>
								<option value="customer" <?php selected( $article ? $article->visibility : '', 'customer' ); ?>>
									<?php _e( 'All Users', 'multi-entity-ticket-system' ); ?>
								</option>
							</select>
						</div>

						<!-- Featured -->
						<div class="misc-pub-section">
							<label>
								<input type="checkbox" 
									   name="is_featured" 
									   id="is_featured"
									   value="1" 
									   data-autosave="true"
									   <?php checked( $article ? $article->featured : 0, 1 ); ?>>
								<?php _e( 'Featured Article', 'multi-entity-ticket-system' ); ?>
							</label>
						</div>
					</div>

					<div id="major-publishing-actions">
						<div id="delete-action">
							<?php if ( ! empty( $article ) && current_user_can( 'delete_kb_articles' ) ): ?>
								<a class="submitdelete deletion" 
								   href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=mets-kb-articles&action=delete&article_id=' . $article->id ), 'delete_kb_article_' . $article->id ); ?>" 
								   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this article?', 'multi-entity-ticket-system' ); ?>')">
									<?php _e( 'Move to Trash', 'multi-entity-ticket-system' ); ?>
								</a>
							<?php endif; ?>
						</div>

						<div id="publishing-action">
							<input type="submit" 
								   name="save_article" 
								   id="publish" 
								   class="button button-primary button-large" 
								   value="<?php echo ! empty( $article ) ? __( 'Update Article', 'multi-entity-ticket-system' ) : __( 'Save Article', 'multi-entity-ticket-system' ); ?>">
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render entity and categories box
	 *
	 * @since    2.0.0
	 */
	private function render_entity_categories_box( $article, $entities, $categories, $current_categories ) {
		?>
		<div id="categorydiv" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'Entity & Categories', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<!-- Entity Selection -->
				<div class="kb-field-group">
					<label for="article_entity"><?php _e( 'Entity:', 'multi-entity-ticket-system' ); ?></label>
					<select name="entity_id" id="article_entity" data-autosave="true">
						<option value=""><?php _e( 'Global Article (All Entities)', 'multi-entity-ticket-system' ); ?></option>
						<?php foreach ( $entities as $entity ): ?>
							<option value="<?php echo $entity->id; ?>" 
									<?php selected( $article ? $article->entity_id : null, $entity->id ); ?>>
								<?php echo esc_html( $entity->name ); ?>
								<?php if ( isset( $entity->parent_name ) && $entity->parent_name ): ?>
									(<?php echo esc_html( $entity->parent_name ); ?>)
								<?php endif; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Categories -->
				<div class="kb-field-group">
					<label><?php _e( 'Categories:', 'multi-entity-ticket-system' ); ?></label>
					<div id="categories-list" class="categories-checklist">
						<?php $this->render_categories_checkboxes( $categories, $current_categories ); ?>
					</div>
					<div id="categories-loading" class="loading-indicator" style="display: none;">
						<span class="spinner is-active"></span>
						<?php _e( 'Loading categories...', 'multi-entity-ticket-system' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render categories checkboxes
	 *
	 * @since    2.0.0
	 */
	private function render_categories_checkboxes( $categories, $current_categories = array() ) {
		if ( empty( $categories ) ) {
			echo '<p class="no-categories">' . __( 'No categories available for this entity.', 'multi-entity-ticket-system' ) . '</p>';
			return;
		}

		foreach ( $categories as $category ) {
			$checked = in_array( $category->id, $current_categories );
			?>
			<label class="category-item">
				<input type="checkbox" 
					   name="categories[]" 
					   value="<?php echo esc_attr( $category->id ); ?>"
					   data-autosave="true"
					   <?php checked( $checked ); ?>>
				<?php echo esc_html( $category->name ); ?>
			</label>
			<?php
		}
	}

	/**
	 * Render tags box
	 *
	 * @since    2.0.0
	 */
	private function render_tags_box( $current_tags, $popular_tags ) {
		$current_tag_names = array_map( function( $tag ) {
			return $tag->name;
		}, $current_tags );
		
		?>
		<div id="tagsdiv" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'Tags', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<div class="kb-field-group">
					<textarea name="tags" 
							  id="article_tags" 
							  rows="3" 
							  data-autosave="true"
							  placeholder="<?php esc_attr_e( 'Enter tags separated by commas', 'multi-entity-ticket-system' ); ?>"><?php echo esc_textarea( implode( ', ', $current_tag_names ) ); ?></textarea>
					<p class="description">
						<?php _e( 'Separate tags with commas. Tags help users find related articles.', 'multi-entity-ticket-system' ); ?>
					</p>
				</div>

				<?php if ( ! empty( $popular_tags ) ): ?>
					<div class="kb-field-group">
						<label><?php _e( 'Popular Tags:', 'multi-entity-ticket-system' ); ?></label>
						<div class="popular-tags">
							<?php foreach ( $popular_tags as $tag ): ?>
								<button type="button" 
										class="button button-small add-tag-button" 
										data-tag="<?php echo esc_attr( $tag->name ); ?>">
									<?php echo esc_html( $tag->name ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render attachments box
	 *
	 * @since    2.0.0
	 */
	private function render_attachments_box( $article ) {
		?>
		<div id="attachmentsdiv" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'Attachments', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<div class="kb-field-group">
					<input type="file" 
						   name="attachments[]" 
						   id="article_attachments" 
						   multiple 
						   accept=".pdf,.doc,.docx,.odt,.ods,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp">
					<p class="description">
						<?php _e( 'Upload files to attach to this article. Allowed types: PDF, Word docs, spreadsheets, images. Max size: 10MB per file.', 'multi-entity-ticket-system' ); ?>
					</p>
				</div>

				<?php if ( ! empty( $article ) ): ?>
					<div id="current-attachments">
						<?php $this->render_current_attachments( $article->id ); ?>
					</div>
				<?php endif; ?>

				<div id="upload-progress" class="upload-progress" style="display: none;">
					<div class="progress-bar">
						<div class="progress-fill"></div>
					</div>
					<div class="progress-text"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render current attachments
	 *
	 * @since    2.0.0
	 */
	private function render_current_attachments( $article_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();
		$attachments = $attachment_model->get_by_article( $article_id );

		if ( empty( $attachments ) ) {
			echo '<p class="no-attachments">' . __( 'No attachments', 'multi-entity-ticket-system' ) . '</p>';
			return;
		}

		foreach ( $attachments as $attachment ) {
			?>
			<div class="attachment-item" data-attachment-id="<?php echo esc_attr( $attachment->id ); ?>">
				<span class="attachment-name"><?php echo esc_html( $attachment->original_filename ); ?></span>
				<span class="attachment-size">(<?php echo size_format( $attachment->file_size ); ?>)</span>
				<button type="button" 
						class="button button-small delete-attachment" 
						data-attachment-id="<?php echo esc_attr( $attachment->id ); ?>">
					<?php _e( 'Remove', 'multi-entity-ticket-system' ); ?>
				</button>
			</div>
			<?php
		}
	}

	/**
	 * Render SEO box
	 *
	 * @since    2.0.0
	 */
	private function render_seo_box( $article ) {
		?>
		<div id="seodiv" class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php _e( 'SEO Settings', 'multi-entity-ticket-system' ); ?></h2>
			</div>
			<div class="inside">
				<div class="kb-field-group">
					<label for="meta_title"><?php _e( 'SEO Title:', 'multi-entity-ticket-system' ); ?></label>
					<input type="text" 
						   name="meta_title" 
						   id="meta_title" 
						   value="<?php echo $article ? esc_attr( $article->meta_title ) : ''; ?>"
						   data-autosave="true"
						   placeholder="<?php esc_attr_e( 'Leave blank to use article title', 'multi-entity-ticket-system' ); ?>">
					<p class="description">
						<?php _e( 'Custom title for search engines (60 characters recommended).', 'multi-entity-ticket-system' ); ?>
					</p>
				</div>

				<div class="kb-field-group">
					<label for="meta_description"><?php _e( 'Meta Description:', 'multi-entity-ticket-system' ); ?></label>
					<textarea name="meta_description" 
							  id="meta_description" 
							  rows="3"
							  data-autosave="true"
							  placeholder="<?php esc_attr_e( 'Brief description for search engines', 'multi-entity-ticket-system' ); ?>"><?php echo $article ? esc_textarea( $article->meta_description ) : ''; ?></textarea>
					<p class="description">
						<?php _e( 'Description for search engine results (160 characters recommended).', 'multi-entity-ticket-system' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render JavaScript templates
	 *
	 * @since    2.0.0
	 */
	private function render_javascript_templates() {
		?>
		<script type="text/html" id="tmpl-category-checkbox">
			<label class="category-item">
				<input type="checkbox" name="categories[]" value="{{ data.id }}" data-autosave="true" <# if (data.checked) { #>checked<# } #>>
				{{ data.name }}
			</label>
		</script>

		<script type="text/html" id="tmpl-attachment-item">
			<div class="attachment-item" data-attachment-id="{{ data.id }}">
				<span class="attachment-name">{{ data.filename }}</span>
				<span class="attachment-size">({{ data.size }})</span>
				<button type="button" class="button button-small delete-attachment" data-attachment-id="{{ data.id }}">
					<?php _e( 'Remove', 'multi-entity-ticket-system' ); ?>
				</button>
			</div>
		</script>
		<?php
	}

	/**
	 * AJAX handler for auto-save
	 *
	 * @since    2.0.0
	 */
	public function ajax_auto_save() {
		check_ajax_referer( 'mets_kb_article_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) ) );
		}

		$article_id = intval( $_POST['article_id'] ?? 0 );
		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$content = wp_kses_post( $_POST['content'] ?? '' );

		// Validate required fields for auto-save
		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Title is required', 'multi-entity-ticket-system' ) ) );
		}

		$article_data = array(
			'title' => $title,
			'content' => $content,
			'entity_id' => ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null,
			'status' => sanitize_text_field( $_POST['status'] ?? 'draft' ),
			'visibility' => sanitize_text_field( $_POST['visibility'] ?? 'customer' ),
			'featured' => ! empty( $_POST['is_featured'] ) ? 1 : 0,
			'excerpt' => sanitize_textarea_field( $_POST['excerpt'] ?? '' ),
			'meta_title' => sanitize_text_field( $_POST['meta_title'] ?? '' ),
			'meta_description' => sanitize_textarea_field( $_POST['meta_description'] ?? '' ),
		);

		// Generate excerpt if empty
		if ( empty( $article_data['excerpt'] ) ) {
			$article_data['excerpt'] = wp_trim_words( strip_tags( $content ), 30 );
		}

		try {
			if ( $article_id > 0 ) {
				// Update existing article
				$result = $this->article_model->update( $article_id, $article_data );
				$saved_article_id = $article_id;
			} else {
				// Create new article
				$article_data['author_id'] = get_current_user_id();
				$result = $this->article_model->create( $article_data );
				$saved_article_id = $result;
			}

			if ( ! $result ) {
				throw new Exception( __( 'Failed to save article', 'multi-entity-ticket-system' ) );
			}

			// Handle categories
			if ( isset( $_POST['categories'] ) ) {
				$this->save_article_categories( $saved_article_id, $_POST['categories'] );
			}

			// Handle tags
			if ( isset( $_POST['tags'] ) ) {
				$this->save_article_tags( $saved_article_id, $_POST['tags'] );
			}

			wp_send_json_success( array(
				'message' => __( 'Auto-saved', 'multi-entity-ticket-system' ),
				'article_id' => $saved_article_id,
				'timestamp' => current_time( 'mysql' )
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler for getting entity categories
	 *
	 * @since    2.0.0
	 */
	public function ajax_get_categories() {
		check_ajax_referer( 'mets_kb_article_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) ) );
		}

		$entity_id = ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		$article_id = intval( $_POST['article_id'] ?? 0 );

		$categories = $this->get_categories_for_entity( $entity_id );

		// Get current article categories if editing
		$current_categories = array();
		if ( $article_id > 0 ) {
			global $wpdb;
			$current_categories = $wpdb->get_col( $wpdb->prepare(
				"SELECT category_id FROM {$wpdb->prefix}mets_kb_article_categories WHERE article_id = %d",
				$article_id
			) );
		}

		$category_data = array();
		foreach ( $categories as $category ) {
			$category_data[] = array(
				'id' => $category->id,
				'name' => $category->name,
				'checked' => in_array( $category->id, $current_categories )
			);
		}

		wp_send_json_success( array( 'categories' => $category_data ) );
	}

	/**
	 * Save article categories
	 *
	 * @since    2.0.0
	 */
	private function save_article_categories( $article_id, $categories ) {
		global $wpdb;

		// Remove existing categories
		$wpdb->delete(
			$wpdb->prefix . 'mets_kb_article_categories',
			array( 'article_id' => $article_id ),
			array( '%d' )
		);

		// Add new categories
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			foreach ( $categories as $category_id ) {
				$category_id = intval( $category_id );
				if ( $category_id > 0 ) {
					$wpdb->insert(
						$wpdb->prefix . 'mets_kb_article_categories',
						array(
							'article_id' => $article_id,
							'category_id' => $category_id
						),
						array( '%d', '%d' )
					);
				}
			}
		}
	}

	/**
	 * Save article tags
	 *
	 * @since    2.0.0
	 */
	private function save_article_tags( $article_id, $tags ) {
		if ( ! empty( $tags ) ) {
			$tag_names = array_map( 'trim', explode( ',', $tags ) );
			$tag_names = array_filter( $tag_names );
			$this->tag_model->set_article_tags( $article_id, $tag_names );
		}
	}

	/**
	 * AJAX handler for file upload
	 *
	 * @since    2.0.0
	 */
	public function ajax_upload_file() {
		check_ajax_referer( 'mets_kb_article_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) ) );
		}

		$article_id = intval( $_POST['article_id'] ?? 0 );
		
		if ( $article_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid article ID', 'multi-entity-ticket-system' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded', 'multi-entity-ticket-system' ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();

		$result = $attachment_model->upload_attachment( $article_id, $_FILES['file'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$attachment = $attachment_model->get( $result );
		
		wp_send_json_success( array(
			'message' => __( 'File uploaded successfully', 'multi-entity-ticket-system' ),
			'attachment' => array(
				'id' => $attachment->id,
				'filename' => $attachment->original_filename,
				'size' => size_format( $attachment->file_size )
			)
		) );
	}

	/**
	 * AJAX handler for deleting attachment
	 *
	 * @since    2.0.0
	 */
	public function ajax_delete_attachment() {
		check_ajax_referer( 'mets_kb_article_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) ) );
		}

		$attachment_id = intval( $_POST['attachment_id'] ?? 0 );
		
		if ( $attachment_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID', 'multi-entity-ticket-system' ) ) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();

		$result = $attachment_model->delete( $attachment_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete attachment', 'multi-entity-ticket-system' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Attachment deleted successfully', 'multi-entity-ticket-system' ) ) );
	}

	/**
	 * AJAX handler for title validation
	 *
	 * @since    2.0.0
	 */
	public function ajax_validate_title() {
		check_ajax_referer( 'mets_kb_article_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) ) );
		}

		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$entity_id = ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null;
		$article_id = intval( $_POST['article_id'] ?? 0 );

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Title is required', 'multi-entity-ticket-system' ) ) );
		}

		// Check for duplicate titles within the same entity
		global $wpdb;
		$where_conditions = array( "title = %s" );
		$params = array( $title );

		if ( is_null( $entity_id ) ) {
			$where_conditions[] = "entity_id IS NULL";
		} else {
			$where_conditions[] = "entity_id = %d";
			$params[] = $entity_id;
		}

		if ( $article_id > 0 ) {
			$where_conditions[] = "id != %d";
			$params[] = $article_id;
		}

		$where_clause = implode( ' AND ', $where_conditions );

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_kb_articles WHERE {$where_clause}",
			$params
		) );

		if ( $exists > 0 ) {
			wp_send_json_error( array( 'message' => __( 'An article with this title already exists for this entity', 'multi-entity-ticket-system' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Title is available', 'multi-entity-ticket-system' ) ) );
	}

	/**
	 * Handle traditional form submission
	 *
	 * @since    2.0.0
	 */
	public function handle_form_submit() {
		// Check permissions
		if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to edit KB articles.', 'multi-entity-ticket-system' ) );
		}

		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['mets_kb_nonce'] ?? '', 'mets_kb_save_article' ) ) {
			wp_die( __( 'Security check failed.', 'multi-entity-ticket-system' ) );
		}

		$article_id = intval( $_POST['article_id'] ?? 0 );
		$is_edit = $article_id > 0;

		// Validate required fields
		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$content = wp_kses_post( $_POST['content'] ?? '' );
		
		if ( empty( $title ) || empty( trim( strip_tags( $content ) ) ) ) {
			wp_redirect( add_query_arg( array(
				'page' => 'mets-kb-add-article',
				'article_id' => $article_id,
				'error' => 'validation'
			), admin_url( 'admin.php' ) ) );
			exit;
		}

		try {
			// Prepare article data
			$article_data = array(
				'title' => $title,
				'content' => $content,
				'entity_id' => ! empty( $_POST['entity_id'] ) ? intval( $_POST['entity_id'] ) : null,
				'status' => sanitize_text_field( $_POST['status'] ?? 'draft' ),
				'visibility' => sanitize_text_field( $_POST['visibility'] ?? 'customer' ),
				'featured' => ! empty( $_POST['is_featured'] ) ? 1 : 0,
				'excerpt' => sanitize_textarea_field( $_POST['excerpt'] ?? '' ),
				'meta_title' => sanitize_text_field( $_POST['meta_title'] ?? '' ),
				'meta_description' => sanitize_textarea_field( $_POST['meta_description'] ?? '' ),
			);

			// Generate excerpt if empty
			if ( empty( $article_data['excerpt'] ) ) {
				$article_data['excerpt'] = wp_trim_words( strip_tags( $content ), 30 );
			}

			// Save article
			if ( $is_edit ) {
				$result = $this->article_model->update( $article_id, $article_data );
				$saved_article_id = $article_id;
				$action = 'updated';
			} else {
				$article_data['author_id'] = get_current_user_id();
				$result = $this->article_model->create( $article_data );
				$saved_article_id = $result;
				$action = 'created';
			}

			if ( ! $result ) {
				throw new Exception( __( 'Failed to save article', 'multi-entity-ticket-system' ) );
			}

			// Handle categories
			if ( isset( $_POST['categories'] ) ) {
				$this->save_article_categories( $saved_article_id, $_POST['categories'] );
			}

			// Handle tags
			if ( isset( $_POST['tags'] ) ) {
				$this->save_article_tags( $saved_article_id, $_POST['tags'] );
			}

			// Handle file uploads
			if ( ! empty( $_FILES['attachments']['name'][0] ) ) {
				$this->handle_file_uploads( $saved_article_id );
			}

			// Redirect with success message
			wp_redirect( add_query_arg( array(
				'page' => 'mets-kb-add-article',
				'article_id' => $saved_article_id,
				'message' => $action
			), admin_url( 'admin.php' ) ) );
			exit;

		} catch ( Exception $e ) {
			wp_redirect( add_query_arg( array(
				'page' => 'mets-kb-add-article',
				'article_id' => $article_id,
				'error' => 'save_failed',
				'message' => urlencode( $e->getMessage() )
			), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Handle file uploads during form submission
	 *
	 * @since    2.0.0
	 */
	private function handle_file_uploads( $article_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-attachment-model.php';
		$attachment_model = new METS_KB_Attachment_Model();

		$uploaded_count = 0;
		$errors = array();

		for ( $i = 0; $i < count( $_FILES['attachments']['name'] ); $i++ ) {
			if ( empty( $_FILES['attachments']['name'][$i] ) ) {
				continue;
			}

			$file = array(
				'name' => $_FILES['attachments']['name'][$i],
				'type' => $_FILES['attachments']['type'][$i],
				'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
				'error' => $_FILES['attachments']['error'][$i],
				'size' => $_FILES['attachments']['size'][$i]
			);

			$result = $attachment_model->upload_attachment( $article_id, $file );

			if ( is_wp_error( $result ) ) {
				$errors[] = $file['name'] . ': ' . $result->get_error_message();
			} else {
				$uploaded_count++;
			}
		}

		// Store upload results in transient for display
		if ( $uploaded_count > 0 || ! empty( $errors ) ) {
			set_transient( 'mets_kb_upload_results_' . get_current_user_id(), array(
				'uploaded' => $uploaded_count,
				'errors' => $errors
			), 60 );
		}
	}
}