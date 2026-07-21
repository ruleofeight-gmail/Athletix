<?php
/**
 * Activation routine.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Runs on plugin activation. Boots the plugin so modules register their
 * activation listeners, fires `athletix/activate` (modules create tables,
 * register post types, seed roles, …), then flushes rewrite rules.
 */
class Activator {

	/**
	 * Activate.
	 *
	 * @return void
	 */
	public static function activate() {
		// Ensure modules are registered even though plugins_loaded has not
		// fired for this plugin during the activation request.
		$plugin = Plugin::instance();

		/**
		 * Fires during activation, after modules are registered.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'athletix/activate', $plugin );

		flush_rewrite_rules();

		update_option( 'athletix_version', ATHLETIX_VERSION );
		add_option( 'athletix_installed_at', time() );
	}
}
