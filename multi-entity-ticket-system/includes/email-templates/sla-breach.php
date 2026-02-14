<?php
/**
 * SLA Breach Email Template
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/email-templates
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ticket = $template_data['ticket'];
$sla_rule = $template_data['sla_rule'];
$breach_type = $template_data['breach_type'];
$due_date = $template_data['due_date'];

// Calculate how long overdue
$due_timestamp = strtotime( $due_date );
$current_timestamp = strtotime( current_time( 'mysql' ) );
$overdue_time = $current_timestamp - $due_timestamp;

$hours_overdue = floor( $overdue_time / 3600 );
$minutes_overdue = floor( ( $overdue_time % 3600 ) / 60 );

if ( $hours_overdue > 0 ) {
	$overdue_display = sprintf( __( '%s hours and %s minutes', METS_TEXT_DOMAIN ), $hours_overdue, $minutes_overdue );
} else {
	$overdue_display = sprintf( __( '%s minutes', METS_TEXT_DOMAIN ), $minutes_overdue );
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( sprintf( __( 'SLA BREACH - Ticket #%s', METS_TEXT_DOMAIN ), $ticket->ticket_number ) ); ?></title>
	<style>
		body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
		.email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #d63638; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
		.breach-icon { font-size: 48px; margin-bottom: 10px; }
		.content { background: #fff; padding: 30px; border: 1px solid #ddd; border-top: none; }
		.alert-box { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0; color: #721c24; }
		.critical-alert { background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 15px; margin: 20px 0; color: #0c5460; }
		.ticket-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
		.info-row { display: flex; justify-content: space-between; margin: 5px 0; }
		.info-label { font-weight: bold; color: #555; }
		.breach-time { color: #d63638; font-weight: bold; font-size: 18px; }
		.action-button { 
			display: inline-block; 
			background: #d63638; 
			color: white; 
			padding: 12px 25px; 
			text-decoration: none; 
			border-radius: 5px; 
			margin: 20px 0; 
			font-weight: bold;
		}
		.escalate-button {
			background: #f0b849;
			margin-left: 10px;
		}
		.footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
		.impact-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
	</style>
</head>
<body>
	<div class="email-container">
		<div class="header">
			<div class="breach-icon">🚨</div>
			<h1><?php _e( 'SLA BREACH ALERT', METS_TEXT_DOMAIN ); ?></h1>
			<p><?php echo esc_html( sprintf( __( 'Ticket #%s has exceeded its SLA', METS_TEXT_DOMAIN ), $ticket->ticket_number ) ); ?></p>
		</div>

		<div class="content">
			<div class="alert-box">
				<h3><?php echo esc_html( sprintf( __( '%s SLA BREACHED', METS_TEXT_DOMAIN ), strtoupper( $breach_type ) ) ); ?></h3>
				<p>
					<?php if ( $breach_type === 'response' ) : ?>
						<?php _e( 'This ticket has exceeded the response time SLA. The customer has not received a response within the required timeframe.', METS_TEXT_DOMAIN ); ?>
					<?php else : ?>
						<?php _e( 'This ticket has exceeded the resolution time SLA. The issue has not been resolved within the required timeframe.', METS_TEXT_DOMAIN ); ?>
					<?php endif; ?>
				</p>
				<p class="breach-time">
					<?php echo esc_html( sprintf( __( 'Overdue by: %s', METS_TEXT_DOMAIN ), $overdue_display ) ); ?>
				</p>
			</div>

			<div class="critical-alert">
				<h3><?php _e( '⚠️ IMMEDIATE ACTION REQUIRED', METS_TEXT_DOMAIN ); ?></h3>
				<p><strong><?php _e( 'This SLA breach requires immediate manager attention and escalation.', METS_TEXT_DOMAIN ); ?></strong></p>
			</div>

			<div class="ticket-info">
				<h3><?php _e( 'Ticket Details', METS_TEXT_DOMAIN ); ?></h3>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Ticket Number:', METS_TEXT_DOMAIN ); ?></span>
					<span>#<?php echo esc_html( $ticket->ticket_number ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( $ticket->subject ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Customer:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( $ticket->customer_name . ' (' . $ticket->customer_email . ')' ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Priority:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( ucfirst( $ticket->priority ) ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Status:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( ucfirst( $ticket->status ) ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Created:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ) ); ?></span>
				</div>
				<div class="info-row">
					<span class="info-label"><?php echo esc_html( sprintf( __( '%s Was Due:', METS_TEXT_DOMAIN ), ucfirst( $breach_type ) ) ); ?></span>
					<span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $due_date ) ) ); ?></span>
				</div>
				<?php if ( ! empty( $ticket->assigned_to ) ) : 
					$assigned_user = get_userdata( $ticket->assigned_to );
				?>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Assigned To:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( $assigned_user ? $assigned_user->display_name : __( 'Unknown', METS_TEXT_DOMAIN ) ); ?></span>
				</div>
				<?php endif; ?>
			</div>

			<div class="impact-section">
				<h3><?php _e( 'Business Impact', METS_TEXT_DOMAIN ); ?></h3>
				<ul>
					<li><?php _e( 'Customer satisfaction may be negatively affected', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'Service level agreements are not being met', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'Potential escalation to management may occur', METS_TEXT_DOMAIN ); ?></li>
					<li><?php _e( 'This incident will be recorded in SLA compliance reports', METS_TEXT_DOMAIN ); ?></li>
				</ul>
			</div>

			<div class="alert-box">
				<h3><?php _e( 'Required Actions', METS_TEXT_DOMAIN ); ?></h3>
				<?php if ( $breach_type === 'response' ) : ?>
					<ul>
						<li><strong><?php _e( 'IMMEDIATE: Respond to the customer now', METS_TEXT_DOMAIN ); ?></strong></li>
						<li><?php _e( 'Acknowledge the delay and apologize for the inconvenience', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Provide a clear timeline for resolution', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Escalate to supervisor immediately if additional resources are needed', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				<?php else : ?>
					<ul>
						<li><strong><?php _e( 'IMMEDIATE: Focus all efforts on resolving this ticket', METS_TEXT_DOMAIN ); ?></strong></li>
						<li><?php _e( 'Contact customer to explain the delay and provide updated timeline', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Escalate to technical specialists if additional expertise is required', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Consider temporary workarounds if full resolution is not immediately possible', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				<?php endif; ?>
			</div>

			<div style="text-align: center;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket->id ) ); ?>" class="action-button">
					<?php _e( 'Handle Ticket Immediately', METS_TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-all-tickets' ) ); ?>" class="action-button escalate-button">
					<?php _e( 'View All Tickets', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>

			<div class="ticket-info" style="margin-top: 30px;">
				<h3><?php _e( 'SLA Rule Information', METS_TEXT_DOMAIN ); ?></h3>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Rule Name:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( $sla_rule->name ); ?></span>
				</div>
				<?php if ( ! empty( $sla_rule->response_time ) ) : ?>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Response Time SLA:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( sprintf( __( '%s hours', METS_TEXT_DOMAIN ), $sla_rule->response_time ) ); ?></span>
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $sla_rule->resolution_time ) ) : ?>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Resolution Time SLA:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( sprintf( __( '%s hours', METS_TEXT_DOMAIN ), $sla_rule->resolution_time ) ); ?></span>
				</div>
				<?php endif; ?>
			</div>

			<div class="critical-alert" style="margin-top: 30px;">
				<h3><?php _e( 'Manager Notification', METS_TEXT_DOMAIN ); ?></h3>
				<p><?php _e( 'This SLA breach has been automatically logged and will appear in management reports. Please ensure appropriate follow-up actions are taken and documented.', METS_TEXT_DOMAIN ); ?></p>
			</div>
		</div>

		<div class="footer">
			<p><?php echo esc_html( sprintf( __( 'This is an automated SLA breach alert from %s', METS_TEXT_DOMAIN ), get_bloginfo( 'name' ) ) ); ?></p>
			<p><?php _e( 'Please do not reply to this email. Log into the admin panel to respond to the ticket.', METS_TEXT_DOMAIN ); ?></p>
			<p><strong><?php _e( 'This breach has been recorded for compliance reporting.', METS_TEXT_DOMAIN ); ?></strong></p>
		</div>
	</div>
</body>
</html>