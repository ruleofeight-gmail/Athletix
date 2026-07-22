<?php
/**
 * Dashboard module.
 *
 * @package Athletix
 */

namespace Athletix\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the admin overview dashboard.
 */
class DashboardModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'dashboard';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		// The dashboard page itself is registered as the Athletix menu's landing
		// page by Admin\Hub (Dashboard tab); here we only add the wp-admin widgets.
		if ( is_admin() ) {
			( new Widgets( $plugin ) )->register();
		}
	}
}
