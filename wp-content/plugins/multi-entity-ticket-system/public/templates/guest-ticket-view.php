<?php
/**
 * Guest Ticket View Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/public/templates
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div class="mets-guest-ticket-view">
    <div class="mets-container">
        <div class="mets-ticket-header">
            <div class="mets-ticket-breadcrumb">
                <a href="<?php echo esc_url( home_url( '/guest-ticket-access/' ) ); ?>" class="mets-back-link">
                    &larr; <?php echo esc_html__( 'Access Another Ticket', 'multi-entity-ticket-system' ); ?>
                </a>
            </div>
            
            <h1><?php echo esc_html( $ticket->ticket_number ); ?>: <?php echo esc_html( $ticket->subject ); ?></h1>
            
            <div class="mets-ticket-meta">
                <span class="mets-status-badge mets-status-<?php echo esc_attr( $ticket->status ); ?>">
                    <?php 
                    $statuses = get_option( 'mets_ticket_statuses', array() );
                    $status_label = isset( $statuses[$ticket->status] ) ? $statuses[$ticket->status]['label'] : ucfirst( str_replace( '_', ' ', $ticket->status ) );
                    echo esc_html( $status_label );
                    ?>
                </span>
                
                <span class="mets-priority-badge mets-priority-<?php echo esc_attr( $ticket->priority ); ?>">
                    <?php 
                    $priorities = get_option( 'mets_ticket_priorities', array() );
                    $priority_label = isset( $priorities[$ticket->priority] ) ? $priorities[$ticket->priority]['label'] : ucfirst( $ticket->priority );
                    echo esc_html( $priority_label );
                    ?>
                </span>
                
                <span class="mets-entity">
                    <?php echo esc_html( $ticket->entity_name ); ?>
                </span>
            </div>
        </div>
        
        <div class="mets-ticket-content">
            <div class="mets-original-message">
                <h2><?php echo esc_html__( 'Original Message', 'multi-entity-ticket-system' ); ?></h2>
                <div class="mets-message-content">
                    <?php echo wp_kses_post( $ticket->description ); ?>
                </div>
                <div class="mets-message-meta">
                    <span><?php echo esc_html__( 'Created:', 'multi-entity-ticket-system' ); ?> <?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ); ?></span>
                </div>
            </div>
            
            <?php if ( ! empty( $replies ) ) : ?>
                <div class="mets-conversation">
                    <h2><?php echo esc_html__( 'Conversation', 'multi-entity-ticket-system' ); ?></h2>
                    
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
        
        <div class="mets-ticket-footer">
            <div class="mets-guest-notice">
                <p><?php echo esc_html__( 'You are viewing this ticket as a guest. This access is temporary and will expire in 48 hours.', 'multi-entity-ticket-system' ); ?></p>
                <p><?php echo esc_html__( 'For continued access, please contact our support team.', 'multi-entity-ticket-system' ); ?></p>
            </div>
        </div>
    </div>
</div>