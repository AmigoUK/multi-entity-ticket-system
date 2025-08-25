<?php
/**
 * Apply database schema updates for METS plugin
 * 
 * This script fixes the database column issues by adding missing columns
 * and updating the schema to match what the code expects.
 * 
 * @package    MultiEntityTicketSystem  
 * @subpackage MultiEntityTicketSystem/database
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Apply schema updates for METS plugin
 */
function mets_apply_schema_updates() {
    require_once plugin_dir_path(__FILE__) . 'class-mets-schema-manager.php';
    
    $schema_manager = new METS_Schema_Manager();
    $results = $schema_manager->update_schema_for_missing_columns();
    
    echo "<h3>METS Database Schema Update Results</h3>";
    echo "<p><strong>Timestamp:</strong> " . $results['timestamp'] . "</p>";
    echo "<p><strong>Success:</strong> " . ($results['success'] ? 'Yes' : 'No') . "</p>";
    
    if (!empty($results['columns_added'])) {
        echo "<h4>Columns Added:</h4>";
        echo "<ul>";
        foreach ($results['columns_added'] as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($results['errors'])) {
        echo "<h4>Errors:</h4>";
        echo "<ul style='color: red;'>";
        foreach ($results['errors'] as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    
    if (empty($results['columns_added']) && empty($results['errors'])) {
        echo "<p>No schema updates were needed - all columns already exist.</p>";
    }
    
    return $results;
}

// If this file is being accessed directly for testing/admin purposes
if (isset($_GET['run_mets_schema_update']) && current_user_can('manage_options')) {
    mets_apply_schema_updates();
}