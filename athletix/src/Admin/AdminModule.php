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

use Athletix\Admin\Lists\ListConfig;
use Athletix\Admin\Lists\ListScreen;
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
			( new StandingsTab( $plugin ) )->register();
			( new QuickAdd( $plugin ) )->register();

			// Sortable + filterable meta columns on the content list screens,
			// driven by config through the standard WordPress list-table hooks.
			foreach ( ListConfig::all() as $config ) {
				( new ListScreen( $config ) )->register();
			}
		}
	}
}
