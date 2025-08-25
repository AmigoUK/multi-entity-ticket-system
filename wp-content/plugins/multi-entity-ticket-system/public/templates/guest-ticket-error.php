<?php
/**
 * Guest Ticket Error Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div class="mets-guest-ticket-access">
    <div class="mets-container">
        <div class="mets-card">
            <div class="mets-card-header">
                <h2><?php echo esc_html__( 'Access Error', 'multi-entity-ticket-system' ); ?></h2>
            </div>
            
            <div class="mets-card-body">
                <div class="mets-alert mets-alert-error">
                    <?php echo esc_html( $error_message ); ?>
                </div>
                
                <p><?php echo esc_html__( 'The access link you used may have expired or is invalid.', 'multi-entity-ticket-system' ); ?></p>
                
                <p><a href="<?php echo esc_url( home_url( '/guest-ticket-access/' ) ); ?>" class="mets-btn mets-btn-primary">
                    <?php echo esc_html__( 'Try Accessing Again', 'multi-entity-ticket-system' ); ?>
                </a></p>
            </div>
            
            <div class="mets-card-footer">
                <p><?php echo esc_html__( 'Need help? Contact our support team.', 'multi-entity-ticket-system' ); ?></p>
            </div>
        </div>
    </div>
</div>