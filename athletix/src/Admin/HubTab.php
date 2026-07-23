<?php
/**
 * Shared hub-tab registration for admin screens.
 *
 * @package Athletix
 */

namespace Athletix\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * A screen that contributes a tab to the Athletix hub uses this trait to declare
 * it in one line, instead of hand-writing the athletix/admin_tabs filter and the
 * tab descriptor. Keeping the descriptor shape (label / cap / order / callback)
 * in a single place means the hub's tab contract can evolve without touching
 * every screen. The tab's panel is the using class's render() method unless a
 * callback is supplied.
 */
trait HubTab {

	/**
	 * Contribute a tab to the Athletix hub.
	 *
	 * @param string   $slug     Tab slug (its query var + array key).
	 * @param string   $label    Tab label.
	 * @param int      $order    Sort order in the tab strip.
	 * @param string   $cap      Capability required (default: manage Athletix).
	 * @param callable $callback Panel renderer (default: $this->render()).
	 * @return void
	 */
	protected function register_hub_tab( $slug, $label, $order, $cap = '', $callback = null ) {
		$cap      = '' !== $cap ? $cap : Keys::capability();
		$callback = null !== $callback ? $callback : array( $this, 'render' );

		add_filter(
			'athletix/admin_tabs',
			static function ( $tabs ) use ( $slug, $label, $order, $cap, $callback ) {
				$tabs[ $slug ] = array(
					'label'    => $label,
					'cap'      => $cap,
					'order'    => $order,
					'callback' => $callback,
				);

				return (array) $tabs;
			}
		);
	}
}
