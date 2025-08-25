<?php
/**
 * Deep dive test to understand the register_shortcodes issue
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

// Load the METS_Public class
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php');

echo "=== Deep Dive Analysis ===\\n";

// Create an instance of METS_Public
$plugin_public = new METS_Public('multi-entity-ticket-system', '1.0.0');
echo "✅ Created METS_Public instance
";
echo "Instance class: " . get_class($plugin_public) . "
";

// Check if method exists
if (method_exists($plugin_public, 'register_shortcodes')) {
    echo "✅ register_shortcodes method exists on instance
";
} else {
    echo "❌ register_shortcodes method does not exist on instance
    
";
    
    // List all methods
    $methods = get_class_methods($plugin_public);
    echo "Available methods:
";
    foreach ($methods as $method) {
        echo "  - $method
";
    }
    exit(1);
}

// Check what WordPress thinks is registered
global $wp_filter;
echo "
=== WordPress Hook Registration ===
";

if (isset($wp_filter['init'])) {
    echo "Found init hooks:
";
    
    foreach ($wp_filter['init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $idx => $callback_info) {
            $callback = $callback_info['function'];
            
            // Check if this is our problematic callback
            if (is_array($callback) && 
                count($callback) == 2 && 
                is_object($callback[0]) && 
                get_class($callback[0]) == 'METS_Public' && 
                $callback[1] == 'register_shortcodes') {
                
                echo "  - Priority $priority: METS_Public::register_shortcodes
";
                echo "    Callback object class: " . get_class($callback[0]) . "
";
                echo "    Callback method: " . $callback[1] . "
";
                
                // Check if method exists on this specific object
                if (method_exists($callback[0], $callback[1])) {
                    echo "    ✅ Method exists on this object
";
                } else {
                    echo "    ❌ Method does not exist on this object
";
                }
                
                // Try to call the method
                try {
                    echo "    Testing callback...
";
                    call_user_func_array($callback, array());
                    echo "    ✅ Callback executed successfully
";
                } catch (Exception $e) {
                    echo "    ❌ Callback failed: " . $e->getMessage() . "
";
                } catch (TypeError $e) {
                    echo "    ❌ Callback failed with TypeError: " . $e->getMessage() . "
";
                }
            }
        }
    }
} else {
    echo "No init hooks found
";
}

echo "
=== Direct Method Test ===
";

// Try to call the method directly
try {
    echo "Testing direct method call...
";
    $plugin_public->register_shortcodes();
    echo "✅ Direct method call successful
";
} catch (Exception $e) {
    echo "❌ Direct method call failed: " . $e->getMessage() . "
";
} catch (TypeError $e) {
    echo "❌ Direct method call failed with TypeError: " . $e->getMessage() . "
";
}

echo "
=== Analysis Complete ===
";
?>