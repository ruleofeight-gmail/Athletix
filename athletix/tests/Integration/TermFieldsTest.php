<?php
/**
 * Taxonomy term-meta fields + standings tab integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Admin\StandingsTab;
use Athletix\Support\Keys;
use Athletix\Taxonomies\TermFields;

/**
 * Confirms the League/Season/Division term screens persist their extra fields,
 * and that the hub gains a Tables tab.
 */
class TermFieldsTest extends IntegrationTestCase {

	/**
	 * Reset request globals.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * A season term saves start/end dates and its parent league.
	 *
	 * @return void
	 */
	public function test_season_term_meta_saves() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$league = $this->make_term( Keys::LEAGUE, 'Premier' );
		$season = $this->make_term( Keys::SEASON, 'Autumn 2026' );

		$_POST['athletix_term_nonce'] = wp_create_nonce( 'athletix_term_fields' );
		$_POST[ Keys::SEASON_START ]  = '2026-08-01';
		$_POST[ Keys::SEASON_END ]    = '2026-12-01';
		$_POST[ Keys::SEASON_LEAGUE ] = (string) $league;

		( new TermFields() )->save( $season );

		$this->assertSame( '2026-08-01', get_term_meta( $season, Keys::SEASON_START, true ) );
		$this->assertSame( '2026-12-01', get_term_meta( $season, Keys::SEASON_END, true ) );
		$this->assertSame( $league, (int) get_term_meta( $season, Keys::SEASON_LEAGUE, true ) );
	}

	/**
	 * Saving is refused without a valid nonce.
	 *
	 * @return void
	 */
	public function test_save_requires_nonce() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$season                      = $this->make_term( Keys::SEASON, 'No Nonce' );
		$_POST[ Keys::SEASON_START ] = '2026-08-01'; // no nonce set.

		( new TermFields() )->save( $season );

		$this->assertSame( '', get_term_meta( $season, Keys::SEASON_START, true ) );
	}

	/**
	 * The hub exposes a Tables tab ordered right after the Dashboard.
	 *
	 * @return void
	 */
	public function test_standings_tab_registered() {
		$tabs = ( new StandingsTab( $this->plugin() ) )->tab( array() );

		$this->assertArrayHasKey( 'tables', $tabs );
		$this->assertSame( 10, $tabs['tables']['order'] );
		$this->assertTrue( is_callable( $tabs['tables']['callback'] ) );
	}
}
