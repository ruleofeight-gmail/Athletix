<?php
/**
 * Base class for Athletix WordPress integration tests.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Data\Schema;
use WP_UnitTestCase;

/**
 * Ensures the plugin's custom tables and roles are installed before tests.
 */
abstract class IntegrationTestCase extends WP_UnitTestCase {

	/**
	 * Create the custom tables once per class, outside the per-test
	 * transaction. WordPress wraps each test in a transaction that is rolled
	 * back, and DDL (CREATE TABLE) does not play well inside it; creating the
	 * tables here guarantees they exist for every test.
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->install();
	}

	/**
	 * Set up: run the plugin's activation installers (roles, etc.).
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// Runs Roles::ensure() and the (idempotent) Schema::install() via hooks.
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

	/**
	 * Create an Athletix taxonomy term and return its id.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $name     Term name.
	 * @return int
	 */
	protected function make_term( $taxonomy, $name ) {
		$term = wp_insert_term( $name, $taxonomy );

		return is_wp_error( $term ) ? 0 : (int) $term['term_id'];
	}
}
