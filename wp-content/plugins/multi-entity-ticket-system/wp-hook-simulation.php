<?php
/**
 * WordPress Hook Loading Simulation
 *
 * This script simulates how WordPress loads and calls hooks to identify the issue
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

echo "=== WordPress Hook Loading Simulation ===
";

// Simulate WordPress plugin loading
echo "Loading METS plugin...
";

// Load the main plugin file
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/multi-entity-ticket-system.php');

// Check if METS_Core class exists
if (!class_exists('METS_Core')) {
    echo "❌ METS_Core class does not exist
";
    exit(1);
}

echo "✅ METS_Core class exists
";

// Get plugin instance
$core = METS_Core::get_instance();
echo "✅ METS_Core instance created
";

// Run plugin initialization
$core->run();
echo "✅ Plugin initialization completed
";

// Check what's registered in WordPress hooks
global $wp_filter;

echo "
=== WordPress Hook Registrations ===
";

if (isset($wp_filter['init'])) {
    echo "Found init hook registrations:
";
    
    foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $idx => $callback_info) {
            $callback = $callback_info['function'];
            
            // Check if this is a METS_Public callback
            if (is_array($callback) && 
                count($callback) == 2 && 
                is_object($callback[0]) && 
                get_class($callback[0]) == 'METS_Public' && 
                $callback[1] == 'register_shortcodes') {
                
                echo "  - Priority $priority: METS_Public::register_shortcodes
";
                echo "    Instance hash: " . spl_object_hash($callback[0]) . "
";
                
                // Check if method exists on this specific instance
                if (method_exists($callback[0], 'register_shortcodes')) {
                    echo "    ✅ Method exists on this instance
";
                    
                    // Try to call the method
                    try {
                        echo "    Calling method...
";
                        call_user_func_array($callback, array());
                        echo "    ✅ Method called successfully
";
                    } catch (Exception $e) {
                        echo "    ❌ Method call failed: " . $e->getMessage() . "
";
                    }
                } else {
                    echo "    ❌ Method does not exist on this instance
";
                    
                    // List available methods
                    $methods = get_class_methods($callback[0]);
                    echo "    Available methods on this instance:
";
                    foreach ($methods as $method) {
                        echo "      - $method
";
                    }
                }
            }
            
            // Check if this is a static reference
            if (is_array($callback) && 
                count($callback) == 2 && 
                is_string($callback[0]) && 
                $callback[0] == 'METS_Public' && 
                $callback[1] == 'register_shortcodes') {
                
                echo "  - Priority $priority: METS_Public::register_shortcodes (STATIC REFERENCE)
";
                echo "    ⚠️  This is a static reference and will fail
";
                
                // Try to call it
                try {
                    echo "    Calling static reference...
";
                    call_user_func_array($callback, array());
                    echo "    ✅ Static reference called successfully
";
                } catch (Exception $e) {
                    echo "    ❌ Static reference call failed: " . $e->getMessage() . "
";
                }
            }
        }
    }
} else {
    echo "No init hook registrations found
";
}

echo "
=== Simulation Complete ===
";
?>