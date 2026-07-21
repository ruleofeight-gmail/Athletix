<?php
/**
 * Tests for BracketSeeder.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Competition\BracketSeeder;
use PHPUnit\Framework\TestCase;

/**
 * Verifies seeded single-elimination bracket generation.
 */
class BracketSeederTest extends TestCase {

	/**
	 * A perfect power-of-two bracket keeps the top seeds apart.
	 *
	 * @return void
	 */
	public function test_eight_seeds_standard_pairings() {
		$seeder = new BracketSeeder();
		$pairs  = $seeder->first_round( array( 1, 2, 3, 4, 5, 6, 7, 8 ) );

		$this->assertCount( 4, $pairs );

		// Standard 8-team seeding: 1v8, 4v5, 2v7, 3v6.
		$this->assertSame( array( 1, 8 ), array( $pairs[0]['home'], $pairs[0]['away'] ) );
		$this->assertSame( array( 4, 5 ), array( $pairs[1]['home'], $pairs[1]['away'] ) );
		$this->assertSame( array( 2, 7 ), array( $pairs[2]['home'], $pairs[2]['away'] ) );
		$this->assertSame( array( 3, 6 ), array( $pairs[3]['home'], $pairs[3]['away'] ) );

		foreach ( $pairs as $pair ) {
			$this->assertFalse( $pair['bye'] );
		}
	}

	/**
	 * Non-power-of-two fields give byes to the top seeds.
	 *
	 * @return void
	 */
	public function test_six_seeds_top_two_get_byes() {
		$seeder = new BracketSeeder();
		$pairs  = $seeder->first_round( array( 1, 2, 3, 4, 5, 6 ) );

		$this->assertCount( 4, $pairs );

		$byes = array();
		foreach ( $pairs as $pair ) {
			if ( $pair['bye'] ) {
				$this->assertNull( $pair['away'] );
				$byes[] = $pair['home'];
			}
		}

		// Seeds 1 and 2 advance on byes.
		sort( $byes );
		$this->assertSame( array( 1, 2 ), $byes );
	}

	/**
	 * Every entrant appears exactly once in the first round.
	 *
	 * @return void
	 */
	public function test_all_teams_placed_once() {
		$seeder = new BracketSeeder();
		$teams  = array( 11, 22, 33, 44, 55 );
		$pairs  = $seeder->first_round( $teams );

		$seen = array();
		foreach ( $pairs as $pair ) {
			$seen[] = $pair['home'];
			if ( null !== $pair['away'] ) {
				$seen[] = $pair['away'];
			}
		}

		sort( $seen );
		$expected = $teams;
		sort( $expected );
		$this->assertSame( $expected, $seen );
	}

	/**
	 * Rounds-needed reflects the padded bracket size.
	 *
	 * @return void
	 */
	public function test_rounds_needed() {
		$seeder = new BracketSeeder();
		$this->assertSame( 3, $seeder->rounds_needed( 8 ) );
		$this->assertSame( 3, $seeder->rounds_needed( 6 ) );
		$this->assertSame( 4, $seeder->rounds_needed( 9 ) );
		$this->assertSame( 0, $seeder->rounds_needed( 1 ) );
	}
}
