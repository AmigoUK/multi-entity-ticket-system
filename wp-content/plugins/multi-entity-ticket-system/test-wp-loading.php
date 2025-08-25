<?php
/**
 * Test WordPress plugin loading to diagnose register_shortcodes issue
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

echo "=== WordPress Plugin Loading Test ===\n";

// Check if METS_Public class exists
if (class_exists('METS_Public')) {
    echo "✅ METS_Public class exists\n";
    
    // Check if method exists
    if (method_exists('METS_Public', 'register_shortcodes')) {
        echo "✅ register_shortcodes method exists in class\n";
    } else {
        echo "❌ register_shortcodes method does not exist in class\n";
        
        // List all methods in the class
        $methods = get_class_methods('METS_Public');
        echo "Available methods in METS_Public:\n";
        foreach ($methods as $method) {
            echo "  - $method\n";
        }
    }
} else {
    echo "❌ METS_Public class does not exist\n";
}

// Check if there are any instances of METS_Public registered with WordPress
global $wp_filter;

echo "\n=== WordPress Hook Registrations ===\n";

if (isset($wp_filter['init'])) {
    echo "Found init hook registrations:\n";
    
    foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $idx => $callback_info) {
            $callback = $callback_info['function'];
            
            // Check if this is a METS_Public callback
            if (is_array($callback) && 
                count($callback) == 2 && 
                is_object($callback[0]) && 
                get_class($callback[0]) == 'METS_Public' && 
                $callback[1] == 'register_shortcodes') {
                
                echo "  - Priority $priority: METS_Public::register_shortcodes\n";
                echo "    Instance hash: " . spl_object_hash($callback[0]) . "\n";
                
                // Check if method exists on this specific instance
                if (method_exists($callback[0], 'register_shortcodes')) {
                    echo "    ✅ Method exists on this instance\n";
                } else {
                    echo "    ❌ Method does not exist on this instance\n";
                }
            }
            
            // Check if this is a static reference
            if (is_array($callback) && 
                count($callback) == 2 && 
                is_string($callback[0]) && 
                $callback[0] == 'METS_Public' && 
                $callback[1] == 'register_shortcodes') {
                
                echo "  - Priority $priority: METS_Public::register_shortcodes (STATIC REFERENCE)\n";
                echo "    ⚠️  This is a static reference and will fail\n";
            }
        }
    }
} else {
    echo "No init hook registrations found\n";
}

echo "\n=== Analysis Complete ===\n";
?>