<?php
/**
 * Test data factory for generating realistic test data
 *
 * @package METS_Tests
 */

class METS_Test_Data_Factory {

    /**
     * Sample customer names
     *
     * @var array
     */
    private $customer_names = [
        'John Smith',
        'Sarah Johnson',
        'Michael Brown',
        'Emma Davis',
        'William Wilson',
        'Olivia Garcia',
        'James Martinez',
        'Isabella Anderson',
        'Robert Taylor',
        'Sophia Thomas'
    ];

    /**
     * Sample ticket titles
     *
     * @var array
     */
    private $ticket_titles = [
        'Login issues with my account',
        'Payment not processing correctly',
        'Feature request: Dark mode',
        'Bug report: Page not loading',
        'Need help with setup',
        'Billing question about subscription',
        'Password reset not working',
        'Mobile app crashes on startup',
        'Data export functionality',
        'Integration with third-party service'
    ];

    /**
     * Sample ticket content
     *
     * @var array
     */
    private $ticket_content = [
        'I am experiencing difficulties accessing my account. When I try to log in, I get an error message saying "Invalid credentials" even though I am sure my password is correct.',
        'I attempted to make a payment but the transaction failed. The payment page shows an error and my credit card was not charged. Please help me complete this purchase.',
        'Would it be possible to add a dark mode option to the interface? Many users would appreciate this feature for better usability in low-light conditions.',
        'There seems to be a bug where the main page does not load properly. I see only a blank white screen instead of the expected content.',
        'I am new to the platform and need assistance setting up my account properly. Could someone guide me through the initial configuration process?',
        'I have a question about my current subscription plan. The billing amount seems different from what I expected. Can you please review my account?',
        'The password reset feature is not working correctly. I click the reset link in the email but nothing happens when I submit the new password.',
        'The mobile application crashes immediately when I try to open it. This happens consistently on my iPhone. Please investigate this issue.',
        'I need to export my data for compliance purposes. Is there a way to download all my information in a standard format like CSV or JSON?',
        'I would like to integrate your service with our existing CRM system. Do you have API documentation or webhooks available for this purpose?'
    ];

    /**
     * Sample entity names
     *
     * @var array
     */
    private $entity_names = [
        'Technical Support',
        'Billing Department',
        'Sales Team',
        'Customer Success',
        'Product Support',
        'Account Management',
        'Security Team',
        'Development Team',
        'Quality Assurance',
        'Marketing Department'
    ];

    /**
     * Sample KB article titles
     *
     * @var array
     */
    private $kb_titles = [
        'How to reset your password',
        'Setting up two-factor authentication',
        'Troubleshooting connection issues',
        'Understanding your billing cycle',
        'Getting started with the API',
        'Mobile app installation guide',
        'Data backup and restore procedures',
        'Account security best practices',
        'Integrating with external services',
        'Performance optimization tips'
    ];

    /**
     * Generate realistic ticket data
     *
     * @param array $overrides Override default values
     * @return array
     */
    public function generate_ticket_data( $overrides = [] ) {
        $defaults = [
            'title' => $this->get_random_ticket_title(),
            'content' => $this->get_random_ticket_content(),
            'customer_name' => $this->get_random_customer_name(),
            'customer_email' => $this->generate_customer_email(),
            'priority' => $this->get_random_priority(),
            'category' => $this->get_random_category(),
            'status' => 'open',
            'entity_id' => 1,
            'created_at' => $this->get_random_past_datetime(),
            'updated_at' => current_time( 'mysql' )
        ];

        return array_merge( $defaults, $overrides );
    }

    /**
     * Generate realistic entity data
     *
     * @param array $overrides Override default values
     * @return array
     */
    public function generate_entity_data( $overrides = [] ) {
        $defaults = [
            'name' => $this->get_random_entity_name(),
            'description' => 'Handles ' . strtolower( $this->get_random_entity_name() ) . ' related inquiries',
            'status' => 'active',
            'parent_id' => 0,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' )
        ];

        return array_merge( $defaults, $overrides );
    }

    /**
     * Generate realistic KB article data
     *
     * @param array $overrides Override default values
     * @return array
     */
    public function generate_kb_article_data( $overrides = [] ) {
        $defaults = [
            'title' => $this->get_random_kb_title(),
            'content' => $this->generate_kb_content(),
            'status' => 'published',
            'category_id' => 1,
            'author_id' => 1,
            'view_count' => rand( 0, 500 ),
            'helpful_count' => rand( 0, 50 ),
            'created_at' => $this->get_random_past_datetime(),
            'updated_at' => current_time( 'mysql' )
        ];

        return array_merge( $defaults, $overrides );
    }

