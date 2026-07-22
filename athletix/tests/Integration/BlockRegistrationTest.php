<?php
/**
 * Block metadata registration integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use WP_Block_Type_Registry;

/**
 * Confirms the blocks register from their block.json metadata — attributes,
 * defaults and category come from the JSON, not from PHP arrays.
 */
class BlockRegistrationTest extends IntegrationTestCase {

	/**
	 * Every Athletix block is registered on init.
	 *
	 * @return void
	 */
	public function test_all_blocks_are_registered() {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( array( 'standings', 'roster', 'schedule', 'bracket', 'match', 'player' ) as $slug ) {
			$this->assertTrue(
				$registry->is_registered( 'athletix/' . $slug ),
				"athletix/{$slug} should be registered from block.json."
			);
		}
	}

	/**
	 * Attributes and category are sourced from the block.json metadata.
	 *
	 * @return void
	 */
	public function test_metadata_drives_attributes_and_category() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'athletix/standings' );

		$this->assertNotNull( $block );
		$this->assertSame( 'athletix', $block->category );
		$this->assertArrayHasKey( 'league', $block->attributes );
		$this->assertSame( 0, $block->attributes['league']['default'] );
		$this->assertArrayHasKey( 'season', $block->attributes );
	}

	/**
	 * The render callback survives the metadata migration (dynamic block).
	 *
	 * @return void
	 */
	public function test_blocks_keep_their_render_callback() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'athletix/bracket' );

		$this->assertNotNull( $block );
		$this->assertIsCallable( $block->render_callback );
	}
}
