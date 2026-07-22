<?php
/**
 * Shortcode league/season term resolution integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;

/**
 * Confirms the standings shortcode accepts a league by slug or name, not only
 * by numeric term id.
 */
class ShortcodeTermResolveTest extends IntegrationTestCase {

	/**
	 * Build a completed match in a league and return the league term id.
	 *
	 * @param string $league_name League name.
	 * @return array{league:int,home:string}
	 */
	private function seed_league( $league_name ) {
		$league = $this->make_term( Keys::LEAGUE, $league_name );
		$home   = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'Rovers',
			)
		);
		$away   = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );

		$match = self::factory()->post->create(
			array(
				'post_type'   => Keys::MATCH,
				'post_status' => 'publish',
			)
		);
		wp_set_object_terms( $match, array( (int) $league ), Keys::LEAGUE, false );
		update_post_meta( $match, Keys::MATCH_HOME_TEAM, $home );
		update_post_meta( $match, Keys::MATCH_AWAY_TEAM, $away );
		update_post_meta( $match, Keys::MATCH_HOME_SCORE, 2 );
		update_post_meta( $match, Keys::MATCH_AWAY_SCORE, 0 );
		update_post_meta( $match, Keys::MATCH_STATUS, Keys::STATUS_COMPLETED );
		do_action( 'save_post_' . Keys::MATCH, $match, get_post( $match ) );

		return array(
			'league' => $league,
			'home'   => 'Rovers',
		);
	}

	/**
	 * The standings shortcode resolves a league passed by slug.
	 *
	 * @return void
	 */
	public function test_standings_shortcode_by_slug() {
		$seed = $this->seed_league( 'Premier Division' );
		$term = get_term( $seed['league'], Keys::LEAGUE );

		$by_slug = do_shortcode( '[athletix_standings league="' . $term->slug . '"]' );
		$this->assertStringContainsString( $seed['home'], $by_slug, 'Resolved league by slug.' );

		$by_id = do_shortcode( '[athletix_standings league="' . $seed['league'] . '"]' );
		$this->assertStringContainsString( $seed['home'], $by_id, 'Numeric ids still work.' );
	}

	/**
	 * An unknown league slug resolves to nothing (empty table), not a fatal.
	 *
	 * @return void
	 */
	public function test_unknown_league_slug_is_safe() {
		$this->seed_league( 'Premier Division' );

		$html = do_shortcode( '[athletix_standings league="does-not-exist"]' );
		$this->assertIsString( $html );
		$this->assertStringNotContainsString( 'Rovers', $html );
	}
}
