<?php
/**
 * Admin list-screen columns/filters integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Admin\Lists\ListScreen;
use Athletix\Support\Keys;
use WP_Query;

/**
 * Confirms the generic ListScreen engine adds its columns, marks them sortable,
 * and rewrites a column sort into a meta-ordered query — the standard-hook path
 * that gives the content lists sortable, filterable meta columns.
 */
class ListScreenTest extends IntegrationTestCase {

	/**
	 * A ListScreen for the Teams config.
	 *
	 * @return ListScreen
	 */
	private function screen() {
		return new ListScreen(
			array(
				'post_type' => Keys::TEAM,
				'columns'   => array(
					'axl_venue'   => array(
						'label' => 'Venue',
						'meta'  => Keys::TEAM_VENUE,
						'type'  => 'text',
					),
					'axl_founded' => array(
						'label'   => 'Founded',
						'meta'    => Keys::TEAM_FOUNDED,
						'type'    => 'number',
						'numeric' => true,
					),
				),
				'filters'   => array( Keys::TAX_SPORT ),
			)
		);
	}

	/**
	 * Configured columns are inserted before the Date column.
	 *
	 * @return void
	 */
	public function test_columns_are_added_before_date() {
		$columns = $this->screen()->columns(
			array(
				'cb'    => '',
				'title' => 'Title',
				'date'  => 'Date',
			)
		);

		$keys = array_keys( $columns );
		$this->assertContains( 'axl_venue', $keys );
		$this->assertContains( 'axl_founded', $keys );
		$this->assertLessThan( array_search( 'date', $keys, true ), array_search( 'axl_venue', $keys, true ), 'Venue sits before Date.' );
	}

	/**
	 * Every configured column is declared sortable.
	 *
	 * @return void
	 */
	public function test_all_columns_are_sortable() {
		$sortable = $this->screen()->sortable_columns( array() );

		$this->assertArrayHasKey( 'axl_venue', $sortable );
		$this->assertArrayHasKey( 'axl_founded', $sortable );
	}

	/**
	 * A column sort becomes a numeric meta-ordered query on the list screen.
	 *
	 * @return void
	 */
	public function test_numeric_column_sort_becomes_meta_value_num() {
		set_current_screen( 'edit-' . Keys::TEAM );

		$query = new WP_Query();
		$query->set( 'post_type', Keys::TEAM );
		$query->set( 'orderby', 'axl_founded' );

		// Make it the "main" query so the guard passes.
		global $wp_the_query;
		$previous     = $wp_the_query;
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- fake the main query for the guard.

		$this->screen()->apply_query( $query );

		$wp_the_query = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restore the main query.
		set_current_screen( 'front' );

		$this->assertSame( Keys::TEAM_FOUNDED, $query->get( 'meta_key' ) );
		$this->assertSame( 'meta_value_num', $query->get( 'orderby' ) );
	}

	/**
	 * A sort on an unrelated column is left untouched.
	 *
	 * @return void
	 */
	public function test_unrelated_sort_is_untouched() {
		set_current_screen( 'edit-' . Keys::TEAM );

		$query = new WP_Query();
		$query->set( 'post_type', Keys::TEAM );
		$query->set( 'orderby', 'title' );

		global $wp_the_query;
		$previous     = $wp_the_query;
		$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- fake the main query for the guard.

		$this->screen()->apply_query( $query );

		$wp_the_query = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restore the main query.
		set_current_screen( 'front' );

		$this->assertSame( '', $query->get( 'meta_key' ) );
		$this->assertSame( 'title', $query->get( 'orderby' ) );
	}
}
