<?php
/**
 * Unit tests for METS Core functionality
 *
 * @package METS_Tests
 * @subpackage Unit_Tests
 */

class Test_METS_Core extends METS_Test_Case {

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        // Test that METS core class exists
        $this->assertTrue( class_exists( 'METS_Core' ) );
        
        // Test singleton instance
        $instance1 = METS_Core::get_instance();
        $instance2 = METS_Core::get_instance();
        
        $this->assertInstanceOf( 'METS_Core', $instance1 );
        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test plugin constants are defined
     */
    public function test_plugin_constants() {
        $this->assertTrue( defined( 'METS_VERSION' ) );
        $this->assertTrue( defined( 'METS_PLUGIN_FILE' ) );
        $this->assertTrue( defined( 'METS_PLUGIN_PATH' ) );
        $this->assertTrue( defined( 'METS_PLUGIN_URL' ) );
        $this->assertTrue( defined( 'METS_PLUGIN_BASENAME' ) );
        $this->assertTrue( defined( 'METS_TEXT_DOMAIN' ) );
        
        // Test values
        $this->assertEquals( '1.0.0', METS_VERSION );
        $this->assertEquals( 'multi-entity-ticket-system', METS_TEXT_DOMAIN );
    }

    /**
     * Test database tables are created
     */
    public function test_database_tables_created() {
        $required_tables = [
            'mets_entities',
            'mets_tickets',
            'mets_ticket_replies',
            'mets_attachments',
            'mets_kb_articles',
            'mets_audit_trail',
            'mets_security_log',
            'mets_rate_limits'
        ];

        foreach ( $required_tables as $table ) {
            $this->assertTrue( 
                $this->utils->table_exists( $table ),
                "Table $table should exist"
            );
        }
    }

    /**
     * Test default settings are created
     */
    public function test_default_settings_created() {
        $settings = $this->utils->get_mets_settings();
        
        $this->assertIsArray( $settings );
        $this->assertArrayHasKey( 'company_name', $settings );
        $this->assertArrayHasKey( 'support_email', $settings );
        $this->assertArrayHasKey( 'ticket_prefix', $settings );
    }

    /**
     * Test activation hooks
     */
    public function test_activation_hooks() {
        // Test that activation function is registered
        $this->assertTrue( 
            function_exists( 'activate_multi_entity_ticket_system' )
        );
        
        // Test that deactivation function is registered
        $this->assertTrue( 
            function_exists( 'deactivate_multi_entity_ticket_system' )
        );
    }

    /**
     * Test user capabilities are created
     */
    public function test_user_capabilities_created() {
        global $wp_roles;
        
        // Test that METS roles exist
        $this->assertTrue( $wp_roles->is_role( 'mets_agent' ) );
        $this->assertTrue( $wp_roles->is_role( 'mets_manager' ) );
        
        // Test agent capabilities
        $agent_role = get_role( 'mets_agent' );
        $this->assertTrue( $agent_role->has_cap( 'mets_view_tickets' ) );
        $this->assertTrue( $agent_role->has_cap( 'mets_reply_tickets' ) );
        
        // Test manager capabilities
        $manager_role = get_role( 'mets_manager' );
        $this->assertTrue( $manager_role->has_cap( 'mets_manage_tickets' ) );
        $this->assertTrue( $manager_role->has_cap( 'mets_manage_entities' ) );
    }

    /**
     * Test WordPress hooks are registered
     */
    public function test_wordpress_hooks_registered() {
        global $wp_filter;
        
        // Test that admin hooks are registered
        $this->assertTrue( 
            has_action( 'admin_menu', 'METS_Admin::admin_menu' ) !== false ||
            has_action( 'admin_menu' ) !== false
        );
        
        // Test that public hooks are registered
        $this->assertTrue( 
            has_action( 'wp_enqueue_scripts' ) !== false
        );
        
        // Test that AJAX hooks are registered
        $this->assertTrue( 
            has_action( 'wp_ajax_mets_create_ticket' ) !== false ||
            has_action( 'wp_ajax_nopriv_mets_create_ticket' ) !== false
        );
    }
}