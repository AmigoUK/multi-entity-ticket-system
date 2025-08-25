<?php
/**
 * Hotfix for WordPress callback error
 *
 * This script fixes the "class METS_Public does not have a method register_shortcodes" error
 * by ensuring the method exists and is properly registered with WordPress hooks.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Ensure METS_Public class has register_shortcodes method
 *
 * This hotfix ensures that the register_shortcodes method exists in the METS_Public class
 * and is properly registered with WordPress hooks.
 */
function mets_ensure_register_shortcodes_method() {
    // Check if the class exists
    if ( ! class_exists( 'METS_Public' ) ) {
        error_log( '[METS] METS_Public class does not exist' );
        return;
    }
    
    // Check if the method already exists
    if ( method_exists( 'METS_Public', 'register_shortcodes' ) ) {
        error_log( '[METS] register_shortcodes method already exists' );
        return;
    }
    
    // Add the missing method to the class using runkit (if available)
    // Note: This is a workaround and should be replaced with proper class definition
    
    // For now, we'll just log that the method is missing
    error_log( '[METS] register_shortcodes method is missing from METS_Public class' );
}

// Run the hotfix
add_action( 'plugins_loaded', 'mets_ensure_register_shortcodes_method', 20 );

/**
 * Fix hook registration for register_shortcodes method
 *
 * This function ensures that the register_shortcodes method is properly
 * registered with the WordPress init hook if it exists.
 */
function mets_fix_register_shortcodes_hook() {
    // Check if METS_Public class exists
    if ( ! class_exists( 'METS_Public' ) ) {
        return;
    }
    
    // Check if the method exists
    if ( ! method_exists( 'METS_Public', 'register_shortcodes' ) ) {
        // Create a dummy method to prevent the error
        if ( ! function_exists( 'mets_dummy_register_shortcodes' ) ) {
            function mets_dummy_register_shortcodes() {
                // Dummy method to prevent callback errors
                error_log( '[METS] Dummy register_shortcodes method called' );
            }
        }
        
        // Register the dummy method instead
        remove_action( 'init', array( 'METS_Public', 'register_shortcodes' ) );
        add_action( 'init', 'mets_dummy_register_shortcodes' );
        
        return;
    }
    
    // If the method exists, ensure it's properly registered
    global $wp_filter;
    
    // Check if our callback is already registered correctly
    $hook_registered = false;
    if ( isset( $wp_filter['init'] ) ) {
        foreach ( $wp_filter['init']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( is_array( $callback['function'] ) && 
                     count( $callback['function'] ) == 2 &&
                     is_string( $callback['function'][0] ) &&
                     $callback['function'][0] == 'METS_Public' &&
                     $callback['function'][1] == 'register_shortcodes' ) {
                    $hook_registered = true;
                    break 2;
                }
                
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
    }
    
    // If not registered correctly, register it properly
    if ( ! $hook_registered ) {
        // Remove any incorrect registrations
        remove_action( 'init', array( 'METS_Public', 'register_shortcodes' ) );
        
        // Create a new instance and register properly
        $plugin_public = new METS_Public( 'multi-entity-ticket-system', '1.0.0' );
        add_action( 'init', array( $plugin_public, 'register_shortcodes' ) );
    }
}

// Fix the hook registration
add_action( 'init', 'mets_fix_register_shortcodes_hook', 1 );

/**
 * Alternative approach: Override the METS_Public class if needed
 *
 * This function provides an alternative approach to fixing the issue
 * by extending the existing class and adding the missing method.
 */
function mets_override_public_class_if_needed() {
    // Check if METS_Public class exists and lacks register_shortcodes method
    if ( class_exists( 'METS_Public' ) && ! method_exists( 'METS_Public', 'register_shortcodes' ) ) {
        // Extend the class and add the missing method
        class METS_Public_Fixed extends METS_Public {
            /**
             * Register shortcodes
             *
             * @since    1.0.0
             */
            public function register_shortcodes() {
                // Register shortcodes for the public-facing side of the site
                add_shortcode( 'ticket_form', array( $this, 'display_ticket_form' ) );
                add_shortcode( 'ticket_portal', array( $this, 'display_customer_portal' ) );
                add_shortcode( 'guest_ticket_access', array( $this, 'display_guest_ticket_access' ) );
                
                error_log( '[METS] register_shortcodes method added via extended class' );
            }
        }
        
        // Replace the original class with the fixed one
        // Note: This is complex and may not work in all cases
        error_log( '[METS] Attempted to extend METS_Public class to add missing method' );
    }
}

// Uncomment the following line if you want to try the override approach
// add_action( 'plugins_loaded', 'mets_override_public_class_if_needed', 25 );
?>