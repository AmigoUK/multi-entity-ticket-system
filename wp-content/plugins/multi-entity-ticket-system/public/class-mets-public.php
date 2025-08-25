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
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Initialize knowledgebase functionality
		$this->init_knowledgebase();
		
		// Add rewrite rules for guest ticket access
		add_action( 'init', array( $this, 'add_guest_ticket_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_guest_ticket_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_guest_ticket_access' ) );
		
		// Handle customer reply submissions
		add_action( 'init', array( $this, 'handle_customer_reply' ) );
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
	 * Add rewrite rules for guest ticket access
	 *
	 * @since    1.0.0
	 */
	public function add_guest_ticket_rewrite_rules() {
		add_rewrite_rule( '^guest-ticket-access/?$', 'index.php?mets_guest_ticket=access', 'top' );
	}

	/**
	 * Add query vars for guest ticket access
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Query variables
	 * @return   array             Updated query variables
	 */
	public function add_guest_ticket_query_vars( $vars ) {
		$vars[] = 'mets_guest_ticket';
		return $vars;
	}

	/**
	 * Handle guest ticket access requests
	 *
	 * @since    1.0.0
	 */
	public function handle_guest_ticket_access() {
		// Handle guest ticket access logic
	}

	/**
	 * Handle customer reply submissions
	 *
	 * @since    1.0.0
	 */
	public function handle_customer_reply() {
		// Check if this is a customer reply submission
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'mets_customer_add_reply' ) {
			return;
		}
		
		// Verify nonce
		if ( ! isset( $_POST['customer_reply_nonce'] ) || ! wp_verify_nonce( $_POST['customer_reply_nonce'], 'mets_customer_reply' ) ) {
			wp_die( __( 'Security check failed', 'multi-entity-ticket-system' ) );
		}
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_die( __( 'You must be logged in to reply to tickets', 'multi-entity-ticket-system' ) );
		}
		
		// Get and validate inputs
		$ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
		$content = isset( $_POST['reply_content'] ) ? sanitize_textarea_field( $_POST['reply_content'] ) : '';
		
		// Validate required fields
		if ( empty( $ticket_id ) || empty( $content ) ) {
			wp_die( __( 'Please fill in all required fields', 'multi-entity-ticket-system' ) );
		}
		
		// Get current user
		$current_user = wp_get_current_user();
		
		// Verify user has access to this ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		$ticket = $ticket_model->get( $ticket_id );
		
		if ( ! $ticket || $ticket->customer_email !== $current_user->user_email ) {
			wp_die( __( 'Access denied', 'multi-entity-ticket-system' ) );
		}
		
		// Check if ticket is closed
		if ( $ticket->status === 'closed' ) {
			wp_die( __( 'This ticket is closed and cannot receive replies', 'multi-entity-ticket-system' ) );
		}
		
		// Create reply
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		$reply_model = new METS_Ticket_Reply_Model();
		
		$reply_data = array(
			'ticket_id'    => $ticket_id,
			'user_id'      => $current_user->ID,
			'user_type'    => 'customer',
			'content'      => $content,
			'is_internal_note' => 0,
		);
		
		$reply_id = $reply_model->create( $reply_data );
		
		// Check if reply creation failed
		if ( is_wp_error( $reply_id ) ) {
			error_log( '[METS] Failed to create reply: ' . $reply_id->get_error_message() );
			wp_die( __( 'Failed to add reply. Please try again.', 'multi-entity-ticket-system' ) );
		}
		
		if ( ! $reply_id ) {
			error_log( '[METS] Failed to create reply: Unknown error' );
			wp_die( __( 'Failed to add reply. Please try again.', 'multi-entity-ticket-system' ) );
		}
		
		// Handle file attachments if any
		if ( ! empty( $_FILES['reply_attachment'] ) && ! empty( $_FILES['reply_attachment']['name'][0] ) ) {
			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-attachment-model.php';
			require_once METS_PLUGIN_PATH . 'includes/class-mets-security-manager.php';
			
			$attachment_model = new METS_Attachment_Model();
			
			// Handle multiple file uploads
			$files = $_FILES['reply_attachment'];
			$upload_errors = array();
			
			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				// Skip empty files
				if ( empty( $files['name'][$i] ) ) {
					continue;
				}
				
				// Create file array for this file
				$file = array(
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i],
					'size'     => $files['size'][$i]
				);
				
				// Validate file using security manager
				$security_manager = METS_Security_Manager::get_instance();
				$validation_result = $security_manager->validate_file_upload( $file );
				
				if ( ! $validation_result['valid'] ) {
					$upload_errors = array_merge( $upload_errors, $validation_result['errors'] );
					continue;
				}
				
				// Handle file upload using WordPress function
				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
				
				$upload_overrides = array(
					'test_form' => false,
				);
				
				$uploaded_file = wp_handle_upload( $file, $upload_overrides );
				
				if ( isset( $uploaded_file['error'] ) ) {
					$upload_errors[] = $uploaded_file['error'];
					continue;
				}
				
				// Create attachment record
				$attachment_data = array(
					'ticket_id' => $ticket_id,
					'reply_id' => $reply_id,
					'file_name' => basename( $uploaded_file['file'] ),
					'file_url' => $uploaded_file['url'],
					'file_size' => filesize( $uploaded_file['file'] ),
					'file_type' => $uploaded_file['type'],
				);
				
				$attachment_result = $attachment_model->create( $attachment_data );
				
				if ( is_wp_error( $attachment_result ) ) {
					// Clean up uploaded file if database insert failed
					wp_delete_file( $uploaded_file['file'] );
					$upload_errors[] = $attachment_result->get_error_message();
				} elseif ( ! $attachment_result ) {
					// Clean up uploaded file if database insert failed
					wp_delete_file( $uploaded_file['file'] );
					$upload_errors[] = __( 'Failed to save attachment information to database.', 'multi-entity-ticket-system' );
				}
			}
			
			// Log any upload errors
			if ( ! empty( $upload_errors ) ) {
				error_log( '[METS] Attachment upload errors for reply ' . $reply_id . ': ' . print_r( $upload_errors, true ) );
			}
		}
		
		// Update ticket status to "open" if it was "new"
		if ( $ticket->status === 'new' ) {
			$ticket_model->update( $ticket_id, array( 'status' => 'open' ) );
		}
		
		// Send notification to agents
		do_action( 'mets_ticket_replied', $ticket_id, $reply_id, 'customer' );
		
		// Redirect back to ticket with success message
		$redirect_url = add_query_arg( 
			array( 
				'mets_view' => 'ticket', 
				'mets_ticket' => $ticket_id,
				'reply_added' => '1'
			),
			get_permalink()
		);
		
		wp_redirect( $redirect_url );
		exit;
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
				'required_fields' => __( 'Please fill in all required fields correctly.' ),
				'generic_error' => __( 'An error occurred. Please try again later.' ),
			),
			'ticket_number_text' => __( 'Ticket number:' ),
			'upload_limits' => $upload_limits,
		) );
		
		// Load n8n chat if enabled
		$n8n_settings = get_option( 'mets_n8n_chat_settings', array() );
		if ( ! empty( $n8n_settings['enabled'] ) && ! is_admin() ) {
			wp_enqueue_script( 'mets-n8n-chat', METS_PLUGIN_URL . 'assets/n8n/n8n-chat-bundle.es.js', array(), METS_VERSION, true );
			wp_localize_script( 'mets-n8n-chat', 'mets_n8n_chat_config', $n8n_settings );
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
		
		// Format the size nicely
		$formatted_size = size_format( $max_upload_size );
		
		return array(
			'max_bytes' => $max_upload_size,
			'max_formatted' => $formatted_size,
		);
	}

	/**
	 * Register shortcodes
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'ticket_form', array( $this, 'display_ticket_form' ) );
		add_shortcode( 'ticket_portal', array( $this, 'display_customer_portal' ) );
		add_shortcode( 'guest_ticket_access', array( $this, 'display_guest_ticket_access' ) );
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
				<h3><?php _e( 'Ticket Submission', 'multi-entity-ticket-system' ); ?></h3>
				<p><?php _e( 'You must be logged in to submit a ticket.', 'multi-entity-ticket-system' ); ?></p>
				<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="mets-login-link"><?php _e( 'Log in', 'multi-entity-ticket-system' ); ?></a></p>
			</div>
			<?php
			return ob_get_clean();
		}

		// Enqueue styles for ticket form
		wp_enqueue_style( 
			'mets-ticket-form', 
			METS_PLUGIN_URL . 'public/css/mets-ticket-form.css', 
			array(), 
			METS_VERSION 
		);
		
		// Generate the form directly
		ob_start();
		?>
		<div class="mets-ticket-form-wrapper">
			<h2><?php _e( 'Submit a Support Ticket', 'multi-entity-ticket-system' ); ?></h2>
			
			<form class="mets-ticket-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'mets_submit_ticket', 'mets_ticket_nonce' ); ?>
				
				<div class="mets-form-group">
					<label for="mets_ticket_subject"><?php _e( 'Subject', 'multi-entity-ticket-system' ); ?> <span class="required">*</span></label>
					<input type="text" id="mets_ticket_subject" name="mets_ticket_subject" required placeholder="<?php _e( 'Briefly describe your issue', 'multi-entity-ticket-system' ); ?>">
				</div>
				
				<div class="mets-form-group">
					<label for="mets_ticket_description"><?php _e( 'Description', 'multi-entity-ticket-system' ); ?> <span class="required">*</span></label>
					<textarea id="mets_ticket_description" name="mets_ticket_description" rows="8" required placeholder="<?php _e( 'Please provide detailed information about your issue', 'multi-entity-ticket-system' ); ?>"></textarea>
				</div>
				
				<div class="mets-form-group">
					<label for="mets_ticket_department"><?php _e( 'Department', 'multi-entity-ticket-system' ); ?> <span class="required">*</span></label>
					<select id="mets_ticket_department" name="mets_ticket_department" required>
						<option value=""><?php _e( 'Select a department', 'multi-entity-ticket-system' ); ?></option>
						<?php
						// Get entities for department selection using the entity model
						require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
						$entity_model = new METS_Entity_Model();
						$entities = $entity_model->get_all( array( 'status' => 'active' ) );
						foreach ( $entities as $entity ) {
							echo '<option value="' . esc_attr( $entity->id ) . '">' . esc_html( $entity->name ) . '</option>';
						}
						?>
					</select>
				</div>
				
				<div class="mets-form-group">
					<label for="mets_ticket_priority"><?php _e( 'Priority', 'multi-entity-ticket-system' ); ?></label>
					<select id="mets_ticket_priority" name="mets_ticket_priority">
						<option value="low"><?php _e( 'Low', 'multi-entity-ticket-system' ); ?></option>
						<option value="medium" selected><?php _e( 'Medium', 'multi-entity-ticket-system' ); ?></option>
						<option value="high"><?php _e( 'High', 'multi-entity-ticket-system' ); ?></option>
						<option value="urgent"><?php _e( 'Urgent', 'multi-entity-ticket-system' ); ?></option>
					</select>
				</div>
				
				<div class="mets-form-group">
					<label for="mets_ticket_attachment"><?php _e( 'Attach Files', 'multi-entity-ticket-system' ); ?></label>
					<input type="file" id="mets_ticket_attachment" name="mets_ticket_attachment[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
					<small><?php _e( 'You can attach multiple files (max 8MB total)', 'multi-entity-ticket-system' ); ?></small>
				</div>
				
				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="mets-form-group">
						<label for="mets_customer_name"><?php _e( 'Your Name', 'multi-entity-ticket-system' ); ?> <span class="required">*</span></label>
						<input type="text" id="mets_customer_name" name="mets_customer_name" required placeholder="<?php _e( 'Enter your full name', 'multi-entity-ticket-system' ); ?>">
					</div>
					
					<div class="mets-form-group">
						<label for="mets_customer_email"><?php _e( 'Your Email', 'multi-entity-ticket-system' ); ?> <span class="required">*</span></label>
						<input type="email" id="mets_customer_email" name="mets_customer_email" required placeholder="<?php _e( 'Enter your email address', 'multi-entity-ticket-system' ); ?>">
					</div>
				<?php endif; ?>
				
				<div class="mets-form-actions">
					<button type="submit" class="mets-submit-button"><?php _e( 'Submit Ticket', 'multi-entity-ticket-system' ); ?></button>
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
				<h3><?php _e( 'Customer Portal', 'multi-entity-ticket-system' ); ?></h3>
				<p><?php _e( 'You must be logged in to view your tickets.', 'multi-entity-ticket-system' ); ?></p>
				<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="mets-login-link"><?php _e( 'Log in', 'multi-entity-ticket-system' ); ?></a></p>
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
			<?php if ( $view === 'ticket' && $ticket_id ) : ?>
				<?php echo $this->display_customer_ticket_detail( $ticket_id ); ?>
			<?php else : ?>
				<?php echo $this->display_customer_ticket_list( $atts, $current_user ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display guest ticket access shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            HTML output
	 */
	public function display_guest_ticket_access( $atts ) {
		$atts = shortcode_atts( array(
			'page_title' => __( 'Guest Ticket Access', 'multi-entity-ticket-system' ),
		), $atts, 'guest_ticket_access' );

		// Load the guest ticket access template
		ob_start();
		
		// Enqueue styles for guest ticket access
		wp_enqueue_style( 
			'mets-guest-ticket-access', 
			METS_PLUGIN_URL . 'public/css/guest-ticket-access.css', 
			array(), 
			METS_VERSION 
		);
		
		// Include the template
		include METS_PLUGIN_PATH . 'public/templates/guest-ticket-access.php';
		
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
	public function display_customer_ticket_list( $atts, $current_user ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		// Get tickets for the current user
		$tickets = $ticket_model->get_by_customer_email( $current_user->user_email );
		
		ob_start();
		?>
		<div class="mets-customer-ticket-list">
			<div class="mets-tickets-header">
				<h3><?php echo esc_html__( 'Your Support Tickets', 'multi-entity-ticket-system' ); ?></h3>
			</div>
			
			<?php if ( empty( $tickets ) ) : ?>
				<div class="mets-no-tickets">
					<p><?php echo esc_html__( 'You have no support tickets yet.', 'multi-entity-ticket-system' ); ?></p>
				</div>
			<?php else : ?>
				<table class="mets-tickets-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Ticket #', 'multi-entity-ticket-system' ); ?></th>
							<th><?php echo esc_html__( 'Subject', 'multi-entity-ticket-system' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'multi-entity-ticket-system' ); ?></th>
							<th><?php echo esc_html__( 'Priority', 'multi-entity-ticket-system' ); ?></th>
							<th><?php echo esc_html__( 'Department', 'multi-entity-ticket-system' ); ?></th>
							<th><?php echo esc_html__( 'Created', 'multi-entity-ticket-system' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tickets as $ticket ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'mets_view' => 'ticket', 'mets_ticket' => $ticket->id ) ) ); ?>">
										<?php echo esc_html( $ticket->ticket_number ); ?>
									</a>
								</td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'mets_view' => 'ticket', 'mets_ticket' => $ticket->id ) ) ); ?>">
										<?php echo esc_html( $ticket->subject ); ?>
									</a>
								</td>
								<td>
									<span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>">
										<?php 
										$statuses = get_option( 'mets_ticket_statuses', array() );
										$status_label = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['label'] : ucfirst( str_replace( '_', ' ', $ticket->status ) );
										echo esc_html( $status_label );
										?>
									</span>
								</td>
								<td>
									<span class="mets-priority-badge mets-priority-<?php echo esc_attr( $ticket->priority ); ?>">
										<?php 
										$priorities = get_option( 'mets_ticket_priorities', array() );
										$priority_label = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['label'] : ucfirst( $ticket->priority );
										echo esc_html( $priority_label );
										?>
									</span>
								</td>
								<td><?php echo esc_html( $ticket->entity_name ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display customer ticket detail
	 *
	 * @since    1.0.0
	 * @param    int       $ticket_id    Ticket ID
	 * @return   string                  HTML output
	 */
	public function display_customer_ticket_detail( $ticket_id ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();
		
		// Get ticket
		$ticket = $ticket_model->get( $ticket_id );
		
		// Verify user has access to this ticket
		$current_user = wp_get_current_user();
		if ( ! $ticket || $ticket->customer_email !== $current_user->user_email ) {
			return '<div class="mets-error"><p>' . esc_html__( 'Ticket not found or access denied.', 'multi-entity-ticket-system' ) . '</p></div>';
		}
		
		// Get replies
		$replies = $reply_model->get_by_ticket( $ticket_id, array( 'exclude_internal' => true ) );
		
		// Get status and priority labels
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		
		$status_label = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['label'] : ucfirst( str_replace( '_', ' ', $ticket->status ) );
		$priority_label = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['label'] : ucfirst( $ticket->priority );
		
		ob_start();
		?>
		<div class="mets-customer-ticket-detail">
			<!-- Header with back button -->
			<div class="mets-ticket-header">
				<div class="mets-breadcrumb">
					<a href="<?php echo esc_url( remove_query_arg( array( 'mets_view', 'mets_ticket' ) ) ); ?>" class="mets-back-link">
						&larr; <?php echo esc_html__( 'Back to My Tickets', 'multi-entity-ticket-system' ); ?>
					</a>
				</div>
				<h3><?php echo esc_html( $ticket->ticket_number ); ?> - <?php echo esc_html( $ticket->subject ); ?></h3>
			</div>
			
			<!-- Success message -->
			<?php if ( isset( $_GET['reply_added'] ) && $_GET['reply_added'] == '1' ) : ?>
				<div class="mets-success-message">
					<p><?php echo esc_html__( 'Your reply has been added successfully.', 'multi-entity-ticket-system' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Ticket Info -->
			<div class="mets-ticket-info">
				<div class="mets-info-grid">
					<div class="mets-info-item">
						<strong><?php echo esc_html__( 'Status:', 'multi-entity-ticket-system' ); ?></strong>
						<span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</div>
					<div class="mets-info-item">
						<strong><?php echo esc_html__( 'Priority:', 'multi-entity-ticket-system' ); ?></strong>
						<span class="mets-priority-badge mets-priority-<?php echo esc_attr( $ticket->priority ); ?>">
							<?php echo esc_html( $priority_label ); ?>
						</span>
					</div>
					<div class="mets-info-item">
						<strong><?php echo esc_html__( 'Department:', 'multi-entity-ticket-system' ); ?></strong>
						<?php echo esc_html( $ticket->entity_name ); ?>
					</div>
					<div class="mets-info-item">
						<strong><?php echo esc_html__( 'Created:', 'multi-entity-ticket-system' ); ?></strong>
						<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?>
					</div>
				</div>
			</div>

			<!-- Original Message -->
			<div class="mets-ticket-message">
				<h4><?php echo esc_html__( 'Original Message', 'multi-entity-ticket-system' ); ?></h4>
				<div class="mets-message-content">
					<?php echo wp_kses_post( $ticket->description ); ?>
				</div>
			</div>

			<!-- Replies -->
			<div class="mets-ticket-replies">
				<h4><?php echo esc_html__( 'Conversation', 'multi-entity-ticket-system' ); ?></h4>
				
				<?php if ( empty( $replies ) ) : ?>
					<div class="mets-no-replies">
						<p><?php echo esc_html__( 'No replies yet. We\'ll respond to your ticket as soon as possible.', 'multi-entity-ticket-system' ); ?></p>
					</div>
				<?php else : ?>
					<div class="mets-replies-list">
						<?php foreach ( $replies as $reply ) : ?>
							<div class="mets-reply <?php echo $reply->user_type === 'customer' ? 'mets-reply-customer' : 'mets-reply-agent'; ?>">
								<div class="mets-reply-header">
									<div class="mets-reply-author">
										<strong><?php echo esc_html( $reply->user_name ?: __( 'Support Agent', 'multi-entity-ticket-system' ) ); ?></strong>
										<?php if ( $reply->user_type === 'customer' ) : ?>
											<span class="mets-reply-badge"><?php echo esc_html__( 'You', 'multi-entity-ticket-system' ); ?></span>
										<?php else : ?>
											<span class="mets-reply-badge"><?php echo esc_html__( 'Support', 'multi-entity-ticket-system' ); ?></span>
										<?php endif; ?>
									</div>
									<div class="mets-reply-date">
										<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->created_at ) ); ?>
									</div>
								</div>
								<div class="mets-reply-content">
									<?php echo wp_kses_post( $reply->content ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Reply Form (only if ticket is not closed) -->
			<?php if ( $ticket->status !== 'closed' ) : ?>
				<div class="mets-reply-form">
					<h4><?php echo esc_html__( 'Add Reply', 'multi-entity-ticket-system' ); ?></h4>
					
					<form id="mets-customer-reply-form" method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'mets_customer_reply', 'customer_reply_nonce' ); ?>
						<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->id ); ?>">
						<input type="hidden" name="action" value="mets_customer_add_reply">
						
						<div class="mets-form-group">
							<label for="reply_content"><?php echo esc_html__( 'Your message:', 'multi-entity-ticket-system' ); ?></label>
							<textarea id="reply_content" name="reply_content" rows="10" required placeholder="<?php echo esc_attr__( 'Type your reply here...', 'multi-entity-ticket-system' ); ?>"></textarea>
						</div>
						
						<div class="mets-form-group">
							<label for="reply_attachment"><?php echo esc_html__( 'Attach Files', 'multi-entity-ticket-system' ); ?></label>
							<input type="file" id="reply_attachment" name="reply_attachment[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
							<small><?php echo esc_html__( 'You can attach multiple files (max 8MB total)', 'multi-entity-ticket-system' ); ?></small>
						</div>
						
						<div class="mets-form-actions">
							<button type="submit" class="mets-submit-button"><?php echo esc_html__( 'Send Reply', 'multi-entity-ticket-system' ); ?></button>
							<span class="mets-loading" style="display: none;"><?php echo esc_html__( 'Sending...', 'multi-entity-ticket-system' ); ?></span>
						</div>

						<div class="mets-form-messages">
							<div class="mets-success-message" style="display: none;"></div>
							<div class="mets-error-message" style="display: none;"></div>
						</div>
					</form>
				</div>
			<?php else : ?>
				<div class="mets-ticket-closed">
					<p><?php echo esc_html__( 'This ticket has been closed. If you need further assistance, please submit a new ticket.', 'multi-entity-ticket-system' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}