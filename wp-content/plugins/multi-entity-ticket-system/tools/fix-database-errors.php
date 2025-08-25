<?php
/**
 * Fix database errors for METS plugin
 * 
 * This script adds missing columns to fix the WordPress database errors
 * appearing in the debug log.
 * 
 * Usage: Include in WordPress context and call mets_fix_database_errors()
 */

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

/**
 * Fix database column issues
 */
function mets_fix_database_errors() {
    global $wpdb;
    
    $results = array(
        'timestamp' => current_time('mysql'),
        'fixes_applied' => array(),
        'errors' => array(),
        'success' => true
    );
    
    try {
        $tickets_table = $wpdb->prefix . 'mets_tickets';
        
        // Check if tickets table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") !== $tickets_table) {
            $results['errors'][] = 'Tickets table does not exist. Please create tables first.';
            $results['success'] = false;
            return $results;
        }
        
        // Get current table structure
        $columns = $wpdb->get_results("DESCRIBE $tickets_table", ARRAY_A);
        $existing_columns = array();
        foreach ($columns as $column) {
            $existing_columns[] = $column['Field'];
        }
        
        // Define columns that need to be added
        $required_columns = array(
            'sla_status' => array(
                'sql' => "ALTER TABLE $tickets_table ADD COLUMN sla_status varchar(50) DEFAULT 'active'",
                'description' => 'SLA status tracking column'
            ),
            'assigned_to_changed_at' => array(
                'sql' => "ALTER TABLE $tickets_table ADD COLUMN assigned_to_changed_at datetime",
                'description' => 'Track when ticket assignment changes'
            ),
            'status_changed_at' => array(
                'sql' => "ALTER TABLE $tickets_table ADD COLUMN status_changed_at datetime",
                'description' => 'Track when ticket status changes'
            )
        );
        
        // Add missing columns
        foreach ($required_columns as $column_name => $column_info) {
            if (!in_array($column_name, $existing_columns)) {
                $result = $wpdb->query($column_info['sql']);
                if ($result !== false) {
                    $results['fixes_applied'][] = array(
                        'column' => $column_name,
                        'description' => $column_info['description'],
                        'status' => 'added'
                    );
                    error_log("[METS Fix] Added column: $column_name");
                } else {
                    $results['errors'][] = "Failed to add column $column_name: " . $wpdb->last_error;
                    $results['success'] = false;
                    error_log("[METS Fix] Failed to add column $column_name: " . $wpdb->last_error);
                }
            } else {
                $results['fixes_applied'][] = array(
                    'column' => $column_name,
                    'description' => $column_info['description'],
                    'status' => 'already_exists'
                );
            }
        }
        
        // Initialize default values for new columns
        if (in_array('sla_status', array_column($results['fixes_applied'], 'column'))) {
            $wpdb->query("UPDATE $tickets_table SET sla_status = 'active' WHERE sla_status IS NULL");
            $results['fixes_applied'][] = array(
                'column' => 'sla_status',
                'description' => 'Set default values for existing tickets',
                'status' => 'initialized'
            );
        }
        
        if (in_array('status_changed_at', array_column($results['fixes_applied'], 'column'))) {
            $wpdb->query("UPDATE $tickets_table SET status_changed_at = updated_at WHERE status_changed_at IS NULL");
            $results['fixes_applied'][] = array(
                'column' => 'status_changed_at',
                'description' => 'Set initial values based on updated_at',
                'status' => 'initialized'
            );
        }
        
        if (in_array('assigned_to_changed_at', array_column($results['fixes_applied'], 'column'))) {
            $wpdb->query("UPDATE $tickets_table SET assigned_to_changed_at = updated_at WHERE assigned_to_changed_at IS NULL AND assigned_to IS NOT NULL");
            $results['fixes_applied'][] = array(
                'column' => 'assigned_to_changed_at', 
                'description' => 'Set initial values for assigned tickets',
                'status' => 'initialized'
            );
        }
        
    } catch (Exception $e) {
        $results['success'] = false;
        $results['errors'][] = $e->getMessage();
        error_log('[METS Fix] Exception: ' . $e->getMessage());
    }
    
    return $results;
}

/**
 * Display results in a readable format
 */
function mets_display_fix_results($results) {
    echo "<div style='background: #f1f1f1; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>METS Database Error Fix Results</h3>";
    echo "<p><strong>Timestamp:</strong> " . esc_html($results['timestamp']) . "</p>";
    echo "<p><strong>Overall Status:</strong> " . ($results['success'] ? '<span style="color: green;">SUCCESS</span>' : '<span style="color: red;">FAILED</span>') . "</p>";
    
    if (!empty($results['fixes_applied'])) {
        echo "<h4>Fixes Applied:</h4>";
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #ddd;'><th style='border: 1px solid #ccc; padding: 8px;'>Column</th><th style='border: 1px solid #ccc; padding: 8px;'>Description</th><th style='border: 1px solid #ccc; padding: 8px;'>Status</th></tr>";
        foreach ($results['fixes_applied'] as $fix) {
            $status_color = $fix['status'] === 'added' ? 'green' : ($fix['status'] === 'already_exists' ? 'orange' : 'blue');
            echo "<tr>";
            echo "<td style='border: 1px solid #ccc; padding: 8px;'><code>" . esc_html($fix['column']) . "</code></td>";
            echo "<td style='border: 1px solid #ccc; padding: 8px;'>" . esc_html($fix['description']) . "</td>";
            echo "<td style='border: 1px solid #ccc; padding: 8px; color: $status_color;'>" . esc_html(str_replace('_', ' ', $fix['status'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if (!empty($results['errors'])) {
        echo "<h4 style='color: red;'>Errors:</h4>";
        echo "<ul style='color: red;'>";
        foreach ($results['errors'] as $error) {
            echo "<li>" . esc_html($error) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<h4>What this fixes:</h4>";
    echo "<ul>";
    echo "<li><strong>sla_status column:</strong> Eliminates 'Unknown column t.sla_status' errors in SLA monitoring</li>";
    echo "<li><strong>assigned_to_changed_at column:</strong> Fixes 'Unknown column assigned_to_changed_at' errors in AJAX polling</li>";
    echo "<li><strong>status_changed_at column:</strong> Fixes 'Unknown column status_changed_at' errors in status change tracking</li>";
    echo "<li><strong>Column name consistency:</strong> All queries now use correct column names (id instead of ticket_id/reply_id)</li>";
    echo "</ul>";
    
    echo "</div>";
}

// If accessed with admin privileges and run parameter
if (isset($_GET['run']) && current_user_can('manage_options')) {
    $results = mets_fix_database_errors();
    mets_display_fix_results($results);
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Check your debug.log - the database errors should now be resolved</li>";
    echo "<li>Test the AJAX polling functionality in the admin</li>";
    echo "<li>Verify SLA monitoring is working correctly</li>";
    echo "</ol>";
}