<?php
/**
 * Analytics module.
 *
 * @package Athletix
 */

namespace Athletix\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers statistical leaderboards.
 */
class AnalyticsModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'analytics';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new Leaderboards( $plugin ) )->register();
	}
}
