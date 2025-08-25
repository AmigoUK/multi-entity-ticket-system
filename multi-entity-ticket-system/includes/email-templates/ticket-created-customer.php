<?php
/**
 * Email Template: Ticket Created - Customer Notification
 * 
 * Available variables:
 * {{ticket_number}}, {{ticket_subject}}, {{ticket_content}}, {{ticket_url}}, 
 * {{customer_name}}, {{entity_name}}, {{created_date}}, {{site_name}}, {{portal_url}}
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_data['subject'] = sprintf( __( '[%s] Ticket Created: %s', METS_TEXT_DOMAIN ), '{{entity_name_safe}}', '{{ticket_number_safe}}' );
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Created</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .ticket-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #007cba;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php _e( 'Your support ticket has been created', METS_TEXT_DOMAIN ); ?></h1>
    </div>

    <p><?php _e( 'Dear', METS_TEXT_DOMAIN ); ?> {{customer_name_safe}},</p>

    <p><?php _e( 'Thank you for contacting our support team. We have received your ticket and our team will review it shortly.', METS_TEXT_DOMAIN ); ?></p>

    <div class="ticket-info">
        <h3><?php _e( 'Ticket Details', METS_TEXT_DOMAIN ); ?></h3>
        <p><strong><?php _e( 'Ticket Number:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_number_safe}}</p>
        <p><strong><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_subject_safe}}</p>
        <p><strong><?php _e( 'Created:', METS_TEXT_DOMAIN ); ?></strong> {{created_date_safe}}</p>
        <p><strong><?php _e( 'Department:', METS_TEXT_DOMAIN ); ?></strong> {{entity_name_safe}}</p>
    </div>

    <p><?php _e( 'Your message:', METS_TEXT_DOMAIN ); ?></p>
    <blockquote style="background: #f9f9f9; padding: 15px; border-left: 4px solid #007cba; margin: 15px 0;">
        {{ticket_content_html}}
    </blockquote>

    <p><?php _e( 'You can track the progress of your ticket and add additional information by visiting:', METS_TEXT_DOMAIN ); ?></p>
    
    <a href="{{ticket_url_safe}}" class="button"><?php _e( 'View Ticket', METS_TEXT_DOMAIN ); ?></a>

    <p><?php _e( 'We will notify you as soon as there is an update on your ticket.', METS_TEXT_DOMAIN ); ?></p>

    <div class="footer">
        <p><?php _e( 'Best regards,', METS_TEXT_DOMAIN ); ?><br>
        {{entity_name_safe}} <?php _e( 'Support Team', METS_TEXT_DOMAIN ); ?></p>
        
        <hr style="margin: 20px 0;">
        
        <p style="font-size: 12px;">
            <?php _e( 'This is an automated message from', METS_TEXT_DOMAIN ); ?> <a href="{{site_url_safe}}">{{site_name_safe}}</a><br>
            <?php _e( 'Please do not reply directly to this email. Use the ticket link above to respond.', METS_TEXT_DOMAIN ); ?>
        </p>
    </div>
</body>
</html>
EOF < /dev/null