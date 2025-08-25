<?php
/**
 * Temporary debug script for security issues
 * Remove this file after debugging
 */

// Check if WordPress is loaded
if ( ! defined( 'ABSPATH' ) ) {
    // Load WordPress
    require_once dirname( __FILE__ ) . '/../../../wp-load.php';
}

echo "=== METS Security Debug ===\n";

// Test if security manager exists
if ( class_exists( 'METS_Security_Manager' ) ) {
    echo "✓ METS_Security_Manager class exists\n";
    
    $security_manager = METS_Security_Manager::get_instance();
    $config = $security_manager->get_security_config();
    
    echo "Security Configuration:\n";
    foreach ( $config as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = implode( ', ', $value );
        } elseif ( is_bool( $value ) ) {
            $value = $value ? 'true' : 'false';
        }
        echo "  {$key}: {$value}\n";
    }
    
    // Test a simple query
    echo "\nTesting SQL injection detection:\n";
    $test_query = "SELECT * FROM wp_mets_tickets WHERE id = 1";
    echo "Test query: {$test_query}\n";
    
    try {
        $result = $security_manager->detect_sql_injection( $test_query );
        echo "✓ Query passed security check\n";
    } catch ( Exception $e ) {
        echo "✗ Query blocked: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ METS_Security_Manager class not found\n";
}

// Check database tables
global $wpdb;
$tables = array(
    'mets_security_log',
    'mets_rate_limits', 
    'mets_security_config',
    'mets_audit_trail'
);

echo "\nDatabase Tables:\n";
foreach ( $tables as $table ) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full_table_name ) );
    if ( $exists ) {
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$full_table_name}" );
        echo "  ✓ {$full_table_name} exists ({$count} rows)\n";
    } else {
        echo "  ✗ {$full_table_name} does not exist\n";
    }
}

echo "\n=== End Debug ===\n";