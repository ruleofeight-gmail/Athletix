<?php
/**
 * Standings engine integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;

/**
 * Exercises the full event flow: saving a completed match recomputes the
 * standings table stored in the custom table.
 */
class StandingsEngineTest extends IntegrationTestCase {

	/**
	 * Create a published match with the given result and simulate its save.
	 *
	 * @param int $league     League id.
	 * @param int $home       Home team id.
	 * @param int $away       Away team id.
	 * @param int $home_score Home score.
	 * @param int $away_score Away score.
	 * @return int Match id.
	 */
	private function record_match( $league, $home, $away, $home_score, $away_score ) {
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
		update_post_meta( $match_id, Keys::MATCH_HOME_SCORE, $home_score );
		update_post_meta( $match_id, Keys::MATCH_AWAY_SCORE, $away_score );
		update_post_meta( $match_id, Keys::MATCH_STATUS, Keys::STATUS_COMPLETED );

		// Meta is set after insertion, so re-fire the save hook to trigger the
		// MatchEngine → event → StandingsEngine recompute.
		do_action( 'save_post_' . Keys::MATCH, $match_id, get_post( $match_id ) );

		return $match_id;
	}

	/**
	 * A win, a draw and goal aggregates land in the standings table.
	 *
	 * @return void
	 */
	public function test_standings_recompute_on_match_save() {
		$league = $this->make_term( Keys::LEAGUE, 'Premier' );
		$a      = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'A',
			)
		);
		$b      = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'B',
			)
		);
		$c      = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'C',
			)
		);

		$this->record_match( $league, $a, $b, 2, 1 ); // A win
		$this->record_match( $league, $b, $c, 3, 0 ); // B win
		$this->record_match( $league, $a, $c, 1, 1 ); // draw

		$table = $this->plugin()->make( 'engine.standings' )->table( $league, 0 );

		$rows = array();
		foreach ( $table as $row ) {
			$rows[ (int) $row['team_id'] ] = $row;
		}

		$this->assertSame( 4, (int) $rows[ $a ]['points'], 'A: win + draw = 4.' );
		$this->assertSame( 3, (int) $rows[ $b ]['points'], 'B: one win = 3.' );
		$this->assertSame( 1, (int) $rows[ $c ]['points'], 'C: one draw = 1.' );

		// Table is ordered best-first.
		$this->assertSame( $a, (int) $table[0]['team_id'], 'A tops the table.' );

		// Goal aggregates for A: scored 3 (2+1), conceded 2 (1+1).
		$this->assertSame( 3, (int) $rows[ $a ]['goals_for'] );
		$this->assertSame( 2, (int) $rows[ $a ]['goals_against'] );
	}

	/**
	 * Deleting a match recomputes and removes its contribution.
	 *
	 * @return void
	 */
	public function test_standings_update_on_match_delete() {
		$league = $this->make_term( Keys::LEAGUE, 'Premier' );
		$a      = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$b      = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );

		$match = $this->record_match( $league, $a, $b, 2, 0 );

		$engine = $this->plugin()->make( 'engine.standings' );
		$this->assertNotEmpty( $engine->table( $league, 0 ) );

		wp_delete_post( $match, true );

		$this->assertSame( array(), $engine->table( $league, 0 ), 'No matches leaves an empty table.' );
	}
}
