<?php
/**
 * SLA Warning Email Template
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
$warning_type = $template_data['warning_type'];
$due_date = $template_data['due_date'];

// Calculate time remaining
$due_timestamp = strtotime( $due_date );
$current_timestamp = strtotime( current_time( 'mysql' ) );
$time_remaining = $due_timestamp - $current_timestamp;

$hours_remaining = floor( $time_remaining / 3600 );
$minutes_remaining = floor( ( $time_remaining % 3600 ) / 60 );

if ( $time_remaining > 0 ) {
	if ( $hours_remaining > 0 ) {
		$time_display = sprintf( __( '%s hours and %s minutes', METS_TEXT_DOMAIN ), $hours_remaining, $minutes_remaining );
	} else {
		$time_display = sprintf( __( '%s minutes', METS_TEXT_DOMAIN ), $minutes_remaining );
	}
} else {
	$time_display = __( 'OVERDUE', METS_TEXT_DOMAIN );
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( sprintf( __( 'SLA Warning - Ticket #%s', METS_TEXT_DOMAIN ), $ticket->ticket_number ) ); ?></title>
	<style>
		body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
		.email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #f0b849; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
		.warning-icon { font-size: 48px; margin-bottom: 10px; }
		.content { background: #fff; padding: 30px; border: 1px solid #ddd; border-top: none; }
		.alert-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
		.ticket-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
		.info-row { display: flex; justify-content: space-between; margin: 5px 0; }
		.info-label { font-weight: bold; color: #555; }
		.time-critical { color: #d63638; font-weight: bold; font-size: 18px; }
		.action-button { 
			display: inline-block; 
			background: #0073aa; 
			color: white; 
			padding: 12px 25px; 
			text-decoration: none; 
			border-radius: 5px; 
			margin: 20px 0; 
		}
		.footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
	</style>
</head>
<body>
	<div class="email-container">
		<div class="header">
			<div class="warning-icon">⚠️</div>
			<h1><?php _e( 'SLA Warning Alert', METS_TEXT_DOMAIN ); ?></h1>
			<p><?php echo esc_html( sprintf( __( 'Ticket #%s requires immediate attention', METS_TEXT_DOMAIN ), $ticket->ticket_number ) ); ?></p>
		</div>

		<div class="content">
			<div class="alert-box">
				<h3><?php echo esc_html( sprintf( __( '%s SLA Warning', METS_TEXT_DOMAIN ), ucfirst( $warning_type ) ) ); ?></h3>
				<p>
					<?php if ( $warning_type === 'response' ) : ?>
						<?php _e( 'This ticket is approaching the response time SLA deadline and requires immediate action.', METS_TEXT_DOMAIN ); ?>
					<?php else : ?>
						<?php _e( 'This ticket is approaching the resolution time SLA deadline and needs to be resolved soon.', METS_TEXT_DOMAIN ); ?>
					<?php endif; ?>
				</p>
				<p class="time-critical">
					<?php echo esc_html( sprintf( __( 'Time remaining: %s', METS_TEXT_DOMAIN ), $time_display ) ); ?>
				</p>
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
					<span class="info-label"><?php echo esc_html( sprintf( __( '%s Due:', METS_TEXT_DOMAIN ), ucfirst( $warning_type ) ) ); ?></span>
					<span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $due_date ) ) ); ?></span>
				</div>
			</div>

			<div class="alert-box">
				<h3><?php _e( 'Required Actions', METS_TEXT_DOMAIN ); ?></h3>
				<?php if ( $warning_type === 'response' ) : ?>
					<ul>
						<li><?php _e( 'Respond to the customer immediately', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'If unable to provide a solution, acknowledge receipt and provide an update', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Escalate to supervisor if additional support is needed', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				<?php else : ?>
					<ul>
						<li><?php _e( 'Complete the resolution of this ticket', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'If resolution is not possible, escalate immediately', METS_TEXT_DOMAIN ); ?></li>
						<li><?php _e( 'Update the customer with current status and next steps', METS_TEXT_DOMAIN ); ?></li>
					</ul>
				<?php endif; ?>
			</div>

			<div style="text-align: center;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mets-all-tickets&action=edit&ticket_id=' . $ticket->id ) ); ?>" class="action-button">
					<?php _e( 'View Ticket Now', METS_TEXT_DOMAIN ); ?>
				</a>
			</div>

			<div class="ticket-info" style="margin-top: 30px;">
				<h3><?php _e( 'SLA Rule Information', METS_TEXT_DOMAIN ); ?></h3>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Rule Name:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( $sla_rule->name ); ?></span>
				</div>
				<?php if ( ! empty( $sla_rule->response_time_hours ) ) : ?>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Response Time:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( sprintf( __( '%s hours', METS_TEXT_DOMAIN ), $sla_rule->response_time_hours ) ); ?></span>
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $sla_rule->resolution_time_hours ) ) : ?>
				<div class="info-row">
					<span class="info-label"><?php _e( 'Resolution Time:', METS_TEXT_DOMAIN ); ?></span>
					<span><?php echo esc_html( sprintf( __( '%s hours', METS_TEXT_DOMAIN ), $sla_rule->resolution_time_hours ) ); ?></span>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="footer">
			<p><?php echo esc_html( sprintf( __( 'This is an automated SLA warning from %s', METS_TEXT_DOMAIN ), get_bloginfo( 'name' ) ) ); ?></p>
			<p><?php _e( 'Please do not reply to this email. Log into the admin panel to respond to the ticket.', METS_TEXT_DOMAIN ); ?></p>
		</div>
	</div>
</body>
</html>