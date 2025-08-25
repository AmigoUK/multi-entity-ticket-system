<?php
/**
 * WebSocket Database Cleanup Script
 * 
 * This script removes all WebSocket-related database entries
 * from the Multi-Entity Ticket System plugin.
 * 
 * Usage: Run this script via WP-CLI or as an administrator
 */

// Check if we're running in WordPress context
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('Error: Could not find wp-load.php. Please run this script from within WordPress.');
    }
}

// Security check - only allow administrators
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    die('Error: You must be an administrator to run this script.');
}

class METS_WebSocket_Database_Cleanup {
    
    private $options_removed = 0;
    private $user_meta_removed = 0;
    private $transients_removed = 0;
    
    /**
     * Run the cleanup process
     */
    public function run() {
        echo "Starting WebSocket database cleanup...\n\n";
        
        $this->remove_options();
        $this->remove_user_meta();
        $this->remove_transients();
        
        $this->display_summary();
    }
    
    /**
     * Remove WebSocket options
     */
    private function remove_options() {
        echo "Removing WebSocket options...\n";
        
        $options_to_remove = array(
            'mets_websocket_enabled',
            'mets_websocket_url',
            'mets_websocket_port',
            'mets_websocket_secret',
            'mets_websocket_server_status',
            'mets_realtime_features_enabled'
        );
        
        foreach ($options_to_remove as $option) {
            if (delete_option($option)) {
                echo "  ✓ Removed option: $option\n";
                $this->options_removed++;
            } else {
                $value = get_option($option);
                if ($value === false) {
                    echo "  - Option not found: $option\n";
                } else {
                    echo "  ✗ Failed to remove option: $option\n";
                }
            }
        }
        
        echo "\n";
    }
    
    /**
     * Remove user meta related to WebSocket
     */
    private function remove_user_meta() {
        echo "Removing user meta...\n";
        
        global $wpdb;
        
        // Remove dismissed notice meta
        $result = $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key = 'dismissed_mets_websocket_notice'"
        );
        
        if ($result !== false) {
            echo "  ✓ Removed $result dismissed notice entries\n";
            $this->user_meta_removed += $result;
        }
        
        // Remove any other WebSocket-related user meta
        $result = $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'mets_websocket_%'"
        );
        
        if ($result !== false && $result > 0) {
            echo "  ✓ Removed $result additional WebSocket user meta entries\n";
            $this->user_meta_removed += $result;
        }
        
        echo "\n";
    }
    
    /**
     * Remove WebSocket-related transients
     */
    private function remove_transients() {
        echo "Removing transients...\n";
        
        global $wpdb;
        
        // Remove WebSocket success transient
        if (delete_transient('mets_websocket_success_shown')) {
            echo "  ✓ Removed transient: mets_websocket_success_shown\n";
            $this->transients_removed++;
        }
        
        // Remove all user online status transients
        $result = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mets_user_online_%' 
             OR option_name LIKE '_transient_timeout_mets_user_online_%'"
        );
        
        if ($result !== false && $result > 0) {
            echo "  ✓ Removed $result user online status transients\n";
            $this->transients_removed += $result;
        }
        
        // Remove any other WebSocket-related transients
        $result = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE (option_name LIKE '_transient_mets_websocket_%' 
             OR option_name LIKE '_transient_timeout_mets_websocket_%')
             AND option_name NOT LIKE '%user_online%'"
        );
        
        if ($result !== false && $result > 0) {
            echo "  ✓ Removed $result additional WebSocket transients\n";
            $this->transients_removed += $result;
        }
        
        echo "\n";
    }
    
    /**
     * Display cleanup summary
     */
    private function display_summary() {
        echo "=================================\n";
        echo "WebSocket Database Cleanup Summary\n";
        echo "=================================\n";
        echo "Options removed: {$this->options_removed}\n";
        echo "User meta removed: {$this->user_meta_removed}\n";
        echo "Transients removed: {$this->transients_removed}\n";
        echo "\n";
        
        $total = $this->options_removed + $this->user_meta_removed + $this->transients_removed;
        echo "Total database entries removed: $total\n";
        echo "\n";
        echo "WebSocket database cleanup completed successfully!\n";
    }
}

// Run the cleanup
$cleanup = new METS_WebSocket_Database_Cleanup();
$cleanup->run();