<?php
/**
 * KB Admin Integration
 *
 * Integrates the new KB Article Manager with the existing admin system
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @since      2.0.0
 */

/**
 * KB Admin Integration class.
 *
 * This class handles the integration of the enhanced KB Article Manager
 * with the existing admin system, providing backward compatibility
 * while enabling new features.
 *
 * @since      2.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin/kb
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_KB_Admin_Integration {

	/**
	 * KB Article Manager instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      METS_KB_Article_Manager    $article_manager    Article manager instance.
	 */
	private $article_manager;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies
	 *
	 * @since    2.0.0
	 */
	private function load_dependencies() {
		require_once METS_PLUGIN_PATH . 'admin/kb/class-mets-kb-article-manager.php';
		$this->article_manager = new METS_KB_Article_Manager();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since    2.0.0
	 */
	private function init_hooks() {
		// Immediately set up KB handlers instead of waiting for admin_init
		$this->maybe_override_kb_handlers();
		
		// Add admin notices for feedback
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		
		// Add heartbeat hooks for auto-save
		add_filter( 'heartbeat_received', array( $this, 'heartbeat_received' ), 10, 2 );
	}

	/**
	 * Override existing KB article handlers if enhanced mode is enabled
	 *
	 * @since    2.0.0
	 */
	public function maybe_override_kb_handlers() {
		// Check if enhanced KB editing is enabled (could be a setting)
		$enhanced_mode = get_option( 'mets_kb_enhanced_mode', true );
		
		if ( $enhanced_mode ) {
			// Remove existing form submission handler if it exists
			remove_action( 'admin_post_mets_kb_save_article', array( 'METS_Admin', 'handle_kb_article_form_submission' ) );
			
			// Override specific admin methods using filters
			add_filter( 'mets_kb_display_article_form', array( $this, 'display_enhanced_article_form' ), 10, 2 );
		}
	}

	/**
	 * Display enhanced article form
	 *
	 * @since    2.0.0
	 * @param    bool    $use_enhanced    Whether to use enhanced form
	 * @param    int     $article_id      Article ID
	 * @return   bool                     Always true to use enhanced form
	 */
	public function display_enhanced_article_form( $use_enhanced, $article_id ) {
		$this->article_manager->display_article_form( $article_id );
		return true;
	}

	/**
	 * Display admin notices
	 *
	 * @since    2.0.0
	 */
	public function display_admin_notices() {
		// Display upload results if available
		$upload_results = get_transient( 'mets_kb_upload_results_' . get_current_user_id() );
		if ( $upload_results ) {
			delete_transient( 'mets_kb_upload_results_' . get_current_user_id() );
			
			if ( $upload_results['uploaded'] > 0 ) {
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>' . sprintf( 
					_n( 
						'%d file uploaded successfully.', 
						'%d files uploaded successfully.', 
						$upload_results['uploaded'], 
						'multi-entity-ticket-system' 
					), 
					$upload_results['uploaded'] 
				) . '</p>';
				echo '</div>';
			}
			
			if ( ! empty( $upload_results['errors'] ) ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . __( 'Some files could not be uploaded:', 'multi-entity-ticket-system' ) . '</p>';
				echo '<ul>';
				foreach ( $upload_results['errors'] as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
			}
		}

		// Display success/error messages from URL parameters
		if ( isset( $_GET['message'] ) ) {
			$message_type = $_GET['message'];
			switch ( $message_type ) {
				case 'created':
					echo '<div class="notice notice-success is-dismissible">';
					echo '<p>' . __( 'KB Article created successfully! You can continue editing or add attachments below.', 'multi-entity-ticket-system' ) . '</p>';
					echo '</div>';
					break;
				case 'updated':
					echo '<div class="notice notice-success is-dismissible">';
					echo '<p>' . __( 'KB Article updated successfully.', 'multi-entity-ticket-system' ) . '</p>';
					echo '</div>';
					break;
			}
		}

		if ( isset( $_GET['error'] ) ) {
			$error_type = $_GET['error'];
			switch ( $error_type ) {
				case 'validation':
					echo '<div class="notice notice-error is-dismissible">';
					echo '<p>' . __( 'Please fill in all required fields (title and content).', 'multi-entity-ticket-system' ) . '</p>';
					echo '</div>';
					break;
				case 'save_failed':
					$message = isset( $_GET['message'] ) ? urldecode( $_GET['message'] ) : __( 'Failed to save article.', 'multi-entity-ticket-system' );
					echo '<div class="notice notice-error is-dismissible">';
					echo '<p>' . esc_html( $message ) . '</p>';
					echo '</div>';
					break;
			}
		}
	}

	/**
	 * Handle heartbeat auto-save
	 *
	 * @since    2.0.0
	 * @param    array    $response    Heartbeat response data
	 * @param    array    $data        Heartbeat data
	 * @return   array                 Modified response data
	 */
	public function heartbeat_received( $response, $data ) {
		if ( isset( $data['mets_kb_autosave'] ) ) {
			// Check permissions
			if ( ! current_user_can( 'edit_kb_articles' ) && ! current_user_can( 'manage_tickets' ) ) {
				$response['mets_kb_autosave_response'] = array(
					'success' => false,
					'data' => array( 'message' => __( 'Permission denied', 'multi-entity-ticket-system' ) )
				);
				return $response;
			}

			$autosave_data = $data['mets_kb_autosave'];
			$article_id = intval( $autosave_data['article_id'] ?? 0 );

			// Validate required fields
			if ( empty( $autosave_data['title'] ) || empty( trim( $autosave_data['title'] ) ) ) {
				$response['mets_kb_autosave_response'] = array(
					'success' => false,
					'data' => array( 'message' => __( 'Title is required', 'multi-entity-ticket-system' ) )
				);
				return $response;
			}

			try {
				// Load models
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-article-model.php';
				require_once METS_PLUGIN_PATH . 'includes/models/class-mets-kb-tag-model.php';
				
				$article_model = new METS_KB_Article_Model();
				$tag_model = new METS_KB_Tag_Model();

				// Prepare article data
				$article_data = array(
					'title' => sanitize_text_field( $autosave_data['title'] ),
					'content' => wp_kses_post( $autosave_data['content'] ?? '' ),
					'entity_id' => ! empty( $autosave_data['entity_id'] ) ? intval( $autosave_data['entity_id'] ) : null,
					'status' => sanitize_text_field( $autosave_data['status'] ?? 'draft' ),
					'visibility' => sanitize_text_field( $autosave_data['visibility'] ?? 'customer' ),
					'featured' => ! empty( $autosave_data['is_featured'] ) ? 1 : 0,
					'excerpt' => sanitize_textarea_field( $autosave_data['excerpt'] ?? '' ),
					'meta_title' => sanitize_text_field( $autosave_data['meta_title'] ?? '' ),
					'meta_description' => sanitize_textarea_field( $autosave_data['meta_description'] ?? '' ),
				);

				// Generate excerpt if empty
				if ( empty( $article_data['excerpt'] ) ) {
					$article_data['excerpt'] = wp_trim_words( strip_tags( $article_data['content'] ), 30 );
				}

				// Save article
				if ( $article_id > 0 ) {
					$result = $article_model->update( $article_id, $article_data );
					$saved_article_id = $article_id;
				} else {
					$article_data['author_id'] = get_current_user_id();
					$result = $article_model->create( $article_data );
					$saved_article_id = $result;
				}

				if ( ! $result ) {
					throw new Exception( __( 'Failed to save article', 'multi-entity-ticket-system' ) );
				}

				// Handle categories
				if ( isset( $autosave_data['categories'] ) ) {
					$this->save_article_categories( $saved_article_id, $autosave_data['categories'] );
				}

				// Handle tags
				if ( isset( $autosave_data['tags'] ) ) {
					$this->save_article_tags( $saved_article_id, $autosave_data['tags'], $tag_model );
				}

				$response['mets_kb_autosave_response'] = array(
					'success' => true,
					'data' => array(
						'message' => __( 'Auto-saved', 'multi-entity-ticket-system' ),
						'article_id' => $saved_article_id,
						'timestamp' => current_time( 'mysql' )
					)
				);

			} catch ( Exception $e ) {
				$response['mets_kb_autosave_response'] = array(
					'success' => false,
					'data' => array( 'message' => $e->getMessage() )
				);
			}
		}

		return $response;
	}

	/**
	 * Save article categories
	 *
	 * @since    2.0.0
	 * @param    int      $article_id    Article ID
	 * @param    array    $categories    Category IDs
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
	 * @param    int                    $article_id    Article ID
	 * @param    string                 $tags          Comma-separated tags
	 * @param    METS_KB_Tag_Model      $tag_model     Tag model instance
	 */
	private function save_article_tags( $article_id, $tags, $tag_model ) {
		if ( ! empty( $tags ) ) {
			$tag_names = array_map( 'trim', explode( ',', $tags ) );
			$tag_names = array_filter( $tag_names );
			$tag_model->set_article_tags( $article_id, $tag_names );
		}
	}

	/**
	 * Get the article manager instance
	 *
	 * @since    2.0.0
	 * @return   METS_KB_Article_Manager    Article manager instance
	 */
	public function get_article_manager() {
		return $this->article_manager;
	}

	/**
	 * Check if enhanced mode is enabled
	 *
	 * @since    2.0.0
	 * @return   bool    True if enhanced mode is enabled
	 */
	public static function is_enhanced_mode_enabled() {
		return get_option( 'mets_kb_enhanced_mode', true );
	}

	/**
	 * Enable enhanced KB editing mode
	 *
	 * @since    2.0.0
	 */
	public static function enable_enhanced_mode() {
		update_option( 'mets_kb_enhanced_mode', true );
	}

	/**
	 * Disable enhanced KB editing mode
	 *
	 * @since    2.0.0
	 */
	public static function disable_enhanced_mode() {
		update_option( 'mets_kb_enhanced_mode', false );
	}
}

// Initialize the integration if we're in admin
if ( is_admin() ) {
	new METS_KB_Admin_Integration();
}