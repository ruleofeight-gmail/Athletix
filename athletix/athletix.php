<?php
/**
 * Plugin Name:       Athletix
 * Plugin URI:        https://github.com/ruleofeight-gmail/athletix
 * Description:       Modular sports-league management for WordPress — teams, players, matches, standings, competitions and a full Elementor integration.
 * Version:           2.1.3
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

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/
define( 'ATHLETIX_VERSION', '2.1.3' );
define( 'ATHLETIX_FILE', __FILE__ );
define( 'ATHLETIX_PATH', plugin_dir_path( __FILE__ ) );
define( 'ATHLETIX_URL', plugin_dir_url( __FILE__ ) );
define( 'ATHLETIX_BASENAME', plugin_basename( __FILE__ ) );
define( 'ATHLETIX_MINIMUM_ELEMENTOR_VERSION', '3.5.0' );

/*
|--------------------------------------------------------------------------
| Autoloader
|--------------------------------------------------------------------------
| Prefer Composer's autoloader when the package has been installed; otherwise
| fall back to a lightweight PSR-4 loader mapping `Athletix\<Ns>` => src/<Ns>.php.
*/
if ( is_readable( ATHLETIX_PATH . 'vendor/autoload.php' ) ) {
	require ATHLETIX_PATH . 'vendor/autoload.php';
} else {
	require ATHLETIX_PATH . 'src/Support/Autoloader.php';
	Support\Autoloader::register( 'Athletix\\', ATHLETIX_PATH . 'src/' );
}

/*
|--------------------------------------------------------------------------
| Lifecycle hooks
|--------------------------------------------------------------------------
*/
register_activation_hook( __FILE__, array( Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Core\Deactivator::class, 'deactivate' ) );

/*
|--------------------------------------------------------------------------
| Boot
|--------------------------------------------------------------------------
*/
/**
 * Shared plugin instance.
 *
 * @return Plugin
 */
function plugin() {
	return Plugin::instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\plugin', 5 );
