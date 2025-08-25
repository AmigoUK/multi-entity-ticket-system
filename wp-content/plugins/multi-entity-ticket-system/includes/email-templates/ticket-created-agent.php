<?php
/**
 * Email Template: Ticket Created - Agent Notification
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes/email-templates
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>New Ticket - {{ticket_number_safe}}</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			max-width: 600px;
			margin: 0 auto;
			padding: 20px;
		}
		.header {
			background: #d63638;
			color: white;
			padding: 20px;
			text-align: center;
			border-radius: 8px 8px 0 0;
		}
		.content {
			background: #f9f9f9;
			padding: 30px;
			border: 1px solid #ddd;
		}
		.ticket-info {
			background: white;
			padding: 20px;
			border-radius: 8px;
			margin: 20px 0;
			border-left: 4px solid #d63638;
		}
		.customer-info {
			background: #e3f2fd;
			padding: 15px;
			border-radius: 4px;
			margin: 15px 0;
		}
		.footer {
			background: #f5f5f5;
			padding: 20px;
			text-align: center;
			border-radius: 0 0 8px 8px;
			font-size: 14px;
			color: #666;
		}
		.button {
			display: inline-block;
			background: #d63638;
			color: white;
			padding: 12px 24px;
			text-decoration: none;
			border-radius: 4px;
			margin: 20px 0;
		}
		.priority-high { border-left-color: #f0b849; }
		.priority-urgent { border-left-color: #d63638; }
		.priority-normal { border-left-color: #007cba; }
		.priority-low { border-left-color: #00a32a; }
		.sla-info {
			background: #fff3e0;
			padding: 15px;
			border-radius: 4px;
			border-left: 4px solid #f57c00;
			margin: 15px 0;
		}
	</style>
</head>
<body>
	<div class="header">
		<h1>New Support Ticket</h1>
		<h2>{{entity_name_safe}}</h2>
	</div>

	<div class="content">
		<p>A new support ticket has been created and requires attention:</p>

		<div class="ticket-info priority-{{priority_safe}}">
			<h3>Ticket #{{ticket_number_safe}}</h3>
			<p><strong>Subject:</strong> {{ticket_subject_safe}}</p>
			<p><strong>Priority:</strong> {{priority_safe}}</p>
			<p><strong>Status:</strong> {{status_safe}}</p>
			<p><strong>Category:</strong> {{category_safe}}</p>
			<p><strong>Created:</strong> {{created_date_safe}}</p>
		</div>

		<div class="customer-info">
			<h4>Customer Information</h4>
			<p><strong>Name:</strong> {{customer_name_safe}}</p>
			<p><strong>Email:</strong> {{customer_email_safe}}</p>
			<?php if ( ! empty( $template_data['customer_phone'] ) ): ?>
			<p><strong>Phone:</strong> {{customer_phone_safe}}</p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $template_data['due_date'] ) ): ?>
		<div class="sla-info">
			<h4>SLA Information</h4>
			<p><strong>Response Due:</strong> {{due_date_safe}}</p>
			<p>This ticket has SLA requirements. Please respond within the specified timeframe.</p>
		</div>
		<?php endif; ?>

		<p><strong>Customer Message:</strong></p>
		<div style="background: white; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
			{{ticket_content_html}}
		</div>

		<p style="text-align: center;">
			<a href="{{admin_ticket_url_safe}}" class="button">View & Respond</a>
		</p>

		<?php if ( ! empty( $template_data['agent_name'] ) ): ?>
		<p><strong>Assigned to:</strong> {{agent_name_safe}}</p>
		<?php else: ?>
		<p><em>This ticket is currently unassigned.</em></p>
		<?php endif; ?>
	</div>

	<div class="footer">
		<p>This is an automated notification from {{entity_name_safe}} support system.</p>
		<p>Login to the admin panel to manage this ticket.</p>
	</div>
</body>
</html>