<?php
/**
 * Configurable standings integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;

/**
 * Proves an admin-defined, sport-scoped Standings Column equation flows all the
 * way through the engine: a league whose sport has a custom Points column
 * ("2 per win") computes and orders by that formula instead of the 3-1-0 default.
 */
class ConfigurableStandingsTest extends IntegrationTestCase {

	/**
	 * Record a completed match in a league.
	 *
	 * @param int $league League term id.
	 * @param int $home   Home team id.
	 * @param int $away   Away team id.
	 * @param int $hs     Home score.
	 * @param int $as     Away score.
	 * @return void
	 */
	private function record( $league, $home, $away, $hs, $as ) {
		$match = self::factory()->post->create(
			array(
				'post_type'   => Keys::MATCH,
				'post_status' => 'publish',
			)
		);
		wp_set_object_terms( $match, array( (int) $league ), Keys::LEAGUE, false );
		update_post_meta( $match, Keys::MATCH_HOME_TEAM, $home );
		update_post_meta( $match, Keys::MATCH_AWAY_TEAM, $away );
		update_post_meta( $match, Keys::MATCH_HOME_SCORE, $hs );
		update_post_meta( $match, Keys::MATCH_AWAY_SCORE, $as );
		update_post_meta( $match, Keys::MATCH_STATUS, Keys::STATUS_COMPLETED );
		do_action( 'save_post_' . Keys::MATCH, $match, get_post( $match ) );
	}

	/**
	 * Create a sport-scoped Points column with a custom equation.
	 *
	 * @param int    $sport_id Sport term id.
	 * @param string $equation Points equation.
	 * @return void
	 */
	private function custom_points_column( $sport_id, $equation ) {
		$post = wp_insert_post(
			array(
				'post_type'   => Keys::STANDING,
				'post_status' => 'publish',
				'post_title'  => 'Points',
				'menu_order'  => 0,
			)
		);
		update_post_meta( $post, Keys::VAR_KEY, 'points' );
		update_post_meta( $post, Keys::VAR_EQUATION, $equation );
		update_post_meta( $post, Keys::VAR_PRECISION, 0 );
		update_post_meta( $post, Keys::VAR_SORT, 1 );
		update_post_meta( $post, Keys::VAR_ORDER, 'desc' );
		update_post_meta( $post, Keys::VAR_SPORT, $sport_id );
	}

	/**
	 * The engine computes and orders by the sport's custom points equation.
	 *
	 * @return void
	 */
	public function test_custom_points_equation_drives_standings() {
		$sport = wp_insert_term( 'Custom Sport', Keys::TAX_SPORT, array( 'slug' => 'custom' ) );
		$this->assertNotWPError( $sport );
		$sport_id = (int) $sport['term_id'];

		$league = $this->make_term( Keys::LEAGUE, 'Custom League' );
		update_term_meta( $league, Keys::LEAGUE_SPORT, $sport_id );

		// 2 points per win, 1 per draw.
		$this->custom_points_column( $sport_id, '( $w * 2 ) + $d' );

		$a = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'A',
			)
		);
		$b = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'B',
			)
		);
		$c = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'C',
			)
		);

		$this->record( $league, $a, $b, 2, 1 ); // A win
		$this->record( $league, $a, $c, 1, 1 ); // A draw, C draw
		$this->record( $league, $b, $c, 3, 0 ); // B win

		$table = $this->plugin()->make( 'engine.standings' )->table( $league, 0 );

		$rows = array();
		foreach ( $table as $row ) {
			$rows[ (int) $row['team_id'] ] = $row;
		}

		// A: 1 win + 1 draw = 2*1 + 1 = 3 (the 3-1-0 default would give 4).
		$this->assertSame( 3, (int) $rows[ $a ]['points'], 'Custom 2-per-win equation applied.' );
		$this->assertSame( 2, (int) $rows[ $b ]['points'], 'B: one win = 2.' );
		$this->assertSame( 1, (int) $rows[ $c ]['points'], 'C: one draw = 1.' );

		// Ordered by the custom points: A, B, C.
		$this->assertSame( $a, (int) $table[0]['team_id'] );
		$this->assertSame( 1, (int) $table[0]['rank'] );
	}

	/**
	 * A league on a sport with no custom variables uses the seeded 3-1-0 default.
	 *
	 * @return void
	 */
	public function test_default_applies_without_custom_variables() {
		$league = $this->make_term( Keys::LEAGUE, 'Default League' );
		$a      = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$b      = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );

		$this->record( $league, $a, $b, 2, 0 ); // A win

		$table = $this->plugin()->make( 'engine.standings' )->table( $league, 0 );
		$rows  = array();
		foreach ( $table as $row ) {
			$rows[ (int) $row['team_id'] ] = $row;
		}

		$this->assertSame( 3, (int) $rows[ $a ]['points'], 'Default 3 points for a win.' );
		$this->assertSame( 2, (int) $rows[ $a ]['goal_difference'] );
	}
}
