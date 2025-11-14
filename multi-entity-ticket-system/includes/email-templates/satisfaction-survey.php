<?php
/**
 * Email Template: Customer Satisfaction Survey
 *
 * Available variables:
 * {{ticket_number}}, {{ticket_subject}}, {{customer_name}}, {{entity_name}},
 * {{survey_url}}, {{rating_1_url}}, {{rating_2_url}}, {{rating_3_url}},
 * {{rating_4_url}}, {{rating_5_url}}, {{site_name}}, {{resolved_date}}
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_data['subject'] = sprintf( __( '[%s] How was your support experience? - Ticket #%s', METS_TEXT_DOMAIN ), '{{entity_name_safe}}', '{{ticket_number_safe}}' );
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e( 'Customer Satisfaction Survey', METS_TEXT_DOMAIN ); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007cba;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .ticket-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #007cba;
        }
        .ticket-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .rating-section {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .rating-section h2 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 20px;
        }
        .stars {
            display: table;
            margin: 20px auto;
            border-spacing: 10px;
        }
        .star-button {
            display: inline-block;
            width: 70px;
            height: 70px;
            text-decoration: none;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #ddd;
            transition: all 0.3s;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .star-button:hover {
            transform: scale(1.1);
            border-color: #007cba;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .star-button span {
            font-size: 36px;
            line-height: 70px;
            display: block;
        }
        .star-button.star-1 span { color: #d32f2f; }
        .star-button.star-2 span { color: #f57c00; }
        .star-button.star-3 span { color: #fbc02d; }
        .star-button.star-4 span { color: #7cb342; }
        .star-button.star-5 span { color: #388e3c; }
        .rating-labels {
            display: table;
            width: 100%;
            margin-top: 15px;
            text-align: center;
        }
        .rating-labels span {
            display: inline-block;
            width: 70px;
            font-size: 12px;
            color: #666;
            margin: 0 5px;
        }
        .text-link {
            text-align: center;
            margin: 25px 0;
        }
        .text-link a {
            color: #007cba;
            text-decoration: none;
            font-weight: 500;
        }
        .text-link a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #666;
            text-align: center;
        }
        .footer p {
            margin: 10px 0;
        }
        @media only screen and (max-width: 600px) {
            .stars {
                display: block;
            }
            .star-button {
                display: inline-block;
                margin: 5px;
            }
            .rating-labels span {
                display: block;
                margin: 5px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php _e( 'How was your support experience?', METS_TEXT_DOMAIN ); ?></h1>
            <p><?php _e( 'Your feedback helps us improve!', METS_TEXT_DOMAIN ); ?></p>
        </div>

        <p><?php _e( 'Dear', METS_TEXT_DOMAIN ); ?> {{customer_name_safe}},</p>

        <p><?php _e( 'We recently resolved your support ticket and wanted to check in with you. Your satisfaction is our top priority, and we\'d love to hear about your experience.', METS_TEXT_DOMAIN ); ?></p>

        <div class="ticket-info">
            <p><strong><?php _e( 'Ticket:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_number_safe}}</p>
            <p><strong><?php _e( 'Subject:', METS_TEXT_DOMAIN ); ?></strong> {{ticket_subject_safe}}</p>
            <p><strong><?php _e( 'Resolved:', METS_TEXT_DOMAIN ); ?></strong> {{resolved_date_safe}}</p>
        </div>

        <div class="rating-section">
            <h2><?php _e( 'How would you rate your experience?', METS_TEXT_DOMAIN ); ?></h2>
            <p><?php _e( 'Click a star below to rate:', METS_TEXT_DOMAIN ); ?></p>

            <div class="stars">
                <a href="{{rating_1_url_safe}}" class="star-button star-1" title="<?php esc_attr_e( 'Very Dissatisfied', METS_TEXT_DOMAIN ); ?>">
                    <span>★</span>
                </a>
                <a href="{{rating_2_url_safe}}" class="star-button star-2" title="<?php esc_attr_e( 'Dissatisfied', METS_TEXT_DOMAIN ); ?>">
                    <span>★★</span>
                </a>
                <a href="{{rating_3_url_safe}}" class="star-button star-3" title="<?php esc_attr_e( 'Neutral', METS_TEXT_DOMAIN ); ?>">
                    <span>★★★</span>
                </a>
                <a href="{{rating_4_url_safe}}" class="star-button star-4" title="<?php esc_attr_e( 'Satisfied', METS_TEXT_DOMAIN ); ?>">
                    <span>★★★★</span>
                </a>
                <a href="{{rating_5_url_safe}}" class="star-button star-5" title="<?php esc_attr_e( 'Very Satisfied', METS_TEXT_DOMAIN ); ?>">
                    <span>★★★★★</span>
                </a>
            </div>

            <div class="rating-labels">
                <span><?php _e( 'Poor', METS_TEXT_DOMAIN ); ?></span>
                <span><?php _e( 'Fair', METS_TEXT_DOMAIN ); ?></span>
                <span><?php _e( 'Good', METS_TEXT_DOMAIN ); ?></span>
                <span><?php _e( 'Great', METS_TEXT_DOMAIN ); ?></span>
                <span><?php _e( 'Excellent', METS_TEXT_DOMAIN ); ?></span>
            </div>
        </div>

        <div class="text-link">
            <p><small><?php _e( 'Or click here to provide detailed feedback:', METS_TEXT_DOMAIN ); ?></small></p>
            <a href="{{survey_url_safe}}"><?php _e( 'Share your feedback', METS_TEXT_DOMAIN ); ?> →</a>
        </div>

        <p style="text-align: center; color: #666; font-size: 14px; margin-top: 30px;">
            <?php _e( 'This survey takes less than 30 seconds to complete.', METS_TEXT_DOMAIN ); ?>
        </p>

        <div class="footer">
            <p><?php _e( 'Thank you for being a valued customer!', METS_TEXT_DOMAIN ); ?></p>
            <p><strong>{{entity_name_safe}} <?php _e( 'Support Team', METS_TEXT_DOMAIN ); ?></strong></p>

            <hr style="margin: 20px auto; width: 80%; border: none; border-top: 1px solid #eee;">

            <p style="font-size: 11px; color: #999;">
                <?php _e( 'This is an automated message from', METS_TEXT_DOMAIN ); ?> <a href="{{site_url_safe}}" style="color: #007cba;">{{site_name_safe}}</a><br>
                <?php _e( 'Please do not reply directly to this email.', METS_TEXT_DOMAIN ); ?>
            </p>
        </div>
    </div>
</body>
</html>
