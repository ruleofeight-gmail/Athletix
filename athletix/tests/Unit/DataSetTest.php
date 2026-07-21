<?php
/**
 * Tests for the DataSet table engine.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Admin\Tables\DataSet;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the shared filter/search/sort/paginate logic that powers both the
 * WP_List_Table wrapper and the custom filterable table.
 */
class DataSetTest extends TestCase {

	/**
	 * Sample rows.
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
			array(
				'team'   => 'Athletic',
				'league' => '2',
				'points' => 4,
			),
		);
	}

	/**
	 * Exact filters keep only matching rows and report the filtered total.
	 *
	 * @return void
	 */
	public function test_filters_exactly() {
		$out = DataSet::process(
			$this->rows(),
			array( 'filters' => array( 'league' => '2' ) )
		);

		$this->assertSame( 2, $out['total'] );
		$this->assertSame( array( 'City', 'Athletic' ), array_column( $out['items'], 'team' ) );
	}

	/**
	 * Blank filter values are ignored.
	 *
	 * @return void
	 */
	public function test_blank_filter_is_ignored() {
		$out = DataSet::process(
			$this->rows(),
			array( 'filters' => array( 'league' => '' ) )
		);

		$this->assertSame( 4, $out['total'] );
	}

	/**
	 * Keyword search matches a substring across searchable columns only.
	 *
	 * @return void
	 */
	public function test_searches_substring_case_insensitively() {
		$out = DataSet::process(
			$this->rows(),
			array(
				'search'     => 'it',
				'searchable' => array( 'team' ),
			)
		);

		// "United" and "City" both contain "it".
		$this->assertSame( array( 'United', 'City' ), array_column( $out['items'], 'team' ) );
	}

	/**
	 * Numeric columns sort numerically, not lexically.
	 *
	 * @return void
	 */
	public function test_sorts_numeric_desc() {
		$out = DataSet::process(
			$this->rows(),
			array(
				'orderby' => 'points',
				'order'   => 'DESC',
			)
		);

		$this->assertSame( array( 12, 9, 4, 4 ), array_column( $out['items'], 'points' ) );
	}

	/**
	 * String columns sort case-insensitively ascending.
	 *
	 * @return void
	 */
	public function test_sorts_string_asc() {
		$out = DataSet::process(
			$this->rows(),
			array(
				'orderby' => 'team',
				'order'   => 'ASC',
			)
		);

		$this->assertSame( array( 'Athletic', 'City', 'Rovers', 'United' ), array_column( $out['items'], 'team' ) );
	}

	/**
	 * Pagination slices the page while total reflects the full match count.
	 *
	 * @return void
	 */
	public function test_paginates() {
		$out = DataSet::process(
			$this->rows(),
			array(
				'orderby'  => 'team',
				'order'    => 'ASC',
				'page'     => 2,
				'per_page' => 2,
			)
		);

		$this->assertSame( 4, $out['total'] );
		$this->assertSame( array( 'Rovers', 'United' ), array_column( $out['items'], 'team' ) );
	}

	/**
	 * Filter, search and sort compose in one call.
	 *
	 * @return void
	 */
	public function test_composes_filter_search_sort() {
		$out = DataSet::process(
			$this->rows(),
			array(
				'filters'    => array( 'league' => '2' ),
				'search'     => 'c',
				'searchable' => array( 'team' ),
				'orderby'    => 'points',
				'order'      => 'DESC',
			)
		);

		// League 2 = City(12), Athletic(4); keyword "c" keeps both; sorted desc.
		$this->assertSame( array( 'City', 'Athletic' ), array_column( $out['items'], 'team' ) );
	}
}
