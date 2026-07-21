<?php
/**
 * Schema installation integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Data\Schema;

/**
 * Verifies the custom tables are created.
 */
class SchemaTest extends IntegrationTestCase {

	/**
	 * Each custom table should exist after activation.
	 *
	 * @return void
	 */
	public function test_tables_exist() {
		global $wpdb;

		foreach ( array( Schema::STANDINGS, Schema::PLAYER_STATS, Schema::RELATIONSHIPS ) as $table ) {
			$name  = Schema::table( $table );
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $name ) ); // phpcs:ignore WordPress.DB
			$this->assertSame( $name, $found, "Table {$name} should exist." );
		}
	}

	/**
	 * The db version option should be recorded.
	 *
	 * @return void
	 */
	public function test_db_version_recorded() {
		$this->assertSame( Schema::DB_VERSION, get_option( Schema::VERSION_OPTION ) );
	}
}
