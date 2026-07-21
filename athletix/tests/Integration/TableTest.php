<?php
/**
 * Table implementations integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Admin\Tables\FilterableTable;
use Athletix\Admin\Tables\ListTable;

/**
 * Confirms both Table implementations behave in a real WordPress admin context:
 * the WP_List_Table wrapper prepares sorted/paginated items, and the custom
 * filterable table renders a filter bar and honours its filters.
 */
class TableTest extends IntegrationTestCase {

	/**
	 * Sample rows shared by the tests.
	 *
	 * @return array[]
	 */
	private function rows() {
		return array(
			array(
				'team'   => 'Rovers',
				'league' => '1',
				'points' => 9,
			),
			array(
				'team'   => 'United',
				'league' => '1',
				'points' => 4,
			),
			array(
				'team'   => 'City',
				'league' => '2',
				'points' => 12,
			),
		);
	}

	/**
	 * Reset request globals between tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET     = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * The WP_List_Table wrapper sorts and paginates through the DataSet engine.
	 *
	 * @return void
	 */
	public function test_list_table_prepares_sorted_page() {
		set_current_screen( 'toplevel_page_athletix' );

		$_REQUEST['orderby'] = 'points';
		$_REQUEST['order']   = 'desc';

		$table = new ListTable(
			array(
				'columns'  => array(
					'team'   => 'Team',
					'points' => 'Points',
				),
				'sortable' => array( 'team', 'points' ),
				'rows'     => $this->rows(),
				'per_page' => 2,
			)
		);

		$table->prepare_items();

		$this->assertCount( 2, $table->items, 'Page size is respected.' );
		$this->assertSame( 'City', $table->items[0]['team'], 'Highest points first.' );
		$this->assertSame( 'Rovers', $table->items[1]['team'] );
	}

	/**
	 * The custom filterable table renders a filter bar and applies its filter.
	 *
	 * @return void
	 */
	public function test_filterable_table_filters_and_renders() {
		$_GET = array(
			'page'          => 'athletix-demo',
			'filter_league' => '2',
		);

		$table = new FilterableTable(
			array(
				'columns'    => array(
					'team'   => 'Team',
					'points' => 'Points',
				),
				'sortable'   => array( 'team', 'points' ),
				'searchable' => array( 'team' ),
				'filters'    => array(
					'league' => array(
						'label'   => 'League',
						'options' => array(
							'1' => 'League One',
							'2' => 'League Two',
						),
					),
				),
				'rows'       => $this->rows(),
				'base_url'   => 'http://example.org/wp-admin/admin.php?page=athletix-demo',
			)
		);

		ob_start();
		$table->render();
		$html = ob_get_clean();

		// Filter bar rendered.
		$this->assertStringContainsString( 'name="filter_league"', $html );
		$this->assertStringContainsString( 'name="s"', $html );

		// Only league 2 rows survive the filter.
		$this->assertStringContainsString( 'City', $html );
		$this->assertStringNotContainsString( 'Rovers', $html );
		$this->assertStringNotContainsString( 'United', $html );

		// Sortable header is a link.
		$this->assertStringContainsString( 'orderby=points', $html );
	}
}
