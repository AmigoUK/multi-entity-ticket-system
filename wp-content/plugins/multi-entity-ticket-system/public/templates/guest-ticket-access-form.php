<?php
/**
 * Guest Ticket Access Form Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get error message if any
global $mets_guest_access;
$error_message = '';
if ( $mets_guest_access && is_object( $mets_guest_access ) ) {
    $error_message = $mets_guest_access->get_error();
}
?>

<div class="mets-guest-ticket-access">
    <div class="mets-container">
        <div class="mets-card">
            <div class="mets-card-header">
                <h2><?php echo esc_html__( 'Access Your Support Ticket', 'multi-entity-ticket-system' ); ?></h2>
                <p><?php echo esc_html__( 'Enter your email and ticket number to view your support ticket.', 'multi-entity-ticket-system' ); ?></p>
            </div>
            
            <div class="mets-card-body">
                <?php if ( ! empty( $error_message ) ) : ?>
                    <div class="mets-alert mets-alert-error">
                        <?php echo esc_html( $error_message ); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="mets-guest-access-form">
                    <?php wp_nonce_field( 'mets_guest_access_action', 'mets_guest_access_nonce' ); ?>
                    
                    <div class="mets-form-group">
                        <label for="customer_email"><?php echo esc_html__( 'Email Address', 'multi-entity-ticket-system' ); ?> <span class="mets-required">*</span></label>
                        <input type="email" 
                               id="customer_email" 
                               name="customer_email" 
                               required 
                               class="mets-form-control"
                               placeholder="<?php echo esc_attr__( 'your.email@example.com', 'multi-entity-ticket-system' ); ?>">
                    </div>
                    
                    <div class="mets-form-group">
                        <label for="ticket_number"><?php echo esc_html__( 'Ticket Number', 'multi-entity-ticket-system' ); ?> <span class="mets-required">*</span></label>
                        <input type="text" 
                               id="ticket_number" 
                               name="ticket_number" 
                               required 
                               class="mets-form-control"
                               placeholder="<?php echo esc_attr__( 'e.g. TICKET-12345', 'multi-entity-ticket-system' ); ?>">
                    </div>
                    
                    <div class="mets-form-actions">
                        <button type="submit" class="mets-btn mets-btn-primary">
                            <?php echo esc_html__( 'Access Ticket', 'multi-entity-ticket-system' ); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="mets-card-footer">
                <p><?php echo esc_html__( 'Need help? Contact our support team.', 'multi-entity-ticket-system' ); ?></p>
            </div>
        </div>
    </div>
</div>