<?php
/**
 * My Account Support Tickets Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates/woocommerce
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="mets-wc-support-tickets">
	<div class="mets-tickets-header">
		<h3><?php _e( 'Your Support Tickets', METS_TEXT_DOMAIN ); ?></h3>
		<button type="button" class="button mets-new-ticket-btn">
			<?php _e( 'Create New Ticket', METS_TEXT_DOMAIN ); ?>
		</button>
	</div>

	<?php if ( empty( $tickets ) ) : ?>
		<div class="mets-no-tickets">
			<p><?php _e( 'You have no support tickets yet.', METS_TEXT_DOMAIN ); ?></p>
			<p><?php _e( 'Click "Create New Ticket" above or create a ticket from your order page.', METS_TEXT_DOMAIN ); ?></p>
		</div>
	<?php else : ?>
		<div class="mets-tickets-list">
			<?php foreach ( $tickets as $ticket ) : ?>
				<div class="mets-ticket-item status-<?php echo esc_attr( $ticket->status ); ?> priority-<?php echo esc_attr( $ticket->priority ); ?>">
					<div class="mets-ticket-header">
						<h4>
							<a href="<?php echo esc_url( add_query_arg( 'ticket_id', $ticket->id, wc_get_account_endpoint_url( 'support-tickets' ) ) ); ?>">
								#<?php echo $ticket->id; ?>: <?php echo esc_html( $ticket->subject ); ?>
							</a>
						</h4>
						<div class="mets-ticket-meta">
							<span class="status status-<?php echo esc_attr( $ticket->status ); ?>">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $ticket->status ) ) ); ?>
							</span>
							<span class="priority priority-<?php echo esc_attr( $ticket->priority ); ?>">
								<?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
							</span>
						</div>
					</div>
					
					<div class="mets-ticket-details">
						<div class="mets-ticket-info">
							<div class="info-item">
								<strong><?php _e( 'Entity:', METS_TEXT_DOMAIN ); ?></strong>
								<?php echo esc_html( $ticket->entity_name ); ?>
							</div>
							<?php if ( $ticket->assigned_agent_name ) : ?>
								<div class="info-item">
									<strong><?php _e( 'Assigned to:', METS_TEXT_DOMAIN ); ?></strong>
									<?php echo esc_html( $ticket->assigned_agent_name ); ?>
								</div>
							<?php endif; ?>
							<div class="info-item">
								<strong><?php _e( 'Created:', METS_TEXT_DOMAIN ); ?></strong>
								<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?>
							</div>
							
							<?php
							// Check if this is a WooCommerce order ticket
							$metadata = json_decode( $ticket->metadata, true );
							if ( isset( $metadata['wc_order_id'] ) ) :
							?>
								<div class="info-item">
									<strong><?php _e( 'Related Order:', METS_TEXT_DOMAIN ); ?></strong>
									<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'view-order' ) . $metadata['wc_order_id'] . '/' ); ?>">
										#<?php echo esc_html( $metadata['wc_order_number'] ?? $metadata['wc_order_id'] ); ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
						
						<div class="mets-ticket-actions">
							<a href="<?php echo esc_url( add_query_arg( 'ticket_id', $ticket->id, wc_get_account_endpoint_url( 'support-tickets' ) ) ); ?>" class="button">
								<?php _e( 'View Details', METS_TEXT_DOMAIN ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- New Ticket Modal -->
	<div id="mets-new-ticket-modal" class="mets-modal" style="display: none;">
		<div class="mets-modal-content">
			<div class="mets-modal-header">
				<h3><?php _e( 'Create Support Ticket', METS_TEXT_DOMAIN ); ?></h3>
				<button type="button" class="mets-modal-close">&times;</button>
			</div>
			
			<form id="mets-new-ticket-form">
				<div class="mets-form-group">
					<label for="mets-ticket-subject"><?php _e( 'Subject', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
					<input type="text" id="mets-ticket-subject" name="subject" required maxlength="255">
				</div>
				
				<div class="mets-form-group">
					<label for="mets-ticket-description"><?php _e( 'Description', METS_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
					<textarea id="mets-ticket-description" name="description" required rows="5"></textarea>
				</div>
				
				<div class="mets-form-group">
					<label for="mets-ticket-priority"><?php _e( 'Priority', METS_TEXT_DOMAIN ); ?></label>
					<select id="mets-ticket-priority" name="priority">
						<option value="low"><?php _e( 'Low', METS_TEXT_DOMAIN ); ?></option>
						<option value="medium" selected><?php _e( 'Medium', METS_TEXT_DOMAIN ); ?></option>
						<option value="high"><?php _e( 'High', METS_TEXT_DOMAIN ); ?></option>
						<option value="critical"><?php _e( 'Critical', METS_TEXT_DOMAIN ); ?></option>
					</select>
				</div>
				
				<div class="mets-form-group">
					<label for="mets-ticket-order"><?php _e( 'Related Order (Optional)', METS_TEXT_DOMAIN ); ?></label>
					<select id="mets-ticket-order" name="order_id">
						<option value=""><?php _e( 'Select an order...', METS_TEXT_DOMAIN ); ?></option>
						<?php foreach ( $orders as $order ) : ?>
							<option value="<?php echo $order->get_id(); ?>">
								#<?php echo $order->get_order_number(); ?> - 
								<?php echo date_i18n( get_option( 'date_format' ), $order->get_date_created()->getTimestamp() ); ?> - 
								<?php echo wc_price( $order->get_total() ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="mets-form-actions">
					<button type="button" class="button mets-modal-close"><?php _e( 'Cancel', METS_TEXT_DOMAIN ); ?></button>
					<button type="submit" class="button button-primary"><?php _e( 'Create Ticket', METS_TEXT_DOMAIN ); ?></button>
				</div>
			</form>
		</div>
	</div>

	<?php
	// Show individual ticket details if ticket_id is provided
	if ( isset( $_GET['ticket_id'] ) ) {
		$ticket_id = intval( $_GET['ticket_id'] );
		$selected_ticket = null;
		
		foreach ( $tickets as $ticket ) {
			if ( $ticket->id == $ticket_id ) {
				$selected_ticket = $ticket;
				break;
			}
		}
		
		if ( $selected_ticket ) {
			// Get ticket replies
			global $wpdb;
			$replies = $wpdb->get_results( $wpdb->prepare(
				"SELECT r.*, u.display_name as author_name, u.user_email as author_email
				FROM {$wpdb->prefix}mets_ticket_replies r
				LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
				WHERE r.ticket_id = %d
				ORDER BY r.created_at ASC",
				$ticket_id
			) );
			?>
			
			<div id="mets-ticket-details" class="mets-ticket-details-view">
				<div class="mets-ticket-details-header">
					<h3>#<?php echo $selected_ticket->id; ?>: <?php echo esc_html( $selected_ticket->subject ); ?></h3>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'support-tickets' ) ); ?>" class="button">
						&larr; <?php _e( 'Back to Tickets', METS_TEXT_DOMAIN ); ?>
					</a>
				</div>
				
				<div class="mets-ticket-conversation">
					<!-- Original ticket -->
					<div class="mets-message customer-message">
						<div class="mets-message-header">
							<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
							<span class="mets-message-date">
								<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $selected_ticket->created_at ) ); ?>
							</span>
						</div>
						<div class="mets-message-content">
							<?php echo wp_kses_post( nl2br( $selected_ticket->description ) ); ?>
						</div>
					</div>
					
					<!-- Replies -->
					<?php foreach ( $replies as $reply ) : ?>
						<?php
						$is_customer = ( $reply->user_id == $current_user->ID );
						$message_class = $is_customer ? 'customer-message' : 'agent-message';
						?>
						<div class="mets-message <?php echo $message_class; ?>">
							<div class="mets-message-header">
								<strong><?php echo esc_html( $reply->author_name ?: $reply->author_email ?: __( 'Unknown', METS_TEXT_DOMAIN ) ); ?></strong>
								<span class="mets-message-date">
									<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reply->created_at ) ); ?>
								</span>
							</div>
							<div class="mets-message-content">
								<?php echo wp_kses_post( nl2br( $reply->content ) ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				
				<?php if ( ! in_array( $selected_ticket->status, array( 'closed', 'resolved' ) ) ) : ?>
					<div class="mets-reply-form">
						<h4><?php _e( 'Add Reply', METS_TEXT_DOMAIN ); ?></h4>
						<form id="mets-ticket-reply-form" data-ticket-id="<?php echo $selected_ticket->id; ?>">
							<div class="mets-form-group">
								<textarea name="reply_content" placeholder="<?php _e( 'Type your reply...', METS_TEXT_DOMAIN ); ?>" required rows="4"></textarea>
							</div>
							<div class="mets-form-actions">
								<button type="submit" class="button button-primary"><?php _e( 'Send Reply', METS_TEXT_DOMAIN ); ?></button>
							</div>
						</form>
					</div>
				<?php else : ?>
					<div class="mets-ticket-closed-notice">
						<p><?php _e( 'This ticket has been closed. If you need further assistance, please create a new ticket.', METS_TEXT_DOMAIN ); ?></p>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
	?>
</div>

<style>
.mets-wc-support-tickets {
	max-width: 100%;
	margin: 0 auto;
}

.mets-tickets-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	padding-bottom: 10px;
	border-bottom: 1px solid #ddd;
}

.mets-ticket-item {
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 5px;
	margin-bottom: 15px;
	padding: 15px;
	transition: box-shadow 0.3s ease;
}

.mets-ticket-item:hover {
	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.mets-ticket-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 10px;
}

.mets-ticket-header h4 {
	margin: 0;
	font-size: 16px;
}

.mets-ticket-header h4 a {
	text-decoration: none;
	color: #2271b1;
}

.mets-ticket-meta {
	display: flex;
	gap: 10px;
}

.mets-ticket-meta .status,
.mets-ticket-meta .priority {
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: bold;
	text-transform: uppercase;
}

.status-open { background: #d1ecf1; color: #0c5460; }
.status-in_progress { background: #fff3cd; color: #856404; }
.status-resolved { background: #d4edda; color: #155724; }
.status-closed { background: #f8d7da; color: #721c24; }

.priority-low { background: #e2e3e5; color: #383d41; }
.priority-medium { background: #d1ecf1; color: #0c5460; }
.priority-high { background: #fff3cd; color: #856404; }
.priority-critical { background: #f8d7da; color: #721c24; }

.mets-ticket-details {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
}

.mets-ticket-info {
	flex: 1;
}

.mets-ticket-info .info-item {
	margin-bottom: 5px;
	font-size: 14px;
}

.mets-ticket-actions {
	flex-shrink: 0;
	margin-left: 15px;
}

.mets-no-tickets {
	text-align: center;
	padding: 40px 20px;
	background: #f9f9f9;
	border-radius: 5px;
}

/* Modal styles */
.mets-modal {
	position: fixed;
	z-index: 1000;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0,0,0,0.5);
}

