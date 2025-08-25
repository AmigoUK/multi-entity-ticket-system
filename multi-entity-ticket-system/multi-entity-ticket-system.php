<?php
/**
 * Multi-Entity Ticket System
 *
 * @package           MultiEntityTicketSystem
 * @author            Tomasz 'Amigo' Lewandowski
 * @copyright         2025 Tomasz Lewandowski
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Multi-Entity Ticket System
 * Plugin URI:        https://attv.uk/multi-entity-ticket-system
 * Description:       Centralized customer service ticket management system for multiple cooperative businesses with hierarchical entity structure.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Tomasz 'Amigo' Lewandowski
 * Author URI:        https://attv.uk
 * Text Domain:       multi-entity-ticket-system
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 3.0
 * WC tested up to:  8.0
 * Network:          false
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'METS_VERSION', '1.0.0' );

/**
 * Plugin constants
 */
define( 'METS_PLUGIN_FILE', __FILE__ );
define( 'METS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'METS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'METS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'METS_TEXT_DOMAIN', 'multi-entity-ticket-system' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mets-activator.php
 */
function activate_multi_entity_ticket_system() {
	require_once METS_PLUGIN_PATH . 'includes/class-mets-activator.php';
	METS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mets-deactivator.php
 */
function deactivate_multi_entity_ticket_system() {
	require_once METS_PLUGIN_PATH . 'includes/class-mets-deactivator.php';
	METS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_multi_entity_ticket_system' );
register_deactivation_hook( __FILE__, 'deactivate_multi_entity_ticket_system' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require METS_PLUGIN_PATH . 'includes/class-mets-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_multi_entity_ticket_system() {
	$plugin = METS_Core::get_instance();
	$plugin->run();
}
run_multi_entity_ticket_system();