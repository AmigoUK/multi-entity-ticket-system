<?php
/**
 * WooCommerce Settings Page
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @since      1.0.0
 */

/**
 * The WooCommerce Settings class.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/admin
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_WooCommerce_Settings {

	/**
	 * Display the WooCommerce settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_ticket_system' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', METS_TEXT_DOMAIN ) );
		}

		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="wrap">';
			echo '<h1>' . __( 'WooCommerce Integration', METS_TEXT_DOMAIN ) . '</h1>';
			echo '<div class="notice notice-error"><p>';
			echo __( 'WooCommerce plugin is not active. Please install and activate WooCommerce to use this integration.', METS_TEXT_DOMAIN );
			echo '</p></div>';
			echo '</div>';
			return;
		}

		// Handle form submission
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['mets_wc_settings_nonce'], 'mets_wc_settings' ) ) {
			$this->save_settings();
		}

		// Get current settings
		$settings = METS_WooCommerce_Integration::get_settings();

		?>
		<div class="wrap">
			<h1><?php _e( 'WooCommerce Integration', METS_TEXT_DOMAIN ); ?></h1>
			
			<p class="description">
				<?php _e( 'Configure how the ticket system integrates with WooCommerce orders and products.', METS_TEXT_DOMAIN ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'mets_wc_settings', 'mets_wc_settings_nonce' ); ?>
				
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="wc_enabled"><?php _e( 'Enable Integration', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="wc_enabled" name="wc_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
								<label for="wc_enabled"><?php _e( 'Enable WooCommerce integration features', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'When enabled, customers can create support tickets from their orders and My Account page.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="show_support_tab"><?php _e( 'Support Tab', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="show_support_tab" name="show_support_tab" value="1" <?php checked( $settings['show_support_tab'] ); ?>>
								<label for="show_support_tab"><?php _e( 'Add Support Tickets tab to My Account page', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Customers will see a "Support Tickets" tab in their My Account page.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="show_order_button"><?php _e( 'Order Support Button', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="show_order_button" name="show_order_button" value="1" <?php checked( $settings['show_order_button'] ); ?>>
								<label for="show_order_button"><?php _e( 'Show support button on order details page', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Customers can create tickets directly from their order details page.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="default_priority"><?php _e( 'Default Priority', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select id="default_priority" name="default_priority">
									<option value="low" <?php selected( $settings['default_priority'], 'low' ); ?>><?php _e( 'Low', METS_TEXT_DOMAIN ); ?></option>
									<option value="medium" <?php selected( $settings['default_priority'], 'medium' ); ?>><?php _e( 'Medium', METS_TEXT_DOMAIN ); ?></option>
									<option value="high" <?php selected( $settings['default_priority'], 'high' ); ?>><?php _e( 'High', METS_TEXT_DOMAIN ); ?></option>
									<option value="critical" <?php selected( $settings['default_priority'], 'critical' ); ?>><?php _e( 'Critical', METS_TEXT_DOMAIN ); ?></option>
								</select>
								<p class="description">
									<?php _e( 'Default priority for tickets created through WooCommerce integration.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h2><?php _e( 'Auto-Create Tickets', METS_TEXT_DOMAIN ); ?></h2>
				<p class="description">
					<?php _e( 'Automatically create support tickets when orders reach specific statuses.', METS_TEXT_DOMAIN ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label><?php _e( 'Order Statuses', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<?php
								$order_statuses = wc_get_order_statuses();
								$auto_statuses = $settings['auto_ticket_statuses'];
								
								foreach ( $order_statuses as $status => $label ) :
									$status_key = str_replace( 'wc-', '', $status );
								?>
									<label style="display: block; margin-bottom: 8px;">
										<input type="checkbox" name="auto_ticket_statuses[]" value="<?php echo esc_attr( $status_key ); ?>" 
											<?php checked( in_array( $status_key, $auto_statuses ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description">
									<?php _e( 'Select order statuses that should automatically create support tickets.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h2><?php _e( 'Product Support', METS_TEXT_DOMAIN ); ?></h2>
				<p class="description">
					<?php _e( 'Configure product-specific support features.', METS_TEXT_DOMAIN ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="show_product_support_tab"><?php _e( 'Product Support Tab', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="show_product_support_tab" name="show_product_support_tab" value="1" 
									<?php checked( $settings['show_product_support_tab'] ?? true ); ?>>
								<label for="show_product_support_tab"><?php _e( 'Add Support tab to product pages', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Customers can create product-specific support tickets from the product page.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="show_product_support_button"><?php _e( 'Product Support Button', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="show_product_support_button" name="show_product_support_button" value="1" 
									<?php checked( $settings['show_product_support_button'] ?? true ); ?>>
								<label for="show_product_support_button"><?php _e( 'Show support button on product summary', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Display a "Get Product Support" button on the product summary area.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h2><?php _e( 'Email Notifications', METS_TEXT_DOMAIN ); ?></h2>
				<p class="description">
					<?php _e( 'Configure email notifications for WooCommerce-related tickets.', METS_TEXT_DOMAIN ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="notify_on_auto_ticket"><?php _e( 'Auto-Ticket Notifications', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="notify_on_auto_ticket" name="notify_on_auto_ticket" value="1" 
									<?php checked( $settings['notify_on_auto_ticket'] ?? true ); ?>>
								<label for="notify_on_auto_ticket"><?php _e( 'Send email notifications for auto-created tickets', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Customers will receive email notifications when tickets are automatically created for their orders.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="include_order_details"><?php _e( 'Include Order Details', METS_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="include_order_details" name="include_order_details" value="1" 
									<?php checked( $settings['include_order_details'] ?? true ); ?>>
								<label for="include_order_details"><?php _e( 'Include order information in ticket emails', METS_TEXT_DOMAIN ); ?></label>
								<p class="description">
									<?php _e( 'Email notifications will include relevant order details for context.', METS_TEXT_DOMAIN ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>

			<div class="mets-wc-integration-info">
				<h2><?php _e( 'Integration Status', METS_TEXT_DOMAIN ); ?></h2>
				
				<div class="mets-status-grid">
					<div class="mets-status-item">
						<h4><?php _e( 'WooCommerce Version', METS_TEXT_DOMAIN ); ?></h4>
						<p><?php echo WC()->version; ?></p>
					</div>
					
					<div class="mets-status-item">
						<h4><?php _e( 'WooCommerce Entity', METS_TEXT_DOMAIN ); ?></h4>
						<p>
							<?php
							$wc_entity = $this->get_wc_entity();
							if ( $wc_entity ) {
								echo sprintf( __( 'Entity #%d: %s', METS_TEXT_DOMAIN ), $wc_entity->id, $wc_entity->name );
							} else {
								echo __( 'Not created yet (will be created automatically)', METS_TEXT_DOMAIN );
							}
							?>
						</p>
					</div>
					
					<div class="mets-status-item">
						<h4><?php _e( 'WooCommerce Tickets', METS_TEXT_DOMAIN ); ?></h4>
						<p>
							<?php
							$wc_ticket_count = $this->get_wc_ticket_count();
							echo sprintf( _n( '%d ticket', '%d tickets', $wc_ticket_count, METS_TEXT_DOMAIN ), $wc_ticket_count );
							?>
						</p>
					</div>
					
					<div class="mets-status-item">
						<h4><?php _e( 'Endpoints', METS_TEXT_DOMAIN ); ?></h4>
						<p>
							<?php
							$endpoints = array( 'support-tickets', 'create-ticket' );
							$active_endpoints = array();
							
							foreach ( $endpoints as $endpoint ) {
								if ( get_option( 'woocommerce_myaccount_' . str_replace( '-', '_', $endpoint ) . '_endpoint', $endpoint ) ) {
									$active_endpoints[] = $endpoint;
								}
							}
							
							if ( ! empty( $active_endpoints ) ) {
								echo implode( ', ', $active_endpoints );
							} else {
								echo __( 'Default endpoints active', METS_TEXT_DOMAIN );
							}
							?>
						</p>
					</div>
				</div>
			</div>

			<div class="mets-wc-actions">
				<h2><?php _e( 'Maintenance Actions', METS_TEXT_DOMAIN ); ?></h2>
				
				<p class="description">
					<?php _e( 'Use these tools to maintain the WooCommerce integration.', METS_TEXT_DOMAIN ); ?>
				</p>
				
				<div class="mets-action-buttons">
					<button type="button" class="button" id="mets-flush-rewrite-rules">
						<?php _e( 'Flush Rewrite Rules', METS_TEXT_DOMAIN ); ?>
					</button>
					<button type="button" class="button" id="mets-create-wc-entity">
						<?php _e( 'Create WooCommerce Entity', METS_TEXT_DOMAIN ); ?>
					</button>
					<button type="button" class="button button-secondary" id="mets-test-wc-integration">
						<?php _e( 'Test Integration', METS_TEXT_DOMAIN ); ?>
					</button>
				</div>
			</div>
		</div>

		<style>
		.mets-status-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin: 20px 0;
		}
		
		.mets-status-item {
			padding: 15px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 5px;
		}
		
		.mets-status-item h4 {
			margin: 0 0 10px 0;
			font-size: 14px;
			color: #23282d;
		}
		
		.mets-status-item p {
			margin: 0;
			font-weight: bold;
			color: #0073aa;
		}
		
		.mets-action-buttons {
			margin: 15px 0;
		}
		
		.mets-action-buttons .button {
			margin-right: 10px;
			margin-bottom: 10px;
		}
		
		.mets-wc-integration-info,
		.mets-wc-actions {
			margin-top: 30px;
			padding: 20px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 5px;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('#mets-flush-rewrite-rules').on('click', function() {
				$(this).prop('disabled', true).text('Flushing...');
				
				$.post(ajaxurl, {
					action: 'mets_flush_rewrite_rules',
					nonce: '<?php echo wp_create_nonce( 'mets_flush_rewrite_rules' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('Rewrite rules flushed successfully!');
					} else {
						alert('Error: ' + (response.data || 'Unknown error'));
					}
				}).always(function() {
					$('#mets-flush-rewrite-rules').prop('disabled', false).text('Flush Rewrite Rules');
				});
			});
			
			$('#mets-create-wc-entity').on('click', function() {
				$(this).prop('disabled', true).text('Creating...');
				
				$.post(ajaxurl, {
					action: 'mets_create_wc_entity',
					nonce: '<?php echo wp_create_nonce( 'mets_create_wc_entity' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('WooCommerce entity created successfully!');
						location.reload();
					} else {
						alert('Error: ' + (response.data || 'Unknown error'));
					}
				}).always(function() {
					$('#mets-create-wc-entity').prop('disabled', false).text('Create WooCommerce Entity');
				});
			});
			
			$('#mets-test-wc-integration').on('click', function() {
				$(this).prop('disabled', true).text('Testing...');
				
				$.post(ajaxurl, {
					action: 'mets_test_wc_integration',
					nonce: '<?php echo wp_create_nonce( 'mets_test_wc_integration' ); ?>'
				}, function(response) {
					if (response.success) {
						alert('Integration test completed:\n\n' + response.data.join('\n'));
					} else {
						alert('Test failed: ' + (response.data || 'Unknown error'));
					}
				}).always(function() {
					$('#mets-test-wc-integration').prop('disabled', false).text('Test Integration');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @since    1.0.0
	 */
	private function save_settings() {
		$settings = array(
			'enabled' => isset( $_POST['wc_enabled'] ),
			'show_support_tab' => isset( $_POST['show_support_tab'] ),
			'show_order_button' => isset( $_POST['show_order_button'] ),
			'default_priority' => sanitize_text_field( $_POST['default_priority'] ?? 'medium' ),
			'auto_ticket_statuses' => array_map( 'sanitize_text_field', $_POST['auto_ticket_statuses'] ?? array() ),
			'show_product_support_tab' => isset( $_POST['show_product_support_tab'] ),
			'show_product_support_button' => isset( $_POST['show_product_support_button'] ),
			'notify_on_auto_ticket' => isset( $_POST['notify_on_auto_ticket'] ),
			'include_order_details' => isset( $_POST['include_order_details'] )
		);

		$updated = METS_WooCommerce_Integration::update_settings( $settings );

		if ( $updated ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . 
				__( 'WooCommerce integration settings saved successfully!', METS_TEXT_DOMAIN ) . 
				'</p></div>';
			
			// Flush rewrite rules when endpoints are changed
			flush_rewrite_rules();
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . 
				__( 'Failed to save settings. Please try again.', METS_TEXT_DOMAIN ) . 
				'</p></div>';
		}
	}

	/**
	 * Get WooCommerce entity
	 *
	 * @since    1.0.0
	 * @return   object|null    Entity object or null
	 */
	private function get_wc_entity() {
		global $wpdb;
		return $wpdb->get_row(
			"SELECT * FROM {$wpdb->prefix}mets_entities 
			WHERE name = 'WooCommerce' AND type = 'company'"
		);
	}

	/**
	 * Get WooCommerce ticket count
	 *
	 * @since    1.0.0
	 * @return   int    Ticket count
	 */
	private function get_wc_ticket_count() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}mets_tickets 
			WHERE source IN ('woocommerce', 'woocommerce_auto')
			OR JSON_EXTRACT(metadata, '$.wc_order_id') IS NOT NULL"
		);
	}
}