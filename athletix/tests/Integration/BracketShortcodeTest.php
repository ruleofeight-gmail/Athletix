<?php
/**
 * Playoff bracket shortcode integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Frontend\Shortcodes;
use Athletix\Support\Keys;

/**
 * Exercises [athletix_bracket]: it reads the playoff-flagged matches for a
 * league, lays them out by round and highlights the winner.
 */
class BracketShortcodeTest extends IntegrationTestCase {

	/**
	 * Create a playoff match and return its id.
	 *
	 * @param int    $league     League term id.
	 * @param int    $home       Home team id.
	 * @param int    $away       Away team id.
	 * @param int    $round      Round number.
	 * @param int    $home_score Home score.
	 * @param int    $away_score Away score.
	 * @param string $status     Match status.
	 * @return int
	 */
	private function playoff_match( $league, $home, $away, $round, $home_score, $away_score, $status ) {
		$match_id = self::factory()->post->create(
			array(
				'post_type'   => Keys::MATCH,
				'post_status' => 'publish',
			)
		);

		wp_set_object_terms( $match_id, array( (int) $league ), Keys::LEAGUE, false );
		update_post_meta( $match_id, Keys::MATCH_HOME_TEAM, $home );
		update_post_meta( $match_id, Keys::MATCH_AWAY_TEAM, $away );
		update_post_meta( $match_id, Keys::MATCH_HOME_SCORE, $home_score );
		update_post_meta( $match_id, Keys::MATCH_AWAY_SCORE, $away_score );
		update_post_meta( $match_id, Keys::MATCH_STATUS, $status );
		update_post_meta( $match_id, Keys::MATCH_ROUND, $round );
		update_post_meta( $match_id, Keys::MATCH_PLAYOFF, 1 );

		return $match_id;
	}

	/**
	 * The bracket renders the seeded teams, the round label and the winner mark.
	 *
	 * @return void
	 */
	public function test_bracket_renders_rounds_and_winner() {
		$league = $this->make_term( Keys::LEAGUE, 'Cup' );
		$lions  = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'Lions',
			)
		);
		$bears  = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'Bears',
			)
		);

		$this->playoff_match( $league, $lions, $bears, 1000, 2, 1, Keys::STATUS_COMPLETED );

		$html = ( new Shortcodes( $this->plugin() ) )->bracket( array( 'league' => $league ) );

		$this->assertStringContainsString( 'athletix-bracket', $html );
		$this->assertStringContainsString( 'Lions', $html );
		$this->assertStringContainsString( 'Bears', $html );
		$this->assertStringContainsString( 'Final', $html, 'A single match is labelled the Final.' );
		$this->assertMatchesRegularExpression( '/is-winner[^>]*>\s*<span[^>]*>Lions/', $html, 'The winner is highlighted.' );
	}

	/**
	 * A league with no playoff matches renders nothing but does not error.
	 *
	 * @return void
	 */
	public function test_bracket_without_playoffs_is_empty() {
		$league = $this->make_term( Keys::LEAGUE, 'Cup' );

		$html = ( new Shortcodes( $this->plugin() ) )->bracket( array( 'league' => $league ) );

		$this->assertStringContainsString( 'athletix-empty', $html );
	}
}