.mets-modal-content {
	background-color: #fefefe;
	margin: 5% auto;
	padding: 0;
	border-radius: 5px;
	width: 90%;
	max-width: 600px;
	max-height: 90vh;
	overflow-y: auto;
}

.mets-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20px;
	background: #f1f1f1;
	border-bottom: 1px solid #ddd;
	border-radius: 5px 5px 0 0;
}

.mets-modal-header h3 {
	margin: 0;
}

.mets-modal-close {
	background: none;
	border: none;
	font-size: 28px;
	font-weight: bold;
	cursor: pointer;
	color: #999;
}

.mets-modal-close:hover {
	color: #000;
}

#mets-new-ticket-form {
	padding: 20px;
}

.mets-form-group {
	margin-bottom: 15px;
}

.mets-form-group label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
}

.mets-form-group input,
.mets-form-group select,
.mets-form-group textarea {
	width: 100%;
	padding: 8px;
	border: 1px solid #ddd;
	border-radius: 3px;
	font-size: 14px;
}

.mets-form-actions {
	display: flex;
	justify-content: flex-end;
	gap: 10px;
	padding-top: 15px;
	border-top: 1px solid #ddd;
}

.required {
	color: #d63638;
}

/* Ticket details view */
.mets-ticket-details-view {
	margin-top: 30px;
	border-top: 2px solid #ddd;
	padding-top: 20px;
}

