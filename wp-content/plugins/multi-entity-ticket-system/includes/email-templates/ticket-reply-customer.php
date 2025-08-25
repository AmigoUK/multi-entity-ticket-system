<?php
/**
 * Email Template: New Reply - Customer Notification
 * 
 * Available variables:
 * {{ticket_number}}, {{ticket_subject}}, {{ticket_url}}, 
 * {{customer_name}}, {{agent_name}}, {{entity_name}}, {{reply_content}}, 
 * {{reply_date}}, {{site_name}}, {{portal_url}}
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_data['subject'] = sprintf( __( '[%s] New Reply: %s', METS_TEXT_DOMAIN ), '{{entity_name_safe}}', '{{ticket_number_safe}}' );
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reply</title>
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
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .ticket-info {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .reply-content {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
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
        <h1><?php _e( 'Your support team has replied to your ticket', METS_TEXT_DOMAIN ); ?></h1>
    </div>

    <p><?php _e( 'Dear', METS_TEXT_DOMAIN ); ?> {{customer_name_safe}},</p>

    <p><?php _e( 'Good news! Our support team has responded to your ticket. Please review the reply below and let us know if you need any additional assistance.', METS_TEXT_DOMAIN ); ?></p>

    <div class="ticket-info">
        <h3><?php _e( 'Ticket Details', METS_TEXT_DOMAIN ); ?></h3>
        <p><strong><?php _e( 'Ticket Number:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_number_safe}}</p>
        <p><strong><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_subject_safe}}</p>
        <p><strong><?php _e( 'Reply Date:', METS_TEXT_DOMAIN ); ?></strong> {{reply_date_safe}}</p>
        <p><strong><?php _e( 'Replied by:', METS_TEXT_DOMAIN ); ?></strong> {{agent_name_safe}} ({{entity_name_safe}})</p>
    </div>

    <h3><?php _e( 'Support Team Reply:', METS_TEXT_DOMAIN ); ?></h3>
    <div class="reply-content">
        {{reply_content_html}}
    </div>

    <p><?php _e( 'You can view the full conversation and reply to this ticket by visiting:', METS_TEXT_DOMAIN ); ?></p>
    
    <a href="{{ticket_url_safe}}" class="button"><?php _e( 'View & Reply', METS_TEXT_DOMAIN ); ?></a>
    
    <p><?php _e( 'For your convenience, you can also access your ticket directly using this secure link (valid for 48 hours):', METS_TEXT_DOMAIN ); ?></p>
    
    <a href="{{guest_access_url_safe}}" class="button" style="background-color: #28a745;"><?php _e( 'Direct Access to Ticket', METS_TEXT_DOMAIN ); ?></a>

    <p><?php _e( 'If your issue has been resolved, you can mark the ticket as resolved from the ticket page.', METS_TEXT_DOMAIN ); ?></p>

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