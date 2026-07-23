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

use Athletix\Admin\Lists\ListScreen;
use Athletix\Contracts\Module;
use Athletix\Customize\ColumnCatalog;
use Athletix\Customize\ColumnRepository;
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
			// resolved from the admin-defined List Columns (with a catalogue
			// fallback) and rendered through the standard WordPress list-table
			// hooks.
			$columns = new ColumnRepository();
			foreach ( ColumnCatalog::lists() as $post_type ) {
				( new ListScreen( $columns->config( $post_type ) ) )->register();
			}
		}
	}
}
