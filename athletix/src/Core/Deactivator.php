<?php
/**
 * Deactivation routine.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Runs on deactivation. Fires `athletix/deactivate` (modules clear scheduled
 * events, caches, …) then flushes rewrite rules. Does not delete data — that
 * only happens on uninstall.
 */
class Deactivator {

	/**
	 * Deactivate.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$plugin = Plugin::instance();

		/**
		 * Fires during deactivation.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'athletix/deactivate', $plugin );

		flush_rewrite_rules();
	}
}
