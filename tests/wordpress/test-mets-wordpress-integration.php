<?php
/**
 * WordPress integration tests for METS
 *
 * @package METS_Tests
 * @subpackage WordPress_Integration
 */

class Test_METS_WordPress_Integration extends METS_Test_Case {

    /**
     * Test plugin activation and deactivation
     */
    public function test_plugin_activation_deactivation() {
        // Test plugin activation
        $this->assertTrue( is_plugin_active( plugin_basename( METS_PLUGIN_FILE ) ) );

        // Verify database tables exist after activation
        $this->assertTrue( $this->utils->table_exists( 'mets_tickets' ) );
        $this->assertTrue( $this->utils->table_exists( 'mets_entities' ) );

        // Verify capabilities are added
        global $wp_roles;
        $this->assertTrue( $wp_roles->is_role( 'mets_agent' ) );
        $this->assertTrue( $wp_roles->is_role( 'mets_manager' ) );
    }

    /**
     * Test admin menu integration
     */
    public function test_admin_menu_integration() {
        global $menu, $submenu;

        // Set admin user
        $admin_id = $this->factory->user->create([ 'role' => 'administrator' ]);
        wp_set_current_user( $admin_id );

        // Trigger admin_menu action
        do_action( 'admin_menu' );

        // Check if METS menu exists
        $mets_menu_found = false;
        foreach ( $menu as $menu_item ) {
            if ( isset( $menu_item[0] ) && strpos( $menu_item[0], 'METS' ) !== false ) {
                $mets_menu_found = true;
                break;
            }
        }

        $this->assertTrue( $mets_menu_found, 'METS admin menu should be registered' );
    }

    /**
     * Test shortcode registration and rendering
     */
    public function test_shortcode_registration() {
        // Test that shortcodes are registered
        $this->assertTrue( shortcode_exists( 'mets_ticket_form' ) );
        $this->assertTrue( shortcode_exists( 'mets_ticket_list' ) );
        $this->assertTrue( shortcode_exists( 'mets_kb_search' ) );

        // Test shortcode rendering
        $form_output = do_shortcode( '[mets_ticket_form]' );
        $this->assertNotEmpty( $form_output );
        $this->assertStringContainsString( 'form', $form_output );

        $list_output = do_shortcode( '[mets_ticket_list]' );
        $this->assertNotEmpty( $list_output );

        $search_output = do_shortcode( '[mets_kb_search]' );
        $this->assertNotEmpty( $search_output );
    }

    /**
     * Test WordPress hooks integration
     */
    public function test_wordpress_hooks_integration() {
        // Test wp_enqueue_scripts hook
        $this->assertGreaterThan( 0, has_action( 'wp_enqueue_scripts' ) );

        // Test admin_enqueue_scripts hook
        $this->assertGreaterThan( 0, has_action( 'admin_enqueue_scripts' ) );

        // Test init hook
        $this->assertGreaterThan( 0, has_action( 'init' ) );

        // Test AJAX hooks
        $this->assertGreaterThan( 0, has_action( 'wp_ajax_mets_create_ticket' ) );
        $this->assertGreaterThan( 0, has_action( 'wp_ajax_nopriv_mets_create_ticket' ) );
    }

    /**
     * Test user roles and capabilities
     */
    public function test_user_roles_and_capabilities() {
        // Test METS Agent role
        $agent_id = $this->mets_factory->create_agent();
        $agent = get_user_by( 'id', $agent_id );

        $this->assertInstanceOf( 'WP_User', $agent );
        $this->assertTrue( in_array( 'mets_agent', $agent->roles ) );
        $this->assertTrue( user_can( $agent, 'mets_view_tickets' ) );
        $this->assertTrue( user_can( $agent, 'mets_reply_tickets' ) );
        $this->assertFalse( user_can( $agent, 'mets_manage_entities' ) );

        // Test METS Manager role
        $manager_id = $this->mets_factory->create_manager();
        $manager = get_user_by( 'id', $manager_id );

        $this->assertInstanceOf( 'WP_User', $manager );
        $this->assertTrue( in_array( 'mets_manager', $manager->roles ) );
        $this->assertTrue( user_can( $manager, 'mets_view_tickets' ) );
        $this->assertTrue( user_can( $manager, 'mets_manage_tickets' ) );
        $this->assertTrue( user_can( $manager, 'mets_manage_entities' ) );
    }

    /**
     * Test custom post types and taxonomies
     */
    public function test_custom_post_types_and_taxonomies() {
        // If METS uses custom post types, test their registration
        $post_types = get_post_types();
        
        // This would be relevant if METS uses CPTs
        // For now, we verify core post types exist
        $this->assertArrayHasKey( 'post', $post_types );
        $this->assertArrayHasKey( 'page', $post_types );

        // Test custom taxonomies if any
        $taxonomies = get_taxonomies();
        $this->assertArrayHasKey( 'category', $taxonomies );
    }

    /**
     * Test widget integration
     */
    public function test_widget_integration() {
        global $wp_widget_factory;

        // If METS provides widgets, test their registration
        $widgets = $wp_widget_factory->widgets;
        
        // For now, just verify widget factory exists
        $this->assertIsArray( $widgets );
    }

