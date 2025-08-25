<?php
/**
 * Detailed test script to diagnose the register_shortcodes issue
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

// Load the METS_Public class
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php');

echo "=== METS_Public Class Analysis ===\n";

// Check if class exists
if (class_exists('METS_Public')) {
    echo "✅ METS_Public class exists\n";
    
    // Create an instance
    $public_instance = new METS_Public('multi-entity-ticket-system', '1.0.0');
    echo "✅ METS_Public instance created\n";
    echo "Instance class: " . get_class($public_instance) . "\n";
    
    // Check if method exists
    if (method_exists($public_instance, 'register_shortcodes')) {
        echo "✅ register_shortcodes method exists\n";
        
        // Try to call the method
        try {
            $public_instance->register_shortcodes();
            echo "✅ register_shortcodes method called successfully\n";
        } catch (Exception $e) {
            echo "❌ Error calling register_shortcodes method: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ register_shortcodes method does not exist\n";
        
        // List available methods
        $methods = get_class_methods($public_instance);
        echo "Available methods:\n";
        foreach ($methods as $method) {
            echo "  - $method\n";
        }
    }
} else {
    echo "❌ METS_Public class does not exist\n";
}

echo "\n=== WordPress Hook Analysis ===\n";

// Check if there are any hooks registered for init
global $wp_filter;
if (isset($wp_filter['init'])) {
    echo "Found init hooks:\n";
    foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $idx => $callback) {
            if (is_array($callback['function']) && 
                count($callback['function']) == 2 &&
                is_object($callback['function'][0])) {
                
                $class_name = get_class($callback['function'][0]);
                $method_name = $callback['function'][1];
                
                echo "  - Priority $priority: $class_name::$method_name\n";
                
                // Check if this is our problematic hook
                if ($class_name == 'METS_Public' && $method_name == 'register_shortcodes') {
                    echo "    ✅ This is the registered hook we're looking for\n";
                    
                    // Check if the method exists on this specific instance
                    if (method_exists($callback['function'][0], 'register_shortcodes')) {
                        echo "    ✅ Method exists on this instance\n";
                    } else {
                        echo "    ❌ Method does not exist on this instance\n";
                    }
                }
            } elseif (is_array($callback['function']) && 
                      count($callback['function']) == 2 &&
                      is_string($callback['function'][0]) &&
                      $callback['function'][0] == 'METS_Public') {
                
                $class_name = $callback['function'][0];
                $method_name = $callback['function'][1];
                
                echo "  - Priority $priority: $class_name::$method_name (static reference)\n";
                
                // Check if this is our problematic hook
                if ($class_name == 'METS_Public' && $method_name == 'register_shortcodes') {
                    echo "    ⚠️  This is a static reference to METS_Public::register_shortcodes\n";
                    echo "    ❌ This will fail because it's not calling an instance method\n";
                }
            }
        }
    }
} else {
    echo "No init hooks found\n";
}

echo "\n=== Analysis Complete ===\n";
?>