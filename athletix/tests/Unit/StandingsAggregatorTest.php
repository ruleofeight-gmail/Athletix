<?php
/**
 * Tests for StandingsAggregator.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Customize\Defaults;
use Athletix\Engine\StandingsAggregator;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that outcome equations decide win/draw/loss during aggregation.
 */
class StandingsAggregatorTest extends TestCase {

	/**
	 * Build a match row.
	 *
	 * @param int $home Home team.
	 * @param int $away Away team.
	 * @param int $hs   Home score.
	 * @param int $as   Away score.
	 * @return array
	 */
	private function match( $home, $away, $hs, $as ) {
		return array(
			'home'       => $home,
			'away'       => $away,
			'home_score' => $hs,
			'away_score' => $as,
		);
	}

	/**
	 * The default soccer outcomes classify a win, a draw and a loss correctly.
	 *
	 * @return void
	 */
	public function test_default_outcomes_aggregate_results() {
		$agg = new StandingsAggregator( Defaults::outcomes() );

		$rows = $agg->compute(
			array(
				$this->match( 1, 2, 2, 1 ), // 1 beats 2
				$this->match( 2, 3, 3, 0 ), // 2 beats 3
				$this->match( 1, 3, 1, 1 ), // 1 draws 3
			)
		);

		$this->assertSame( 1, $rows[1]['won'] );
		$this->assertSame( 1, $rows[1]['drawn'] );
		$this->assertSame( 0, $rows[1]['lost'] );
		$this->assertSame( 2, $rows[1]['played'] );
		$this->assertSame( 3, $rows[1]['goals_for'] );
		$this->assertSame( 2, $rows[1]['goals_against'] );

		$this->assertSame( 1, $rows[2]['won'], 'Team 2 beat team 3.' );
		$this->assertSame( 1, $rows[2]['lost'], 'Team 2 lost to team 1.' );

		$this->assertSame( 1, $rows[3]['lost'] );
		$this->assertSame( 1, $rows[3]['drawn'] );
	}

	/**
	 * Custom outcomes (e.g. draws impossible) reshape the buckets.
	 *
	 * @return void
	 */
	public function test_custom_outcomes_change_classification() {
		// A ruleset with no draw: only a higher score wins, everything else loses.
		$outcomes = array(
			array(
				'label'    => 'Win',
				'key'      => 'w',
				'equation' => '$gf > $ga',
			),
			array(
				'label'    => 'Loss',
				'key'      => 'l',
				'equation' => '$gf <= $ga',
			),
		);

		$rows = ( new StandingsAggregator( $outcomes ) )->compute(
			array( $this->match( 1, 2, 1, 1 ) ) // level by score, but no draw bucket exists
		);

		$this->assertSame( 0, $rows[1]['won'] );
		$this->assertSame( 0, $rows[1]['drawn'], 'No draw outcome is defined.' );
		$this->assertSame( 1, $rows[1]['lost'], 'A level score falls through to loss.' );
		$this->assertSame( 1, $rows[2]['lost'] );
	}

	/**
	 * A match referencing the same team twice is ignored.
	 *
	 * @return void
	 */
	public function test_invalid_match_is_skipped() {
		$rows = ( new StandingsAggregator( Defaults::outcomes() ) )->compute(
			array( $this->match( 5, 5, 2, 1 ) )
		);

		$this->assertSame( array(), $rows );
	}
}
