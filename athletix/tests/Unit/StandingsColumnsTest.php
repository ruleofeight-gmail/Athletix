<?php
/**
 * Tests for StandingsColumns.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Customize\Defaults;
use Athletix\Engine\StandingsColumns;
use PHPUnit\Framework\TestCase;

/**
 * Verifies equation-computed columns and the priority-chain default sort.
 */
class StandingsColumnsTest extends TestCase {

	/**
	 * A raw aggregation row.
	 *
	 * @param int $team Team id.
	 * @param int $w    Won.
	 * @param int $d    Drawn.
	 * @param int $l    Lost.
	 * @param int $gf   Goals for.
	 * @param int $ga   Goals against.
	 * @return array
	 */
	private function row( $team, $w, $d, $l, $gf, $ga ) {
		return array(
			'team_id'       => $team,
			'played'        => $w + $d + $l,
			'won'           => $w,
			'drawn'         => $d,
			'lost'          => $l,
			'goals_for'     => $gf,
			'goals_against' => $ga,
		);
	}

	/**
	 * Default columns compute points and GD, and order the table.
	 *
	 * @return void
	 */
	public function test_default_columns_compute_and_order() {
		$columns = new StandingsColumns( Defaults::columns() );

		$rows = $columns->build(
			array(
				$this->row( 1, 2, 0, 1, 5, 4 ), // 6 pts, GD +1
				$this->row( 2, 3, 0, 0, 8, 2 ), // 9 pts, GD +6
				$this->row( 3, 1, 1, 1, 4, 4 ), // 4 pts, GD 0
			)
		);

		// Ordered by points desc: team 2, team 1, team 3.
		$this->assertSame( array( 2, 1, 3 ), array_column( $rows, 'team_id' ) );
		$this->assertSame( array( 1, 2, 3 ), array_column( $rows, 'rank' ) );

		$top = $rows[0];
		$this->assertSame( 9, $top['points'], '3 wins * 3 = 9.' );
		$this->assertSame( 6, $top['goal_difference'] );
	}

	/**
	 * The tie-break chain (points → GD → GF) breaks equal points.
	 *
	 * @return void
	 */
	public function test_priority_chain_breaks_ties() {
		$columns = new StandingsColumns( Defaults::columns() );

		$rows = $columns->build(
			array(
				$this->row( 1, 2, 0, 1, 4, 4 ), // 6 pts, GD 0
				$this->row( 2, 2, 0, 1, 9, 2 ), // 6 pts, GD +7
			)
		);

		$this->assertSame( 2, $rows[0]['team_id'], 'Equal points broken by goal difference.' );
	}

	/**
	 * A custom points equation (2 for a win) changes the order.
	 *
	 * @return void
	 */
	public function test_custom_points_equation() {
		$columns = array(
			array(
				'key'       => 'points',
				'equation'  => '( $w * 2 ) + $d',
				'precision' => 0,
				'sort'      => 1,
				'order'     => 'desc',
			),
		);

		$rows = ( new StandingsColumns( $columns ) )->build(
			array(
				$this->row( 1, 3, 0, 0, 0, 0 ), // 6 pts under 2-per-win
				$this->row( 2, 2, 3, 0, 0, 0 ), // 7 pts (4 + 3)
			)
		);

		$this->assertSame( 2, $rows[0]['team_id'] );
		$this->assertSame( 7, $rows[0]['points'] );
	}
}
