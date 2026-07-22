<?php
/**
 * Tests for the sport profiles and the sport registry.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Contracts\SportProfile;
use Athletix\Engine\StandingsSorter;
use Athletix\Sports\Profiles\GenericProfile;
use Athletix\Sports\Profiles\SoccerProfile;
use Athletix\Sports\SportRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the profile contract data and the registry's lookup + fallback rules.
 */
class SportProfileTest extends TestCase {

	/**
	 * Soccer encodes Athletix's historical 3/1/0, goal-based behaviour.
	 *
	 * @return void
	 */
	public function test_soccer_profile_encodes_default_soccer_rules() {
		$soccer = new SoccerProfile();

		$this->assertSame( 'soccer', $soccer->slug() );
		$this->assertSame(
			array(
				'win'           => 3,
				'draw'          => 1,
				'loss'          => 0,
				'draws_allowed' => true,
			),
			$soccer->scoring()
		);
		$this->assertSame( array( 'points', 'goal_difference', 'goals_for', 'won' ), $soccer->tiebreakers() );
		$this->assertArrayHasKey( 'goals', $soccer->metrics() );
		$this->assertSame( array( 'GK', 'DF', 'MF', 'FW' ), $soccer->positions() );
		$this->assertSame( 'Goals', $soccer->labels()['score'] );
	}

	/**
	 * Generic is a safe neutral Null Object with the default tie-break chain.
	 *
	 * @return void
	 */
	public function test_generic_profile_is_neutral_fallback() {
		$generic = new GenericProfile();

		$this->assertSame( 'generic', $generic->slug() );
		$this->assertSame( StandingsSorter::DEFAULT_CHAIN, $generic->tiebreakers() );
		$this->assertSame( array(), $generic->positions() );
		$this->assertSame( 'Score', $generic->labels()['score'] );
	}

	/**
	 * Both shipped profiles honour the contract shape.
	 *
	 * @return void
	 */
	public function test_shipped_profiles_implement_contract() {
		foreach ( array( new SoccerProfile(), new GenericProfile() ) as $profile ) {
			$this->assertInstanceOf( SportProfile::class, $profile );
			$this->assertIsString( $profile->slug() );
			$this->assertIsString( $profile->label() );
			$this->assertArrayHasKey( 'win', $profile->scoring() );
			$this->assertNotEmpty( $profile->tiebreakers() );
			$this->assertArrayHasKey( 'match', $profile->labels() );
		}
	}

	/**
	 * The registry ships Soccer and always guarantees the generic fallback.
	 *
	 * @return void
	 */
	public function test_registry_ships_soccer_and_generic() {
		$registry = new SportRegistry();

		$this->assertTrue( $registry->has( 'soccer' ) );
		$this->assertTrue( $registry->has( 'generic' ) );
		$this->assertInstanceOf( SoccerProfile::class, $registry->get( 'soccer' ) );
		$this->assertArrayHasKey( 'generic', $registry->all() );
	}

	/**
	 * An unknown slug resolves to the generic profile rather than fataling.
	 *
	 * @return void
	 */
	public function test_registry_falls_back_to_generic_for_unknown_slug() {
		$registry = new SportRegistry();

		$this->assertFalse( $registry->has( 'quidditch' ) );
		$this->assertInstanceOf( GenericProfile::class, $registry->get( 'quidditch' ) );
	}
}
