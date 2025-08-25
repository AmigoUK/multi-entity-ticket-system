<?php
/**
 * Capability Updater for METS
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Capability updater class
 *
 * This class handles updating user capabilities for existing installations
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Capability_Updater {

	/**
	 * Update capabilities for existing users
	 *
	 * @since    1.0.0
	 */
	public static function update_capabilities() {
		// Add manage_agents capability to administrators
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_agents' );
		}
		
		// Add manage_agents capability to ticket_admin role if it exists
		$ticket_admin_role = get_role( 'ticket_admin' );
		if ( $ticket_admin_role ) {
			$ticket_admin_role->add_cap( 'manage_agents' );
		}
		
		// Update version to prevent running this again
		update_option( 'mets_capability_version', '1.0.1' );
		
		return true;
	}
	
	/**
	 * Check if capability update is needed
	 *
	 * @since    1.0.0
	 */
	public static function needs_update() {
		$version = get_option( 'mets_capability_version', '1.0.0' );
		return version_compare( $version, '1.0.1', '<' );
	}
}