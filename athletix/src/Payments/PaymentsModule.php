<?php
/**
 * Payments module.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Wires the manual payment ledger and its admin meta box.
 */
class PaymentsModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'payments';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$ledger = new Ledger( $plugin->events() );
		$plugin->container()->instance( 'payments.ledger', $ledger );

		if ( is_admin() ) {
			( new PaymentsAdmin( $ledger ) )->register();
		}
	}
}
