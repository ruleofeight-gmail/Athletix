<?php
/**
 * Tests for BracketBuilder.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Competition\BracketBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Verifies round grouping/ordering, round labels and winner detection.
 */
class BracketBuilderTest extends TestCase {

	/**
	 * Build a match row with defaults.
	 *
	 * @param int    $round      Round number.
	 * @param int    $home       Home team id.
	 * @param int    $away       Away team id.
	 * @param int    $home_score Home score.
	 * @param int    $away_score Away score.
	 * @param string $status     Status.
	 * @return array
	 */
	private function match( $round, $home, $away, $home_score = 0, $away_score = 0, $status = 'scheduled' ) {
		return array(
			'round'      => $round,
			'home'       => $home,
			'away'       => $away,
			'home_score' => $home_score,
			'away_score' => $away_score,
			'status'     => $status,
		);
	}

	/**
	 * Rounds are grouped and ordered earliest-first, and labelled by size.
	 *
	 * @return void
	 */
	public function test_groups_orders_and_labels_rounds() {
		$builder = new BracketBuilder();

		// Two semifinals (round 1000) and one final (round 1001), supplied
		// out of order to prove sorting.
		$rounds = $builder->build(
			array(
				$this->match( 1001, 1, 3 ),
				$this->match( 1000, 1, 4 ),
				$this->match( 1000, 2, 3 ),
			)
		);

		$this->assertCount( 2, $rounds );
		$this->assertSame( 1000, $rounds[0]['round'] );
		$this->assertSame( 'Semifinals', $rounds[0]['label'] );
		$this->assertCount( 2, $rounds[0]['matches'] );
		$this->assertSame( 1001, $rounds[1]['round'] );
		$this->assertSame( 'Final', $rounds[1]['label'] );
	}

	/**
	 * Four matches label as Quarterfinals; larger rounds as "Round of N".
	 *
	 * @return void
	 */
	public function test_labels_quarterfinals_and_round_of_n() {
		$builder = new BracketBuilder();

		$quarters = $builder->build(
			array(
				$this->match( 1, 1, 2 ),
				$this->match( 1, 3, 4 ),
				$this->match( 1, 5, 6 ),
				$this->match( 1, 7, 8 ),
			)
		);
		$this->assertSame( 'Quarterfinals', $quarters[0]['label'] );

		$round_of_16 = $builder->build(
			array_fill( 0, 8, $this->match( 1, 1, 2 ) )
		);
		$this->assertSame( 'Round of 16', $round_of_16[0]['label'] );
	}

	/**
	 * The winning side is marked only on completed matches.
	 *
	 * @return void
	 */
	public function test_marks_winner_on_completed_matches() {
		$builder = new BracketBuilder();

		$rounds = $builder->build(
			array(
				$this->match( 1, 10, 20, 3, 1, 'completed' ), // home wins
				$this->match( 1, 30, 40, 0, 2, 'completed' ), // away wins
				$this->match( 1, 50, 60, 5, 5, 'completed' ), // draw -> none
				$this->match( 1, 70, 80, 9, 0, 'scheduled' ), // not played -> none
			)
		);

		$matches = $rounds[0]['matches'];
		$this->assertSame( 'home', $matches[0]['winner'] );
		$this->assertSame( 'away', $matches[1]['winner'] );
		$this->assertSame( '', $matches[2]['winner'], 'A draw has no winner.' );
		$this->assertSame( '', $matches[3]['winner'], 'An unplayed match has no winner.' );
	}

	/**
	 * An empty input yields no rounds.
	 *
	 * @return void
	 */
	public function test_empty_input_yields_no_rounds() {
		$this->assertSame( array(), ( new BracketBuilder() )->build( array() ) );
	}
}
