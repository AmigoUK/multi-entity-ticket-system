<?php
/**
 * Test script to verify METS_Public class and register_shortcodes method
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

// Load the METS_Public class
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php');

// Check if class exists
if (class_exists('METS_Public')) {
    echo "✅ METS_Public class exists\n";
    
    // Create an instance
    $public_instance = new METS_Public('test-plugin', '1.0.0');
    echo "✅ METS_Public instance created\n";
    
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
?>