.mets-ticket-details-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.mets-ticket-conversation {
	margin-bottom: 30px;
}

.mets-message {
	margin-bottom: 20px;
	padding: 15px;
	border-radius: 5px;
}

.customer-message {
	background: #e7f3ff;
	border-left: 4px solid #2271b1;
}

.agent-message {
	background: #f0f0f0;
	border-left: 4px solid #50575e;
}

.mets-message-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10px;
	font-size: 14px;
}

.mets-message-date {
	color: #666;
	font-size: 12px;
}

.mets-message-content {
	line-height: 1.6;
}

.mets-reply-form {
	background: #f9f9f9;
	padding: 20px;
	border-radius: 5px;
}

.mets-ticket-closed-notice {
	background: #f8d7da;
	color: #721c24;
	padding: 15px;
	border-radius: 5px;
	text-align: center;
}

@media (max-width: 768px) {
	.mets-tickets-header {
		flex-direction: column;
		gap: 10px;
		text-align: center;
	}
	
	.mets-ticket-header {
		flex-direction: column;
		gap: 10px;
	}
	
	.mets-ticket-details {
		flex-direction: column;
		gap: 15px;
	}
	
	.mets-ticket-actions {
		margin-left: 0;
	}
	
	.mets-modal-content {
		width: 95%;
		margin: 10% auto;
	}
}
</style>