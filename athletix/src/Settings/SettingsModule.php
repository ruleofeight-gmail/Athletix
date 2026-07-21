<?php
/**
 * Settings module.
 *
 * @package Athletix
 */

namespace Athletix\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the unified settings screen.
 */
class SettingsModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'settings';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		unset( $plugin );

		if ( is_admin() ) {
			( new SettingsPage() )->register();
		}
	}
}
