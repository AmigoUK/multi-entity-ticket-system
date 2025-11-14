<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @since      1.0.0
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Initialize knowledgebase functionality
		$this->init_knowledgebase();
	}

	/**
	 * Initialize knowledgebase functionality
	 *
	 * @since    1.0.0
	 */
	private function init_knowledgebase() {
		require_once METS_PLUGIN_PATH . 'public/class-mets-knowledgebase-public.php';
		$kb_public = new METS_Knowledgebase_Public( $this->plugin_name, $this->version );
		$kb_public->init();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, METS_PLUGIN_URL . 'assets/css/mets-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, METS_PLUGIN_URL . 'assets/js/mets-public.js', array( 'jquery' ), $this->version, false );
		
		// Get actual PHP upload limits
		$upload_limits = $this->get_php_upload_limits();
		
		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'mets_public_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mets_public_nonce' ),
			'error_messages' => array(
				'required_fields' => __( 'Please fill in all required fields correctly.', METS_TEXT_DOMAIN ),
				'generic_error' => __( 'An error occurred. Please try again later.', METS_TEXT_DOMAIN ),
			),
			'ticket_number_text' => __( 'Ticket number:', METS_TEXT_DOMAIN ),
			'upload_limits' => $upload_limits,
		) );
		
		// Load n8n chat if enabled
		$n8n_settings = get_option( 'mets_n8n_chat_settings', array() );
		if ( ! empty( $n8n_settings['enabled'] ) && ! empty( $n8n_settings['webhook_url'] ) ) {
			// Check if we should show on current page
			$show_chat = false;
			$current_page_id = get_queried_object_id();
			
			if ( $n8n_settings['allowed_pages'] === 'all' ) {
				$show_chat = true;
			} elseif ( $n8n_settings['allowed_pages'] === 'specific' && ! empty( $n8n_settings['specific_pages'] ) ) {
				$allowed_pages = array_map( 'intval', explode( ',', $n8n_settings['specific_pages'] ) );
				$show_chat = in_array( $current_page_id, $allowed_pages );
			} elseif ( $n8n_settings['allowed_pages'] === 'except' && ! empty( $n8n_settings['specific_pages'] ) ) {
				$excluded_pages = array_map( 'intval', explode( ',', $n8n_settings['specific_pages'] ) );
				$show_chat = ! in_array( $current_page_id, $excluded_pages );
			}
			
			// Check mobile setting
			if ( $show_chat && ! $n8n_settings['show_on_mobile'] && wp_is_mobile() ) {
				$show_chat = false;
			}
			
			if ( $show_chat ) {
				// Prepare chat configuration with parameters to fix input textarea bug
				$chat_config = array(
					'webhookUrl' => $n8n_settings['webhook_url'],
					'position' => $n8n_settings['position'],
					'themeColor' => $n8n_settings['theme_color'],
					'windowTitle' => $n8n_settings['window_title'],
					'subtitle' => $n8n_settings['subtitle'],
					'initialMessage' => $n8n_settings['initial_message'],
					// Additional parameters to fix known input bugs
					'mode' => 'window',
					'chatInputKey' => 'chatInput',
					'showWelcomeScreen' => false,
					'loadPreviousSession' => true,
					'target' => null // Let it auto-create the container
				);
				
				// Add n8n chat to footer using local files to comply with CSP
				add_action( 'wp_footer', function() use ( $chat_config ) {
					$css_url = METS_PLUGIN_URL . 'assets/n8n/n8n-chat-style.css';
					$custom_css_url = METS_PLUGIN_URL . 'assets/n8n/n8n-chat-custom.css';
					$js_url = METS_PLUGIN_URL . 'assets/n8n/n8n-chat-bundle.es.js';
					?>
					<link href="<?php echo esc_url( $css_url ); ?>" rel="stylesheet" />
					<link href="<?php echo esc_url( $custom_css_url ); ?>" rel="stylesheet" />
					<script type="module">
						import { createChat } from '<?php echo esc_url( $js_url ); ?>';
						
						document.addEventListener('DOMContentLoaded', function() {
							try {
								createChat(<?php echo json_encode( $chat_config ); ?>);
								console.log('n8n chat initialized successfully');
							} catch (error) {
								console.error('n8n chat initialization failed:', error);
							}
						});
					</script>
					<?php
				}, 100 );
			}
		}
	}

	/**
	 * Get PHP upload limits
	 *
	 * @since    1.0.0
	 * @return   array    Upload limits array
	 */
	private function get_php_upload_limits() {
		// Get PHP upload limits
		$max_upload_size = wp_max_upload_size();
		$post_max_size = $this->parse_size( ini_get( 'post_max_size' ) );
		$upload_max_filesize = $this->parse_size( ini_get( 'upload_max_filesize' ) );
		
		// The actual limit is the smallest of these values
		$max_file_size = min( $max_upload_size, $post_max_size, $upload_max_filesize );
		
		return array(
			'max_file_size' => $max_file_size,
			'max_file_size_mb' => round( $max_file_size / 1024 / 1024, 1 ),
			'post_max_size' => $post_max_size,
			'upload_max_filesize' => $upload_max_filesize,
			'wp_max_upload_size' => $max_upload_size,
		);
	}

	/**
	 * Parse size string to bytes
	 *
	 * @since    1.0.0
	 * @param    string    $size    Size string (e.g., '8M', '64M')
	 * @return   int                Size in bytes
	 */
	private function parse_size( $size ) {
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size );
		$size = preg_replace( '/[^0-9\.]/', '', $size );
		
		if ( $unit ) {
			return round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			return round( $size );
		}
	}

	/**
	 * Register shortcodes
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'ticket_form', array( $this, 'display_ticket_form' ) );
		add_shortcode( 'ticket_portal', array( $this, 'display_customer_portal' ) );
	}

	/**
	 * Display ticket form shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            HTML output
	 */
	public function display_ticket_form( $atts ) {
		$atts = shortcode_atts( array(
			'entity' => '',
			'require_login' => 'no',
			'success_message' => '',
			'categories' => '',
			'require_kb_search' => 'yes',
		), $atts, 'ticket_form' );

		// Check if login is required
		if ( $atts['require_login'] === 'yes' && ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="mets-ticket-form mets-login-required">
				<p><?php _e( 'You must be logged in to submit a ticket.', METS_TEXT_DOMAIN ); ?></p>
				<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="mets-login-link"><?php _e( 'Log in', METS_TEXT_DOMAIN ); ?></a></p>
			</div>
			<?php
			return ob_get_clean();
		}

		// Get entities for dropdown
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		
		// If specific entity is set, validate it exists
		$selected_entity = null;
		if ( ! empty( $atts['entity'] ) ) {
			$entity = $entity_model->get_by_slug( $atts['entity'] );
			if ( $entity && $entity->status === 'active' ) {
				$selected_entity = $entity;
			}
		}
		
		// Get all active entities if no specific entity is set
		$entities = array();
		if ( ! $selected_entity ) {
			$entities = $entity_model->get_all( array( 'status' => 'active' ) );
		}

		// Get categories
		$categories = get_option( 'mets_ticket_categories', array() );
		if ( ! empty( $atts['categories'] ) ) {
			$allowed_categories = array_map( 'trim', explode( ',', $atts['categories'] ) );
			$categories = array_intersect_key( $categories, array_flip( $allowed_categories ) );
		}

		// Get current user info if logged in
		$current_user = wp_get_current_user();

		ob_start();
		?>
		<div class="mets-ticket-form-wrapper">
			<?php if ( $atts['require_kb_search'] === 'yes' ) : ?>
			<!-- Mandatory KB Search Section -->
			<div id="mets-kb-search-gate" class="mets-kb-search-gate">
				<div class="mets-kb-gate-header">
					<h3><?php _e( 'Search Our Knowledge Base First', METS_TEXT_DOMAIN ); ?></h3>
					<p><?php _e( 'Before creating a ticket, please search our knowledge base. You might find the answer to your question immediately!', METS_TEXT_DOMAIN ); ?></p>
				</div>
				
				<div class="mets-kb-gate-search">
					<div class="mets-form-group">
						<label for="kb-gate-search"><?php _e( 'Describe your issue or question:', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
						<input type="text" id="kb-gate-search" class="large-text" placeholder="<?php esc_attr_e( 'e.g., How to reset my password, Login issues, Account settings...', METS_TEXT_DOMAIN ); ?>" required>
					</div>
					
					<div class="mets-kb-gate-actions">
						<button type="button" id="kb-gate-search-btn" class="button button-primary button-large">
							<?php _e( 'Search Knowledge Base', METS_TEXT_DOMAIN ); ?>
						</button>
					</div>
				</div>
				
				<div id="kb-gate-results" class="mets-kb-gate-results" style="display: none;">
					<h4><?php _e( 'Here are some articles that might help:', METS_TEXT_DOMAIN ); ?></h4>
					<div id="kb-gate-results-list"></div>
					
					<div class="mets-kb-gate-footer">
						<div class="mets-kb-gate-question">
							<p><strong><?php _e( 'Did you find what you were looking for?', METS_TEXT_DOMAIN ); ?></strong></p>
							<div class="mets-kb-gate-buttons">
								<button type="button" id="kb-gate-found-answer" class="button button-secondary">
									<?php _e( 'Yes, I found my answer', METS_TEXT_DOMAIN ); ?>
								</button>
								<button type="button" id="kb-gate-need-help" class="button button-primary">
									<?php _e( 'No, I still need help', METS_TEXT_DOMAIN ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
				
				<div id="kb-gate-success" class="mets-kb-gate-success" style="display: none;">
					<div class="mets-success-message">
						<h4><?php _e( 'Great! We\'re glad we could help.', METS_TEXT_DOMAIN ); ?></h4>
						<p><?php _e( 'If you need help with something else, feel free to search again or create a new ticket.', METS_TEXT_DOMAIN ); ?></p>
						<button type="button" id="kb-gate-search-again" class="button button-secondary">
							<?php _e( 'Search Again', METS_TEXT_DOMAIN ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Ticket Form (Initially Hidden for KB search, Shown for no KB search) -->
			<form id="mets-ticket-form" class="mets-ticket-form" method="post" enctype="multipart/form-data" <?php echo ( $atts['require_kb_search'] === 'yes' ) ? 'style="display: none;"' : ''; ?>>
				<?php wp_nonce_field( 'mets_submit_ticket', 'mets_ticket_nonce' ); ?>
				
				<?php if ( $atts['require_kb_search'] === 'yes' ) : ?>
				<div class="mets-kb-search-completed">
					<div class="mets-info-message">
						<p><?php _e( 'Thank you for searching our knowledge base first. Please provide the details for your support request below.', METS_TEXT_DOMAIN ); ?></p>
					</div>
				</div>
				<?php endif; ?>
				
				<?php if ( $selected_entity ) : ?>
					<input type="hidden" name="entity_id" value="<?php echo esc_attr( $selected_entity->id ); ?>">
				<?php else : ?>
					<div class="mets-form-group">
						<label for="entity_search"><?php _e( 'Select Department', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
						<div class="mets-entity-search-wrapper">
							<input type="text" id="entity_search" class="mets-entity-search" placeholder="<?php esc_attr_e( 'Start typing to search departments...', METS_TEXT_DOMAIN ); ?>" autocomplete="off">
							<input type="hidden" id="entity_id" name="entity_id" required>
							<div class="mets-entity-results" style="display: none;">
								<div class="mets-entity-results-content">
									<!-- Search results will be populated here -->
								</div>
							</div>
							
							<!-- Fallback select for users with JavaScript disabled -->
							<noscript>
								<select name="entity_id" required>
									<option value=""><?php _e( 'Choose a department...', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $entities as $entity ) : ?>
										<?php $prefix = ! empty( $entity->parent_id ) ? '— ' : ''; ?>
										<option value="<?php echo esc_attr( $entity->id ); ?>">
											<?php echo $prefix . esc_html( $entity->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</noscript>
						</div>
					</div>
				<?php endif; ?>

				<div class="mets-form-group">
					<label for="ticket_subject"><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
					<input type="text" id="ticket_subject" name="subject" required maxlength="255">
				</div>

				<!-- KB Article Suggestions Section -->
				<div class="mets-kb-suggestions" id="mets-kb-suggestions" style="display: none;">
					<div class="mets-kb-suggestions-header">
						<h4><?php _e( 'Related Articles', METS_TEXT_DOMAIN ); ?></h4>
						<p><?php _e( 'These articles might help answer your question:', METS_TEXT_DOMAIN ); ?></p>
					</div>
					<div class="mets-kb-suggestions-list" id="mets-kb-suggestions-list">
						<!-- Article suggestions will be populated here -->
					</div>
					<div class="mets-kb-suggestions-footer">
						<p><small><?php _e( 'Still need help? Continue creating your ticket below.', METS_TEXT_DOMAIN ); ?></small></p>
					</div>
				</div>

				<?php if ( ! empty( $categories ) ) : ?>
					<div class="mets-form-group">
						<label for="ticket_category"><?php _e( 'Category', METS_TEXT_DOMAIN ); ?></label>
						<select id="ticket_category" name="category">
							<option value=""><?php _e( 'Select Category', METS_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $categories as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="mets-form-group">
					<label for="ticket_description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
					<textarea id="ticket_description" name="description" rows="12" required></textarea>
				</div>

				<div class="mets-form-row">
					<div class="mets-form-group">
						<label for="customer_name"><?php _e( 'Your Name', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
						<input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr( $current_user->display_name ); ?>" required>
					</div>

					<div class="mets-form-group">
						<label for="customer_email"><?php _e( 'Your Email', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
						<input type="email" id="customer_email" name="customer_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
					</div>
				</div>

				<div class="mets-form-group">
					<label for="customer_phone"><?php _e( 'Phone Number', METS_TEXT_DOMAIN ); ?></label>
					<input type="tel" id="customer_phone" name="customer_phone">
				</div>

				<div class="mets-form-group">
					<label for="ticket_attachments"><?php _e( 'Attachments', METS_TEXT_DOMAIN ); ?></label>
					<input type="file" id="ticket_attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
					<div class="mets-file-upload-info">
						<small>
							<?php 
							$upload_limits = $this->get_php_upload_limits();
							printf( 
								__( 'Maximum %d files, %s each. Allowed types: %s', METS_TEXT_DOMAIN ),
								10, // max files
								$upload_limits['max_file_size_mb'] . 'MB', // actual PHP limit
								'JPG, PNG, GIF, PDF, DOC, DOCX, TXT, ZIP'
							); 
							?>
						</small>
					</div>
					<div class="mets-file-preview" style="display: none;">
						<div class="mets-file-list"></div>
					</div>
				</div>

				<div class="mets-form-actions">
					<button type="submit" class="mets-submit-button"><?php _e( 'Submit Ticket', METS_TEXT_DOMAIN ); ?></button>
					<span class="mets-loading" style="display: none;"><?php _e( 'Submitting...', METS_TEXT_DOMAIN ); ?></span>
				</div>

				<div class="mets-form-messages">
					<div class="mets-success-message" style="display: none;"></div>
					<div class="mets-error-message" style="display: none;"></div>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display customer portal shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            HTML output
	 */
	public function display_customer_portal( $atts ) {
		$atts = shortcode_atts( array(
			'show_closed' => 'no',
			'per_page' => '10',
			'allow_new_ticket' => 'yes',
		), $atts, 'ticket_portal' );

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div class="mets-customer-portal mets-login-required">
				<h3><?php _e( 'Customer Portal', METS_TEXT_DOMAIN ); ?></h3>
				<p><?php _e( 'You must be logged in to view your tickets.', METS_TEXT_DOMAIN ); ?></p>
				<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="mets-login-link"><?php _e( 'Log in', METS_TEXT_DOMAIN ); ?></a></p>
			</div>
			<?php
			return ob_get_clean();
		}

		$current_user = wp_get_current_user();
		$view = isset( $_GET['mets_view'] ) ? sanitize_text_field( $_GET['mets_view'] ) : 'list';
		$ticket_id = isset( $_GET['mets_ticket'] ) ? intval( $_GET['mets_ticket'] ) : 0;

		ob_start();
		?>
		<div class="mets-customer-portal">
			<!-- Portal Navigation -->
			<div class="mets-portal-nav">
				<a href="<?php echo esc_url( remove_query_arg( array( 'mets_view', 'mets_ticket' ) ) ); ?>"
				   class="mets-nav-link <?php echo $view === 'list' ? 'active' : ''; ?>">
					<?php _e( 'My Tickets', METS_TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'mets_view', 'profile' ) ); ?>"
				   class="mets-nav-link <?php echo $view === 'profile' ? 'active' : ''; ?>">
					<?php _e( 'Profile & Settings', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>

			<?php if ( $view === 'ticket' && $ticket_id ) : ?>
				<?php echo $this->display_customer_ticket_detail( $ticket_id ); ?>
			<?php elseif ( $view === 'profile' ) : ?>
				<?php echo $this->display_customer_profile( $current_user ); ?>
			<?php else : ?>
				<?php echo $this->display_customer_ticket_list( $atts, $current_user ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display customer ticket list
	 *
	 * @since    1.0.0
	 * @param    array     $atts         Shortcode attributes
	 * @param    WP_User   $current_user Current user object
	 * @return   string                  HTML output
	 */
	private function display_customer_ticket_list( $atts, $current_user ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		// Get user's tickets
		$per_page = intval( $atts['per_page'] );
		$page = isset( $_GET['mets_page'] ) ? max( 1, intval( $_GET['mets_page'] ) ) : 1;
		$offset = ( $page - 1 ) * $per_page;

		// Check if user wants to show closed tickets (URL parameter overrides shortcode attribute)
		$show_closed = isset( $_GET['mets_show_closed'] ) ? true : ( $atts['show_closed'] === 'yes' );

		// Build query args
		$args = array(
			'customer_email' => $current_user->user_email,
			'limit' => $per_page,
			'offset' => $offset,
			'order_by' => 'created_at',
			'order' => 'DESC',
		);

		// Advanced filtering
		// Keyword search (searches subject and description)
		if ( ! empty( $_GET['mets_search'] ) ) {
			$args['search'] = sanitize_text_field( $_GET['mets_search'] );
		}

		// Status filter
		if ( ! empty( $_GET['mets_status'] ) && $_GET['mets_status'] !== 'all' ) {
			$args['status'] = sanitize_text_field( $_GET['mets_status'] );
		} elseif ( ! $show_closed ) {
			// Include closed tickets if requested
			$args['exclude_statuses'] = array( 'closed' );
		}

		// Priority filter
		if ( ! empty( $_GET['mets_priority'] ) && $_GET['mets_priority'] !== 'all' ) {
			$args['priority'] = sanitize_text_field( $_GET['mets_priority'] );
		}

		// Category filter
		if ( ! empty( $_GET['mets_category'] ) && $_GET['mets_category'] !== 'all' ) {
			$args['category'] = sanitize_text_field( $_GET['mets_category'] );
		}

		// Date range filter
		if ( ! empty( $_GET['mets_date_from'] ) ) {
			$args['date_from'] = sanitize_text_field( $_GET['mets_date_from'] );
		}
		if ( ! empty( $_GET['mets_date_to'] ) ) {
			$args['date_to'] = sanitize_text_field( $_GET['mets_date_to'] );
		}
		
		$tickets = $ticket_model->get_all( $args );

		// Build count args (same as query args without limit/offset)
		$count_args = array(
			'customer_email' => $current_user->user_email,
		);

		// Apply same filters to count
		if ( ! empty( $args['search'] ) ) {
			$count_args['search'] = $args['search'];
		}
		if ( ! empty( $args['status'] ) ) {
			$count_args['status'] = $args['status'];
		} elseif ( ! $show_closed ) {
			$count_args['exclude_statuses'] = array( 'closed' );
		}
		if ( ! empty( $args['priority'] ) ) {
			$count_args['priority'] = $args['priority'];
		}
		if ( ! empty( $args['category'] ) ) {
			$count_args['category'] = $args['category'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$count_args['date_from'] = $args['date_from'];
		}
		if ( ! empty( $args['date_to'] ) ) {
			$count_args['date_to'] = $args['date_to'];
		}

		$total_tickets = $ticket_model->get_count( $count_args );

		// Get statuses, priorities, and categories for display
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		$categories = get_option( 'mets_ticket_categories', array() );

		// Get current filter values
		$current_search = isset( $_GET['mets_search'] ) ? sanitize_text_field( $_GET['mets_search'] ) : '';
		$current_status = isset( $_GET['mets_status'] ) ? sanitize_text_field( $_GET['mets_status'] ) : '';
		$current_priority = isset( $_GET['mets_priority'] ) ? sanitize_text_field( $_GET['mets_priority'] ) : '';
		$current_category = isset( $_GET['mets_category'] ) ? sanitize_text_field( $_GET['mets_category'] ) : '';
		$current_date_from = isset( $_GET['mets_date_from'] ) ? sanitize_text_field( $_GET['mets_date_from'] ) : '';
		$current_date_to = isset( $_GET['mets_date_to'] ) ? sanitize_text_field( $_GET['mets_date_to'] ) : '';

		// Get general settings for customizable text
		$general_settings = get_option( 'mets_general_settings', array() );
		$portal_header_text = isset( $general_settings['portal_header_text'] ) ? $general_settings['portal_header_text'] : __( 'View your support tickets and their current status. Need help?', METS_TEXT_DOMAIN );
		$new_ticket_link_text = isset( $general_settings['new_ticket_link_text'] ) ? $general_settings['new_ticket_link_text'] : __( 'Submit a new ticket', METS_TEXT_DOMAIN );
		$new_ticket_link_url = isset( $general_settings['new_ticket_link_url'] ) ? $general_settings['new_ticket_link_url'] : '#';
		
		ob_start();
		?>
		<div class="mets-portal-header">
			<h3><?php _e( 'My Support Tickets', METS_TEXT_DOMAIN ); ?></h3>
			<?php if ( $atts['allow_new_ticket'] === 'yes' ) : ?>
				<p><?php echo esc_html( $portal_header_text ); ?>
				<?php if ( ! empty( $new_ticket_link_url ) && $new_ticket_link_url !== '#' ) : ?>
					<a href="<?php echo esc_url( $new_ticket_link_url ); ?>" class="mets-new-ticket-link"><?php echo esc_html( $new_ticket_link_text ); ?></a></p>
				<?php else : ?>
					<a href="#" class="mets-new-ticket-link"><?php echo esc_html( $new_ticket_link_text ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<!-- Dashboard Summary Widget -->
		<?php echo $this->display_dashboard_summary( $current_user, $ticket_model ); ?>

		<?php if ( empty( $tickets ) ) : ?>
			<div class="mets-no-tickets">
				<p><?php _e( 'You don\'t have any support tickets yet.', METS_TEXT_DOMAIN ); ?></p>
				<?php if ( $atts['allow_new_ticket'] === 'yes' ) : ?>
					<p>
						<?php if ( ! empty( $new_ticket_link_url ) && $new_ticket_link_url !== '#' ) : ?>
							<a href="<?php echo esc_url( $new_ticket_link_url ); ?>" class="mets-new-ticket-link button"><?php _e( 'Submit your first ticket', METS_TEXT_DOMAIN ); ?></a>
						<?php else : ?>
							<a href="#" class="mets-new-ticket-link button"><?php _e( 'Submit your first ticket', METS_TEXT_DOMAIN ); ?></a>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<!-- Advanced Filter Options -->
			<div class="mets-portal-filters-advanced">
				<form method="get" class="mets-filters-form" id="mets-filters-form">
					<!-- Preserve other query params -->
					<input type="hidden" name="mets_view" value="<?php echo esc_attr( isset( $_GET['mets_view'] ) ? $_GET['mets_view'] : '' ); ?>">
					<?php if ( $show_closed ) : ?>
						<input type="hidden" name="mets_show_closed" value="1">
					<?php endif; ?>

					<!-- Quick Filter Presets -->
					<div class="mets-filter-presets">
						<button type="button" class="mets-preset-filter" data-preset="all">
							<?php _e( 'All Tickets', METS_TEXT_DOMAIN ); ?>
						</button>
						<button type="button" class="mets-preset-filter" data-preset="open">
							<?php _e( 'Open', METS_TEXT_DOMAIN ); ?>
						</button>
						<button type="button" class="mets-preset-filter" data-preset="in_progress">
							<?php _e( 'In Progress', METS_TEXT_DOMAIN ); ?>
						</button>
						<button type="button" class="mets-preset-filter" data-preset="urgent">
							<?php _e( 'Urgent', METS_TEXT_DOMAIN ); ?>
						</button>
						<button type="button" class="mets-preset-filter" data-preset="last_7_days">
							<?php _e( 'Last 7 Days', METS_TEXT_DOMAIN ); ?>
						</button>
					</div>

					<!-- Filter Fields -->
					<div class="mets-filter-fields">
						<!-- Keyword Search -->
						<div class="mets-filter-group">
							<label for="mets-search-input"><?php _e( 'Search:', METS_TEXT_DOMAIN ); ?></label>
							<input type="text"
								   id="mets-search-input"
								   name="mets_search"
								   value="<?php echo esc_attr( $current_search ); ?>"
								   placeholder="<?php esc_attr_e( 'Search by subject or content...', METS_TEXT_DOMAIN ); ?>">
						</div>

						<!-- Status Filter -->
						<div class="mets-filter-group">
							<label for="mets-status-filter"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></label>
							<select id="mets-status-filter" name="mets_status">
								<option value="all" <?php selected( $current_status, 'all' ); ?>><?php _e( 'All Statuses', METS_TEXT_DOMAIN ); ?></option>
								<?php foreach ( $statuses as $status_key => $status_data ) : ?>
									<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_status, $status_key ); ?>>
										<?php echo esc_html( $status_data['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Priority Filter -->
						<div class="mets-filter-group">
							<label for="mets-priority-filter"><?php _e( 'Priority:', METS_TEXT_DOMAIN ); ?></label>
							<select id="mets-priority-filter" name="mets_priority">
								<option value="all" <?php selected( $current_priority, 'all' ); ?>><?php _e( 'All Priorities', METS_TEXT_DOMAIN ); ?></option>
								<?php foreach ( $priorities as $priority_key => $priority_data ) : ?>
									<option value="<?php echo esc_attr( $priority_key ); ?>" <?php selected( $current_priority, $priority_key ); ?>>
										<?php echo esc_html( $priority_data['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Category Filter -->
						<?php if ( ! empty( $categories ) ) : ?>
							<div class="mets-filter-group">
								<label for="mets-category-filter"><?php _e( 'Category:', METS_TEXT_DOMAIN ); ?></label>
								<select id="mets-category-filter" name="mets_category">
									<option value="all" <?php selected( $current_category, 'all' ); ?>><?php _e( 'All Categories', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $categories as $category_key => $category_data ) : ?>
										<option value="<?php echo esc_attr( $category_key ); ?>" <?php selected( $current_category, $category_key ); ?>>
											<?php echo esc_html( $category_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<!-- Date Range Filter -->
						<div class="mets-filter-group">
							<label for="mets-date-from"><?php _e( 'Date From:', METS_TEXT_DOMAIN ); ?></label>
							<input type="date"
								   id="mets-date-from"
								   name="mets_date_from"
								   value="<?php echo esc_attr( $current_date_from ); ?>">
						</div>

						<div class="mets-filter-group">
							<label for="mets-date-to"><?php _e( 'Date To:', METS_TEXT_DOMAIN ); ?></label>
							<input type="date"
								   id="mets-date-to"
								   name="mets_date_to"
								   value="<?php echo esc_attr( $current_date_to ); ?>">
						</div>

						<!-- Filter Actions -->
						<div class="mets-filter-actions">
							<button type="submit" class="button button-primary">
								<?php _e( 'Apply Filters', METS_TEXT_DOMAIN ); ?>
							</button>
							<a href="<?php echo esc_url( strtok( $_SERVER['REQUEST_URI'], '?' ) ); ?>" class="button button-secondary">
								<?php _e( 'Clear All', METS_TEXT_DOMAIN ); ?>
							</a>
							<?php if ( ! $show_closed ) : ?>
								<a href="<?php echo esc_url( add_query_arg( 'mets_show_closed', '1' ) ); ?>" class="button button-secondary">
									<?php _e( 'Show Closed', METS_TEXT_DOMAIN ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( remove_query_arg( 'mets_show_closed' ) ); ?>" class="button button-secondary">
									<?php _e( 'Hide Closed', METS_TEXT_DOMAIN ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</form>

				<?php
				// Display active filters count
				$active_filters = 0;
				if ( ! empty( $current_search ) ) $active_filters++;
				if ( ! empty( $current_status ) && $current_status !== 'all' ) $active_filters++;
				if ( ! empty( $current_priority ) && $current_priority !== 'all' ) $active_filters++;
				if ( ! empty( $current_category ) && $current_category !== 'all' ) $active_filters++;
				if ( ! empty( $current_date_from ) || ! empty( $current_date_to ) ) $active_filters++;

				if ( $active_filters > 0 ) :
				?>
					<div class="mets-active-filters-notice">
						<?php printf( _n( '%d filter active', '%d filters active', $active_filters, METS_TEXT_DOMAIN ), $active_filters ); ?> |
						<?php printf( _n( '%d result found', '%d results found', $total_tickets, METS_TEXT_DOMAIN ), $total_tickets ); ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Tickets Table -->
			<table class="mets-tickets-table">
				<thead>
					<tr>
						<th><?php _e( 'Ticket #', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Status', METS_TEXT_DOMAIN ); ?></th>
						<th class="priority-column"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Last Updated', METS_TEXT_DOMAIN ); ?></th>
						<th><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tickets as $ticket ) : ?>
						<tr data-status="<?php echo esc_attr( $ticket->status ); ?>">
							<td>
								<strong><?php echo esc_html( $ticket->ticket_number ); ?></strong>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'mets_view' => 'ticket', 'mets_ticket' => $ticket->id ) ) ); ?>" class="ticket-subject">
									<?php echo esc_html( $ticket->subject ); ?>
								</a>
								<div class="ticket-entity">
									<small><?php echo esc_html( $ticket->entity_name ); ?></small>
								</div>
							</td>
							<td>
								<?php 
								$status_label = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['label'] : ucfirst( $ticket->status );
								$status_color = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['color'] : '#666';
								?>
								<span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>" 
									  style="background-color: <?php echo esc_attr( $status_color ); ?>15; color: <?php echo esc_attr( $status_color ); ?>;">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td class="priority-column">
								<?php 
								$priority_label = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['label'] : ucfirst( $ticket->priority );
								$priority_color = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['color'] : '#666';
								?>
								<span class="mets-priority-badge mets-priority-<?php echo esc_attr( $ticket->priority ); ?>"
									  style="background-color: <?php echo esc_attr( $priority_color ); ?>15; color: <?php echo esc_attr( $priority_color ); ?>;">
									<?php echo esc_html( $priority_label ); ?>
								</span>
							</td>
							<td>
								<?php echo date_i18n( get_option( 'date_format' ), strtotime( $ticket->updated_at ) ); ?>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'mets_view' => 'ticket', 'mets_ticket' => $ticket->id ) ) ); ?>" 
								   class="button button-small"><?php _e( 'View', METS_TEXT_DOMAIN ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_tickets > $per_page ) : ?>
				<div class="mets-pagination">
					<?php
					$total_pages = ceil( $total_tickets / $per_page );
					$current_page = $page;
					
					// Previous page
					if ( $current_page > 1 ) {
						$prev_url = add_query_arg( 'mets_page', $current_page - 1 );
						echo '<a href="' . esc_url( $prev_url ) . '" class="mets-page-link">&laquo; ' . __( 'Previous', METS_TEXT_DOMAIN ) . '</a>';
					}
					
					// Page numbers
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						if ( $i == $current_page ) {
							echo '<span class="mets-page-current">' . $i . '</span>';
						} else {
							$page_url = add_query_arg( 'mets_page', $i );
							echo '<a href="' . esc_url( $page_url ) . '" class="mets-page-link">' . $i . '</a>';
						}
					}
					
					// Next page
					if ( $current_page < $total_pages ) {
						$next_url = add_query_arg( 'mets_page', $current_page + 1 );
						echo '<a href="' . esc_url( $next_url ) . '" class="mets-page-link">' . __( 'Next', METS_TEXT_DOMAIN ) . ' &raquo;</a>';
					}
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<script>
		jQuery(document).ready(function($) {
			// Preset filter buttons
			$('.mets-preset-filter').on('click', function(e) {
				e.preventDefault();
				var preset = $(this).data('preset');
				var form = $('#mets-filters-form');

				// Reset all filters
				form.find('#mets-search-input').val('');
				form.find('#mets-status-filter').val('all');
				form.find('#mets-priority-filter').val('all');
				form.find('#mets-category-filter').val('all');
				form.find('#mets-date-from').val('');
				form.find('#mets-date-to').val('');

				// Apply preset
				switch(preset) {
					case 'all':
						// No filters
						break;
					case 'open':
						form.find('#mets-status-filter').val('open');
						break;
					case 'in_progress':
						form.find('#mets-status-filter').val('in_progress');
						break;
					case 'urgent':
						form.find('#mets-priority-filter').val('urgent');
						break;
					case 'last_7_days':
						var today = new Date();
						var sevenDaysAgo = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
						var dateFrom = sevenDaysAgo.toISOString().split('T')[0];
						var dateTo = today.toISOString().split('T')[0];
						form.find('#mets-date-from').val(dateFrom);
						form.find('#mets-date-to').val(dateTo);
						break;
				}

				// Submit form
				form.submit();
			});

			// Highlight active preset based on current filters
			var currentStatus = $('#mets-status-filter').val();
			var currentPriority = $('#mets-priority-filter').val();

			$('.mets-preset-filter').each(function() {
				var preset = $(this).data('preset');
				var isActive = false;

				switch(preset) {
					case 'all':
						if (!currentStatus && !currentPriority) isActive = true;
						break;
					case 'open':
						if (currentStatus === 'open') isActive = true;
						break;
					case 'in_progress':
						if (currentStatus === 'in_progress') isActive = true;
						break;
					case 'urgent':
						if (currentPriority === 'urgent') isActive = true;
						break;
				}

				if (isActive) {
					$(this).addClass('active');
				}
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display dashboard summary widget
	 *
	 * Shows customer quick stats: total tickets, open, resolved, avg response time
	 *
	 * @since    1.0.1
	 * @param    WP_User              $current_user  Current user object
	 * @param    METS_Ticket_Model    $ticket_model  Ticket model instance
	 * @return   string                             HTML output
	 */
	private function display_dashboard_summary( $current_user, $ticket_model ) {
		global $wpdb;

		// Get customer statistics
		$customer_email = $current_user->user_email;

		// Total tickets
		$total_tickets = $ticket_model->get_count( array(
			'customer_email' => $customer_email,
		) );

		// Open tickets (new + in_progress + on_hold)
		$open_tickets = $ticket_model->get_count( array(
			'customer_email' => $customer_email,
			'statuses' => array( 'new', 'open', 'in_progress', 'on_hold' ),
		) );

		// Resolved tickets
		$resolved_tickets = $ticket_model->get_count( array(
			'customer_email' => $customer_email,
			'statuses' => array( 'resolved', 'closed' ),
		) );

		// Average response time (in hours)
		$avg_response = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours
			FROM {$wpdb->prefix}mets_tickets
			WHERE customer_email = %s
			AND first_response_at IS NOT NULL",
			$customer_email
		) );

		$avg_response_hours = $avg_response ? round( floatval( $avg_response ), 1 ) : null;

		// Recent activity - last ticket created
		$last_ticket = $wpdb->get_row( $wpdb->prepare(
			"SELECT created_at FROM {$wpdb->prefix}mets_tickets
			WHERE customer_email = %s
			ORDER BY created_at DESC
			LIMIT 1",
			$customer_email
		) );

		ob_start();
		?>
		<div class="mets-dashboard-summary">
			<div class="mets-dashboard-cards">
				<div class="mets-dashboard-card mets-card-total">
					<div class="mets-card-icon">📊</div>
					<div class="mets-card-content">
						<div class="mets-card-value"><?php echo esc_html( $total_tickets ); ?></div>
						<div class="mets-card-label"><?php _e( 'Total Tickets', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>

				<div class="mets-dashboard-card mets-card-open">
					<div class="mets-card-icon">🔓</div>
					<div class="mets-card-content">
						<div class="mets-card-value"><?php echo esc_html( $open_tickets ); ?></div>
						<div class="mets-card-label"><?php _e( 'Open Tickets', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>

				<div class="mets-dashboard-card mets-card-resolved">
					<div class="mets-card-icon">✅</div>
					<div class="mets-card-content">
						<div class="mets-card-value"><?php echo esc_html( $resolved_tickets ); ?></div>
						<div class="mets-card-label"><?php _e( 'Resolved', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>

				<div class="mets-dashboard-card mets-card-response">
					<div class="mets-card-icon">⏱️</div>
					<div class="mets-card-content">
						<div class="mets-card-value">
							<?php
							if ( $avg_response_hours !== null ) {
								if ( $avg_response_hours < 1 ) {
									echo '&lt; 1h';
								} else if ( $avg_response_hours >= 24 ) {
									printf( _n( '%d day', '%d days', round( $avg_response_hours / 24 ), METS_TEXT_DOMAIN ), round( $avg_response_hours / 24 ) );
								} else {
									printf( _n( '%d hour', '%d hours', round( $avg_response_hours ), METS_TEXT_DOMAIN ), round( $avg_response_hours ) );
								}
							} else {
								echo '-';
							}
							?>
						</div>
						<div class="mets-card-label"><?php _e( 'Avg Response Time', METS_TEXT_DOMAIN ); ?></div>
					</div>
				</div>
			</div>

			<?php if ( $last_ticket ) : ?>
				<div class="mets-dashboard-activity">
					<p class="mets-last-activity">
						<span class="mets-activity-icon">🕐</span>
						<?php
						printf(
							__( 'Last ticket: %s', METS_TEXT_DOMAIN ),
							'<strong>' . human_time_diff( strtotime( $last_ticket->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', METS_TEXT_DOMAIN ) . '</strong>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display customer profile and settings page
	 *
	 * @since    1.0.1
	 * @param    WP_User    $current_user    Current user object
	 * @return   string                      HTML output
	 */
	private function display_customer_profile( $current_user ) {
		// Get user's notification preferences
		$preferences = get_user_meta( $current_user->ID, 'mets_notification_preferences', true );
		if ( ! is_array( $preferences ) ) {
			$preferences = array(
				'email_on_reply'          => true,
				'email_on_status_change'  => true,
				'email_on_resolution'     => true,
				'email_digest'            => 'none', // none, daily, weekly
			);
		}

		// Get recent activity
		global $wpdb;
		$recent_activity = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.id, t.ticket_number, t.subject, t.status, t.updated_at,
			        'ticket' as activity_type
			FROM {$wpdb->prefix}mets_tickets t
			WHERE t.customer_email = %s
			UNION ALL
			SELECT r.ticket_id as id, t.ticket_number, t.subject, 'reply' as status, r.created_at as updated_at,
			        'reply' as activity_type
			FROM {$wpdb->prefix}mets_ticket_replies r
			INNER JOIN {$wpdb->prefix}mets_tickets t ON r.ticket_id = t.id
			WHERE t.customer_email = %s AND r.user_type = 'agent'
			ORDER BY updated_at DESC
			LIMIT 10",
			$current_user->user_email,
			$current_user->user_email
		) );

		ob_start();
		?>
		<div class="mets-customer-profile">
			<h3><?php _e( 'Profile & Settings', METS_TEXT_DOMAIN ); ?></h3>

			<!-- Contact Information Section -->
			<div class="mets-profile-section">
				<h4><?php _e( 'Contact Information', METS_TEXT_DOMAIN ); ?></h4>
				<form id="mets-update-profile-form" class="mets-profile-form">
					<?php wp_nonce_field( 'mets_update_profile', 'profile_nonce' ); ?>

					<div class="mets-form-group">
						<label for="profile_name"><?php _e( 'Name', METS_TEXT_DOMAIN ); ?></label>
						<input type="text" id="profile_name" name="name" value="<?php echo esc_attr( $current_user->display_name ); ?>" required>
					</div>

					<div class="mets-form-group">
						<label for="profile_email"><?php _e( 'Email', METS_TEXT_DOMAIN ); ?></label>
						<input type="email" id="profile_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" disabled>
						<small><?php _e( 'Contact support to change your email address', METS_TEXT_DOMAIN ); ?></small>
					</div>

					<div class="mets-form-group">
						<label for="profile_phone"><?php _e( 'Phone (Optional)', METS_TEXT_DOMAIN ); ?></label>
						<input type="tel" id="profile_phone" name="phone" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'mets_phone', true ) ); ?>">
					</div>

					<button type="submit" class="mets-button mets-button-primary">
						<?php _e( 'Save Changes', METS_TEXT_DOMAIN ); ?>
					</button>
					<span class="mets-profile-message" style="display:none;"></span>
				</form>
			</div>

			<!-- Notification Preferences Section -->
			<div class="mets-profile-section">
				<h4><?php _e( 'Notification Preferences', METS_TEXT_DOMAIN ); ?></h4>
				<form id="mets-update-preferences-form" class="mets-preferences-form">
					<?php wp_nonce_field( 'mets_update_preferences', 'preferences_nonce' ); ?>

					<div class="mets-checkbox-group">
						<label>
							<input type="checkbox" name="email_on_reply" value="1" <?php checked( ! empty( $preferences['email_on_reply'] ) ); ?>>
							<?php _e( 'Email me when an agent replies to my ticket', METS_TEXT_DOMAIN ); ?>
						</label>
					</div>

					<div class="mets-checkbox-group">
						<label>
							<input type="checkbox" name="email_on_status_change" value="1" <?php checked( ! empty( $preferences['email_on_status_change'] ) ); ?>>
							<?php _e( 'Email me when ticket status changes', METS_TEXT_DOMAIN ); ?>
						</label>
					</div>

					<div class="mets-checkbox-group">
						<label>
							<input type="checkbox" name="email_on_resolution" value="1" <?php checked( ! empty( $preferences['email_on_resolution'] ) ); ?>>
							<?php _e( 'Email me when my ticket is resolved', METS_TEXT_DOMAIN ); ?>
						</label>
					</div>

					<div class="mets-form-group">
						<label for="email_digest"><?php _e( 'Email Digest', METS_TEXT_DOMAIN ); ?></label>
						<select id="email_digest" name="email_digest">
							<option value="none" <?php selected( $preferences['email_digest'], 'none' ); ?>>
								<?php _e( 'None - Send emails immediately', METS_TEXT_DOMAIN ); ?>
							</option>
							<option value="daily" <?php selected( $preferences['email_digest'], 'daily' ); ?>>
								<?php _e( 'Daily Digest', METS_TEXT_DOMAIN ); ?>
							</option>
							<option value="weekly" <?php selected( $preferences['email_digest'], 'weekly' ); ?>>
								<?php _e( 'Weekly Digest', METS_TEXT_DOMAIN ); ?>
							</option>
						</select>
						<small><?php _e( 'Receive a summary of all ticket updates in one email', METS_TEXT_DOMAIN ); ?></small>
					</div>

					<button type="submit" class="mets-button mets-button-primary">
						<?php _e( 'Save Preferences', METS_TEXT_DOMAIN ); ?>
					</button>
					<span class="mets-preferences-message" style="display:none;"></span>
				</form>
			</div>

			<!-- Recent Activity Section -->
			<div class="mets-profile-section">
				<h4><?php _e( 'Recent Activity', METS_TEXT_DOMAIN ); ?></h4>
				<?php if ( ! empty( $recent_activity ) ) : ?>
					<div class="mets-activity-timeline">
						<?php foreach ( $recent_activity as $activity ) : ?>
							<div class="mets-activity-item">
								<div class="mets-activity-icon">
									<?php echo $activity->activity_type === 'reply' ? '💬' : '🎫'; ?>
								</div>
								<div class="mets-activity-content">
									<p class="mets-activity-title">
										<?php if ( $activity->activity_type === 'reply' ) : ?>
											<?php _e( 'New reply on ticket', METS_TEXT_DOMAIN ); ?>
										<?php else : ?>
											<?php _e( 'Ticket updated', METS_TEXT_DOMAIN ); ?>
										<?php endif; ?>
										<a href="<?php echo esc_url( add_query_arg( array( 'mets_view' => 'ticket', 'mets_ticket' => $activity->id ) ) ); ?>">
											#<?php echo esc_html( $activity->ticket_number ); ?>
										</a>
									</p>
									<p class="mets-activity-subject"><?php echo esc_html( $activity->subject ); ?></p>
									<p class="mets-activity-time">
										<?php echo human_time_diff( strtotime( $activity->updated_at ), current_time( 'timestamp' ) ); ?>
										<?php _e( 'ago', METS_TEXT_DOMAIN ); ?>
									</p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php _e( 'No recent activity', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Handle profile update
			$('#mets-update-profile-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $message = $form.find('.mets-profile-message');

				$.ajax({
					url: mets_public_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'mets_update_customer_profile',
						nonce: mets_public_ajax.nonce,
						name: $('#profile_name').val(),
						phone: $('#profile_phone').val()
					},
					success: function(response) {
						if (response.success) {
							$message.text('<?php echo esc_js( __( 'Profile updated successfully!', METS_TEXT_DOMAIN ) ); ?>').css('color', '#4caf50').show();
						} else {
							$message.text(response.data.message || '<?php echo esc_js( __( 'Failed to update profile', METS_TEXT_DOMAIN ) ); ?>').css('color', '#d32f2f').show();
						}
						setTimeout(function() { $message.fadeOut(); }, 3000);
					}
				});
			});

			// Handle preferences update
			$('#mets-update-preferences-form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this);
				var $message = $form.find('.mets-preferences-message');

				$.ajax({
					url: mets_public_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'mets_update_notification_preferences',
						nonce: mets_public_ajax.nonce,
						email_on_reply: $('input[name="email_on_reply"]').is(':checked') ? 1 : 0,
						email_on_status_change: $('input[name="email_on_status_change"]').is(':checked') ? 1 : 0,
						email_on_resolution: $('input[name="email_on_resolution"]').is(':checked') ? 1 : 0,
						email_digest: $('#email_digest').val()
					},
					success: function(response) {
						if (response.success) {
							$message.text('<?php echo esc_js( __( 'Preferences saved successfully!', METS_TEXT_DOMAIN ) ); ?>').css('color', '#4caf50').show();
						} else {
							$message.text(response.data.message || '<?php echo esc_js( __( 'Failed to save preferences', METS_TEXT_DOMAIN ) ); ?>').css('color', '#d32f2f').show();
						}
						setTimeout(function() { $message.fadeOut(); }, 3000);
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display customer ticket detail view
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @return   string               HTML output
	 */
	private function display_customer_ticket_detail( $ticket_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();
		
		// Get ticket and verify customer ownership
		$ticket = $ticket_model->get( $ticket_id );
		$current_user = wp_get_current_user();
		
		if ( ! $ticket || $ticket->customer_email !== $current_user->user_email ) {
			return '<div class="mets-error"><p>' . __( 'Ticket not found or access denied.', METS_TEXT_DOMAIN ) . '</p></div>';
		}
		
		// Get replies (exclude internal notes for customers)
		$replies = $reply_model->get_by_ticket( $ticket_id, array( 'exclude_internal' => true ) );
		
		// Get statuses and priorities for display
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		
		$status_label = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['label'] : ucfirst( $ticket->status );
		$status_color = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['color'] : '#666';
		$priority_label = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['label'] : ucfirst( $ticket->priority );
		$priority_color = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['color'] : '#666';
		
		ob_start();
		?>
		<div class="mets-ticket-detail">
			<!-- Header with back button -->
			<div class="mets-ticket-header">
				<div class="mets-breadcrumb">
					<a href="<?php echo esc_url( remove_query_arg( array( 'mets_view', 'mets_ticket' ) ) ); ?>" class="mets-back-link">
						&larr; <?php _e( 'Back to My Tickets', METS_TEXT_DOMAIN ); ?>
					</a>
				</div>
				<h3><?php echo esc_html( $ticket->ticket_number ); ?> - <?php echo esc_html( $ticket->subject ); ?></h3>
			</div>

			<!-- Ticket Info -->
			<div class="mets-ticket-info">
				<div class="mets-info-grid">
					<div class="mets-info-item">
						<strong><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></strong>
						<span class="mets-status-badge" style="background-color: <?php echo esc_attr( $status_color ); ?>15; color: <?php echo esc_attr( $status_color ); ?>;">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</div>
					<div class="mets-info-item">
						<strong><?php _e( 'Priority:', METS_TEXT_DOMAIN ); ?></strong>
						<span class="mets-priority-badge" style="background-color: <?php echo esc_attr( $priority_color ); ?>15; color: <?php echo esc_attr( $priority_color ); ?>;">
							<?php echo esc_html( $priority_label ); ?>
						</span>
					</div>
					<div class="mets-info-item">
						<strong><?php _e( 'Department:', METS_TEXT_DOMAIN ); ?></strong>
						<?php echo esc_html( $ticket->entity_name ); ?>
					</div>
					<div class="mets-info-item">
						<strong><?php _e( 'Created:', METS_TEXT_DOMAIN ); ?></strong>
						<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?>
					</div>
					<?php if ( ! empty( $ticket->category ) ) : ?>
						<div class="mets-info-item">
							<strong><?php _e( 'Category:', METS_TEXT_DOMAIN ); ?></strong>
							<?php 
							$categories = get_option( 'mets_ticket_categories', array() );
							$category_label = isset( $categories[$ticket->category] ) ? $categories[$ticket->category] : $ticket->category;
							echo esc_html( $category_label );
							?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $ticket->assigned_to_name ) ) : ?>
						<div class="mets-info-item">
							<strong><?php _e( 'Assigned to:', METS_TEXT_DOMAIN ); ?></strong>
							<?php echo esc_html( $ticket->assigned_to_name ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Original Message -->
			<div class="mets-ticket-message">
				<h4><?php _e( 'Original Message', METS_TEXT_DOMAIN ); ?></h4>
				<div class="mets-message-content">
					<?php echo wp_kses_post( $ticket->description ); ?>
				</div>
				<?php echo $this->display_attachments( $ticket->id ); ?>
			</div>

			<!-- Replies -->
			<div class="mets-ticket-replies">
				<h4><?php _e( 'Conversation', METS_TEXT_DOMAIN ); ?></h4>
				
				<?php if ( empty( $replies ) ) : ?>
					<div class="mets-no-replies">
						<p><?php _e( 'No replies yet. We\'ll respond to your ticket as soon as possible.', METS_TEXT_DOMAIN ); ?></p>
					</div>
				<?php else : ?>
					<div class="mets-replies-list">
						<?php foreach ( $replies as $reply ) : ?>
							<div class="mets-reply <?php echo $reply->user_type === 'customer' ? 'mets-reply-customer' : 'mets-reply-agent'; ?>">
								<div class="mets-reply-header">
									<div class="mets-reply-author">
										<strong><?php echo esc_html( $reply->user_name ?: __( 'Support Agent', METS_TEXT_DOMAIN ) ); ?></strong>
										<?php if ( $reply->user_type === 'customer' ) : ?>
											<span class="mets-reply-badge"><?php _e( 'You', METS_TEXT_DOMAIN ); ?></span>
										<?php else : ?>
											<span class="mets-reply-badge"><?php _e( 'Support', METS_TEXT_DOMAIN ); ?></span>
										<?php endif; ?>
									</div>
									<div class="mets-reply-date">
										<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->created_at ) ); ?>
									</div>
								</div>
								<div class="mets-reply-content">
									<?php echo wp_kses_post( $reply->content ); ?>
								</div>
								<?php echo $this->display_attachments( $ticket->id, $reply->id ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Reply Form (only if ticket is not closed) -->
			<?php if ( $ticket->status !== 'closed' ) : ?>
				<div class="mets-reply-form">
					<h4><?php _e( 'Add Reply', METS_TEXT_DOMAIN ); ?></h4>
					<form id="mets-customer-reply-form" method="post">
						<?php wp_nonce_field( 'mets_customer_reply', 'customer_reply_nonce' ); ?>
						<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
						
						<div class="mets-form-group">
							<label for="reply_content"><?php _e( 'Your message:', METS_TEXT_DOMAIN ); ?></label>
							<textarea id="reply_content" name="reply_content" rows="10" required placeholder="<?php esc_attr_e( 'Type your reply here...', METS_TEXT_DOMAIN ); ?>"></textarea>
						</div>
						
						<div class="mets-form-actions">
							<button type="submit" class="mets-submit-button"><?php _e( 'Send Reply', METS_TEXT_DOMAIN ); ?></button>
							<span class="mets-loading" style="display: none;"><?php _e( 'Sending...', METS_TEXT_DOMAIN ); ?></span>
						</div>

						<div class="mets-form-messages">
							<div class="mets-success-message" style="display: none;"></div>
							<div class="mets-error-message" style="display: none;"></div>
						</div>
					</form>
				</div>
			<?php else : ?>
				<?php
				// Check if ticket can be reopened (closed within 7 days)
				$closed_date = strtotime( $ticket->updated_at );
				$days_since_closed = floor( ( time() - $closed_date ) / ( 60 * 60 * 24 ) );
				$can_reopen = ( $days_since_closed <= 7 );
				?>

				<div class="mets-ticket-closed">
					<?php if ( $can_reopen ) : ?>
						<div class="mets-reopen-section">
							<p><?php _e( 'This ticket has been closed.', METS_TEXT_DOMAIN ); ?></p>
							<p><?php _e( 'If you need to continue the conversation, you can reopen this ticket.', METS_TEXT_DOMAIN ); ?></p>
							<form id="mets-reopen-ticket-form" method="post" style="margin-top: 15px;">
								<?php wp_nonce_field( 'mets_reopen_ticket', 'reopen_ticket_nonce' ); ?>
								<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">

								<div class="mets-form-group">
									<label for="reopen_reason"><?php _e( 'Reason for reopening (optional):', METS_TEXT_DOMAIN ); ?></label>
									<textarea id="reopen_reason" name="reopen_reason" rows="4" placeholder="<?php esc_attr_e( 'Let us know why you need to reopen this ticket...', METS_TEXT_DOMAIN ); ?>"></textarea>
								</div>

								<div class="mets-form-actions">
									<button type="submit" class="button button-primary">
										<?php _e( 'Reopen Ticket', METS_TEXT_DOMAIN ); ?>
									</button>
									<span class="mets-loading" style="display: none;"><?php _e( 'Reopening...', METS_TEXT_DOMAIN ); ?></span>
								</div>

								<div class="mets-form-messages">
									<div class="mets-success-message" style="display: none;"></div>
									<div class="mets-error-message" style="display: none;"></div>
								</div>
							</form>
						</div>
					<?php else : ?>
						<p><?php printf( __( 'This ticket was closed %d days ago and can no longer be reopened.', METS_TEXT_DOMAIN ), $days_since_closed ); ?></p>
						<p><?php _e( 'If you need further assistance, please submit a new ticket.', METS_TEXT_DOMAIN ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Handle reopen ticket form submission
			$('#mets-reopen-ticket-form').on('submit', function(e) {
				e.preventDefault();

				var form = $(this);
				var submitButton = form.find('button[type="submit"]');
				var loading = form.find('.mets-loading');
				var successMsg = form.find('.mets-success-message');
				var errorMsg = form.find('.mets-error-message');

				// Hide previous messages
				successMsg.hide();
				errorMsg.hide();

				// Show loading
				submitButton.prop('disabled', true);
				loading.show();

				$.ajax({
					url: metsPublicData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'mets_reopen_ticket',
						nonce: metsPublicData.nonce,
						ticket_id: form.find('input[name="ticket_id"]').val(),
						reopen_reason: form.find('textarea[name="reopen_reason"]').val()
					},
					success: function(response) {
						loading.hide();

						if (response.success) {
							successMsg.html(response.data.message).show();

							// Reload page after 2 seconds if reload flag is set
							if (response.data.reload) {
								setTimeout(function() {
									location.reload();
								}, 2000);
							}
						} else {
							errorMsg.html(response.data.message).show();
							submitButton.prop('disabled', false);
						}
					},
					error: function(xhr, status, error) {
						loading.hide();
						errorMsg.html('<?php _e( 'An error occurred. Please try again.', METS_TEXT_DOMAIN ); ?>').show();
						submitButton.prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX submit ticket
	 *
	 * @since    1.0.0
	 */
	public function ajax_submit_ticket() {
		check_ajax_referer( 'mets_submit_ticket', 'mets_ticket_nonce' );
		
		// Validate required fields
		$required_fields = array( 'entity_id', 'subject', 'description', 'customer_name', 'customer_email' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[$field] ) ) {
				wp_send_json_error( array(
					'message' => sprintf( __( 'Required field missing: %s', METS_TEXT_DOMAIN ), $field )
				) );
			}
		}
		
		// Sanitize data
		$ticket_data = array(
			'entity_id'      => intval( $_POST['entity_id'] ),
			'subject'        => sanitize_text_field( $_POST['subject'] ),
			'description'    => wp_kses_post( $_POST['description'] ),
			'customer_name'  => sanitize_text_field( $_POST['customer_name'] ),
			'customer_email' => sanitize_email( $_POST['customer_email'] ),
			'customer_phone' => sanitize_text_field( $_POST['customer_phone'] ?? '' ),
			'category'       => sanitize_text_field( $_POST['category'] ?? '' ),
			'status'         => 'new',
			'priority'       => 'normal',
			'created_by'     => get_current_user_id() ?: null,
		);
		
		// Validate entity exists and is active
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entity = $entity_model->get( $ticket_data['entity_id'] );
		
		if ( ! $entity || $entity->status !== 'active' ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid entity selected.', METS_TEXT_DOMAIN )
			) );
		}
		
		// Create ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$result = $ticket_model->create( $ticket_data );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message()
			) );
		}
		
		// Get the created ticket
		$ticket = $ticket_model->get( $result );
		
		wp_send_json_success( array(
			'message' => __( 'Your ticket has been submitted successfully.', METS_TEXT_DOMAIN ),
			'ticket_number' => $ticket->ticket_number,
			'ticket_id' => $result,
			'redirect' => '', // Can be used for redirecting to a thank you page
		) );
	}

	/**
	 * AJAX upload file
	 *
	 * @since    1.0.0
	 */
	public function ajax_upload_file() {
		try {
			check_ajax_referer( 'mets_public_nonce', 'nonce' );

			// Validate required fields
			if ( empty( $_POST['ticket_id'] ) ) {
				wp_send_json_error( array(
					'message' => __( 'Ticket ID is required.', METS_TEXT_DOMAIN )
				) );
			}

			$ticket_id = intval( $_POST['ticket_id'] );

			// Check if file was uploaded
			if ( empty( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
				error_log( 'METS File Upload Error - No file or upload error: ' . ( isset( $_FILES['file']['name'] ) ? $_FILES['file']['name'] : 'unknown' ) . ' | Error: ' . ( isset( $_FILES['file']['error'] ) ? $_FILES['file']['error'] : 'unknown' ) );
				wp_send_json_error( array(
					'message' => __( 'No file uploaded or upload error occurred.', METS_TEXT_DOMAIN )
				) );
			}

			// Handle single file upload
			require_once METS_PLUGIN_PATH . 'includes/class-mets-file-handler.php';
			$file_handler = new METS_File_Handler();

			// Convert single file to array format expected by handle_upload
			$file_data = array(
				'name'     => array( $_FILES['file']['name'] ),
				'type'     => array( $_FILES['file']['type'] ),
				'tmp_name' => array( $_FILES['file']['tmp_name'] ),
				'error'    => array( $_FILES['file']['error'] ),
				'size'     => array( $_FILES['file']['size'] ),
			);

			$upload_results = $file_handler->handle_upload( $file_data, $ticket_id );

			if ( ! empty( $upload_results ) && $upload_results[0]['success'] ) {
				wp_send_json_success( array(
					'message' => __( 'File uploaded successfully.', METS_TEXT_DOMAIN ),
					'file_name' => $upload_results[0]['file'],
					'attachment_id' => $upload_results[0]['attachment_id']
				) );
			} else {
				$error_message = ! empty( $upload_results[0]['message'] ) ? $upload_results[0]['message'] : __( 'File upload failed.', METS_TEXT_DOMAIN );
				error_log( 'METS File Upload Error - Upload failed: ' . $_FILES['file']['name'] . ' | Error: ' . $error_message );
				wp_send_json_error( array(
					'message' => $error_message
				) );
			}
		} catch ( Exception $e ) {
			error_log( 'METS File Upload Error - Exception: ' . $e->getMessage() . ' | File: ' . ( isset( $_FILES['file']['name'] ) ? $_FILES['file']['name'] : 'unknown' ) );
			wp_send_json_error( array(
				'message' => __( 'An unexpected error occurred during file upload.', METS_TEXT_DOMAIN )
			) );
		}
	}

	/**
	 * Display attachments for a ticket
	 *
	 * @since    1.0.0
	 * @param    int    $ticket_id    Ticket ID
	 * @param    int    $reply_id     Reply ID (optional)
	 * @return   string               HTML output
	 */
	public function display_attachments( $ticket_id, $reply_id = null ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
		$attachment_model = new METS_Attachment_Model();
		
		$attachments = $attachment_model->get_by_ticket( $ticket_id, $reply_id );
		
		if ( empty( $attachments ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="mets-attachments-section">
			<h4 class="mets-attachments-title"><?php _e( 'Attachments', METS_TEXT_DOMAIN ); ?></h4>
			<?php foreach ( $attachments as $attachment ) : ?>
				<div class="mets-attachment-item">
					<a href="<?php echo esc_url( $attachment->file_url ); ?>" 
					   class="mets-attachment-link" 
					   target="_blank" 
					   download="<?php echo esc_attr( $attachment->file_name ); ?>">
						<div class="mets-file-icon <?php echo esc_attr( $attachment_model->get_file_icon_class( $attachment->file_type ) ); ?>"></div>
						<div class="mets-attachment-info">
							<div class="mets-attachment-name"><?php echo esc_html( $attachment->file_name ); ?></div>
							<div class="mets-attachment-meta">
								<?php 
								printf( 
									__( '%s • Uploaded by %s on %s', METS_TEXT_DOMAIN ),
									$attachment_model->format_file_size( $attachment->file_size ),
									esc_html( $attachment->uploaded_by_name ?: __( 'Unknown', METS_TEXT_DOMAIN ) ),
									date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attachment->created_at ) )
								);
								?>
							</div>
						</div>
					</a>
					<a href="<?php echo esc_url( $attachment->file_url ); ?>" 
					   class="mets-download-button" 
					   download="<?php echo esc_attr( $attachment->file_name ); ?>">
						<?php _e( 'Download', METS_TEXT_DOMAIN ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}


	/**
	 * AJAX customer reply
	 *
	 * @since    1.0.0
	 */
	public function ajax_customer_reply() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to reply to tickets.', METS_TEXT_DOMAIN )
			) );
		}
		
		// Validate required fields
		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$reply_content = wp_kses_post( $_POST['reply_content'] ?? '' );
		
		if ( ! $ticket_id || empty( $reply_content ) ) {
			wp_send_json_error( array(
				'message' => __( 'Missing required fields.', METS_TEXT_DOMAIN )
			) );
		}
		
		// Load models
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();
		
		// Verify ticket exists and customer ownership
		$ticket = $ticket_model->get( $ticket_id );
		$current_user = wp_get_current_user();
		
		if ( ! $ticket || $ticket->customer_email !== $current_user->user_email ) {
			wp_send_json_error( array(
				'message' => __( 'Ticket not found or access denied.', METS_TEXT_DOMAIN )
			) );
		}
		
		// Check if ticket is closed
		if ( $ticket->status === 'closed' ) {
			wp_send_json_error( array(
				'message' => __( 'Cannot reply to a closed ticket.', METS_TEXT_DOMAIN )
			) );
		}
		
		// Create reply
		$reply_data = array(
			'ticket_id'        => $ticket_id,
			'user_id'          => $current_user->ID,
			'user_type'        => 'customer',
			'content'          => $reply_content,
			'is_internal_note' => false,
		);
		
		$result = $reply_model->create( $reply_data );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message()
			) );
		}
		
		// Update ticket's updated_at timestamp
		$ticket_model->update( $ticket_id, array( 'updated_at' => current_time( 'mysql' ) ) );
		
		wp_send_json_success( array(
			'message' => __( 'Your reply has been sent successfully.', METS_TEXT_DOMAIN )
		) );
	}

	/**
	 * AJAX reopen ticket
	 *
	 * Allows customers to reopen closed tickets within 7 days
	 *
	 * @since    1.0.1
	 */
	public function ajax_reopen_ticket() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to reopen tickets.', METS_TEXT_DOMAIN )
			) );
		}

		// Validate ticket ID
		$ticket_id = intval( $_POST['ticket_id'] ?? 0 );
		$reopen_reason = wp_kses_post( $_POST['reopen_reason'] ?? '' );

		if ( ! $ticket_id ) {
			wp_send_json_error( array(
				'message' => __( 'Missing ticket ID.', METS_TEXT_DOMAIN )
			) );
		}

		// Load models
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';

		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();

		// Verify ticket exists and customer ownership
		$ticket = $ticket_model->get( $ticket_id );
		$current_user = wp_get_current_user();

		if ( ! $ticket || $ticket->customer_email !== $current_user->user_email ) {
			wp_send_json_error( array(
				'message' => __( 'Ticket not found or access denied.', METS_TEXT_DOMAIN )
			) );
		}

		// Check if ticket is actually closed
		if ( $ticket->status !== 'closed' ) {
			wp_send_json_error( array(
				'message' => __( 'This ticket is not closed.', METS_TEXT_DOMAIN )
			) );
		}

		// Check if ticket was closed within 7 days
		$closed_date = strtotime( $ticket->updated_at );
		$days_since_closed = floor( ( time() - $closed_date ) / ( 60 * 60 * 24 ) );

		if ( $days_since_closed > 7 ) {
			wp_send_json_error( array(
				'message' => sprintf(
					__( 'This ticket was closed %d days ago and can no longer be reopened. Please submit a new ticket.', METS_TEXT_DOMAIN ),
					$days_since_closed
				)
			) );
		}

		// Reopen the ticket (set to 'open' status)
		$result = $ticket_model->update( $ticket_id, array(
			'status'     => 'open',
			'updated_at' => current_time( 'mysql' ),
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message()
			) );
		}

		// Add system note about reopening
		$reopen_message = sprintf(
			__( 'Ticket reopened by %s.', METS_TEXT_DOMAIN ),
			$current_user->display_name
		);

		if ( ! empty( $reopen_reason ) ) {
			$reopen_message .= "\n\n" . __( 'Reason:', METS_TEXT_DOMAIN ) . ' ' . $reopen_reason;
		}

		$reply_model->create( array(
			'ticket_id'        => $ticket_id,
			'user_id'          => $current_user->ID,
			'user_type'        => 'customer',
			'content'          => $reopen_message,
			'is_internal_note' => false,
		) );

		// Trigger action for notifications
		do_action( 'mets_ticket_reopened', $ticket_id, $current_user->ID );

		wp_send_json_success( array(
			'message' => __( 'Ticket has been reopened successfully. You can now continue the conversation.', METS_TEXT_DOMAIN ),
			'reload'  => true, // Signal to reload the page
		) );
	}

	/**
	 * AJAX search entities
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_entities() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );
		
		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		
		if ( strlen( $search_term ) < 2 ) {
			wp_send_json_error( array(
				'message' => __( 'Search term must be at least 2 characters.', METS_TEXT_DOMAIN )
			) );
		}

		// Load entity model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		
		// Search entities by name
		$entities = $entity_model->search( $search_term, 10 );

		$results = array();
		foreach ( $entities as $entity ) {
			$results[] = array(
				'id' => $entity->id,
				'name' => $entity->name,
				'description' => $entity->description ?? '',
				'is_parent' => empty( $entity->parent_id ),
				'parent_name' => isset( $entity->parent_name ) ? $entity->parent_name : null
			);
		}

		wp_send_json_success( array(
			'entities' => $results
		) );
	}

	/**
	 * AJAX search KB articles for suggestions
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_kb_articles() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );
		
		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		$entity_id = intval( $_POST['entity_id'] ?? 0 );
		
		if ( strlen( $search_term ) < 3 ) {
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
			'visibility' => array( 'customer', 'staff' ),
			'search' => $search_term,
			'per_page' => 5,
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
				'url' => $this->get_kb_article_url( $article ),
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
	 * Get KB article URL
	 *
	 * @since    1.0.0
	 * @param    object   $article   Article object
	 * @return   string             Article URL
	 */
	private function get_kb_article_url( $article ) {
		// Check if knowledgebase has a dedicated page
		$kb_settings = get_option( 'mets_kb_settings', array() );
		$kb_page_id = $kb_settings['kb_page_id'] ?? 0;
		
		if ( $kb_page_id && get_post_status( $kb_page_id ) === 'publish' ) {
			$base_url = get_permalink( $kb_page_id );
			return add_query_arg( array(
				'article' => $article->slug,
				'entity' => $article->entity_id
			), $base_url );
		}
		
		// Fallback to home page with parameters
		return add_query_arg( array(
			'mets_kb' => 'article',
			'article' => $article->slug,
			'entity' => $article->entity_id
		), home_url() );
	}

	/**
	 * Handle satisfaction survey responses
	 *
	 * Processes survey submissions from email links or survey page.
	 *
	 * @since    1.0.1
	 */
	public function handle_satisfaction_survey() {
		// Check if this is a survey request
		if ( ! isset( $_GET['mets_survey'] ) ) {
			return;
		}

		$token = sanitize_text_field( $_GET['mets_survey'] );

		// Load survey model
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-satisfaction-survey-model.php';
		$survey_model = new METS_Satisfaction_Survey_Model();

		// Get survey by token
		$survey = $survey_model->get_by_token( $token );

		if ( ! $survey ) {
			wp_die( __( 'Invalid survey link. This survey may have expired or been completed.', METS_TEXT_DOMAIN ), __( 'Survey Not Found', METS_TEXT_DOMAIN ), array( 'response' => 404 ) );
		}

		// Check if already completed
		if ( ! empty( $survey->survey_completed_at ) ) {
			$this->display_survey_thank_you( $survey, true );
			exit;
		}

		// Check if rating was provided in URL (one-click from email)
		if ( isset( $_GET['rating'] ) ) {
			$rating = intval( $_GET['rating'] );

			if ( $rating >= 1 && $rating <= 5 ) {
				// Submit the rating
				$result = $survey_model->submit_response( $token, $rating, '' );

				if ( is_wp_error( $result ) ) {
					wp_die( $result->get_error_message(), __( 'Survey Error', METS_TEXT_DOMAIN ), array( 'response' => 400 ) );
				}

				// Refresh survey data
				$survey = $survey_model->get_by_token( $token );

				// Show thank you page
				$this->display_survey_thank_you( $survey );
				exit;
			}
		}

		// Show survey form
		$this->display_survey_form( $survey );
		exit;
	}

	/**
	 * Display survey form
	 *
	 * @since    1.0.1
	 * @param    object    $survey    Survey object
	 */
	private function display_survey_form( $survey ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php _e( 'Customer Satisfaction Survey', METS_TEXT_DOMAIN ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background-color: #f5f5f5;
					margin: 0;
					padding: 20px;
					line-height: 1.6;
				}
				.survey-container {
					max-width: 600px;
					margin: 40px auto;
					background: white;
					border-radius: 8px;
					padding: 40px;
					box-shadow: 0 2px 10px rgba(0,0,0,0.1);
				}
				.survey-header {
					text-align: center;
					margin-bottom: 30px;
				}
				.survey-header h1 {
					color: #007cba;
					margin: 0 0 10px 0;
					font-size: 28px;
				}
				.survey-header p {
					color: #666;
					margin: 5px 0;
				}
				.ticket-info {
					background: #f8f9fa;
					padding: 15px;
					border-radius: 6px;
					margin: 20px 0;
					border-left: 4px solid #007cba;
				}
				.ticket-info p {
					margin: 5px 0;
					font-size: 14px;
				}
				.rating-section {
					text-align: center;
					margin: 30px 0;
				}
				.rating-section h2 {
					color: #333;
					font-size: 22px;
					margin-bottom: 20px;
				}
				.star-rating {
					display: inline-flex;
					gap: 15px;
					margin: 20px 0;
				}
				.star-rating input[type="radio"] {
					display: none;
				}
				.star-rating label {
					font-size: 50px;
					color: #ddd;
					cursor: pointer;
					transition: all 0.3s;
				}
				.star-rating input[type="radio"]:checked ~ label,
				.star-rating label:hover,
				.star-rating label:hover ~ label {
					color: #ffc107;
				}
				.star-rating {
					flex-direction: row-reverse;
					justify-content: center;
				}
				.feedback-section {
					margin: 30px 0;
				}
				.feedback-section label {
					display: block;
					margin-bottom: 10px;
					color: #333;
					font-weight: 500;
				}
				.feedback-section textarea {
					width: 100%;
					min-height: 100px;
					padding: 12px;
					border: 1px solid #ddd;
					border-radius: 4px;
					font-family: inherit;
					font-size: 14px;
					resize: vertical;
				}
				.submit-button {
					background: #007cba;
					color: white;
					border: none;
					padding: 15px 40px;
					font-size: 16px;
					border-radius: 4px;
					cursor: pointer;
					width: 100%;
					font-weight: 500;
					transition: background 0.3s;
				}
				.submit-button:hover {
					background: #005a87;
				}
				.submit-button:disabled {
					background: #ccc;
					cursor: not-allowed;
				}
				.error-message {
					color: #d32f2f;
					background: #ffebee;
					padding: 12px;
					border-radius: 4px;
					margin: 15px 0;
					display: none;
				}
				.rating-labels {
					display: flex;
					justify-content: space-between;
					margin-top: 10px;
					font-size: 13px;
					color: #666;
				}
			</style>
		</head>
		<body>
			<div class="survey-container">
				<div class="survey-header">
					<h1><?php _e( 'How was your support experience?', METS_TEXT_DOMAIN ); ?></h1>
					<p><?php _e( 'Your feedback helps us improve!', METS_TEXT_DOMAIN ); ?></p>
				</div>

				<div class="ticket-info">
					<p><strong><?php _e( 'Ticket:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $survey->ticket_number ); ?></p>
					<p><strong><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></strong> <?php echo esc_html( $survey->subject ); ?></p>
				</div>

				<form method="post" id="satisfaction-survey-form">
					<?php wp_nonce_field( 'mets_survey_submit', 'survey_nonce' ); ?>
					<input type="hidden" name="survey_token" value="<?php echo esc_attr( $survey->survey_token ); ?>">
					<input type="hidden" name="action" value="mets_submit_survey">

					<div class="rating-section">
						<h2><?php _e( 'How would you rate your experience?', METS_TEXT_DOMAIN ); ?></h2>
						<div class="star-rating">
							<input type="radio" name="rating" value="5" id="star5" required>
							<label for="star5" title="<?php esc_attr_e( 'Excellent', METS_TEXT_DOMAIN ); ?>">★</label>
							<input type="radio" name="rating" value="4" id="star4">
							<label for="star4" title="<?php esc_attr_e( 'Good', METS_TEXT_DOMAIN ); ?>">★</label>
							<input type="radio" name="rating" value="3" id="star3">
							<label for="star3" title="<?php esc_attr_e( 'Average', METS_TEXT_DOMAIN ); ?>">★</label>
							<input type="radio" name="rating" value="2" id="star2">
							<label for="star2" title="<?php esc_attr_e( 'Below Average', METS_TEXT_DOMAIN ); ?>">★</label>
							<input type="radio" name="rating" value="1" id="star1">
							<label for="star1" title="<?php esc_attr_e( 'Poor', METS_TEXT_DOMAIN ); ?>">★</label>
						</div>
						<div class="rating-labels">
							<span><?php _e( 'Poor', METS_TEXT_DOMAIN ); ?></span>
							<span><?php _e( 'Excellent', METS_TEXT_DOMAIN ); ?></span>
						</div>
					</div>

					<div class="feedback-section">
						<label for="feedback"><?php _e( 'Additional Comments (Optional)', METS_TEXT_DOMAIN ); ?></label>
						<textarea name="feedback" id="feedback" placeholder="<?php esc_attr_e( 'Tell us more about your experience...', METS_TEXT_DOMAIN ); ?>"></textarea>
					</div>

					<div class="error-message" id="error-message"></div>

					<button type="submit" class="submit-button"><?php _e( 'Submit Feedback', METS_TEXT_DOMAIN ); ?></button>
				</form>
			</div>

			<script>
			document.getElementById('satisfaction-survey-form').addEventListener('submit', function(e) {
				e.preventDefault();

				const rating = document.querySelector('input[name="rating"]:checked');
				if (!rating) {
					document.getElementById('error-message').textContent = '<?php echo esc_js( __( 'Please select a rating before submitting.', METS_TEXT_DOMAIN ) ); ?>';
					document.getElementById('error-message').style.display = 'block';
					return;
				}

				this.submit();
			});
			</script>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Display thank you page after survey submission
	 *
	 * @since    1.0.1
	 * @param    object    $survey           Survey object
	 * @param    bool      $already_completed  Whether survey was already completed
	 */
	private function display_survey_thank_you( $survey, $already_completed = false ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php _e( 'Thank You', METS_TEXT_DOMAIN ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background-color: #f5f5f5;
					margin: 0;
					padding: 20px;
					line-height: 1.6;
				}
				.thank-you-container {
					max-width: 600px;
					margin: 80px auto;
					background: white;
					border-radius: 8px;
					padding: 60px 40px;
					box-shadow: 0 2px 10px rgba(0,0,0,0.1);
					text-align: center;
				}
				.success-icon {
					font-size: 80px;
					color: #4caf50;
					margin-bottom: 20px;
				}
				.thank-you-container h1 {
					color: #333;
					margin: 0 0 15px 0;
					font-size: 32px;
				}
				.thank-you-container p {
					color: #666;
					font-size: 16px;
					margin: 10px 0;
				}
				.rating-display {
					margin: 30px 0;
					padding: 20px;
					background: #f8f9fa;
					border-radius: 6px;
				}
				.rating-display .stars {
					font-size: 40px;
					color: #ffc107;
					margin: 10px 0;
				}
				.home-link {
					display: inline-block;
					margin-top: 30px;
					padding: 12px 30px;
					background: #007cba;
					color: white;
					text-decoration: none;
					border-radius: 4px;
					transition: background 0.3s;
				}
				.home-link:hover {
					background: #005a87;
				}
			</style>
		</head>
		<body>
			<div class="thank-you-container">
				<div class="success-icon">✓</div>
				<h1><?php _e( 'Thank You for Your Feedback!', METS_TEXT_DOMAIN ); ?></h1>

				<?php if ( $already_completed ) : ?>
					<p><?php _e( 'You have already completed this survey.', METS_TEXT_DOMAIN ); ?></p>
				<?php else : ?>
					<p><?php _e( 'Your feedback has been submitted successfully.', METS_TEXT_DOMAIN ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $survey->rating ) && $survey->rating > 0 ) : ?>
					<div class="rating-display">
						<p><strong><?php _e( 'Your Rating:', METS_TEXT_DOMAIN ); ?></strong></p>
						<div class="stars">
							<?php echo str_repeat( '★', intval( $survey->rating ) ); ?>
						</div>
					</div>
				<?php endif; ?>

				<p><?php _e( 'We appreciate you taking the time to share your experience with us. Your input helps us provide better support.', METS_TEXT_DOMAIN ); ?></p>

				<a href="<?php echo esc_url( home_url() ); ?>" class="home-link">
					<?php _e( 'Return to Home Page', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Handle survey form submission
	 *
	 * @since    1.0.1
	 */
	public function ajax_submit_survey() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );

		$token = isset( $_POST['survey_token'] ) ? sanitize_text_field( $_POST['survey_token'] ) : '';
		$rating = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;
		$feedback = isset( $_POST['feedback'] ) ? sanitize_textarea_field( $_POST['feedback'] ) : '';

		if ( empty( $token ) || $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid survey data.', METS_TEXT_DOMAIN ),
			) );
		}

		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-satisfaction-survey-model.php';
		$survey_model = new METS_Satisfaction_Survey_Model();

		$result = $survey_model->submit_response( $token, $rating, $feedback );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			) );
		}

		wp_send_json_success( array(
			'message' => __( 'Thank you for your feedback!', METS_TEXT_DOMAIN ),
			'redirect' => home_url( '/?mets_survey=' . $token ),
		) );
	}

	/**
	 * AJAX handler for updating customer profile
	 *
	 * @since    1.0.1
	 */
	public function ajax_update_customer_profile() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in.', METS_TEXT_DOMAIN ),
			) );
		}

		$current_user = wp_get_current_user();
		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

		if ( empty( $name ) ) {
			wp_send_json_error( array(
				'message' => __( 'Name is required.', METS_TEXT_DOMAIN ),
			) );
		}

		// Update display name
		$updated = wp_update_user( array(
			'ID'           => $current_user->ID,
			'display_name' => $name,
		) );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( array(
				'message' => $updated->get_error_message(),
			) );
		}

		// Update phone
		update_user_meta( $current_user->ID, 'mets_phone', $phone );

		wp_send_json_success( array(
			'message' => __( 'Profile updated successfully!', METS_TEXT_DOMAIN ),
		) );
	}

	/**
	 * AJAX handler for updating notification preferences
	 *
	 * @since    1.0.1
	 */
	public function ajax_update_notification_preferences() {
		check_ajax_referer( 'mets_public_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in.', METS_TEXT_DOMAIN ),
			) );
		}

		$current_user = wp_get_current_user();

		$preferences = array(
			'email_on_reply'         => ! empty( $_POST['email_on_reply'] ),
			'email_on_status_change' => ! empty( $_POST['email_on_status_change'] ),
			'email_on_resolution'    => ! empty( $_POST['email_on_resolution'] ),
			'email_digest'           => isset( $_POST['email_digest'] ) ? sanitize_text_field( $_POST['email_digest'] ) : 'none',
		);

		// Validate email_digest value
		if ( ! in_array( $preferences['email_digest'], array( 'none', 'daily', 'weekly' ) ) ) {
			$preferences['email_digest'] = 'none';
		}

		// Save preferences
		update_user_meta( $current_user->ID, 'mets_notification_preferences', $preferences );

		wp_send_json_success( array(
			'message' => __( 'Preferences saved successfully!', METS_TEXT_DOMAIN ),
		) );
	}
}