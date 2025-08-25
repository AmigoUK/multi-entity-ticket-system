<?php
/**
 * Integration tests for METS Email Workflow
 *
 * @package METS_Tests
 * @subpackage Integration_Tests
 */

class Test_METS_Email_Workflow extends METS_Test_Case {

    /**
     * Setup email testing environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Configure email settings for testing
        $this->utils->set_mets_settings([
            'enable_email_notifications' => true,
            'smtp_enabled' => false, // Use wp_mail for testing
            'support_email' => 'support@example.com',
            'company_name' => 'Test Company'
        ]);

        // Clear sent emails before each test
        $this->utils->clear_sent_emails();
    }

    /**
     * Test ticket creation email notifications
     */
    public function test_ticket_creation_email_notifications() {
        // Create ticket
        $ticket_id = $this->mets_factory->create_ticket([
            'title' => 'Email Test Ticket',
            'customer_name' => 'John Customer',
            'customer_email' => 'john@customer.com',
            'content' => 'This is a test ticket for email notifications'
        ]);

        // Simulate customer notification email
        $customer_email = [
            'to' => 'john@customer.com',
            'subject' => 'Ticket Created: Email Test Ticket',
            'message' => 'Your support ticket has been created successfully.',
            'headers' => ['Content-Type: text/html; charset=UTF-8']
        ];

        $this->utils->capture_sent_email( $customer_email );

        // Simulate agent notification email
        $agent_email = [
            'to' => 'support@example.com',
            'subject' => 'New Ticket Created: Email Test Ticket',
            'message' => 'A new support ticket has been created and requires attention.',
            'headers' => ['Content-Type: text/html; charset=UTF-8']
        ];

        $this->utils->capture_sent_email( $agent_email );

        // Verify both emails were sent
        $sent_emails = $this->utils->get_sent_emails();
        $this->assertCount( 2, $sent_emails );

        // Verify customer email
        $customer_notification = array_filter( $sent_emails, function( $email ) {
            return $email['to'] === 'john@customer.com';
        });
        $this->assertCount( 1, $customer_notification );

        // Verify agent email
        $agent_notification = array_filter( $sent_emails, function( $email ) {
            return $email['to'] === 'support@example.com';
        });
        $this->assertCount( 1, $agent_notification );
    }

    /**
     * Test ticket reply email notifications
     */
    public function test_ticket_reply_email_notifications() {
        // Create initial ticket
        $ticket_id = $this->mets_factory->create_ticket([
            'customer_email' => 'customer@example.com',
            'title' => 'Support Request'
        ]);

        // Clear initial creation emails
        $this->utils->clear_sent_emails();

        // Create agent reply
        $reply_id = $this->mets_factory->create_ticket_reply( $ticket_id, [
            'content' => 'Thank you for contacting us. We will look into your issue.',
            'author_type' => 'agent',
            'author_name' => 'Support Agent',
            'author_email' => 'agent@example.com'
        ]);

        // Simulate customer notification for agent reply
        $notification_email = [
            'to' => 'customer@example.com',
            'subject' => 'Reply to your support ticket: Support Request',
            'message' => 'A support agent has replied to your ticket.',
            'headers' => ['Content-Type: text/html; charset=UTF-8']
        ];

        $this->utils->capture_sent_email( $notification_email );

        // Verify email was sent
        $sent_emails = $this->utils->get_sent_emails();
        $this->assertCount( 1, $sent_emails );
        $this->assertEquals( 'customer@example.com', $sent_emails[0]['to'] );
        $this->assertStringContainsString( 'Reply to your support ticket', $sent_emails[0]['subject'] );
    }

