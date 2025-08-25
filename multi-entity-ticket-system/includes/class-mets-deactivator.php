<?php
/**
 * Fired during plugin deactivation
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled hooks
		wp_clear_scheduled_hook( 'mets_process_sla_notifications' );
		wp_clear_scheduled_hook( 'mets_process_escalations' );
		wp_clear_scheduled_hook( 'mets_auto_close_resolved_tickets' );
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Note: We don't remove user roles or database tables on deactivation
		// This allows for reactivation without data loss
		// Cleanup only happens during uninstall
	}
}