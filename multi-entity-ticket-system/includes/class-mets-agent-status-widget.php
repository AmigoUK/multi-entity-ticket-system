<?php
/**
 * Agent Status Widget
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

class METS_Agent_Status_Widget {

	/**
	 * Single instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Agent_Status_Widget    $instance    Single instance.
	 */
	private static $instance = null;

	/**
	 * Agent profile instance
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      METS_Agent_Profile    $agent_profile
	 */
	private $agent_profile;

	/**
	 * Get instance
	 *
	 * @since    1.0.0
	 * @return   METS_Agent_Status_Widget    Single instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->agent_profile = METS_Agent_Profile::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since    1.0.0
	 */
	private function init_hooks() {
		// Add to admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_status' ), 100 );
		
		// Add to dashboard widgets
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		
		// AJAX handler for quick status update
		add_action( 'wp_ajax_mets_quick_status_update', array( $this, 'ajax_quick_status_update' ) );
		
		// Add admin styles
		add_action( 'admin_head', array( $this, 'add_inline_styles' ) );
		add_action( 'wp_head', array( $this, 'add_inline_styles' ) );
	}

	/**
	 * Add status to admin bar
	 *
	 * @since    1.0.0
	 * @param    WP_Admin_Bar    $wp_admin_bar    Admin bar object
	 */
	public function add_admin_bar_status( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		// Check if user has any METS roles
		$mets_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor' );
		$has_mets_role = false;
		
		foreach ( $mets_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_mets_role = true;
				break;
			}
		}
		
		if ( ! $has_mets_role ) {
			return;
		}
		
		// Get current status
		$preferences = $this->agent_profile->get_agent_preferences( $current_user->ID );
		$status = $preferences['status'];
		$status_message = $preferences['status_message'];
		
		// Get workload
		$role_manager = METS_Role_Manager::get_instance();
		$workload = $role_manager->get_agent_workload( $current_user->ID );
		
		// Status colors
		$status_colors = array(
			'available' => '#00a32a',
			'busy' => '#dba617',
			'away' => '#e65054',
			'offline' => '#646970'
		);
		
		$status_color = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : '#646970';
		
		// Add main node
		$wp_admin_bar->add_node( array(
			'id' => 'mets-agent-status',
			'title' => sprintf(
				'<span class="mets-status-indicator" style="background: %s;"></span> %s <span class="mets-workload">(%d)</span>',
				esc_attr( $status_color ),
				esc_html( ucfirst( $status ) ),
				$workload
			),
			'href' => admin_url( 'profile.php#mets-agent-profile' ),
			'meta' => array(
				'title' => $status_message ?: __( 'Click to update your status', METS_TEXT_DOMAIN )
			)
		) );
		
		// Add status options
		$statuses = array(
			'available' => __( 'Available', METS_TEXT_DOMAIN ),
			'busy' => __( 'Busy', METS_TEXT_DOMAIN ),
			'away' => __( 'Away', METS_TEXT_DOMAIN ),
			'offline' => __( 'Offline', METS_TEXT_DOMAIN )
		);
		
		foreach ( $statuses as $status_key => $status_label ) {
			$wp_admin_bar->add_node( array(
				'id' => 'mets-status-' . $status_key,
				'parent' => 'mets-agent-status',
				'title' => sprintf(
					'<a href="#" class="mets-quick-status" data-status="%s">%s %s</a>',
					esc_attr( $status_key ),
					$status === $status_key ? '✓' : '',
					esc_html( $status_label )
				),
				'meta' => array(
					'html' => '<a href="#" class="mets-quick-status" data-status="' . esc_attr( $status_key ) . '">' . 
							 ( $status === $status_key ? '✓ ' : '' ) . esc_html( $status_label ) . '</a>'
				)
			) );
		}
		
		// Add divider
		$wp_admin_bar->add_node( array(
			'id' => 'mets-status-divider',
			'parent' => 'mets-agent-status',
			'title' => '<hr style="margin: 5px 0; border: none; border-top: 1px solid #ddd;">',
			'meta' => array( 'html' => true )
		) );
		
		// Add quick links
		$wp_admin_bar->add_node( array(
			'id' => 'mets-my-tickets',
			'parent' => 'mets-agent-status',
			'title' => sprintf( __( 'My Tickets (%d)', METS_TEXT_DOMAIN ), $workload ),
			'href' => admin_url( 'admin.php?page=mets-all-tickets&assigned_to=' . $current_user->ID )
		) );
		
		$wp_admin_bar->add_node( array(
			'id' => 'mets-agent-profile',
			'parent' => 'mets-agent-status',
			'title' => __( 'Agent Profile', METS_TEXT_DOMAIN ),
			'href' => admin_url( 'profile.php#mets-agent-profile' )
		) );
		
		// Add JavaScript for quick status update
		add_action( is_admin() ? 'admin_footer' : 'wp_footer', array( $this, 'add_status_script' ) );
	}

	/**
	 * Add dashboard widget
	 *
	 * @since    1.0.0
	 */
	public function add_dashboard_widget() {
		$current_user = wp_get_current_user();
		
		// Check if user has any METS roles
		$mets_roles = array( 'ticket_agent', 'senior_agent', 'ticket_manager', 'support_supervisor' );
		$has_mets_role = false;
		
		foreach ( $mets_roles as $role ) {
			if ( in_array( $role, $current_user->roles ) ) {
				$has_mets_role = true;
				break;
			}
		}
		
		if ( ! $has_mets_role ) {
			return;
		}
		
		wp_add_dashboard_widget(
			'mets_agent_status_widget',
			__( 'My Agent Status', METS_TEXT_DOMAIN ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 *
	 * @since    1.0.0
	 */
	public function render_dashboard_widget() {
		$current_user_id = get_current_user_id();
		$preferences = $this->agent_profile->get_agent_preferences( $current_user_id );
		$availability = $this->agent_profile->get_agent_availability( $current_user_id );
		$skills = $this->agent_profile->get_agent_skills( $current_user_id );
		$languages = $this->agent_profile->get_agent_languages( $current_user_id );
		
		// Get workload info
		$role_manager = METS_Role_Manager::get_instance();
		$workload = $role_manager->get_agent_workload( $current_user_id );
		$performance = $role_manager->get_agent_performance( $current_user_id, 'week' );
		
		?>
		<div class="mets-agent-status-widget">
			<!-- Current Status -->
			<div class="mets-status-section">
				<h4><?php _e( 'Current Status', METS_TEXT_DOMAIN ); ?></h4>
				<div class="mets-status-selector">
					<select id="mets-dashboard-status" style="width: 100%;">
						<option value="available" <?php selected( $preferences['status'], 'available' ); ?>><?php _e( 'Available', METS_TEXT_DOMAIN ); ?></option>
						<option value="busy" <?php selected( $preferences['status'], 'busy' ); ?>><?php _e( 'Busy', METS_TEXT_DOMAIN ); ?></option>
						<option value="away" <?php selected( $preferences['status'], 'away' ); ?>><?php _e( 'Away', METS_TEXT_DOMAIN ); ?></option>
						<option value="offline" <?php selected( $preferences['status'], 'offline' ); ?>><?php _e( 'Offline', METS_TEXT_DOMAIN ); ?></option>
					</select>
					<input type="text" id="mets-status-message" placeholder="<?php esc_attr_e( 'Status message (optional)', METS_TEXT_DOMAIN ); ?>" value="<?php echo esc_attr( $preferences['status_message'] ); ?>" style="width: 100%; margin-top: 5px;" />
					<button class="button button-primary" id="mets-update-status" style="margin-top: 5px;"><?php _e( 'Update Status', METS_TEXT_DOMAIN ); ?></button>
				</div>
			</div>
			
			<!-- Workload Info -->
			<div class="mets-workload-section" style="margin-top: 20px;">
				<h4><?php _e( 'Workload', METS_TEXT_DOMAIN ); ?></h4>
				<div class="mets-workload-info">
					<div class="mets-stat">
						<span class="mets-stat-label"><?php _e( 'Active Tickets:', METS_TEXT_DOMAIN ); ?></span>
						<span class="mets-stat-value"><?php echo esc_html( $workload ); ?> / <?php echo esc_html( $preferences['max_tickets'] ); ?></span>
					</div>
					<div class="mets-workload-bar">
						<div class="mets-workload-fill" style="width: <?php echo esc_attr( min( ( $workload / $preferences['max_tickets'] ) * 100, 100 ) ); ?>%"></div>
					</div>
				</div>
			</div>
			
			<!-- This Week Performance -->
			<div class="mets-performance-section" style="margin-top: 20px;">
				<h4><?php _e( 'This Week', METS_TEXT_DOMAIN ); ?></h4>
				<div class="mets-stat-grid">
					<div class="mets-stat">
						<span class="mets-stat-label"><?php _e( 'Resolved:', METS_TEXT_DOMAIN ); ?></span>
						<span class="mets-stat-value"><?php echo esc_html( $performance['resolved_tickets'] ); ?></span>
					</div>
					<div class="mets-stat">
						<span class="mets-stat-label"><?php _e( 'Avg Response:', METS_TEXT_DOMAIN ); ?></span>
						<span class="mets-stat-value"><?php echo esc_html( $performance['avg_resolution_time'] ); ?>h</span>
					</div>
				</div>
			</div>
			
			<!-- Quick Links -->
			<div class="mets-quick-links" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dcdcde;">
				<a href="<?php echo admin_url( 'admin.php?page=mets-all-tickets&assigned_to=' . $current_user_id ); ?>" class="button"><?php _e( 'My Tickets', METS_TEXT_DOMAIN ); ?></a>
				<a href="<?php echo admin_url( 'profile.php#mets-agent-profile' ); ?>" class="button"><?php _e( 'Edit Profile', METS_TEXT_DOMAIN ); ?></a>
			</div>
		</div>
		
		<style>
			.mets-workload-bar {
				background: #f0f0f1;
				height: 20px;
				border-radius: 10px;
				overflow: hidden;
				margin-top: 10px;
			}
			.mets-workload-fill {
				background: #2271b1;
				height: 100%;
				transition: width 0.3s ease;
			}
			.mets-stat-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 10px;
			}
			.mets-stat {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.mets-stat-label {
				color: #646970;
			}
			.mets-stat-value {
				font-weight: bold;
				color: #2271b1;
			}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			$('#mets-update-status').on('click', function() {
				var status = $('#mets-dashboard-status').val();
				var message = $('#mets-status-message').val();
				
				$.post(ajaxurl, {
					action: 'mets_update_agent_availability',
					nonce: '<?php echo wp_create_nonce( 'mets_admin_nonce' ); ?>',
					status: status,
					message: message
				}, function(response) {
					if (response.success) {
						location.reload();
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Add inline styles
	 *
	 * @since    1.0.0
	 */
	public function add_inline_styles() {
		?>
		<style>
			.mets-status-indicator {
				display: inline-block;
				width: 8px;
				height: 8px;
				border-radius: 50%;
				margin-right: 5px;
			}
			#wpadminbar .mets-workload {
				opacity: 0.7;
				font-size: 0.9em;
			}
			#wpadminbar .mets-quick-status {
				display: block;
				padding: 5px 10px;
				text-decoration: none;
				color: inherit;
			}
			#wpadminbar .mets-quick-status:hover {
				background: rgba(0,0,0,0.1);
			}
		</style>
		<?php
	}

	/**
	 * Add status update script
	 *
	 * @since    1.0.0
	 */
	public function add_status_script() {
		?>
		<script>
		jQuery(document).ready(function($) {
			$('.mets-quick-status').on('click', function(e) {
				e.preventDefault();
				var status = $(this).data('status');
				
				$.post(ajaxurl, {
					action: 'mets_quick_status_update',
					nonce: '<?php echo wp_create_nonce( 'mets_quick_status_nonce' ); ?>',
					status: status
				}, function(response) {
					if (response.success) {
						location.reload();
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler for quick status update
	 *
	 * @since    1.0.0
	 */
	public function ajax_quick_status_update() {
		check_ajax_referer( 'mets_quick_status_nonce', 'nonce' );
		
		$user_id = get_current_user_id();
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		
		if ( ! in_array( $status, array( 'available', 'busy', 'away', 'offline' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status.', METS_TEXT_DOMAIN ) ) );
		}
		
		$preferences = $this->agent_profile->get_agent_preferences( $user_id );
		$preferences['status'] = $status;
		
		update_user_meta( $user_id, 'mets_agent_preferences', $preferences );
		update_user_meta( $user_id, 'mets_last_activity', current_time( 'mysql' ) );
		
		wp_send_json_success( array( 
			'message' => __( 'Status updated successfully.', METS_TEXT_DOMAIN ),
			'status' => $status
		) );
	}
}