<?php
/**
 * REST write endpoint integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;
use WP_REST_Request;

/**
 * Exercises POST /athletix/v1/matches/{id}/result: capability enforcement plus
 * the full write → event → standings recompute flow through the REST server.
 */
class RestWriteTest extends IntegrationTestCase {

	/**
	 * REST server.
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * Boot the REST server and register the Athletix routes.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;
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
	 * Create a scheduled match with both teams assigned.
	 *
	 * @param int $league League id.
	 * @param int $home   Home team id.
	 * @param int $away   Away team id.
	 * @return int Match id.
	 */
	private function scheduled_match( $league, $home, $away ) {
		$match_id = self::factory()->post->create(
			array(
				'post_type'   => Keys::MATCH,
				'post_status' => 'publish',
				'post_title'  => 'Fixture',
			)
		);
		wp_set_object_terms( $match_id, array( (int) $league ), Keys::LEAGUE, false );
		update_post_meta( $match_id, Keys::MATCH_HOME_TEAM, $home );
		update_post_meta( $match_id, Keys::MATCH_AWAY_TEAM, $away );
		update_post_meta( $match_id, Keys::MATCH_STATUS, Keys::STATUS_SCHEDULED );

		return $match_id;
	}

	/**
	 * Build the result request for a match.
	 *
	 * @param int    $match_id      Match id.
	 * @param int    $home_score Home score.
	 * @param int    $away_score Away score.
	 * @param string $status     Status.
	 * @return WP_REST_Request
	 */
	private function result_request( $match_id, $home_score, $away_score, $status = Keys::STATUS_COMPLETED ) {
		$request = new WP_REST_Request( 'POST', '/athletix/v1/matches/' . $match_id . '/result' );
		$request->set_body_params(
			array(
				'home_score' => $home_score,
				'away_score' => $away_score,
				'status'     => $status,
			)
		);

		return $request;
	}

	/**
	 * A manager can post a result and standings recompute from it.
	 *
	 * @return void
	 */
	public function test_manager_can_record_result() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$league   = $this->make_term( Keys::LEAGUE, 'League' );
		$home     = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$away     = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$match_id = $this->scheduled_match( $league, $home, $away );

		$response = $this->server->dispatch( $this->result_request( $match_id, 3, 1 ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['saved'] );

		// Meta persisted.
		$this->assertSame( 3, (int) get_post_meta( $match_id, Keys::MATCH_HOME_SCORE, true ) );
		$this->assertSame( Keys::STATUS_COMPLETED, get_post_meta( $match_id, Keys::MATCH_STATUS, true ) );

		// Standings recomputed from the posted result.
		$table = $this->plugin()->make( 'engine.standings' )->table( $league, 0 );
		$rows  = array();
		foreach ( $table as $row ) {
			$rows[ (int) $row['team_id'] ] = $row;
		}
		$this->assertSame( 3, (int) $rows[ $home ]['points'], 'Home win = 3 points.' );
		$this->assertSame( 0, (int) $rows[ $away ]['points'] );
	}

	/**
	 * A visitor without the manage capability is refused.
	 *
	 * @return void
	 */
	public function test_subscriber_is_forbidden() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$league   = $this->make_term( Keys::LEAGUE, 'League' );
		$home     = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$away     = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$match_id = $this->scheduled_match( $league, $home, $away );

		$response = $this->server->dispatch( $this->result_request( $match_id, 2, 2 ) );

		$this->assertSame( 403, $response->get_status(), 'Logged-in-but-uncapable is rejected.' );
		// Nothing was written.
		$this->assertSame( '', get_post_meta( $match_id, Keys::MATCH_HOME_SCORE, true ) );
	}

	/**
	 * A result for a non-existent match id returns 404.
	 *
	 * @return void
	 */
	public function test_unknown_match_is_not_found() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$response = $this->server->dispatch( $this->result_request( 999999, 1, 0 ) );

		$this->assertSame( 404, $response->get_status() );
	}
}
