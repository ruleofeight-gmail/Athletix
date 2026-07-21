<?php
/**
 * Tests for StandingsCalculator.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Engine\StandingsCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the pure standings math.
 */
class StandingsCalculatorTest extends TestCase {

	/**
	 * Points used across tests.
	 *
	 * @var array
	 */
	private $points = array(
		'win'  => 3,
		'draw' => 1,
		'loss' => 0,
	);

	/**
	 * A small league computes wins, draws, goals and points correctly.
	 *
	 * @return void
	 */
	public function test_computes_results_and_points() {
		$calc = new StandingsCalculator( $this->points );

		$table = $calc->compute(
			array(
				array(
					'home'       => 1,
					'away'       => 2,
					'home_score' => 2,
					'away_score' => 1,
				),
				array(
					'home'       => 2,
					'away'       => 3,
					'home_score' => 3,
					'away_score' => 0,
				),
				array(
					'home'       => 1,
					'away'       => 3,
					'home_score' => 1,
					'away_score' => 1,
				),
			)
		);

		$this->assertSame( 4, $table[1]['points'], 'Team 1: one win + one draw = 4.' );
		$this->assertSame( 3, $table[2]['points'], 'Team 2: one win = 3.' );
		$this->assertSame( 1, $table[3]['points'], 'Team 3: one draw = 1.' );

		$this->assertSame( 2, $table[1]['played'] );
		$this->assertSame( 1, $table[1]['won'] );
		$this->assertSame( 1, $table[1]['drawn'] );
		$this->assertSame( 0, $table[1]['lost'] );
		$this->assertSame( 3, $table[1]['goals_for'] );
		$this->assertSame( 2, $table[1]['goals_against'] );
	}

	/**
	 * Invalid matches (missing or identical teams) are ignored.
	 *
	 * @return void
	 */
	public function test_skips_invalid_matches() {
		$calc = new StandingsCalculator( $this->points );

		$table = $calc->compute(
			array(
				array(
					'home'       => 5,
					'away'       => 5,
					'home_score' => 1,
					'away_score' => 0,
				), // same team
				array(
					'home'       => 0,
					'away'       => 6,
					'home_score' => 1,
					'away_score' => 0,
				), // missing home
				array(
					'home'       => 7,
					'away'       => 8,
					'home_score' => 0,
					'away_score' => 0,
				), // valid draw
			)
		);

		$this->assertArrayNotHasKey( 5, $table );
		$this->assertArrayNotHasKey( 6, $table );
		$this->assertSame( 1, $table[7]['drawn'] );
		$this->assertSame( 1, $table[8]['drawn'] );
	}

	/**
	 * Custom point schemes are honoured.
	 *
	 * @return void
	 */
	public function test_respects_custom_points() {
		$calc = new StandingsCalculator(
			array(
				'win'  => 2,
				'draw' => 1,
				'loss' => 0,
			)
		);

		$table = $calc->compute(
			array(
				array(
					'home'       => 1,
					'away'       => 2,
					'home_score' => 4,
					'away_score' => 0,
				),
			)
		);

		$this->assertSame( 2, $table[1]['points'] );
	}
}
