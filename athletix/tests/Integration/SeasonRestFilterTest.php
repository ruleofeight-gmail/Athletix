<?php
/**
 * Season-by-league REST scoping integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;
use WP_REST_Request;

/**
 * Confirms that GET /wp/v2/ax_season?athletix_league=<id> returns only the
 * seasons whose League term-meta matches, which is what makes the editor's
 * Season picker dependent on the chosen League.
 */
class SeasonRestFilterTest extends IntegrationTestCase {

	/**
	 * Boot the REST server so the core taxonomy routes exist.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down the REST server.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Create a season term bound to a league via term meta.
	 *
	 * @param string $name   Season name.
	 * @param int    $league League term id (0 for none).
	 * @return int Season term id.
	 */
	private function season( $name, $league ) {
		$id = $this->make_term( Keys::SEASON, $name );
		if ( $league ) {
			update_term_meta( $id, Keys::SEASON_LEAGUE, $league );
		}

		return $id;
	}

	/**
	 * Dispatch a GET against the Seasons endpoint and return the term ids.
	 *
	 * @param array $args Query args.
	 * @return int[]
	 */
	private function query_seasons( array $args ) {
		$request = new WP_REST_Request( 'GET', '/wp/v2/' . Keys::SEASON );
		$request->set_param( 'per_page', 100 );
		$request->set_param( 'hide_empty', false );
		foreach ( $args as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_get_server()->dispatch( $request );

		return wp_list_pluck( (array) $response->get_data(), 'id' );
	}

	/**
	 * The league filter narrows the result set to that league's seasons.
	 *
	 * @return void
	 */
	public function test_league_param_scopes_seasons() {
		$premier  = $this->make_term( Keys::LEAGUE, 'Premier' );
		$sunday   = $this->make_term( Keys::LEAGUE, 'Sunday' );
		$p_spring = $this->season( 'Premier Spring', $premier );
		$p_autumn = $this->season( 'Premier Autumn', $premier );
		$s_summer = $this->season( 'Sunday Summer', $sunday );

		$ids = $this->query_seasons( array( 'athletix_league' => $premier ) );

		$this->assertContains( $p_spring, $ids );
		$this->assertContains( $p_autumn, $ids );
		$this->assertNotContains( $s_summer, $ids, 'Other leagues\' seasons are excluded.' );
	}

	/**
	 * Omitting the param (or passing 0) returns every season, unfiltered.
	 *
	 * @return void
	 */
	public function test_without_param_returns_all_seasons() {
		$league   = $this->make_term( Keys::LEAGUE, 'Premier' );
		$scoped   = $this->season( 'Scoped', $league );
		$unscoped = $this->season( 'Unscoped', 0 );

		$ids = $this->query_seasons( array() );

		$this->assertContains( $scoped, $ids );
		$this->assertContains( $unscoped, $ids );
	}
}
