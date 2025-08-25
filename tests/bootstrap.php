<?php
/**
 * Bootstrap file for METS Plugin tests
 *
 * @package METS_Tests
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

// Define testing environment constants
define( 'METS_TESTS_DIR', dirname( __FILE__ ) );
define( 'METS_PLUGIN_DIR', dirname( METS_TESTS_DIR ) );
define( 'METS_WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib' );
define( 'WP_TESTS_CONFIG_FILE_PATH', METS_WP_TESTS_DIR . '/wp-tests-config.php' );

// Test database settings
define( 'DB_NAME', getenv( 'METS_TEST_DB_NAME' ) ?: 'mets_test' );
define( 'DB_USER', getenv( 'METS_TEST_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'METS_TEST_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'METS_TEST_DB_HOST' ) ?: 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// WordPress test environment constants
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

// Set up WordPress test framework
if ( ! file_exists( WP_TESTS_CONFIG_FILE_PATH ) ) {
    die( "WordPress test configuration not found. Please install WordPress test suite.\n" );
}

// Load WordPress test environment
require_once WP_TESTS_CONFIG_FILE_PATH;
require_once METS_WP_TESTS_DIR . '/includes/functions.php';

/**
 * Setup METS plugin for testing
 */
function _mets_tests_setup_plugin() {
    // Load the METS plugin
    $plugin_file = METS_PLUGIN_DIR . '/wp-content/plugins-backup/multi-entity-ticket-system-emergency-backup/multi-entity-ticket-system.php';
    if ( file_exists( $plugin_file ) ) {
        require_once $plugin_file;
    } else {
        die( "METS plugin file not found: $plugin_file\n" );
    }
    
    // Activate the plugin
    activate_multi_entity_ticket_system();
}

tests_add_filter( 'muplugins_loaded', '_mets_tests_setup_plugin' );

// Start up the WP testing environment
require METS_WP_TESTS_DIR . '/includes/bootstrap.php';

// Load test helper classes
require_once METS_TESTS_DIR . '/helpers/class-mets-test-factory.php';
require_once METS_TESTS_DIR . '/helpers/class-mets-test-utils.php';
require_once METS_TESTS_DIR . '/helpers/class-mets-test-data-factory.php';
require_once METS_TESTS_DIR . '/helpers/class-mets-test-case.php';

// Clean up test environment after each test
register_shutdown_function( function() {
    // Clean up uploaded files
    $upload_dir = wp_upload_dir();
    $test_uploads = $upload_dir['basedir'] . '/mets-tests';
    if ( is_dir( $test_uploads ) ) {
        exec( "rm -rf $test_uploads" );
    }
});