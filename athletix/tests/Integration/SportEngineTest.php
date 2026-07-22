<?php
/**
 * Sport engine integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Engine\SportEngine;
use Athletix\Sports\Profiles\SoccerProfile;
use Athletix\Sports\SportRegistry;
use Athletix\Support\Keys;

/**
 * Exercises per-league sport resolution and the profile-sourced points /
 * tie-break defaults, including the settings-page override for the primary sport.
 */
class SportEngineTest extends IntegrationTestCase {

	/**
	 * The registry-backed engine as wired by the container.
	 *
	 * @return SportEngine
	 */
	private function engine() {
		return $this->plugin()->make( 'engine.sport' );
	}

	/**
	 * A league with no sport meta falls back to the active (primary) sport.
	 *
	 * @return void
	 */
	public function test_for_league_defaults_to_active_sport() {
		$league = $this->make_term( Keys::LEAGUE, 'Unscoped League' );

		$this->assertSame( $this->engine()->active(), $this->engine()->for_league( $league ) );
	}

	/**
	 * A league's sport is read from its LEAGUE_SPORT term meta (a Sport term).
	 *
	 * @return void
	 */
	public function test_for_league_reads_league_sport_meta() {
		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );
		$this->assertNotWPError( $sport );

		$league = $this->make_term( Keys::LEAGUE, 'Sunday Soccer' );
		update_term_meta( $league, Keys::LEAGUE_SPORT, (int) $sport['term_id'] );

		$this->assertSame( 'soccer', $this->engine()->for_league( $league ) );

		$profile = $this->engine()->profile( $this->engine()->for_league( $league ) );
		$this->assertInstanceOf( SoccerProfile::class, $profile );
	}

	/**
	 * Points for a non-primary sport come straight from its profile, untouched by
	 * the settings-page overrides that only apply to the active sport.
	 *
	 * @return void
	 */
	public function test_points_for_non_primary_sport_use_profile_defaults() {
		$engine = $this->engine();

		// The generic profile is never the site's active sport in these tests, so
		// its scoring is returned verbatim (3/1/0).
		$points = $engine->points( 'generic' );

		$this->assertSame( 3, $points['win'] );
		$this->assertSame( 1, $points['draw'] );
		$this->assertSame( 0, $points['loss'] );
	}

	/**
	 * The active sport still honours the settings-page point overrides.
	 *
	 * @return void
	 */
	public function test_points_for_active_sport_honour_config_overrides() {
		$config = $this->plugin()->config();
		$config->set( 'points_win', 2 );

		$engine = new SportEngine( $config, new SportRegistry() );
		$points = $engine->points( $engine->active() );

		$this->assertSame( 2, $points['win'], 'Active sport reads the configured win points.' );

		$config->set( 'points_win', 3 );
	}

	/**
	 * Tie-breakers for a sport come from its profile chain.
	 *
	 * @return void
	 */
	public function test_tiebreakers_source_from_profile() {
		$chain = $this->engine()->tiebreakers( 'soccer' );

		$this->assertSame( array( 'points', 'goal_difference', 'goals_for', 'won' ), $chain );
	}
}