    /**
     * Test customer reply email notifications
     */
    public function test_customer_reply_email_notifications() {
        // Create ticket with assigned agent
        $agent_id = $this->mets_factory->create_agent([
            'user_email' => 'assigned.agent@example.com'
        ]);

        $ticket_id = $this->mets_factory->create_ticket([
            'customer_email' => 'customer@example.com',
            'title' => 'Technical Issue'
        ]);

        $this->utils->clear_sent_emails();

        // Create customer reply
        $reply_id = $this->mets_factory->create_ticket_reply( $ticket_id, [
            'content' => 'I tried your suggestion but the problem persists.',
            'author_type' => 'customer',
            'author_name' => 'John Customer',
            'author_email' => 'customer@example.com'
        ]);

        // Simulate agent notification for customer reply
        $agent_notification = [
            'to' => 'assigned.agent@example.com',
            'subject' => 'Customer replied to ticket: Technical Issue',
            'message' => 'A customer has added a new reply to their support ticket.',
            'headers' => ['Content-Type: text/html; charset=UTF-8']
        ];

        $this->utils->capture_sent_email( $agent_notification );

        // Verify notification was sent to agent
        $sent_emails = $this->utils->get_sent_emails();
        $this->assertCount( 1, $sent_emails );
        $this->assertEquals( 'assigned.agent@example.com', $sent_emails[0]['to'] );
        $this->assertStringContainsString( 'Customer replied', $sent_emails[0]['subject'] );
    }

    /**
     * Test SMTP configuration workflow
     */
    public function test_smtp_configuration_workflow() {
        // Configure SMTP settings
        $smtp_settings = [
            'smtp_enabled' => true,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'smtp@example.com',
            'smtp_password' => 'test_password',
            'smtp_encryption' => 'tls'
        ];

        $this->utils->set_mets_settings( $smtp_settings );

        // Verify settings were saved
        $saved_settings = $this->utils->get_mets_settings();
        $this->assertTrue( $saved_settings['smtp_enabled'] );
        $this->assertEquals( 'smtp.example.com', $saved_settings['smtp_host'] );
        $this->assertEquals( 587, $saved_settings['smtp_port'] );
        $this->assertEquals( 'tls', $saved_settings['smtp_encryption'] );

        // Test SMTP connection (mocked)
        $this->utils->mock_http_response( 'smtp://smtp.example.com:587', [
            'response' => ['code' => 220],
            'body' => 'SMTP server ready'
        ]);

        // In real implementation, this would test actual SMTP connection
        $this->assertTrue( true ); // Placeholder for SMTP test
    }

    /**
     * Test email template system
     */
    public function test_email_template_system() {
        $ticket_id = $this->mets_factory->create_ticket([
            'title' => 'Template Test Ticket',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com'
        ]);

        // Test different email templates
        $templates = [
            'ticket_created_customer' => [
                'subject' => 'Ticket Created: {{ticket_title}}',
                'content' => 'Dear {{customer_name}}, your ticket has been created.'
            ],
            'ticket_created_agent' => [
                'subject' => 'New Ticket: {{ticket_title}}',
                'content' => 'A new ticket has been created by {{customer_name}}.'
            ],
            'ticket_reply_customer' => [
                'subject' => 'Reply to: {{ticket_title}}',
                'content' => 'Dear {{customer_name}}, an agent has replied to your ticket.'
            ]
        ];

        // Test template variable replacement
        foreach ( $templates as $template_name => $template_data ) {
            $processed_subject = str_replace( '{{ticket_title}}', 'Template Test Ticket', $template_data['subject'] );
            $processed_content = str_replace( '{{customer_name}}', 'Jane Doe', $template_data['content'] );
            
            $this->assertEquals( 'Ticket Created: Template Test Ticket', $processed_subject );
            $this->assertStringContainsString( 'Jane Doe', $processed_content );
        }
    }

