<?php
/**
 * Admin module.
 *
 * @package Athletix
 */

namespace Athletix\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Wires the unified top-level Athletix admin menu.
 */
class AdminModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'admin';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		if ( is_admin() ) {
			( new Hub( $plugin ) )->register();
			( new LeagueTablesPage( $plugin ) )->register();
		}
	}
}
