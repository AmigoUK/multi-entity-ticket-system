<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @package    MultiEntityTicketSystem
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Only proceed if user wants to delete all data
 * This can be controlled via a setting in the plugin
 */
$delete_data = get_option( 'mets_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	// User chose to preserve data, exit without cleanup
	return;
}

/**
 * Remove all plugin data
 */

// Remove custom roles
$roles_to_remove = array( 'ticket_admin', 'ticket_manager', 'ticket_agent', 'ticket_customer' );

foreach ( $roles_to_remove as $role_name ) {
	remove_role( $role_name );
}

// Remove capabilities from administrator role
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
	$capabilities_to_remove = array(
		'manage_ticket_system',
		'manage_entities',
		'manage_tickets',
		'view_all_tickets',
		'assign_tickets',
		'edit_tickets',
		'delete_tickets',
		'manage_sla_rules',
		'view_reports',
		'export_data',
	);

	foreach ( $capabilities_to_remove as $cap ) {
		$admin_role->remove_cap( $cap );
	}
}

// Remove all plugin options
global $wpdb;

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'mets_%'" );

// Remove database tables
require_once plugin_dir_path( __FILE__ ) . 'database/class-mets-tables.php';
$tables = new METS_Tables();
$tables->drop_all_tables();

// Clear scheduled hooks
wp_clear_scheduled_hook( 'mets_process_sla_notifications' );
wp_clear_scheduled_hook( 'mets_process_escalations' );
wp_clear_scheduled_hook( 'mets_auto_close_resolved_tickets' );

// Remove uploaded files directory (if it exists)
$upload_dir = wp_upload_dir();
$mets_upload_dir = $upload_dir['basedir'] . '/mets-tickets/';

if ( is_dir( $mets_upload_dir ) ) {
	// Recursively delete directory and contents
	function mets_delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );

		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				mets_delete_directory( $path );
			} else {
				unlink( $path );
			}
		}

		return rmdir( $dir );
	}

	mets_delete_directory( $mets_upload_dir );
}