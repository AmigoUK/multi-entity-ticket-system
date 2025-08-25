<?php
/**
 * Guest Ticket Access Extension for Multi-Entity Ticket System
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @since      1.0.0
 */

/**
 * Guest Ticket Access Extension class.
 *
 * This class extends the Multi-Entity Ticket System with guest ticket access functionality.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Guest_Ticket_Access {

	/**
	 * Error message container
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $error_message    Error message
	 */
	private $error_message = '';

	/**
	 * Initialize the guest ticket access functionality.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		// Add rewrite rules
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		
		// Add query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		
		// Handle template redirects
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
		
		// Register shortcodes
		add_shortcode( 'guest_ticket_access_form', array( $this, 'display_access_form' ) );
		
		// Handle form submissions
		add_action( 'init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Add rewrite rules for guest ticket access
	 *
	 * @since    1.0.0
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^guest-ticket-access/?$', 'index.php?mets_guest_access=1', 'top' );
		add_rewrite_rule( '^guest-ticket/([^/]+)/?$', 'index.php?mets_guest_ticket=1&token=$matches[1]', 'top' );
	}

	/**
	 * Add query vars for guest ticket access
	 *
	 * @since    1.0.0
	 * @param    array    $vars    Query variables
	 * @return   array             Modified query variables
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'mets_guest_access';
		$vars[] = 'mets_guest_ticket';
		$vars[] = 'token';
		return $vars;
	}

	/**
	 * Handle template redirect for guest access
	 *
	 * @since    1.0.0
	 */
	public function handle_template_redirect() {
		global $wp_query;
		
		// Handle guest access form
		if ( isset( $wp_query->query_vars['mets_guest_access'] ) ) {
			$this->load_guest_access_template();
			exit;
		}
		
		// Handle guest ticket view
		if ( isset( $wp_query->query_vars['mets_guest_ticket'] ) ) {
			$token = isset( $wp_query->query_vars['token'] ) ? sanitize_text_field( $wp_query->query_vars['token'] ) : '';
			if ( $token ) {
				$this->load_guest_ticket_template( $token );
				exit;
			}
		}
	}

	/**
	 * Load guest access template
	 *
	 * @since    1.0.0
	 */
	private function load_guest_access_template() {
		// Make this object available globally for templates
		global $mets_guest_access;
		$mets_guest_access = $this;
		
		// Enqueue styles
		wp_enqueue_style( 
			'mets-guest-ticket-access', 
			METS_PLUGIN_URL . 'public/css/guest-ticket-access.css', 
			array(), 
			METS_VERSION 
		);
		
		// Include the template
		include METS_PLUGIN_PATH . 'public/templates/guest-ticket-access-form.php';
	}

	/**
	 * Load guest ticket template
	 *
	 * @since    1.0.0
	 * @param    string    $token    Access token
	 */
	private function load_guest_ticket_template( $token ) {
		// Make this object available globally for templates
		global $mets_guest_access;
		$mets_guest_access = $this;
		
		// Validate token and get ticket
		$ticket_data = $this->validate_and_get_ticket( $token );
		
		if ( ! $ticket_data ) {
			$error_message = __( 'Invalid or expired access token.', 'multi-entity-ticket-system' );
			include METS_PLUGIN_PATH . 'public/templates/guest-ticket-error.php';
			return;
		}
		
		$ticket = $ticket_data['ticket'];
		$replies = $ticket_data['replies'];
		
		// Enqueue styles
		wp_enqueue_style( 
			'mets-guest-ticket-access', 
			METS_PLUGIN_URL . 'public/css/guest-ticket-access.css', 
			array(), 
			METS_VERSION 
		);
		
		// Include the template
		include METS_PLUGIN_PATH . 'public/templates/guest-ticket-view.php';
	}

	/**
	 * Display access form shortcode
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes
	 * @return   string            HTML output
	 */
	public function display_access_form( $atts ) {
		$atts = shortcode_atts( array(
			'page_title' => __( 'Guest Ticket Access', 'multi-entity-ticket-system' ),
		), $atts, 'guest_ticket_access_form' );
		
		ob_start();
		include METS_PLUGIN_PATH . 'public/templates/guest-ticket-access-form-shortcode.php';
		return ob_get_clean();
	}

	/**
	 * Handle form submission
	 *
	 * @since    1.0.0
	 */
	public function handle_form_submission() {
		// Check if this is a guest access form submission
		if ( ! isset( $_POST['mets_guest_access_nonce'] ) ) {
			return;
		}
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['mets_guest_access_nonce'], 'mets_guest_access_action' ) ) {
			$this->error_message = __( 'Security check failed. Please try again.', 'multi-entity-ticket-system' );
			return;
		}
		
		// Get and sanitize inputs
		$email = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
		$ticket_number = isset( $_POST['ticket_number'] ) ? sanitize_text_field( $_POST['ticket_number'] ) : '';
		
		// Validate inputs
		if ( empty( $email ) || empty( $ticket_number ) ) {
			$this->error_message = __( 'Please provide both email and ticket number.', 'multi-entity-ticket-system' );
			return;
		}
		
		if ( ! is_email( $email ) ) {
			$this->error_message = __( 'Please provide a valid email address.', 'multi-entity-ticket-system' );
			return;
		}
		
		// Rate limiting check (simplified for now)
		$transient_key = 'mets_guest_access_' . md5( $email );
		$attempts = get_transient( $transient_key );
		if ( $attempts && $attempts >= 5 ) {
			$this->error_message = __( 'Too many access attempts. Please try again later.', 'multi-entity-ticket-system' );
			return;
		}
		
		// Validate ticket access
		$ticket = $this->validate_ticket_access( $email, $ticket_number );
		if ( ! $ticket ) {
			// Increment rate limiting counter
			$attempts = $attempts ? $attempts + 1 : 1;
			set_transient( $transient_key, $attempts, 15 * MINUTE_IN_SECONDS );
			
			$this->error_message = __( 'Invalid email or ticket number.', 'multi-entity-ticket-system' );
			return;
		}
		
		// Generate access token
		$token = $this->generate_access_token( $ticket->id, $email );
		if ( ! $token ) {
			$this->error_message = __( 'Unable to generate access token. Please try again.', 'multi-entity-ticket-system' );
			return;
		}
		
		// Clear rate limiting counter on success
		delete_transient( $transient_key );
		
		// Redirect to ticket view
		$redirect_url = home_url( '/guest-ticket/' . $token . '/' );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Validate ticket access for guest
	 *
	 * @since    1.0.0
	 * @param    string    $email           Customer email
	 * @param    string    $ticket_number   Ticket number
	 * @return   object|bool                Ticket object or false
	 */
	private function validate_ticket_access( $email, $ticket_number ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		$ticket_model = new METS_Ticket_Model();
		
		$ticket = $ticket_model->get_by_ticket_number( $ticket_number );
		
		if ( ! $ticket ) {
			return false;
		}
		
		if ( strtolower( $ticket->customer_email ) !== strtolower( $email ) ) {
			return false;
		}
		
		return $ticket;
	}

	/**
	 * Generate secure access token
	 *
	 * @since    1.0.0
	 * @param    int       $ticket_id    Ticket ID
	 * @param    string    $email        Customer email
	 * @return   string|bool             Access token or false
	 */
	private function generate_access_token( $ticket_id, $email ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-guest-access-token-model.php';
		$token_model = new METS_Guest_Access_Token_Model();
		
		// Generate secure token using existing method
		$token = $token_model->generate_token( $ticket_id, $email, 48, 5 );
		
		return $token;
	}

	/**
	 * Validate token and get ticket data
	 *
	 * @since    1.0.0
	 * @param    string    $token    Access token
	 * @return   array|bool          Ticket data or false
	 */
	private function validate_and_get_ticket( $token ) {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-guest-access-token-model.php';
		$token_model = new METS_Guest_Access_Token_Model();
		
		// Get token data
		$token_data = $token_model->get_by_token( $token );
		
		if ( ! $token_data ) {
			return false;
		}
		
		// Check expiration
		if ( strtotime( $token_data->expires_at ) < time() ) {
			$token_model->delete( $token_data->id );
			return false;
		}
		
		// Check usage limit
		if ( $token_data->use_count >= $token_data->max_uses ) {
			return false;
		}
		
		// Check IP address
		if ( $token_data->ip_address !== $this->get_client_ip() ) {
			// For now, we'll allow this but could add additional security measures
		}
		
		// Get ticket
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-model.php';
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-ticket-reply-model.php';
		
		$ticket_model = new METS_Ticket_Model();
		$reply_model = new METS_Ticket_Reply_Model();
		
		$ticket = $ticket_model->get( $token_data->ticket_id );
		
		if ( ! $ticket ) {
			return false;
		}
		
		// Get replies
		$replies = $reply_model->get_by_ticket( $token_data->ticket_id, array( 'exclude_internal' => true ) );
		
		// Increment token usage
		$token_model->update( $token_data->id, array(
			'use_count' => $token_data->use_count + 1
		) );
		
		return array(
			'ticket' => $ticket,
			'replies' => $replies
		);
	}

	/**
	 * Get client IP address
	 *
	 * @since    1.0.0
	 * @return   string    Client IP address
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' );
		
		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				// Handle comma-separated IPs (forwarded)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}
		
		return '0.0.0.0';
	}

	/**
	 * Set error message
	 *
	 * @since    1.0.0
	 * @param    string    $message    Error message
	 */
	public function set_error( $message ) {
		$this->error_message = $message;
	}

	/**
	 * Get error message
	 *
	 * @since    1.0.0
	 * @return   string    Error message
	 */
	public function get_error() {
		return $this->error_message;
	}
}