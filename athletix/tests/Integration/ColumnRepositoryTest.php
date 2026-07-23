<?php
/**
 * List-column repository integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Customize\ColumnRepository;
use Athletix\Support\Keys;

/**
 * Confirms the content lists draw their columns from the admin-defined List
 * Columns — order, labels and which fields appear — falling back to the full
 * catalogue when none are configured.
 */
class ColumnRepositoryTest extends IntegrationTestCase {

	/**
	 * Delete every seeded List Column for a list so a test starts clean.
	 *
	 * @param string $list List post type.
	 * @return void
	 */
	private function clear_columns( $list ) {
		$posts = get_posts(
			array(
				'post_type'      => Keys::LIST_COLUMN,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::COL_LIST,
						'value' => $list,
					),
				),
			)
		);
		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}
	}

	/**
	 * Create a List Column mapping to a catalogue field.
	 *
	 * @param string $label  Column heading.
	 * @param string $list   List post type.
	 * @param string $source Source field key.
	 * @param int    $order  menu_order.
	 * @return void
	 */
	private function add_column( $label, $list, $source, $order ) {
		$id = wp_insert_post(
			array(
				'post_type'   => Keys::LIST_COLUMN,
				'post_status' => 'publish',
				'post_title'  => $label,
				'menu_order'  => $order,
			)
		);
		update_post_meta( $id, Keys::COL_LIST, $list );
		update_post_meta( $id, Keys::COL_SOURCE, $source );
	}

	/**
	 * The seeded defaults give every Teams column with its catalogue label.
	 *
	 * @return void
	 */
	public function test_seeded_defaults_present() {
		$columns = ( new ColumnRepository() )->columns( Keys::TEAM );

		$this->assertArrayHasKey( 'axl_venue', $columns );
		$this->assertArrayHasKey( 'axl_founded', $columns );
		$this->assertArrayHasKey( 'axl_color', $columns );
		$this->assertTrue( $columns['axl_founded']['numeric'], 'Founded sorts numerically.' );
	}

	/**
	 * Admin-defined columns control order, label and which fields appear.
	 *
	 * @return void
	 */
	public function test_admin_columns_reorder_rename_and_subset() {
		$this->clear_columns( Keys::TEAM );

		// Only two columns, Founded first (renamed), then Venue. No colour.
		$this->add_column( 'Est.', Keys::TEAM, 'founded', 0 );
		$this->add_column( 'Ground', Keys::TEAM, 'venue', 1 );

		$columns = ( new ColumnRepository() )->columns( Keys::TEAM );

		$this->assertSame( array( 'axl_founded', 'axl_venue' ), array_keys( $columns ), 'Order follows menu_order.' );
		$this->assertSame( 'Est.', $columns['axl_founded']['label'], 'Title becomes the heading.' );
		$this->assertSame( 'Ground', $columns['axl_venue']['label'] );
		$this->assertArrayNotHasKey( 'axl_color', $columns, 'Unlisted fields are dropped.' );

		// The source resolution still carries the real meta + type from the catalogue.
		$this->assertSame( Keys::TEAM_FOUNDED, $columns['axl_founded']['meta'] );
		$this->assertTrue( $columns['axl_founded']['numeric'] );
	}

	/**
	 * With no columns configured, the full catalogue is used.
	 *
	 * @return void
	 */
	public function test_fallback_to_catalogue_when_empty() {
		$this->clear_columns( Keys::MATCH );

		$columns = ( new ColumnRepository() )->columns( Keys::MATCH );

		$this->assertArrayHasKey( 'axl_date', $columns );
		$this->assertArrayHasKey( 'axl_status', $columns );
		$this->assertArrayHasKey( 'axl_home', $columns );
	}
}
