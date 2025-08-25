<?php
/**
 * Guest Ticket Access Form Shortcode Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get error message if any
global $mets_guest_access;
$error_message = '';
if ( $mets_guest_access && is_object( $mets_guest_access ) ) {
    $error_message = $mets_guest_access->get_error();
}
?>

<div class="mets-guest-access-shortcode-wrapper">
	<h2><?php echo esc_html( $atts['page_title'] ); ?></h2>
	
	<?php if ( ! empty( $error_message ) ) : ?>
		<div class="mets-alert mets-alert-error">
			<?php echo esc_html( $error_message ); ?>
		</div>
	<?php endif; ?>
	
	<div class="mets-guest-access-form-container">
		<form class="mets-guest-access-form" method="post">
			<?php wp_nonce_field( 'mets_guest_access_action', 'mets_guest_access_nonce' ); ?>
			
			<div class="mets-form-group">
				<label for="customer_email"><?php echo esc_html__( 'Email Address', 'multi-entity-ticket-system' ); ?> <span class="mets-required">*</span></label>
				<input type="email" id="customer_email" name="customer_email" required class="mets-form-control" placeholder="<?php echo esc_attr__( 'your.email@example.com', 'multi-entity-ticket-system' ); ?>">
			</div>
			
			<div class="mets-form-group">
				<label for="ticket_number"><?php echo esc_html__( 'Ticket Number', 'multi-entity-ticket-system' ); ?> <span class="mets-required">*</span></label>
				<input type="text" id="ticket_number" name="ticket_number" required class="mets-form-control" placeholder="<?php echo esc_attr__( 'e.g., TICKET-12345', 'multi-entity-ticket-system' ); ?>">
			</div>
			
			<div class="mets-form-actions">
				<button type="submit" class="mets-btn mets-btn-primary"><?php echo esc_html__( 'Access Ticket', 'multi-entity-ticket-system' ); ?></button>
			</div>
		</form>
	</div>
	
	<div class="mets-guest-access-info">
		<p><?php echo esc_html__( 'ℹ️ No account needed. Enter the ticket number and email used when creating your ticket.', 'multi-entity-ticket-system' ); ?></p>
		<p><?php echo esc_html__( '🔒 Your access is secure and temporary. Sessions expire automatically for security.', 'multi-entity-ticket-system' ); ?></p>
	</div>
</div>