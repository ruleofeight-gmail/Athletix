<?php
/**
 * Base class for Athletix WordPress integration tests.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use WP_UnitTestCase;

/**
 * Ensures the plugin's custom tables and roles are installed before each test.
 */
abstract class IntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Set up: run the plugin's activation installers.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// Runs Schema::install() and Roles::ensure() via their hooks.
		do_action( 'athletix/activate' );
	}

	/**
	 * The booted plugin instance.
	 *
	 * @return \Athletix\Plugin
	 */
	protected function plugin() {
		return \Athletix\Plugin::instance();
	}
}
