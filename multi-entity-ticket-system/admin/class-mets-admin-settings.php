<?php
/**
 * Settings management for admin operations
 *
 * Extracted from METS_Admin to reduce God class complexity.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.1.0
 */

class METS_Admin_Settings {

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
	 * Handle settings form submission
	 *
	 * @since    1.0.0
	 */
	public function handle_settings_form_submission() {
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['action'] );
		$tab = isset( $_POST['tab'] ) ? sanitize_text_field( $_POST['tab'] ) : 'statuses';

		if ( $action === 'save_statuses' ) {
			check_admin_referer( 'mets_save_statuses', 'statuses_nonce' );

			$statuses = array();

			// Save existing statuses
			if ( isset( $_POST['statuses'] ) && is_array( $_POST['statuses'] ) ) {
				foreach ( $_POST['statuses'] as $key => $status ) {
					if ( ! empty( $status['label'] ) ) {
						$statuses[ sanitize_key( $key ) ] = array(
							'label' => sanitize_text_field( $status['label'] ),
							'color' => sanitize_hex_color( $status['color'] ),
						);
					}
				}
			}

			// Add new status if provided
			if ( isset( $_POST['new_status'] ) && ! empty( $_POST['new_status']['key'] ) && ! empty( $_POST['new_status']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_status']['key'] );
				if ( ! isset( $statuses[ $new_key ] ) ) {
					$statuses[ $new_key ] = array(
						'label' => sanitize_text_field( $_POST['new_status']['label'] ),
						'color' => sanitize_hex_color( $_POST['new_status']['color'] ),
					);
				}
			}

			// Save to database
			update_option( 'mets_ticket_statuses', $statuses );

			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket statuses saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );

		} elseif ( $action === 'save_general_settings' ) {
			check_admin_referer( 'mets_save_general_settings', 'general_settings_nonce' );

			$settings = array();

			// Save portal header text
			if ( isset( $_POST['portal_header_text'] ) ) {
				$settings['portal_header_text'] = sanitize_textarea_field( $_POST['portal_header_text'] );
			}

			// Save new ticket link text
			if ( isset( $_POST['new_ticket_link_text'] ) ) {
				$settings['new_ticket_link_text'] = sanitize_text_field( $_POST['new_ticket_link_text'] );
			}

			// Save new ticket link URL
			if ( isset( $_POST['new_ticket_link_url'] ) ) {
				$settings['new_ticket_link_url'] = esc_url_raw( $_POST['new_ticket_link_url'] );
			}

			// Save ticket portal URL
			if ( isset( $_POST['ticket_portal_url'] ) ) {
				$settings['ticket_portal_url'] = esc_url_raw( $_POST['ticket_portal_url'] );
			}

			// Save terms and conditions URL
			if ( isset( $_POST['terms_conditions_url'] ) ) {
				$settings['terms_conditions_url'] = esc_url_raw( $_POST['terms_conditions_url'] );
			}

			// Save to database
			update_option( 'mets_general_settings', $settings );

			set_transient( 'mets_admin_notice', array(
				'message' => __( 'General settings saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );

		} elseif ( $action === 'save_priorities' ) {
			check_admin_referer( 'mets_save_priorities', 'priorities_nonce' );

			$priorities = array();

			// Save existing priorities
			if ( isset( $_POST['priorities'] ) && is_array( $_POST['priorities'] ) ) {
				foreach ( $_POST['priorities'] as $key => $priority ) {
					if ( ! empty( $priority['label'] ) ) {
						$priorities[ sanitize_key( $key ) ] = array(
							'label' => sanitize_text_field( $priority['label'] ),
							'color' => sanitize_hex_color( $priority['color'] ),
							'order' => intval( $priority['order'] ),
						);
					}
				}
			}

			// Add new priority if provided
			if ( isset( $_POST['new_priority'] ) && ! empty( $_POST['new_priority']['key'] ) && ! empty( $_POST['new_priority']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_priority']['key'] );
				if ( ! isset( $priorities[ $new_key ] ) ) {
					$priorities[ $new_key ] = array(
						'label' => sanitize_text_field( $_POST['new_priority']['label'] ),
						'color' => sanitize_hex_color( $_POST['new_priority']['color'] ),
						'order' => intval( $_POST['new_priority']['order'] ),
					);
				}
			}

			// Save to database
			update_option( 'mets_ticket_priorities', $priorities );

			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket priorities saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );

		} elseif ( $action === 'save_categories' ) {
			check_admin_referer( 'mets_save_categories', 'categories_nonce' );

			$categories = array();

			// Save existing categories
			if ( isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ) {
				foreach ( $_POST['categories'] as $key => $label ) {
					if ( ! empty( $label ) ) {
						$categories[ sanitize_key( $key ) ] = sanitize_text_field( $label );
					}
				}
			}

			// Add new category if provided
			if ( isset( $_POST['new_category'] ) && ! empty( $_POST['new_category']['key'] ) && ! empty( $_POST['new_category']['label'] ) ) {
				$new_key = sanitize_key( $_POST['new_category']['key'] );
				if ( ! isset( $categories[ $new_key ] ) ) {
					$categories[ $new_key ] = sanitize_text_field( $_POST['new_category']['label'] );
				}
			}

			// Save to database
			update_option( 'mets_ticket_categories', $categories );

			set_transient( 'mets_admin_notice', array(
				'message' => __( 'Ticket categories saved successfully.', METS_TEXT_DOMAIN ),
				'type' => 'success'
			), 45 );

		} elseif ( $action === 'save_workflow' ) {
			check_admin_referer( 'mets_save_workflow', 'workflow_nonce' );

			require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
			$workflow_model = new METS_Workflow_Model();

			// Handle workflow rule creation/update
			if ( isset( $_POST['workflow_rule'] ) ) {
				$rule_data = $_POST['workflow_rule'];

				// Validate required fields
				if ( ! empty( $rule_data['from_status'] ) && ! empty( $rule_data['to_status'] ) && ! empty( $rule_data['allowed_roles'] ) ) {
					$result = $workflow_model->create( $rule_data );

					if ( is_wp_error( $result ) ) {
						set_transient( 'mets_admin_notice', array(
							'message' => $result->get_error_message(),
							'type' => 'error'
						), 45 );
					} else {
						set_transient( 'mets_admin_notice', array(
							'message' => __( 'Workflow rule saved successfully.', METS_TEXT_DOMAIN ),
							'type' => 'success'
						), 45 );
					}
				}
			}

			// Handle rule deletion
			if ( isset( $_POST['delete_rule'] ) && ! empty( $_POST['rule_id'] ) ) {
				$rule_id = intval( $_POST['rule_id'] );
				$result = $workflow_model->delete( $rule_id );

				if ( is_wp_error( $result ) ) {
					set_transient( 'mets_admin_notice', array(
						'message' => $result->get_error_message(),
						'type' => 'error'
					), 45 );
				} else {
					set_transient( 'mets_admin_notice', array(
						'message' => __( 'Workflow rule deleted successfully.', METS_TEXT_DOMAIN ),
						'type' => 'success'
					), 45 );
				}
			}
		} elseif ( $action === 'save_smtp_settings' ) {
			check_admin_referer( 'mets_save_smtp_settings', 'smtp_settings_nonce' );

			$smtp_manager = METS_SMTP_Manager::get_instance();

			// Prepare settings array
			$method = sanitize_text_field( $_POST['smtp_method'] ?? 'wordpress' );
			$settings = array(
				'enabled' => isset( $_POST['smtp_enabled'] ) && $_POST['smtp_enabled'] === '1',
				'method' => $method,
				'provider' => sanitize_text_field( $_POST['smtp_provider'] ?? '' ),
				'host' => sanitize_text_field( $_POST['smtp_host'] ?? '' ),
				'port' => intval( $_POST['smtp_port'] ?? 587 ),
				'encryption' => sanitize_text_field( $_POST['smtp_encryption'] ?? 'tls' ),
				'auth_required' => isset( $_POST['smtp_auth_required'] ) && $_POST['smtp_auth_required'] === '1',
				'username' => sanitize_text_field( $_POST['smtp_username'] ?? '' ),
				'password' => $_POST['smtp_password'] ?? '',
				'from_email' => sanitize_email( $_POST['smtp_from_email'] ?? '' ),
				'from_name' => sanitize_text_field( $_POST['smtp_from_name'] ?? '' ),
				'reply_to' => sanitize_email( $_POST['smtp_reply_to'] ?? '' ),
				'test_email' => sanitize_email( $_POST['smtp_test_email'] ?? '' ),
			);

			// Logic fix: If "Enable SMTP" is checked but method is "wordpress",
			// automatically set method to "smtp" to maintain consistency
			if ( $settings['enabled'] && $settings['method'] === 'wordpress' ) {
				$settings['method'] = 'smtp';
			}

			// Conversely, if method is "smtp" but "Enable SMTP" is unchecked,
			// disable SMTP functionality
			if ( ! $settings['enabled'] && $settings['method'] === 'smtp' ) {
				$settings['enabled'] = false;
				$settings['method'] = 'wordpress';
			}

			// Debug logging (sanitized)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$debug_settings = $settings;
				if ( isset( $debug_settings['password'] ) ) {
					$debug_settings['password'] = str_repeat( '*', strlen( $debug_settings['password'] ) );
				}
				error_log( 'METS SMTP Settings Debug - Saving settings: ' . print_r( $debug_settings, true ) );
			}

			// Save settings
			if ( $smtp_manager->save_global_settings( $settings ) ) {
				// Verify settings were saved correctly (sanitized debug)
				$saved_settings = $smtp_manager->get_global_settings();
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$debug_saved = $saved_settings;
					if ( isset( $debug_saved['password'] ) ) {
						$debug_saved['password'] = str_repeat( '*', strlen( $debug_saved['password'] ) );
					}
					error_log( 'METS SMTP Settings Debug - Settings after save: ' . print_r( $debug_saved, true ) );
				}

				set_transient( 'mets_admin_notice', array(
					'message' => __( 'SMTP settings saved successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			} else {
				// Get validation errors for more specific error message
				$validation = $smtp_manager->validate_settings( $settings );
				$error_message = __( 'Failed to save SMTP settings.', METS_TEXT_DOMAIN );

				if ( ! $validation['valid'] && ! empty( $validation['errors'] ) ) {
					$error_message .= ' ' . implode( ' ', $validation['errors'] );
				}

				set_transient( 'mets_admin_notice', array(
					'message' => $error_message,
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'test_smtp_connection' ) {
			check_admin_referer( 'mets_test_smtp_connection', 'test_smtp_nonce' );

			$smtp_manager = METS_SMTP_Manager::get_instance();

			// Prepare test settings
			$settings = array(
				'enabled' => true,
				'method' => sanitize_text_field( $_POST['smtp_method'] ?? 'smtp' ),
				'provider' => sanitize_text_field( $_POST['smtp_provider'] ?? '' ),
				'host' => sanitize_text_field( $_POST['smtp_host'] ?? '' ),
				'port' => intval( $_POST['smtp_port'] ?? 587 ),
				'encryption' => sanitize_text_field( $_POST['smtp_encryption'] ?? 'tls' ),
				'auth_required' => isset( $_POST['smtp_auth_required'] ) && $_POST['smtp_auth_required'] === '1',
				'username' => sanitize_text_field( $_POST['smtp_username'] ?? '' ),
				'password' => $_POST['smtp_password'] ?? '',
			);

			// Test connection
			$result = $smtp_manager->test_connection( $settings );

			if ( $result['success'] ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'success'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'send_test_email' ) {
			check_admin_referer( 'mets_send_test_email', 'test_email_nonce' );

			$smtp_manager = METS_SMTP_Manager::get_instance();
			$settings = $smtp_manager->get_global_settings();
			$test_email = sanitize_email( $_POST['test_email_address'] ?? '' );

			// Send test email
			$result = $smtp_manager->send_test_email( $settings, $test_email );

			if ( $result['success'] ) {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'success'
				), 45 );
			} else {
				set_transient( 'mets_admin_notice', array(
					'message' => $result['message'],
					'type' => 'error'
				), 45 );
			}
		} elseif ( $action === 'save_n8n_chat_settings' ) {
			check_admin_referer( 'mets_save_n8n_chat_settings', 'n8n_chat_settings_nonce' );

			// Prepare settings array
			$settings = array(
				'enabled' => isset( $_POST['n8n_enabled'] ) && $_POST['n8n_enabled'] === '1',
				'webhook_url' => esc_url_raw( $_POST['n8n_webhook_url'] ?? '' ),
				'position' => sanitize_text_field( $_POST['n8n_position'] ?? 'bottom-right' ),
				'initial_message' => sanitize_textarea_field( $_POST['n8n_initial_message'] ?? '' ),
				'theme_color' => sanitize_hex_color( $_POST['n8n_theme_color'] ?? '#007cba' ),
				'window_title' => sanitize_text_field( $_POST['n8n_window_title'] ?? 'Support Chat' ),
				'subtitle' => sanitize_text_field( $_POST['n8n_subtitle'] ?? '' ),
				'show_on_mobile' => isset( $_POST['n8n_show_on_mobile'] ) && $_POST['n8n_show_on_mobile'] === '1',
				'allowed_pages' => sanitize_text_field( $_POST['n8n_allowed_pages'] ?? 'all' ),
				'specific_pages' => sanitize_text_field( $_POST['n8n_specific_pages'] ?? '' ),
			);

			// Validate webhook URL if chat is enabled
			if ( $settings['enabled'] && empty( $settings['webhook_url'] ) ) {
				set_transient( 'mets_admin_notice', array(
					'message' => __( 'Webhook URL is required when n8n chat is enabled.', METS_TEXT_DOMAIN ),
					'type' => 'error'
				), 45 );
			} else {
				// Save settings
				update_option( 'mets_n8n_chat_settings', $settings );

				set_transient( 'mets_admin_notice', array(
					'message' => __( 'n8n chat settings saved successfully.', METS_TEXT_DOMAIN ),
					'type' => 'success'
				), 45 );
			}
		}

		// Redirect back to settings page
		$redirect_url = admin_url( "admin.php?page=mets-settings&tab={$tab}" );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check for admin notices
		$notice = get_transient( 'mets_admin_notice' );
		if ( $notice ) {
			delete_transient( 'mets_admin_notice' );
			// Use nl2br for line breaks in error messages (especially for SMTP troubleshooting)
			$message = nl2br( esc_html( $notice['message'] ) );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), $message );
		}

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		echo '<div class="wrap">';
		echo '<h1>' . __( 'Ticket System Settings', METS_TEXT_DOMAIN ) . '</h1>';

		// Tab navigation
		echo '<nav class="nav-tab-wrapper">';
		$tabs = array(
			'general'    => __( 'General', METS_TEXT_DOMAIN ),
			'statuses'   => __( 'Statuses', METS_TEXT_DOMAIN ),
			'priorities' => __( 'Priorities', METS_TEXT_DOMAIN ),
			'categories' => __( 'Categories', METS_TEXT_DOMAIN ),
			'workflow'   => __( 'Workflow Rules', METS_TEXT_DOMAIN ),
			'email_smtp' => __( 'Email & SMTP', METS_TEXT_DOMAIN ),
			'n8n_chat'   => __( 'n8n Chat', METS_TEXT_DOMAIN ),
			'shortcodes' => __( 'Shortcodes', METS_TEXT_DOMAIN ),
		);

		foreach ( $tabs as $tab_key => $tab_label ) {
			$active_class = $current_tab === $tab_key ? 'nav-tab-active' : '';
			$tab_url = admin_url( "admin.php?page=mets-settings&tab={$tab_key}" );
			echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . $active_class . '">' . esc_html( $tab_label ) . '</a>';
		}
		echo '</nav>';

		// Tab content
		echo '<div class="tab-content" style="margin-top: 20px;">';
		switch ( $current_tab ) {
			case 'statuses':
				$this->display_statuses_settings();
				break;
			case 'priorities':
				$this->display_priorities_settings();
				break;
			case 'categories':
				$this->display_categories_settings();
				break;
			case 'workflow':
				$this->display_workflow_settings();
				break;
			case 'email_smtp':
				$this->display_smtp_settings();
				break;
			case 'n8n_chat':
				$this->display_n8n_chat_settings();
				break;
			case 'shortcodes':
				$this->display_shortcodes_info();
				break;
			case 'general':
			default:
				$this->display_general_settings();
				break;
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Display general settings
	 *
	 * @since    1.0.0
	 */
	public function display_general_settings() {
		$settings = get_option( 'mets_general_settings', array() );

		// Fallback to defaults if empty
		$new_ticket_link_url = isset( $settings['new_ticket_link_url'] ) ? $settings['new_ticket_link_url'] : '';
		$new_ticket_link_text = isset( $settings['new_ticket_link_text'] ) ? $settings['new_ticket_link_text'] : __( 'Submit a new ticket', METS_TEXT_DOMAIN );
		$portal_header_text = isset( $settings['portal_header_text'] ) ? $settings['portal_header_text'] : __( 'View your support tickets and their current status. Need help?', METS_TEXT_DOMAIN );
		$ticket_portal_url = isset( $settings['ticket_portal_url'] ) ? $settings['ticket_portal_url'] : '';
		$terms_conditions_url = isset( $settings['terms_conditions_url'] ) ? $settings['terms_conditions_url'] : '';

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Customer Portal Settings', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Configure the customer portal display settings including custom links and text.', METS_TEXT_DOMAIN ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="general">
					<?php wp_nonce_field( 'mets_save_general_settings', 'general_settings_nonce' ); ?>
					<input type="hidden" name="action" value="save_general_settings">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="portal_header_text"><?php _e( 'Portal Header Text', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<textarea id="portal_header_text" name="portal_header_text" rows="2" cols="50" class="large-text"><?php echo esc_textarea( $portal_header_text ); ?></textarea>
									<p class="description"><?php _e( 'Text displayed in the customer portal header (before the new ticket link).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="new_ticket_link_text"><?php _e( 'New Ticket Link Text', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="text" id="new_ticket_link_text" name="new_ticket_link_text" value="<?php echo esc_attr( $new_ticket_link_text ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Text displayed for the new ticket submission link.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="new_ticket_link_url"><?php _e( 'New Ticket Link URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="new_ticket_link_url" name="new_ticket_link_url" value="<?php echo esc_attr( $new_ticket_link_url ); ?>" class="regular-text" placeholder="https://example.com/submit-ticket">
									<p class="description"><?php _e( 'Custom URL for the new ticket link. Leave empty to use default JavaScript behavior (show form on same page).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>

					<h3><?php _e( 'Additional Endpoint URLs', METS_TEXT_DOMAIN ); ?></h3>
					<p><?php _e( 'Configure custom URLs for various system endpoints. These can be used in emails, redirects, or custom integrations.', METS_TEXT_DOMAIN ); ?></p>
					<div class="notice notice-info inline">
						<p><strong><?php _e( 'Developer Note:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'These URLs can be accessed programmatically using:', METS_TEXT_DOMAIN ); ?></p>
						<ul style="margin-left: 20px;">
							<li><code>METS_Core::get_ticket_portal_url()</code></li>
							<li><code>METS_Core::get_terms_conditions_url()</code></li>
							<li><code>METS_Core::get_general_setting('custom_key')</code></li>
						</ul>
					</div>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ticket_portal_url"><?php _e( 'Ticket Portal URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="ticket_portal_url" name="ticket_portal_url" value="<?php echo esc_attr( $ticket_portal_url ); ?>" class="regular-text" placeholder="https://example.com/customer-portal">
									<p class="description"><?php _e( 'URL where customers can view their tickets. Used in email notifications and system redirects.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="terms_conditions_url"><?php _e( 'Terms and Conditions URL', METS_TEXT_DOMAIN ); ?></label>
								</th>
								<td>
									<input type="url" id="terms_conditions_url" name="terms_conditions_url" value="<?php echo esc_attr( $terms_conditions_url ); ?>" class="regular-text" placeholder="https://example.com/terms">
									<p class="description"><?php _e( 'URL to your terms and conditions page. Can be referenced in ticket forms and email templates.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Save General Settings', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Display status settings
	 *
	 * @since    1.0.0
	 */
	public function display_statuses_settings() {
		$statuses = get_option( 'mets_ticket_statuses', array() );

		// Fallback to defaults if empty
		if ( empty( $statuses ) ) {
			$statuses = array(
				'new' => array( 'label' => __( 'New', METS_TEXT_DOMAIN ), 'color' => '#007cba' ),
				'open' => array( 'label' => __( 'Open', METS_TEXT_DOMAIN ), 'color' => '#00a32a' ),
				'in_progress' => array( 'label' => __( 'In Progress', METS_TEXT_DOMAIN ), 'color' => '#f0b849' ),
				'resolved' => array( 'label' => __( 'Resolved', METS_TEXT_DOMAIN ), 'color' => '#46b450' ),
				'closed' => array( 'label' => __( 'Closed', METS_TEXT_DOMAIN ), 'color' => '#787c82' ),
			);
		}

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Statuses', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available statuses for tickets. Each status should have a unique key, display label and color.', METS_TEXT_DOMAIN ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="statuses">
					<?php wp_nonce_field( 'mets_save_statuses', 'statuses_nonce' ); ?>
					<input type="hidden" name="action" value="save_statuses">

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 80px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="statuses-list">
							<?php foreach ( $statuses as $key => $status ) : ?>
								<tr>
									<td><input type="text" name="statuses[<?php echo esc_attr( $key ); ?>][key]" value="<?php echo esc_attr( $key ); ?>" class="small-text" readonly></td>
									<td><input type="text" name="statuses[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $status['label'] ); ?>" class="regular-text" required></td>
									<td><input type="color" name="statuses[<?php echo esc_attr( $key ); ?>][color]" value="<?php echo esc_attr( $status['color'] ); ?>" class="color-picker"></td>
									<td><button type="button" class="button button-small remove-status" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Status', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_status_key"><?php _e( 'Status Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_status_key" name="new_status[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., pending', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_status_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_status_label" name="new_status[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Pending Review', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_status_color"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="color" id="new_status_color" name="new_status[color]" value="#0073aa"></td>
							</tr>
						</table>
					</div>

					<?php submit_button( __( 'Save Statuses', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Remove status functionality
			$('.remove-status').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this status?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display priorities settings
	 *
	 * @since    1.0.0
	 */
	public function display_priorities_settings() {
		$priorities = get_option( 'mets_ticket_priorities', array() );

		// Fallback to defaults if empty
		if ( empty( $priorities ) ) {
			$priorities = array(
				'low' => array( 'label' => __( 'Low', METS_TEXT_DOMAIN ), 'color' => '#00a32a', 'order' => 1 ),
				'normal' => array( 'label' => __( 'Normal', METS_TEXT_DOMAIN ), 'color' => '#007cba', 'order' => 2 ),
				'high' => array( 'label' => __( 'High', METS_TEXT_DOMAIN ), 'color' => '#f0b849', 'order' => 3 ),
				'urgent' => array( 'label' => __( 'Urgent', METS_TEXT_DOMAIN ), 'color' => '#d63638', 'order' => 4 ),
			);
		}

		// Sort by order
		uasort( $priorities, function( $a, $b ) {
			return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
		});

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Priorities', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available priorities for tickets. Priorities have an order that determines their escalation level.', METS_TEXT_DOMAIN ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="priorities">
					<?php wp_nonce_field( 'mets_save_priorities', 'priorities_nonce' ); ?>
					<input type="hidden" name="action" value="save_priorities">

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 60px;"><?php _e( 'Order', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="priorities-list">
							<?php foreach ( $priorities as $key => $priority ) : ?>
								<tr>
									<td><input type="number" name="priorities[<?php echo esc_attr( $key ); ?>][order]" value="<?php echo esc_attr( $priority['order'] ?? 1 ); ?>" class="small-text" min="1" max="99"></td>
									<td><input type="text" name="priorities[<?php echo esc_attr( $key ); ?>][key]" value="<?php echo esc_attr( $key ); ?>" class="small-text" readonly></td>
									<td><input type="text" name="priorities[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $priority['label'] ); ?>" class="regular-text" required></td>
									<td><input type="color" name="priorities[<?php echo esc_attr( $key ); ?>][color]" value="<?php echo esc_attr( $priority['color'] ); ?>" class="color-picker"></td>
									<td><button type="button" class="button button-small remove-priority" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Priority', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_priority_key"><?php _e( 'Priority Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_priority_key" name="new_priority[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., critical', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_priority_label" name="new_priority[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Critical', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_color"><?php _e( 'Color', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="color" id="new_priority_color" name="new_priority[color]" value="#d63638"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_priority_order"><?php _e( 'Order', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="number" id="new_priority_order" name="new_priority[order]" value="5" class="small-text" min="1" max="99"></td>
							</tr>
						</table>
					</div>

					<?php submit_button( __( 'Save Priorities', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Remove priority functionality
			$('.remove-priority').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this priority?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display categories settings
	 *
	 * @since    1.0.0
	 */
	public function display_categories_settings() {
		$categories = get_option( 'mets_ticket_categories', array() );

		// Fallback to defaults if empty
		if ( empty( $categories ) ) {
			$categories = array(
				'general' => __( 'General', METS_TEXT_DOMAIN ),
				'technical' => __( 'Technical Support', METS_TEXT_DOMAIN ),
				'billing' => __( 'Billing', METS_TEXT_DOMAIN ),
				'sales' => __( 'Sales Inquiry', METS_TEXT_DOMAIN ),
			);
		}

		?>
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Ticket Categories', METS_TEXT_DOMAIN ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Manage the available categories for tickets. Categories help organize and filter tickets by topic or department.', METS_TEXT_DOMAIN ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="mets-settings">
					<input type="hidden" name="tab" value="categories">
					<?php wp_nonce_field( 'mets_save_categories', 'categories_nonce' ); ?>
					<input type="hidden" name="action" value="save_categories">

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Key', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Label', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 80px;"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody id="categories-list">
							<?php foreach ( $categories as $key => $label ) : ?>
								<tr>
									<td><input type="text" name="categories_keys[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $key ); ?>" class="regular-text" readonly></td>
									<td><input type="text" name="categories[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" required></td>
									<td><button type="button" class="button button-small remove-category" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', METS_TEXT_DOMAIN ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin: 20px 0;">
						<h4><?php _e( 'Add New Category', METS_TEXT_DOMAIN ); ?></h4>
						<table class="form-table">
							<tr>
								<th scope="row"><label for="new_category_key"><?php _e( 'Category Key', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_category_key" name="new_category[key]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., feature_request', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
							<tr>
								<th scope="row"><label for="new_category_label"><?php _e( 'Display Label', METS_TEXT_DOMAIN ); ?></label></th>
								<td><input type="text" id="new_category_label" name="new_category[label]" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Feature Request', METS_TEXT_DOMAIN ); ?>"></td>
							</tr>
						</table>
					</div>

					<?php submit_button( __( 'Save Categories', METS_TEXT_DOMAIN ) ); ?>
				</form>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Remove category functionality
			$('.remove-category').on('click', function() {
				if (confirm('<?php _e( 'Are you sure you want to remove this category?', METS_TEXT_DOMAIN ); ?>')) {
					$(this).closest('tr').remove();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display workflow settings
	 *
	 * @since    1.0.0
	 */
	public function display_workflow_settings() {
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-workflow-model.php';
		$workflow_model = new METS_Workflow_Model();

		// Get existing workflow rules
		$workflow_rules = $workflow_model->get_all();

		// Get statuses, priorities, and categories for dropdowns
		$statuses = get_option( 'mets_ticket_statuses', array() );
		$priorities = get_option( 'mets_ticket_priorities', array() );
		$categories = get_option( 'mets_ticket_categories', array() );

		// Get WordPress roles
		global $wp_roles;
		$all_roles = $wp_roles->roles;

		// Filter to relevant roles
		$ticket_roles = array();
		foreach ( $all_roles as $role_key => $role_data ) {
			if ( isset( $role_data['capabilities'] ) &&
				 ( isset( $role_data['capabilities']['ticket_agent'] ) ||
				   isset( $role_data['capabilities']['ticket_manager'] ) ||
				   isset( $role_data['capabilities']['ticket_admin'] ) ||
				   $role_key === 'administrator' ) ) {
				$ticket_roles[ $role_key ] = $role_data['name'];
			}
		}

		?>
		<div class="workflow-settings">
			<h2><?php _e( 'Workflow Rules', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Define rules for status transitions. Each rule specifies which roles can change from one status to another.', METS_TEXT_DOMAIN ); ?></p>

			<!-- Existing Rules -->
			<?php if ( ! empty( $workflow_rules ) ) : ?>
				<h3><?php _e( 'Existing Rules', METS_TEXT_DOMAIN ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php _e( 'From Status', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'To Status', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Allowed Roles', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Conditions', METS_TEXT_DOMAIN ); ?></th>
							<th scope="col"><?php _e( 'Actions', METS_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $workflow_rules as $rule ) : ?>
							<tr>
								<td>
									<?php
									$from_label = isset( $statuses[ $rule->from_status ] ) ? $statuses[ $rule->from_status ]['label'] : ucfirst( $rule->from_status );
									echo esc_html( $from_label );
									?>
								</td>
								<td>
									<?php
									$to_label = isset( $statuses[ $rule->to_status ] ) ? $statuses[ $rule->to_status ]['label'] : ucfirst( $rule->to_status );
									echo esc_html( $to_label );
									?>
								</td>
								<td>
									<?php
									$allowed_roles = is_array( $rule->allowed_roles ) ? $rule->allowed_roles : array( $rule->allowed_roles );
									$role_names = array();
									foreach ( $allowed_roles as $role ) {
										$role_names[] = isset( $ticket_roles[ $role ] ) ? $ticket_roles[ $role ] : ucfirst( $role );
									}
									echo esc_html( implode( ', ', $role_names ) );
									?>
								</td>
								<td>
									<?php
									$conditions = array();
									if ( ! empty( $rule->priority_id ) ) {
										$conditions[] = __( 'Priority specific', METS_TEXT_DOMAIN );
									}
									if ( ! empty( $rule->category ) ) {
										$conditions[] = __( 'Category specific', METS_TEXT_DOMAIN );
									}
									if ( $rule->requires_note ) {
										$conditions[] = __( 'Note required', METS_TEXT_DOMAIN );
									}
									if ( $rule->auto_assign ) {
										$conditions[] = __( 'Auto-assign', METS_TEXT_DOMAIN );
									}
									echo ! empty( $conditions ) ? esc_html( implode( ', ', $conditions ) ) : '—';
									?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: inline;">
										<input type="hidden" name="page" value="mets-settings">
										<input type="hidden" name="tab" value="workflow">
										<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule->id ); ?>">
										<?php wp_nonce_field( 'mets_save_workflow', 'workflow_nonce' ); ?>
										<input type="hidden" name="action" value="save_workflow">
										<button type="submit" name="delete_rule" value="1" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this workflow rule?', METS_TEXT_DOMAIN ) ); ?>');">
											<?php _e( 'Delete', METS_TEXT_DOMAIN ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Add New Rule Form -->
			<h3><?php _e( 'Add New Rule', METS_TEXT_DOMAIN ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="workflow">
				<?php wp_nonce_field( 'mets_save_workflow', 'workflow_nonce' ); ?>
				<input type="hidden" name="action" value="save_workflow">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="from_status"><?php _e( 'From Status', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<select name="workflow_rule[from_status]" id="from_status" class="regular-text" required>
									<option value=""><?php _e( 'Select Status', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $statuses as $status_key => $status_data ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>">
											<?php echo esc_html( $status_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="to_status"><?php _e( 'To Status', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<select name="workflow_rule[to_status]" id="to_status" class="regular-text" required>
									<option value=""><?php _e( 'Select Status', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $statuses as $status_key => $status_data ) : ?>
										<option value="<?php echo esc_attr( $status_key ); ?>">
											<?php echo esc_html( $status_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php _e( 'Allowed Roles', METS_TEXT_DOMAIN ); ?> <span class="description">(required)</span></label>
							</th>
							<td>
								<?php foreach ( $ticket_roles as $role_key => $role_name ) : ?>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="workflow_rule[allowed_roles][]" value="<?php echo esc_attr( $role_key ); ?>">
										<?php echo esc_html( $role_name ); ?>
									</label>
								<?php endforeach; ?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="priority_id"><?php _e( 'Priority Restriction', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="workflow_rule[priority_id]" id="priority_id" class="regular-text">
									<option value=""><?php _e( 'Any Priority', METS_TEXT_DOMAIN ); ?></option>
									<?php
									$priority_index = 1;
									foreach ( $priorities as $priority_key => $priority_data ) : ?>
										<option value="<?php echo esc_attr( $priority_index ); ?>">
											<?php echo esc_html( $priority_data['label'] ); ?>
										</option>
									<?php
									$priority_index++;
									endforeach; ?>
								</select>
								<p class="description"><?php _e( 'Restrict this rule to tickets with a specific priority (optional).', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="category"><?php _e( 'Category Restriction', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select name="workflow_rule[category]" id="category" class="regular-text">
									<option value=""><?php _e( 'Any Category', METS_TEXT_DOMAIN ); ?></option>
									<?php foreach ( $categories as $category_key => $category_label ) : ?>
										<option value="<?php echo esc_attr( $category_key ); ?>">
											<?php echo esc_html( $category_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php _e( 'Restrict this rule to tickets with a specific category (optional).', METS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label><?php _e( 'Rule Options', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="workflow_rule[auto_assign]" value="1">
									<?php _e( 'Auto-assign ticket to user making the status change', METS_TEXT_DOMAIN ); ?>
								</label>
								<label style="display: block; margin-bottom: 5px;">
									<input type="checkbox" name="workflow_rule[requires_note]" value="1">
									<?php _e( 'Require a note when making this status change', METS_TEXT_DOMAIN ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Add Workflow Rule', METS_TEXT_DOMAIN ), 'primary', 'save_workflow_rule' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display shortcodes information
	 *
	 * @since    1.0.0
	 */
	public function display_shortcodes_info() {
		// Get entities for examples
		require_once METS_PLUGIN_PATH . 'includes/models/class-mets-entity-model.php';
		$entity_model = new METS_Entity_Model();
		$entities = $entity_model->get_all( array( 'status' => 'active', 'limit' => 3 ) );

		// Get categories for examples
		$categories = get_option( 'mets_ticket_categories', array() );
		$category_keys = array_keys( array_slice( $categories, 0, 3, true ) );
		?>
		<div class="shortcodes-info">
			<h2><?php _e( 'Available Shortcodes', METS_TEXT_DOMAIN ); ?></h2>
			<p><?php _e( 'Use these shortcodes to add ticket functionality to your pages and posts.', METS_TEXT_DOMAIN ); ?></p>

			<!-- Ticket Form Shortcode -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Ticket Submission Form', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Display a public ticket submission form on any page or post.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Basic Usage', METS_TEXT_DOMAIN ); ?></h4>
					<div class="shortcode-example">
						<code>[ticket_form]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
					</div>
					<p class="description"><?php _e( 'Shows a complete ticket form with all active entities available for selection.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Parameters', METS_TEXT_DOMAIN ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Parameter', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Default', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>entity</strong></td>
								<td><?php _e( 'Pre-select a specific entity by slug. Users won\'t see the entity dropdown.', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'None', METS_TEXT_DOMAIN ); ?></td>
							</tr>
							<tr>
								<td><strong>require_login</strong></td>
								<td><?php _e( 'Require users to be logged in to submit tickets.', METS_TEXT_DOMAIN ); ?></td>
								<td>no</td>
							</tr>
							<tr>
								<td><strong>categories</strong></td>
								<td><?php _e( 'Limit available categories (comma-separated list of category keys).', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'All categories', METS_TEXT_DOMAIN ); ?></td>
							</tr>
							<tr>
								<td><strong>success_message</strong></td>
								<td><?php _e( 'Custom message shown after successful ticket submission.', METS_TEXT_DOMAIN ); ?></td>
								<td><?php _e( 'Default message', METS_TEXT_DOMAIN ); ?></td>
							</tr>
						</tbody>
					</table>

					<h4><?php _e( 'Examples', METS_TEXT_DOMAIN ); ?></h4>

					<?php if ( ! empty( $entities ) ) : ?>
						<div class="shortcode-example">
							<strong><?php _e( 'Pre-selected Entity:', METS_TEXT_DOMAIN ); ?></strong><br>
							<code>[ticket_form entity="<?php echo esc_attr( $entities[0]->slug ); ?>"]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form entity=&quot;<?php echo esc_attr( $entities[0]->slug ); ?>&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
							<p class="description"><?php printf( __( 'Form will be locked to the "%s" entity.', METS_TEXT_DOMAIN ), esc_html( $entities[0]->name ) ); ?></p>
						</div>
					<?php endif; ?>

					<div class="shortcode-example">
						<strong><?php _e( 'Logged-in Users Only:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_form require_login="yes"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form require_login=&quot;yes&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Form will only be shown to logged-in users.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<?php if ( ! empty( $category_keys ) ) : ?>
						<div class="shortcode-example">
							<strong><?php _e( 'Limited Categories:', METS_TEXT_DOMAIN ); ?></strong><br>
							<code>[ticket_form categories="<?php echo esc_attr( implode( ',', $category_keys ) ); ?>"]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form categories=&quot;<?php echo esc_attr( implode( ',', $category_keys ) ); ?>&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
							<p class="description"><?php _e( 'Form will only show specified categories in the dropdown.', METS_TEXT_DOMAIN ); ?></p>
						</div>
					<?php endif; ?>

					<div class="shortcode-example">
						<strong><?php _e( 'Complete Example:', METS_TEXT_DOMAIN ); ?></strong><br>
						<?php if ( ! empty( $entities ) && ! empty( $category_keys ) ) : ?>
							<code>[ticket_form entity="<?php echo esc_attr( $entities[0]->slug ); ?>" categories="<?php echo esc_attr( implode( ',', array_slice( $category_keys, 0, 2 ) ) ); ?>" success_message="Thank you! We'll respond within 24 hours."]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form entity=&quot;<?php echo esc_attr( $entities[0]->slug ); ?>&quot; categories=&quot;<?php echo esc_attr( implode( ',', array_slice( $category_keys, 0, 2 ) ) ); ?>&quot; success_message=&quot;Thank you! We'll respond within 24 hours.&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<?php else : ?>
							<code>[ticket_form require_login="yes" success_message="Thank you! We'll respond within 24 hours."]</code>
							<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_form require_login=&quot;yes&quot; success_message=&quot;Thank you! We'll respond within 24 hours.&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<?php endif; ?>
						<p class="description"><?php _e( 'Combines multiple parameters for a customized form.', METS_TEXT_DOMAIN ); ?></p>
					</div>
				</div>
			</div>

			<!-- Customer Portal Shortcode -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Customer Portal', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Display a customer portal where logged-in users can view and manage their tickets.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Basic Usage', METS_TEXT_DOMAIN ); ?></h4>
					<div class="shortcode-example">
						<code>[ticket_portal]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
					</div>
					<p class="description"><?php _e( 'Shows a list of tickets for the logged-in user with filtering and pagination.', METS_TEXT_DOMAIN ); ?></p>

					<h4><?php _e( 'Parameters', METS_TEXT_DOMAIN ); ?></h4>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col" style="width: 120px;"><?php _e( 'Parameter', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?></th>
								<th scope="col" style="width: 100px;"><?php _e( 'Default', METS_TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong>show_closed</strong></td>
								<td><?php _e( 'Include closed tickets in the initial view.', METS_TEXT_DOMAIN ); ?></td>
								<td>no</td>
							</tr>
							<tr>
								<td><strong>per_page</strong></td>
								<td><?php _e( 'Number of tickets to show per page.', METS_TEXT_DOMAIN ); ?></td>
								<td>10</td>
							</tr>
							<tr>
								<td><strong>allow_new_ticket</strong></td>
								<td><?php _e( 'Show links to submit new tickets.', METS_TEXT_DOMAIN ); ?></td>
								<td>yes</td>
							</tr>
						</tbody>
					</table>

					<h4><?php _e( 'Examples', METS_TEXT_DOMAIN ); ?></h4>

					<div class="shortcode-example">
						<strong><?php _e( 'Show Closed Tickets:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal show_closed="yes"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal show_closed=&quot;yes&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Portal will include closed tickets in the initial view.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="shortcode-example">
						<strong><?php _e( 'Custom Pagination:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal per_page="20"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal per_page=&quot;20&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Show 20 tickets per page instead of the default 10.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="shortcode-example">
						<strong><?php _e( 'Complete Example:', METS_TEXT_DOMAIN ); ?></strong><br>
						<code>[ticket_portal show_closed="yes" per_page="15" allow_new_ticket="no"]</code>
						<button type="button" class="button button-small copy-shortcode" data-shortcode="[ticket_portal show_closed=&quot;yes&quot; per_page=&quot;15&quot; allow_new_ticket=&quot;no&quot;]"><?php _e( 'Copy', METS_TEXT_DOMAIN ); ?></button>
						<p class="description"><?php _e( 'Portal with all tickets, 15 per page, without new ticket links.', METS_TEXT_DOMAIN ); ?></p>
					</div>

					<h4><?php _e( 'Features', METS_TEXT_DOMAIN ); ?></h4>
					<ul style="list-style: disc; margin-left: 20px;">
						<li><?php _e( '<strong>Ticket List:</strong> View all submitted tickets with status, priority, and last update info', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Filtering:</strong> Filter tickets by status and toggle closed tickets', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Pagination:</strong> Automatic pagination for large ticket lists', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Ticket Details:</strong> Click any ticket to view full conversation history', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Reply System:</strong> Add replies directly from the customer portal', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Security:</strong> Users can only see their own tickets based on email address', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				</div>
			</div>

			<!-- Tips Section -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Tips & Best Practices', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<ul style="list-style: disc; margin-left: 20px;">
						<li><?php _e( '<strong>Page vs Post:</strong> Shortcodes work in both pages and posts. For permanent forms, use a dedicated page.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Entity Selection:</strong> Use the entity parameter on department-specific pages to pre-select the appropriate team.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Categories:</strong> Limit categories to reduce confusion and guide users to the right support channel.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Login Requirement:</strong> Consider requiring login for internal support forms or premium features.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Customer Portal:</strong> The ticket_portal shortcode requires users to be logged in and shows only their own tickets.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Portal Pages:</strong> Create dedicated "My Account" or "Support Portal" pages for the best customer experience.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Custom Messages:</strong> Use success_message to provide specific instructions or set expectations.', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( '<strong>Styling:</strong> The forms are responsive and inherit your theme styles. Add custom CSS using the provided classes if needed.', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<style>
		.shortcode-example {
			background: #f1f1f1;
			padding: 10px;
			margin: 10px 0;
			border-left: 4px solid #007cba;
			position: relative;
		}
		.shortcode-example code {
			background: none;
			padding: 0;
			display: inline-block;
			margin-right: 10px;
			font-weight: bold;
		}
		.copy-shortcode {
			float: right;
		}
		.coming-soon {
			font-size: 12px;
			color: #666;
			font-weight: normal;
		}
		.shortcodes-info h4 {
			margin-top: 20px;
			margin-bottom: 10px;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('.copy-shortcode').on('click', function() {
				var shortcode = $(this).data('shortcode');
				var $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(shortcode).select();
				document.execCommand('copy');
				$temp.remove();

				var $button = $(this);
				var originalText = $button.text();
				$button.text('<?php echo esc_js( __( 'Copied!', METS_TEXT_DOMAIN ) ); ?>');
				setTimeout(function() {
					$button.text(originalText);
				}, 2000);
			});
		});
		</script>
		<?php
	}

	/**
	 * Display SMTP settings tab
	 *
	 * @since    1.0.0
	 */
	public function display_smtp_settings() {
		$smtp_manager = METS_SMTP_Manager::get_instance();
		$settings = $smtp_manager->get_global_settings();

		require_once METS_PLUGIN_PATH . 'includes/smtp/class-mets-smtp-providers.php';
		$providers = METS_SMTP_Providers::get_providers();
		?>
		<div class="wrap">
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_save_smtp_settings', 'smtp_settings_nonce' ); ?>
				<input type="hidden" name="action" value="save_smtp_settings">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="email_smtp">

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'SMTP Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Enable SMTP', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smtp_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php _e( 'Enable SMTP for email delivery', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, all plugin emails will be sent using SMTP instead of WordPress default mail.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="smtp-toggle-row">
								<th scope="row"><?php _e( 'Email Method', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_method" id="smtp_method">
										<option value="wordpress" <?php selected( $settings['method'], 'wordpress' ); ?>><?php _e( 'WordPress Default', METS_TEXT_DOMAIN ); ?></option>
										<option value="smtp" <?php selected( $settings['method'], 'smtp' ); ?>><?php _e( 'SMTP', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'Choose email delivery method. SMTP is recommended for reliable delivery.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox smtp-settings-section">
					<h3 class="hndle"><span><?php _e( 'SMTP Server Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Provider', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_provider" id="smtp_provider">
										<option value="custom" <?php selected( $settings['provider'], 'custom' ); ?>><?php _e( 'Custom SMTP Server', METS_TEXT_DOMAIN ); ?></option>
										<optgroup label="<?php _e( 'Popular Email Providers', METS_TEXT_DOMAIN ); ?>">
											<?php foreach ( $providers as $key => $provider ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['provider'], $key ); ?>>
													<?php echo esc_html( $provider['name'] ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									</select>
									<p class="description"><?php _e( 'Select a popular email provider for auto-configuration, or choose "Custom SMTP Server" to manually configure any SMTP server.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'SMTP Host', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_host" value="<?php echo esc_attr( $settings['host'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP server hostname (e.g., smtp.gmail.com)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'SMTP Port', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" name="smtp_port" value="<?php echo esc_attr( $settings['port'] ); ?>" class="small-text">
									<p class="description"><?php _e( 'SMTP server port (587 for TLS, 465 for SSL, 25 for no encryption)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="custom-smtp-row">
								<th scope="row"><?php _e( 'Encryption', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="smtp_encryption">
										<option value="none" <?php selected( $settings['encryption'], 'none' ); ?>><?php _e( 'None', METS_TEXT_DOMAIN ); ?></option>
										<option value="tls" <?php selected( $settings['encryption'], 'tls' ); ?>><?php _e( 'TLS', METS_TEXT_DOMAIN ); ?></option>
										<option value="ssl" <?php selected( $settings['encryption'], 'ssl' ); ?>><?php _e( 'SSL', METS_TEXT_DOMAIN ); ?></option>
									</select>
									<p class="description"><?php _e( 'TLS is recommended for most servers.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox smtp-settings-section">
					<h3 class="hndle"><span><?php _e( 'Authentication', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Authentication Required', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smtp_auth_required" value="1" <?php checked( $settings['auth_required'] ); ?>>
										<?php _e( 'Enable SMTP authentication', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'Most SMTP servers require authentication.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="smtp-auth-row">
								<th scope="row"><?php _e( 'Username', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_username" value="<?php echo esc_attr( $settings['username'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP username (usually your email address)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr class="smtp-auth-row">
								<th scope="row"><?php _e( 'Password', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="password" name="smtp_password" value="<?php echo esc_attr( $settings['password'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'SMTP password (stored encrypted)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<!-- Gmail Setup Instructions -->
							<tr class="gmail-setup-info smtp-auth-row gmail-hidden">
								<td colspan="2">
									<div class="notice notice-info" style="margin: 0; padding: 12px;">
										<h4 style="margin-top: 0;"><?php _e( '📧 Gmail Setup Instructions', METS_TEXT_DOMAIN ); ?></h4>
										<p><?php _e( '<strong>Important:</strong> Gmail requires App Passwords for SMTP authentication. Follow these steps:', METS_TEXT_DOMAIN ); ?></p>
										<ol style="margin-left: 20px;">
											<li><?php _e( '<strong>Enable 2-Factor Authentication</strong> in your Google Account (required for App Passwords)', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Generate an App Password:</strong>', METS_TEXT_DOMAIN ); ?>
												<br><a href="https://myaccount.google.com/apppasswords" target="_blank" class="button button-small" style="margin-top: 5px;"><?php _e( '🔗 Open Google App Passwords', METS_TEXT_DOMAIN ); ?></a>
											</li>
											<li><?php _e( 'Select "Mail" as the app and "Other" as the device', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( 'Copy the generated 16-character App Password', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Use your full Gmail address as Username</strong> (e.g., you@gmail.com)', METS_TEXT_DOMAIN ); ?></li>
											<li><?php _e( '<strong>Use the App Password (not your regular Gmail password) in the Password field above</strong>', METS_TEXT_DOMAIN ); ?></li>
										</ol>
										<p style="margin-bottom: 0;"><strong><?php _e( 'Note:', METS_TEXT_DOMAIN ); ?></strong> <?php _e( 'Never use your regular Gmail password for SMTP. It will not work and may compromise your account security.', METS_TEXT_DOMAIN ); ?></p>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Email Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'From Email', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="email" name="smtp_from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Email address for outgoing emails', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'From Name', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="smtp_from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Name for outgoing emails', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Reply-To Email', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="email" name="smtp_reply_to" value="<?php echo esc_attr( $settings['reply_to'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Email address for replies (optional)', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<?php submit_button( __( 'Save SMTP Settings', METS_TEXT_DOMAIN ) ); ?>
			</form>

			<!-- Test Section -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Test SMTP Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<form method="post" action="" style="display: inline-block; margin-right: 15px;">
						<?php wp_nonce_field( 'mets_test_smtp_connection', 'test_smtp_nonce' ); ?>
						<input type="hidden" name="action" value="test_smtp_connection">
						<input type="hidden" name="page" value="mets-settings">
						<input type="hidden" name="tab" value="email_smtp">
						<input type="hidden" name="smtp_method" id="test_smtp_method" value="<?php echo esc_attr( $settings['method'] ); ?>">
						<input type="hidden" name="smtp_provider" id="test_smtp_provider" value="<?php echo esc_attr( $settings['provider'] ); ?>">
						<input type="hidden" name="smtp_host" id="test_smtp_host" value="<?php echo esc_attr( $settings['host'] ); ?>">
						<input type="hidden" name="smtp_port" id="test_smtp_port" value="<?php echo esc_attr( $settings['port'] ); ?>">
						<input type="hidden" name="smtp_encryption" id="test_smtp_encryption" value="<?php echo esc_attr( $settings['encryption'] ); ?>">
						<input type="hidden" name="smtp_auth_required" id="test_smtp_auth_required" value="<?php echo $settings['auth_required'] ? '1' : '0'; ?>">
						<input type="hidden" name="smtp_username" id="test_smtp_username" value="<?php echo esc_attr( $settings['username'] ); ?>">
						<input type="hidden" name="smtp_password" id="test_smtp_password" value="<?php echo esc_attr( $settings['password'] ); ?>">
						<?php submit_button( __( 'Test Connection', METS_TEXT_DOMAIN ), 'secondary', 'test_connection', false ); ?>
					</form>

					<form method="post" action="" style="display: inline-block;">
						<?php wp_nonce_field( 'mets_send_test_email', 'test_email_nonce' ); ?>
						<input type="hidden" name="action" value="send_test_email">
						<input type="hidden" name="page" value="mets-settings">
						<input type="hidden" name="tab" value="email_smtp">
						<input type="email" name="test_email_address" value="<?php echo esc_attr( $settings['test_email'] ); ?>" placeholder="<?php _e( 'Test email address', METS_TEXT_DOMAIN ); ?>" style="margin-right: 10px;">
						<?php submit_button( __( 'Send Test Email', METS_TEXT_DOMAIN ), 'secondary', 'send_test', false ); ?>
					</form>

					<p class="description" style="margin-top: 10px;">
						<?php _e( 'Use "Test Connection" to verify SMTP server connectivity. Use "Send Test Email" to test complete email delivery.', METS_TEXT_DOMAIN ); ?>
					</p>
				</div>
			</div>
		</div>

		<style>
		.smtp-toggle-row,
		.smtp-settings-section,
		.smtp-auth-row {
			display: none;
		}
		.smtp-enabled .smtp-toggle-row,
		.smtp-enabled.smtp-method-smtp .smtp-settings-section {
			display: table-row;
		}
		.smtp-enabled.smtp-method-smtp .smtp-settings-section {
			display: block;
		}
		.smtp-enabled.smtp-method-smtp.smtp-auth-enabled .smtp-auth-row {
			display: table-row;
		}
		.custom-smtp-row {
			display: none;
		}
		.smtp-provider-custom .custom-smtp-row {
			display: table-row;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Ensure we're only running this on the SMTP settings tab
			var currentTab = '<?php echo isset( $_GET['tab'] ) ? esc_js( $_GET['tab'] ) : 'general'; ?>';
			if (currentTab !== 'email_smtp') {
				return;
			}

			function toggleSMTPSections() {
				var $wrap = $('.wrap');
				var smtpEnabled = $('input[name="smtp_enabled"]').is(':checked');
				var smtpMethod = $('#smtp_method').val();
				var authRequired = $('input[name="smtp_auth_required"]').is(':checked');
				var provider = $('#smtp_provider').val();

				$wrap.toggleClass('smtp-enabled', smtpEnabled);
				$wrap.toggleClass('smtp-method-smtp', smtpMethod === 'smtp');
				$wrap.toggleClass('smtp-auth-enabled', authRequired);
				$wrap.toggleClass('smtp-provider-custom', provider === 'custom');

				// Show/hide Gmail setup instructions - only within the SMTP form
				var $gmailInfo = $('form .gmail-setup-info');
				if (provider === 'gmail' && smtpEnabled && smtpMethod === 'smtp' && authRequired) {
					$gmailInfo.removeClass('gmail-hidden').addClass('gmail-visible');
				} else {
					$gmailInfo.removeClass('gmail-visible').addClass('gmail-hidden');
				}

				// Update test form values
				$('#test_smtp_method').val($('#smtp_method').val());
				$('#test_smtp_provider').val($('#smtp_provider').val());
				$('#test_smtp_host').val($('input[name="smtp_host"]').val());
				$('#test_smtp_port').val($('input[name="smtp_port"]').val());
				$('#test_smtp_encryption').val($('select[name="smtp_encryption"]').val());
				$('#test_smtp_auth_required').val($('input[name="smtp_auth_required"]').is(':checked') ? '1' : '0');
				$('#test_smtp_username').val($('input[name="smtp_username"]').val());
				$('#test_smtp_password').val($('input[name="smtp_password"]').val());
			}

			// Handle provider selection
			$('#smtp_provider').on('change', function() {
				var provider = $(this).val();
				if (provider !== 'custom') {
					// Auto-fill provider settings
					var providers = <?php echo json_encode( $providers ); ?>;
					if (providers[provider]) {
						$('input[name="smtp_host"]').val(providers[provider].host);
						$('input[name="smtp_port"]').val(providers[provider].port);
						$('select[name="smtp_encryption"]').val(providers[provider].encryption);
					}
				}
				toggleSMTPSections();
			});

			// Handle checkbox and select changes
			$('input[name="smtp_enabled"], #smtp_method, input[name="smtp_auth_required"]').on('change', toggleSMTPSections);
			$('input[name="smtp_host"], input[name="smtp_port"], select[name="smtp_encryption"], input[name="smtp_username"], input[name="smtp_password"]').on('input change', toggleSMTPSections);

			// Initial toggle
			toggleSMTPSections();
		});
		</script>

		<style>
		.gmail-setup-info.gmail-hidden {
			display: none !important;
		}
		.gmail-setup-info.gmail-visible {
			display: table-row !important;
		}
		</style>
		<?php
	}

	/**
	 * Display n8n chat settings
	 *
	 * @since    1.0.0
	 */
	public function display_n8n_chat_settings() {
		// Get settings with defaults
		$defaults = array(
			'enabled' => false,
			'webhook_url' => '',
			'position' => 'bottom-right',
			'initial_message' => __( 'Hello! How can we help you today?', METS_TEXT_DOMAIN ),
			'theme_color' => '#007cba',
			'window_title' => __( 'Support Chat', METS_TEXT_DOMAIN ),
			'subtitle' => __( 'We typically reply within minutes', METS_TEXT_DOMAIN ),
			'show_on_mobile' => true,
			'allowed_pages' => 'all',
			'specific_pages' => '',
		);
		$settings = wp_parse_args( get_option( 'mets_n8n_chat_settings', array() ), $defaults );
		?>
		<div class="wrap">
			<form method="post" action="">
				<?php wp_nonce_field( 'mets_save_n8n_chat_settings', 'n8n_chat_settings_nonce' ); ?>
				<input type="hidden" name="action" value="save_n8n_chat_settings">
				<input type="hidden" name="page" value="mets-settings">
				<input type="hidden" name="tab" value="n8n_chat">

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'n8n Chat Configuration', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Enable n8n Chat', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="n8n_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php _e( 'Enable n8n chat widget on your website', METS_TEXT_DOMAIN ); ?>
									</label>
									<p class="description"><?php _e( 'When enabled, the n8n chat widget will appear on your website.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Webhook URL', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></th>
								<td>
									<input type="url" name="n8n_webhook_url" value="<?php echo esc_attr( $settings['webhook_url'] ); ?>" class="regular-text" placeholder="https://your-domain.com/webhook/..." required>
									<p class="description"><?php _e( 'Enter your n8n webhook URL. Example: https://mws02-51886.wykr.es/webhook/b24f624e-eb28-438b-9eac-063c1edfd042/chat', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Chat Widget Appearance', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Position', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<select name="n8n_position">
										<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>><?php _e( 'Bottom Right', METS_TEXT_DOMAIN ); ?></option>
										<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>><?php _e( 'Bottom Left', METS_TEXT_DOMAIN ); ?></option>
										<option value="top-right" <?php selected( $settings['position'], 'top-right' ); ?>><?php _e( 'Top Right', METS_TEXT_DOMAIN ); ?></option>
										<option value="top-left" <?php selected( $settings['position'], 'top-left' ); ?>><?php _e( 'Top Left', METS_TEXT_DOMAIN ); ?></option>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Theme Color', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="color" name="n8n_theme_color" value="<?php echo esc_attr( $settings['theme_color'] ); ?>">
									<p class="description"><?php _e( 'Choose the primary color for the chat widget.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Window Title', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_window_title" value="<?php echo esc_attr( $settings['window_title'] ); ?>" class="regular-text">
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Subtitle', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_subtitle" value="<?php echo esc_attr( $settings['subtitle'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Appears below the window title.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Initial Message', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<textarea name="n8n_initial_message" rows="3" class="large-text"><?php echo esc_textarea( $settings['initial_message'] ); ?></textarea>
									<p class="description"><?php _e( 'The first message users see when they open the chat.', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php _e( 'Show on Mobile', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="n8n_show_on_mobile" value="1" <?php checked( $settings['show_on_mobile'] ); ?>>
										<?php _e( 'Display chat widget on mobile devices', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Display Settings', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'Show Chat On', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="all" <?php checked( $settings['allowed_pages'], 'all' ); ?>>
										<?php _e( 'All pages', METS_TEXT_DOMAIN ); ?>
									</label><br>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="specific" <?php checked( $settings['allowed_pages'], 'specific' ); ?>>
										<?php _e( 'Specific pages only', METS_TEXT_DOMAIN ); ?>
									</label><br>
									<label>
										<input type="radio" name="n8n_allowed_pages" value="except" <?php checked( $settings['allowed_pages'], 'except' ); ?>>
										<?php _e( 'All pages except', METS_TEXT_DOMAIN ); ?>
									</label>
								</td>
							</tr>

							<tr class="n8n-specific-pages" style="<?php echo $settings['allowed_pages'] === 'all' ? 'display:none;' : ''; ?>">
								<th scope="row"><?php _e( 'Page IDs', METS_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="n8n_specific_pages" value="<?php echo esc_attr( $settings['specific_pages'] ); ?>" class="regular-text">
									<p class="description"><?php _e( 'Enter comma-separated page IDs (e.g., 12,34,56).', METS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Integration Guide', METS_TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'To set up n8n chat:', METS_TEXT_DOMAIN ); ?></p>
						<ol>
							<li><?php _e( 'Create a workflow in n8n with a Webhook node', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Copy the webhook URL from n8n', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Paste it in the Webhook URL field above', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Configure the appearance settings as desired', METS_TEXT_DOMAIN ); ?></li>
							<li><?php _e( 'Save settings and the chat widget will appear on your site', METS_TEXT_DOMAIN ); ?></li>
						</ol>
						<p>
							<a href="https://www.npmjs.com/package/@n8n/chat" target="_blank" class="button button-secondary">
								<?php _e( 'View n8n Chat Documentation', METS_TEXT_DOMAIN ); ?>
							</a>
						</p>
					</div>
				</div>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Settings', METS_TEXT_DOMAIN ); ?>">
				</p>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Toggle specific pages field
			$('input[name="n8n_allowed_pages"]').on('change', function() {
				if ($(this).val() === 'all') {
					$('.n8n-specific-pages').hide();
				} else {
					$('.n8n-specific-pages').show();
				}
			});

			// Validate webhook URL
			$('form').on('submit', function(e) {
				var webhookUrl = $('input[name="n8n_webhook_url"]').val();
				if ($('input[name="n8n_enabled"]').is(':checked') && !webhookUrl) {
					e.preventDefault();
					alert('<?php _e( 'Please enter a valid webhook URL', METS_TEXT_DOMAIN ); ?>');
					$('input[name="n8n_webhook_url"]').focus();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Display WooCommerce settings page
	 *
	 * @since    1.0.0
	 */
	public function display_woocommerce_settings_page() {
		require_once METS_PLUGIN_PATH . 'admin/class-mets-woocommerce-settings.php';
		$wc_settings = new METS_WooCommerce_Settings();
		$wc_settings->display_settings_page();
	}
}
