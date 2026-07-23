<?php
/**
 * Sport engine integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Engine\SportEngine;
use Athletix\Sports\Profiles\SoccerProfile;
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
	 * A team's sport is read from its own Sport term when set — this drives the
	 * sport-aware position list on the Add Player screen.
	 *
	 * @return void
	 */
	public function test_for_team_reads_team_sport_term() {
		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );
		$this->assertNotWPError( $sport );

		$team = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		wp_set_object_terms( $team, array( (int) $sport['term_id'] ), Keys::TAX_SPORT, false );

		$this->assertSame( 'soccer', $this->engine()->for_team( $team ) );
		$this->assertNotEmpty( $this->engine()->profile( $this->engine()->for_team( $team ) )->positions() );
	}

	/**
	 * With no Sport term, a team inherits the sport of the league it belongs to.
	 *
	 * @return void
	 */
	public function test_for_team_falls_back_to_league_sport() {
		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );
		$this->assertNotWPError( $sport );

		$league = $this->make_term( Keys::LEAGUE, 'Div One' );
		update_term_meta( $league, Keys::LEAGUE_SPORT, (int) $sport['term_id'] );

		$team = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		wp_set_object_terms( $team, array( (int) $league ), Keys::LEAGUE, false );

		$this->assertSame( 'soccer', $this->engine()->for_team( $team ) );
	}

	/**
	 * A team with neither a Sport term nor a league falls back to the active sport.
	 *
	 * @return void
	 */
	public function test_for_team_defaults_to_active_sport() {
		$team = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );

		$this->assertSame( $this->engine()->active(), $this->engine()->for_team( $team ) );
	}

	/**
	 * The resolved profile exposes the sport's descriptive metadata.
	 *
	 * @return void
	 */
	public function test_profile_exposes_sport_metadata() {
		$profile = $this->engine()->profile( 'soccer' );

		$this->assertSame( 'soccer', $profile->slug() );
		$this->assertNotEmpty( $profile->positions() );
	}
}
