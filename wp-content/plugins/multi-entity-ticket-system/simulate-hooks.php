<?php
/**
 * Simulate WordPress hook system to test register_shortcodes issue
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

// Load the METS_Public class
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-content/plugins/multi-entity-ticket-system/public/class-mets-public.php');

echo "=== Simulating WordPress Hook System ===\n";

// Create an instance of METS_Public
$plugin_public = new METS_Public('multi-entity-ticket-system', '1.0.0');
echo "✅ Created METS_Public instance\n";

// Check if method exists on this instance
if (method_exists($plugin_public, 'register_shortcodes')) {
    echo "✅ register_shortcodes method exists on instance\n";
} else {
    echo "❌ register_shortcodes method does not exist on instance\n";
    exit(1);
}

// Simulate calling the hook directly
echo "Simulating direct call to register_shortcodes method...\n";
try {
    $plugin_public->register_shortcodes();
    echo "✅ Direct method call successful\n";
} catch (Exception $e) {
    echo "❌ Direct method call failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Simulate WordPress hook system calling the method
echo "Simulating WordPress hook system call...\n";
try {
    // This simulates what WordPress does internally
    call_user_func_array(array($plugin_public, 'register_shortcodes'), array());
    echo "✅ WordPress hook system call successful\n";
} catch (Exception $e) {
    echo "❌ WordPress hook system call failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Simulation Complete ===\n";
echo "No issues found with the register_shortcodes method.\n";
echo "The problem might be with how/when WordPress is loading the plugin.\n";
?>