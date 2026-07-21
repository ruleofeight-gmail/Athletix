<?php
/**
 * Tests for StandingsSorter.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Engine\StandingsSorter;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the configurable tie-break ordering.
 */
class StandingsSorterTest extends TestCase {

	/**
	 * Build a row with sensible defaults.
	 *
	 * @param int   $team      Team id.
	 * @param array $overrides Column overrides.
	 * @return array
	 */
	private function row( $team, array $overrides = array() ) {
		return array_merge(
			array(
				'team_id'       => $team,
				'played'        => 0,
				'won'           => 0,
				'drawn'         => 0,
				'lost'          => 0,
				'goals_for'     => 0,
				'goals_against' => 0,
				'points'        => 0,
			),
			$overrides
		);
	}

	/**
	 * Points is the primary key and rank is stamped from 1.
	 *
	 * @return void
	 */
	public function test_orders_by_points_and_stamps_rank() {
		$sorter = new StandingsSorter();

		$sorted = $sorter->sort(
			array(
				$this->row( 1, array( 'points' => 3 ) ),
				$this->row( 2, array( 'points' => 9 ) ),
				$this->row( 3, array( 'points' => 6 ) ),
			)
		);

		$this->assertSame( array( 2, 3, 1 ), array_column( $sorted, 'team_id' ) );
		$this->assertSame( array( 1, 2, 3 ), array_column( $sorted, 'rank' ) );
	}

	/**
	 * Equal points break on goal difference by default.
	 *
	 * @return void
	 */
	public function test_default_breaks_tie_on_goal_difference() {
		$sorter = new StandingsSorter();

		$sorted = $sorter->sort(
			array(
				$this->row(
					1,
					array(
						'points'        => 6,
						'goals_for'     => 4,
						'goals_against' => 4,
					)
				), // GD 0
				$this->row(
					2,
					array(
						'points'        => 6,
						'goals_for'     => 8,
						'goals_against' => 2,
					)
				), // GD 6
			)
		);

		$this->assertSame( array( 2, 1 ), array_column( $sorted, 'team_id' ) );
	}

	/**
	 * A custom chain can prefer wins over goal difference.
	 *
	 * @return void
	 */
	public function test_custom_chain_prefers_wins() {
		$default = new StandingsSorter();
		$rows    = array(
			$this->row(
				1,
				array(
					'points'        => 6,
					'won'           => 2,
					'goals_for'     => 10,
					'goals_against' => 1,
				)
			), // GD 9, 2 wins
			$this->row(
				2,
				array(
					'points'        => 6,
					'won'           => 3,
					'goals_for'     => 5,
					'goals_against' => 4,
				)
			),   // GD 1, 3 wins
		);

		// Default: team 1 first (better GD).
		$this->assertSame( array( 1, 2 ), array_column( $default->sort( $rows ), 'team_id' ) );

		// Wins-first chain: team 2 first (more wins).
		$wins = new StandingsSorter( array( 'points', 'won', 'goal_difference' ) );
		$this->assertSame( array( 2, 1 ), array_column( $wins->sort( $rows ), 'team_id' ) );
	}

	/**
	 * Unknown fields are dropped and an all-invalid chain falls back to default.
	 *
	 * @return void
	 */
	public function test_invalid_chain_falls_back_to_default() {
		$sorter = new StandingsSorter( array( 'bogus', 'also_bad' ) );
		$this->assertSame( StandingsSorter::DEFAULT_CHAIN, $sorter->chain() );

		$mixed = new StandingsSorter( array( 'points', 'nonsense', 'goals_for' ) );
		$this->assertSame( array( 'points', 'goals_for' ), $mixed->chain() );
	}

	/**
	 * Fully tied rows fall back to team id for a stable, deterministic order.
	 *
	 * @return void
	 */
	public function test_full_tie_is_deterministic_by_team_id() {
		$sorter = new StandingsSorter();

		$sorted = $sorter->sort(
			array(
				$this->row( 30, array( 'points' => 5 ) ),
				$this->row( 10, array( 'points' => 5 ) ),
				$this->row( 20, array( 'points' => 5 ) ),
			)
		);

		$this->assertSame( array( 10, 20, 30 ), array_column( $sorted, 'team_id' ) );
	}

	/**
	 * Goal difference is derived when a row omits it.
	 *
	 * @return void
	 */
	public function test_derives_goal_difference_when_absent() {
		$sorter = new StandingsSorter();

		$sorted = $sorter->sort(
			array(
				$this->row(
					1,
					array(
						'points'        => 3,
						'goals_for'     => 2,
						'goals_against' => 5,
					)
				),
				$this->row(
					2,
					array(
						'points'        => 3,
						'goals_for'     => 9,
						'goals_against' => 1,
					)
				),
			)
		);

		$this->assertSame( 8, $sorted[0]['goal_difference'] );
		$this->assertSame( 2, $sorted[0]['team_id'] );
	}
}