    /**
     * Get random customer name
     *
     * @return string
     */
    private function get_random_customer_name() {
        return $this->customer_names[ array_rand( $this->customer_names ) ];
    }

    /**
     * Get random ticket title
     *
     * @return string
     */
    private function get_random_ticket_title() {
        return $this->ticket_titles[ array_rand( $this->ticket_titles ) ];
    }

    /**
     * Get random ticket content
     *
     * @return string
     */
    private function get_random_ticket_content() {
        return $this->ticket_content[ array_rand( $this->ticket_content ) ];
    }

    /**
     * Get random entity name
     *
     * @return string
     */
    private function get_random_entity_name() {
        return $this->entity_names[ array_rand( $this->entity_names ) ];
    }

    /**
     * Get random KB title
     *
     * @return string
     */
    private function get_random_kb_title() {
        return $this->kb_titles[ array_rand( $this->kb_titles ) ];
    }

    /**
     * Generate customer email from name
     *
     * @return string
     */
    private function generate_customer_email() {
        $domains = [ 'example.com', 'test.com', 'demo.org', 'sample.net' ];
        $username = 'customer' . rand( 1000, 9999 );
        $domain = $domains[ array_rand( $domains ) ];
        
        return $username . '@' . $domain;
    }

    /**
     * Get random priority
     *
     * @return string
     */
    private function get_random_priority() {
        $priorities = [ 'low', 'medium', 'high', 'urgent' ];
        return $priorities[ array_rand( $priorities ) ];
    }

    /**
     * Get random category
     *
     * @return string
     */
    private function get_random_category() {
        $categories = [ 'technical', 'billing', 'general', 'feature_request', 'bug_report' ];
        return $categories[ array_rand( $categories ) ];
    }

    /**
     * Get random past datetime
     *
     * @return string
     */
    private function get_random_past_datetime() {
        $days_ago = rand( 1, 30 );
        $timestamp = time() - ( $days_ago * 24 * 60 * 60 );
        return date( 'Y-m-d H:i:s', $timestamp );
    }

    /**
     * Generate KB article content
     *
     * @return string
     */
    private function generate_kb_content() {
        $sections = [
            "## Overview\n\nThis article explains how to resolve common issues that users encounter when using our platform.\n\n",
            "## Step-by-Step Instructions\n\n1. First, log into your account\n2. Navigate to the settings page\n3. Look for the relevant option\n4. Apply the changes and save\n\n",
            "## Troubleshooting Tips\n\n- Clear your browser cache\n- Disable browser extensions\n- Try using an incognito window\n- Check your internet connection\n\n",
            "## Additional Resources\n\nFor more information, please refer to our other help articles or contact our support team directly.\n\n",
            "## Frequently Asked Questions\n\n**Q: How long does this process take?**\nA: Usually between 5-10 minutes.\n\n**Q: Is this available on mobile?**\nA: Yes, this feature is available on all platforms.\n"
        ];

        return implode( '', $sections );
    }

    /**
     * Create bulk test data
     *
     * @param int $ticket_count Number of tickets to create
     * @param int $entity_count Number of entities to create
     * @param int $kb_count Number of KB articles to create
     * @return array Summary of created data
     */
    public function create_bulk_test_data( $ticket_count = 10, $entity_count = 5, $kb_count = 8 ) {
        global $wpdb;
        
        $created = [
            'tickets' => [],
            'entities' => [],
            'kb_articles' => []
        ];

        // Create entities first
        for ( $i = 0; $i < $entity_count; $i++ ) {
            $entity_data = $this->generate_entity_data();
            
            $table = $wpdb->prefix . 'mets_entities';
            $wpdb->insert( $table, $entity_data );
            $created['entities'][] = $wpdb->insert_id;
        }

        // Create tickets
        for ( $i = 0; $i < $ticket_count; $i++ ) {
            $ticket_data = $this->generate_ticket_data([
                'entity_id' => $created['entities'][ array_rand( $created['entities'] ) ]
            ]);
            
            $table = $wpdb->prefix . 'mets_tickets';
            $wpdb->insert( $table, $ticket_data );
            $created['tickets'][] = $wpdb->insert_id;
        }

        // Create KB articles
        for ( $i = 0; $i < $kb_count; $i++ ) {
            $kb_data = $this->generate_kb_article_data();
            
            $table = $wpdb->prefix . 'mets_kb_articles';
            $wpdb->insert( $table, $kb_data );
            $created['kb_articles'][] = $wpdb->insert_id;
        }

        return $created;
    }
}