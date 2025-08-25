<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @since      1.0.0
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    MultiEntityTicketSystem
 * @subpackage MultiEntityTicketSystem/includes
 * @author     Tomasz Lewandowski <lewandowski.tl@gmail.com>
 */
class METS_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		// Only load textdomain after init action to avoid warnings
		if ( ! did_action( 'init' ) ) {
			return;
		}
		
		load_plugin_textdomain(
			METS_TEXT_DOMAIN,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}