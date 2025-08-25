<?php
/**
 * Exact Error Scenario Simulation
 *
 * This script simulates the exact error scenario to understand what's happening
 */

// Load WordPress
require_once('/Users/tomaszlewandowski/Library/CloudStorage/Dropbox/coding/QwenCodeTest/dev/wp-load.php');

echo "=== Exact Error Scenario Simulation ===\n";

// Check if METS_Public class exists
if (!class_exists('METS_Public')) {
    echo "❌ METS_Public class does not exist\n";
    exit(1);
}

echo "✅ METS_Public class exists\n";

// Create an instance
$plugin_public = new METS_Public('multi-entity-ticket-system', '1.0.0');
echo "✅ METS_Public instance created\n";
echo "Instance class: " . get_class($plugin_public) . "\n";
echo "Instance hash: " . spl_object_hash($plugin_public) . "\n";

// Check if method exists
if (!method_exists($plugin_public, 'register_shortcodes')) {
    echo "❌ register_shortcodes method does not exist on instance\n";
    
    // List available methods
    $methods = get_class_methods($plugin_public);
    echo "Available methods:\n";
    foreach ($methods as $method) {
        echo "  - $method\n";
    }
    exit(1);
}

echo "✅ register_shortcodes method exists on instance\n";

// Simulate exactly what WordPress is doing
echo "\n=== Simulating WordPress Hook System ===\n";

// Create callback array like WordPress would
$callback = array($plugin_public, 'register_shortcodes');

// Check if it's a valid callback
if (is_callable($callback)) {
    echo "✅ Callback is valid\n";
} else {
    echo "❌ Callback is not valid\n";
    var_dump($callback);
    exit(1);
}

// Try to call it with call_user_func_array like WordPress does
echo "Calling callback with call_user_func_array...\n";
try {
    $result = call_user_func_array($callback, array());
    echo "✅ Callback executed successfully\n";
} catch (TypeError $e) {
    echo "❌ TypeError: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Simulation Complete ===\n";
echo "No issues found with the callback in isolation.\n";
echo "The problem must be occurring in a different context.\n";
?>