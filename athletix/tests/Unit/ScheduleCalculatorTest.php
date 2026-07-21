<?php
/**
 * Tests for ScheduleCalculator.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Engine\ScheduleCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Verifies round-robin fixture generation.
 */
class ScheduleCalculatorTest extends TestCase {

	/**
	 * Flatten rounds to a list of games.
	 *
	 * @param array $rounds Rounds.
	 * @return array
	 */
	private function games( array $rounds ) {
		$games = array();
		foreach ( $rounds as $round ) {
			foreach ( $round as $game ) {
				$games[] = $game;
			}
		}
		return $games;
	}

	/**
	 * An even number of teams: n-1 rounds, each pair exactly once.
	 *
	 * @return void
	 */
	public function test_even_teams_single_round() {
		$calc   = new ScheduleCalculator();
		$rounds = $calc->round_robin( array( 1, 2, 3, 4 ) );

		$this->assertCount( 3, $rounds, '4 teams => 3 rounds.' );

		$games = $this->games( $rounds );
		$this->assertCount( 6, $games, '4 teams => 6 games.' );

		$pairs = array();
		foreach ( $games as $game ) {
			$pairs[ min( $game['home'], $game['away'] ) . '-' . max( $game['home'], $game['away'] ) ] = true;
		}
		$this->assertCount( 6, $pairs, 'Every pair appears exactly once.' );
	}

	/**
	 * An odd number of teams handles byes: no zero-team games.
	 *
	 * @return void
	 */
	public function test_odd_teams_have_byes() {
		$calc   = new ScheduleCalculator();
		$rounds = $calc->round_robin( array( 1, 2, 3 ) );

		$this->assertCount( 3, $rounds, '3 teams => 3 rounds.' );

		$games = $this->games( $rounds );
		$this->assertCount( 3, $games, '3 teams => 3 games.' );

		foreach ( $games as $game ) {
			$this->assertNotSame( 0, $game['home'] );
			$this->assertNotSame( 0, $game['away'] );
		}
	}

	/**
	 * A double round doubles the fixtures and reverses legs.
	 *
	 * @return void
	 */
	public function test_double_round() {
		$calc   = new ScheduleCalculator();
		$single = $this->games( $calc->round_robin( array( 1, 2, 3, 4 ) ) );
		$double = $this->games( $calc->round_robin( array( 1, 2, 3, 4 ), true ) );

		$this->assertCount( 12, $double, 'Double round-robin => 12 games.' );
		$this->assertCount( 6, $single );
	}

	/**
	 * Fewer than two teams yields no fixtures.
	 *
	 * @return void
	 */
	public function test_insufficient_teams() {
		$calc = new ScheduleCalculator();
		$this->assertSame( array(), $calc->round_robin( array( 1 ) ) );
		$this->assertSame( array(), $calc->round_robin( array() ) );
	}
}
