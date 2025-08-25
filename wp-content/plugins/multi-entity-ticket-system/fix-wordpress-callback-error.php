<?php
/**
 * Fix for WordPress callback error
 *
 * This script fixes the "class METS_Public does not have a method register_shortcodes" error
 * by ensuring the method exists and is properly registered with WordPress hooks.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Add the missing register_shortcodes method to METS_Public class
 *
 * This fix ensures that the register_shortcodes method exists in the METS_Public class
 * and is properly registered with WordPress hooks.
 */
function fix_mets_public_register_shortcodes() {
    // Check if the class exists
    if ( ! class_exists( 'METS_Public' ) ) {
        return;
    }
    
    // Check if the method already exists
    if ( method_exists( 'METS_Public', 'register_shortcodes' ) ) {
        return;
    }
    
    // Add the missing method to the class
    $reflection = new ReflectionClass( 'METS_Public' );
    $methods = $reflection->getMethods();
    
    // We can't dynamically add methods to existing classes in PHP
    // Instead, we'll ensure the method is properly defined in the class file
    
    // Check the class file
    $class_file = METS_PLUGIN_PATH . 'public/class-mets-public.php';
    
    if ( ! file_exists( $class_file ) ) {
        return;
    }
    
    // Read the class file
    $content = file_get_contents( $class_file );
    
    // Check if the method is defined
    if ( strpos( $content, 'function register_shortcodes' ) === false ) {
        // Add the missing method
        $method_definition = "
	/**
	 * Register shortcodes
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'ticket_form', array( \$this, 'display_ticket_form' ) );
		add_shortcode( 'ticket_portal', array( \$this, 'display_customer_portal' ) );
		add_shortcode( 'guest_ticket_access', array( \$this, 'display_guest_ticket_access' ) );
	}
";
        
        // Find the position to insert the method (before the last closing brace)
        $last_brace_pos = strrpos( $content, '}' );
        
        if ( $last_brace_pos !== false ) {
            // Insert the method before the last closing brace
            $content = substr_replace( $content, $method_definition, $last_brace_pos, 0 );
            
            // Write the updated content back to the file
            file_put_contents( $class_file, $content );
        }
    }
}

// Run the fix
fix_mets_public_register_shortcodes();

/**
 * Ensure proper hook registration
 *
 * This function ensures that the register_shortcodes method is properly
 * registered with the WordPress init hook.
 */
function ensure_mets_shortcodes_registration() {
    // Check if METS_Core class exists
    if ( ! class_exists( 'METS_Core' ) ) {
        return;
    }
    
    // Get the plugin instance
    $plugin = METS_Core::get_instance();
    
    // Check if the loader exists
    if ( ! property_exists( $plugin, 'loader' ) ) {
        return;
    }
    
    // Get the loader
    $loader = $plugin->get_loader();
    
    // Check if the loader has the add_action method
    if ( ! method_exists( $loader, 'add_action' ) ) {
        return;
    }
    
    // Check if the hook is already registered
    global $wp_filter;
    
    if ( ! isset( $wp_filter['init'] ) ) {
        return;
    }
    
    // Check if our callback is already registered
    $hook_registered = false;
    foreach ( $wp_filter['init']->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback ) {
            if ( is_array( $callback['function'] ) && 
                 count( $callback['function'] ) == 2 &&
                 is_object( $callback['function'][0] ) &&
                 get_class( $callback['function'][0] ) == 'METS_Public' &&
                 $callback['function'][1] == 'register_shortcodes' ) {
                $hook_registered = true;
                break 2;
            }
        }
    }
    
    // If not registered, register it
    if ( ! $hook_registered ) {
        // Create a new instance of METS_Public
        $plugin_public = new METS_Public( 'multi-entity-ticket-system', '1.0.0' );
        
        // Register the hook
        add_action( 'init', array( $plugin_public, 'register_shortcodes' ) );
    }
}

// Register the hook
add_action( 'plugins_loaded', 'ensure_mets_shortcodes_registration', 20 );
?>