    /**
     * Test REST API integration
     */
    public function test_rest_api_integration() {
        // Test that REST routes are registered
        $routes = rest_get_server()->get_routes();
        
        // Check for METS REST routes
        $mets_routes_found = false;
        foreach ( array_keys( $routes ) as $route ) {
            if ( strpos( $route, '/mets/' ) !== false ) {
                $mets_routes_found = true;
                break;
            }
        }

        $this->assertTrue( $mets_routes_found, 'METS REST API routes should be registered' );
    }

    /**
     * Test database table creation and structure
     */
    public function test_database_table_structure() {
        global $wpdb;

        // Test tickets table structure
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        $columns = $wpdb->get_results( "DESCRIBE $tickets_table" );
        
        $column_names = array_column( $columns, 'Field' );
        $this->assertContains( 'id', $column_names );
        $this->assertContains( 'title', $column_names );
        $this->assertContains( 'customer_email', $column_names );
        $this->assertContains( 'status', $column_names );
        $this->assertContains( 'created_at', $column_names );

        // Test entities table structure
        $entities_table = $wpdb->prefix . 'mets_entities';
        $columns = $wpdb->get_results( "DESCRIBE $entities_table" );
        
        $column_names = array_column( $columns, 'Field' );
        $this->assertContains( 'id', $column_names );
        $this->assertContains( 'name', $column_names );
        $this->assertContains( 'parent_id', $column_names );
        $this->assertContains( 'status', $column_names );
    }

    /**
     * Test WordPress options integration
     */
    public function test_wordpress_options_integration() {
        // Test that METS options are properly stored
        $settings = get_option( 'mets_settings' );
        $this->assertIsArray( $settings );

        // Test updating settings
        $new_settings = array_merge( $settings, [
            'test_setting' => 'test_value'
        ]);

        update_option( 'mets_settings', $new_settings );
        $updated_settings = get_option( 'mets_settings' );
        
        $this->assertEquals( 'test_value', $updated_settings['test_setting'] );
    }

    /**
     * Test multisite compatibility
     */
    public function test_multisite_compatibility() {
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'Multisite tests can only run on multisite installations' );
        }

        // Test plugin activation on network
        $this->assertTrue( is_plugin_active( plugin_basename( METS_PLUGIN_FILE ) ) );

        // Test that tables are created with correct prefixes
        global $wpdb;
        $blog_id = get_current_blog_id();
        
        $expected_prefix = $wpdb->get_blog_prefix( $blog_id );
        $tickets_table = $expected_prefix . 'mets_tickets';
        
        $this->assertTrue( $this->utils->table_exists( 'mets_tickets' ) );
    }

    /**
     * Test theme compatibility
     */
    public function test_theme_compatibility() {
        // Test that METS works with different themes
        $original_theme = get_option( 'stylesheet' );

        // Switch to Twenty Twenty-One theme for testing
        switch_theme( 'twentytwentyone' );
        
        // Test shortcode rendering with new theme
        $output = do_shortcode( '[mets_ticket_form]' );
        $this->assertNotEmpty( $output );

        // Switch back to original theme
        switch_theme( $original_theme );
    }

    /**
     * Test localization integration
     */
    public function test_localization_integration() {
        // Test that text domain is loaded
        $this->assertTrue( is_textdomain_loaded( METS_TEXT_DOMAIN ) );

        // Test translation function
        $translated = __( 'Ticket', METS_TEXT_DOMAIN );
        $this->assertIsString( $translated );

        // Test with different locale
        $original_locale = get_locale();
        
        // Switch to Spanish (if available)
        add_filter( 'locale', function() { return 'es_ES'; } );
        
        // Load translations for Spanish
        load_plugin_textdomain( METS_TEXT_DOMAIN );
        
        $spanish_translation = __( 'Ticket', METS_TEXT_DOMAIN );
        $this->assertIsString( $spanish_translation );

        // Restore original locale
        remove_all_filters( 'locale' );
    }

    /**
     * Test cache integration
     */
    public function test_cache_integration() {
        // Test WordPress object cache integration
        $cache_key = 'mets_test_cache';
        $cache_value = 'test_data';

        wp_cache_set( $cache_key, $cache_value, 'mets' );
        $cached_value = wp_cache_get( $cache_key, 'mets' );

        $this->assertEquals( $cache_value, $cached_value );

        // Test cache deletion
        wp_cache_delete( $cache_key, 'mets' );
        $deleted_value = wp_cache_get( $cache_key, 'mets' );

        $this->assertFalse( $deleted_value );
    }

    /**
     * Test security integration
     */
    public function test_security_integration() {
        // Test nonce verification
        $nonce = wp_create_nonce( 'mets_create_ticket' );
        $this->assertNotEmpty( $nonce );

        // Test nonce validation
        $is_valid = wp_verify_nonce( $nonce, 'mets_create_ticket' );
        $this->assertTrue( $is_valid );

        // Test capability checks
        $subscriber_id = $this->factory->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user( $subscriber_id );

        $this->assertFalse( current_user_can( 'mets_manage_tickets' ) );

        // Test admin user capabilities
        $admin_id = $this->factory->user->create([ 'role' => 'administrator' ]);
        wp_set_current_user( $admin_id );

        $this->assertTrue( current_user_can( 'manage_options' ) );
    }
}