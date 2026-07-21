<?php
/**
 * Reports module.
 *
 * @package Athletix
 */

namespace Athletix\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the league report shortcode.
 */
class ReportsModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'reports';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new ReportBuilder( $plugin ) )->register();
	}
}