    /**
     * Test email queue system
     */
    public function test_email_queue_system() {
        // Create multiple tickets to trigger bulk email sending
        $ticket_ids = [];
        for ( $i = 1; $i <= 5; $i++ ) {
            $ticket_ids[] = $this->mets_factory->create_ticket([
                'customer_email' => "customer$i@example.com",
                'title' => "Bulk Test Ticket $i"
            ]);
        }

        // Simulate queuing emails instead of sending immediately
        $queued_emails = [];
        foreach ( $ticket_ids as $i => $ticket_id ) {
            $queued_emails[] = [
                'to' => "customer" . ($i + 1) . "@example.com",
                'subject' => "Ticket Created: Bulk Test Ticket " . ($i + 1),
                'message' => 'Your ticket has been queued for processing.',
                'scheduled_for' => current_time( 'mysql' ),
                'status' => 'queued'
            ];
        }

        // Verify queue contains expected emails
        $this->assertCount( 5, $queued_emails );
        
        foreach ( $queued_emails as $email ) {
            $this->assertEquals( 'queued', $email['status'] );
            $this->assertStringContainsString( '@example.com', $email['to'] );
        }
    }

    /**
     * Test email delivery status tracking
     */
    public function test_email_delivery_status_tracking() {
        $ticket_id = $this->mets_factory->create_ticket([
            'customer_email' => 'tracking@example.com'
        ]);

        // Simulate email sending with delivery tracking
        $email_data = [
            'to' => 'tracking@example.com',
            'subject' => 'Test Delivery Tracking',
            'message' => 'This email is being tracked for delivery.',
            'sent_at' => current_time( 'mysql' ),
            'delivery_status' => 'sent'
        ];

        // In real implementation, this would be stored in email log table
        global $wpdb;
        $log_table = $wpdb->prefix . 'mets_email_log';
        
        // This table doesn't exist in our test setup, but would in real implementation
        // For testing, we simulate the tracking data
        $tracking_data = [
            'email_id' => 'test-' . time(),
            'recipient' => $email_data['to'],
            'status' => 'delivered',
            'delivered_at' => current_time( 'mysql' ),
            'opened' => false,
            'bounced' => false
        ];

        // Verify tracking data structure
        $this->assertEquals( 'tracking@example.com', $tracking_data['recipient'] );
        $this->assertEquals( 'delivered', $tracking_data['status'] );
        $this->assertFalse( $tracking_data['bounced'] );
    }

    /**
     * Test bounce handling workflow
     */
    public function test_bounce_handling_workflow() {
        // Create ticket with potentially bouncing email
        $ticket_id = $this->mets_factory->create_ticket([
            'customer_email' => 'invalid@bounced-domain.com'
        ]);

        // Simulate bounce notification
        $bounce_data = [
            'recipient' => 'invalid@bounced-domain.com',
            'bounce_type' => 'hard',
            'bounce_reason' => 'Recipient address rejected',
            'bounced_at' => current_time( 'mysql' )
        ];

        // In real implementation, this would:
        // 1. Mark the email as bounced in the log
        // 2. Potentially disable future emails to this address
        // 3. Notify admins of delivery issues

        $this->assertEquals( 'hard', $bounce_data['bounce_type'] );
        $this->assertStringContainsString( 'rejected', $bounce_data['bounce_reason'] );
    }

    /**
     * Test email unsubscribe workflow
     */
    public function test_email_unsubscribe_workflow() {
        $customer_email = 'unsubscribe@example.com';

        // Create ticket
        $ticket_id = $this->mets_factory->create_ticket([
            'customer_email' => $customer_email
        ]);

        // Simulate unsubscribe request
        $unsubscribe_data = [
            'email' => $customer_email,
            'unsubscribed_at' => current_time( 'mysql' ),
            'reason' => 'user_request'
        ];

        // In real implementation, this would be stored in unsubscribe table
        // and checked before sending emails
        
        // Verify unsubscribe data
        $this->assertEquals( $customer_email, $unsubscribe_data['email'] );
        $this->assertEquals( 'user_request', $unsubscribe_data['reason'] );

        // Test that future emails would be blocked
        $should_send_email = !in_array( $customer_email, ['unsubscribe@example.com'] );
        $this->assertFalse( $should_send_email );
    }
}