<?php
/**
 * Plugin Name:       Athletix
 * Plugin URI:        https://github.com/ruleofeight-gmail/athletix
 * Description:       Sports & fitness toolkit for WordPress — athlete, team and event management with a full Elementor integration (widgets, dynamic tags and Theme Builder support).
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Athletix
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       athletix
 * Domain Path:       /languages
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Core plugin constants.
 */
define( 'ATHLETIX_VERSION', '1.0.0' );
define( 'ATHLETIX_FILE', __FILE__ );
define( 'ATHLETIX_PATH', plugin_dir_path( __FILE__ ) );
define( 'ATHLETIX_URL', plugin_dir_url( __FILE__ ) );
define( 'ATHLETIX_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum required Elementor version for the integration to load.
 */
define( 'ATHLETIX_MINIMUM_ELEMENTOR_VERSION', '3.5.0' );

require_once ATHLETIX_PATH . 'includes/class-athletix.php';

/**
 * Activation / deactivation lifecycle.
 */
register_activation_hook( __FILE__, array( 'Athletix\\Athletix', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Athletix\\Athletix', 'deactivate' ) );

/**
 * Bootstrap the plugin.
 *
 * @return \Athletix\Athletix
 */
function athletix() {
	return \Athletix\Athletix::instance();
}

athletix();
