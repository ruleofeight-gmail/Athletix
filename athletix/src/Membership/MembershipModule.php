<?php
/**
 * Membership module.
 *
 * @package Athletix
 */

namespace Athletix\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers front-end team registration.
 */
class MembershipModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'membership';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new Registration( $plugin ) )->register();
	}
}
