<?php
/**
 * Base test case for METS tests
 *
 * @package METS_Tests
 */

use PHPUnit\Framework\TestCase;

class METS_Test_Case extends WP_UnitTestCase {

    /**
     * Test factory instance
     *
     * @var METS_Test_Factory
     */
    protected $mets_factory;

    /**
     * Test data factory
     *
     * @var METS_Test_Data_Factory
     */
    protected $data_factory;

    /**
     * Test utils instance
     *
     * @var METS_Test_Utils
     */
    protected $utils;

    /**
     * Setup test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->mets_factory = new METS_Test_Factory();
        $this->data_factory = new METS_Test_Data_Factory();
        $this->utils = new METS_Test_Utils();
        
        // Clear any existing METS data
        $this->cleanup_mets_data();
        
        // Setup basic test environment
        $this->setup_test_environment();
    }

    /**
     * Clean up after test
     */
    public function tearDown(): void {
        $this->cleanup_mets_data();
        $this->cleanup_uploaded_files();
        
        parent::tearDown();
    }

    /**
     * Clean up METS data
     */
    protected function cleanup_mets_data() {
        global $wpdb;
        
        // Clean up METS tables
        $tables = [
            $wpdb->prefix . 'mets_tickets',
            $wpdb->prefix . 'mets_ticket_replies',
            $wpdb->prefix . 'mets_entities',
            $wpdb->prefix . 'mets_attachments',
            $wpdb->prefix . 'mets_kb_articles',
            $wpdb->prefix . 'mets_audit_trail',
            $wpdb->prefix . 'mets_security_log',
            $wpdb->prefix . 'mets_rate_limits'
        ];

        foreach ( $tables as $table ) {
            $wpdb->query( "TRUNCATE TABLE $table" );
        }
        
        // Clean up options
        delete_option( 'mets_settings' );
        delete_option( 'mets_entities' );
    }

    /**
     * Setup test environment
     */
    protected function setup_test_environment() {
        // Create default entities for testing
        $this->create_default_entities();
        
        // Set default METS settings
        $this->set_default_settings();
    }

    /**
     * Create default test entities
     */
    protected function create_default_entities() {
        $this->mets_factory->create_entity([
            'name' => 'Test Main Entity',
            'description' => 'Main test entity for unit tests',
            'status' => 'active',
            'parent_id' => 0
        ]);
        
        $this->mets_factory->create_entity([
            'name' => 'Test Sub Entity',
            'description' => 'Sub entity for testing hierarchical structure',
            'status' => 'active',
            'parent_id' => 1
        ]);
    }

    /**
     * Set default METS settings
     */
    protected function set_default_settings() {
        $settings = [
            'company_name' => 'Test Company',
            'support_email' => 'test@example.com',
            'ticket_prefix' => 'TEST-',
            'default_status' => 'open',
            'default_priority' => 'medium',
            'enable_email_notifications' => true,
            'smtp_enabled' => false
        ];
        
        update_option( 'mets_settings', $settings );
    }

    /**
     * Clean up uploaded test files
     */
    protected function cleanup_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . '/mets-tests';
        
        if ( is_dir( $test_dir ) ) {
            $this->utils->delete_directory( $test_dir );
        }
    }

    /**
     * Assert METS ticket exists
     *
     * @param int $ticket_id Ticket ID
     * @param string $message Assertion message
     */
    protected function assertTicketExists( $ticket_id, $message = '' ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mets_tickets';
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $ticket_id
        ));
        
        $this->assertNotNull( $ticket, $message ?: "Ticket $ticket_id should exist" );
    }

    /**
     * Assert METS entity exists
     *
     * @param int $entity_id Entity ID
     * @param string $message Assertion message
     */
    protected function assertEntityExists( $entity_id, $message = '' ) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mets_entities';
        $entity = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $entity_id
        ));
        
        $this->assertNotNull( $entity, $message ?: "Entity $entity_id should exist" );
    }

    /**
     * Assert email was sent
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     */
    protected function assertEmailSent( $to, $subject ) {
        $sent_emails = $this->utils->get_sent_emails();
        
        $found = false;
        foreach ( $sent_emails as $email ) {
            if ( $email['to'] === $to && strpos( $email['subject'], $subject ) !== false ) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue( $found, "Email to $to with subject containing '$subject' should have been sent" );
    }
}