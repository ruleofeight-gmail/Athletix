<?php
/**
 * Block server-render integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Blocks\BlocksModule;
use Athletix\Support\Keys;

/**
 * Confirms the dynamic blocks render through the shared shortcode path and emit
 * the same markup a shortcode would.
 */
class BlocksRenderTest extends IntegrationTestCase {

	/**
	 * Booted blocks module.
	 *
	 * @var BlocksModule
	 */
	private $blocks;

	/**
	 * Boot the module so the render callbacks have their shortcode renderer.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->blocks = new BlocksModule();
		$this->blocks->register( $this->plugin() );
	}

	/**
	 * The standings block renders the recomputed table with team names.
	 *
	 * @return void
	 */
	public function test_standings_block_renders_table() {
		$league = self::factory()->post->create( array( 'post_type' => Keys::LEAGUE ) );
		$home   = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'Rovers',
			)
		);
		$away   = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'United',
			)
		);

		$match = self::factory()->post->create(
			array(
				'post_type'   => Keys::MATCH,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $match, Keys::MATCH_LEAGUE, $league );
		update_post_meta( $match, Keys::MATCH_HOME_TEAM, $home );
		update_post_meta( $match, Keys::MATCH_AWAY_TEAM, $away );
		update_post_meta( $match, Keys::MATCH_HOME_SCORE, 2 );
		update_post_meta( $match, Keys::MATCH_AWAY_SCORE, 0 );
		update_post_meta( $match, Keys::MATCH_STATUS, Keys::STATUS_COMPLETED );
		do_action( 'save_post_' . Keys::MATCH, $match, get_post( $match ) );

		$html = $this->blocks->render_standings( array( 'league' => $league ) );

		$this->assertIsString( $html );
		$this->assertStringContainsString( 'Rovers', $html );
		$this->assertStringContainsString( 'United', $html );
	}

	/**
	 * The roster block lists the team's players.
	 *
	 * @return void
	 */
	public function test_roster_block_lists_players() {
		$team   = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		$player = self::factory()->post->create(
			array(
				'post_type'  => Keys::PLAYER,
				'post_title' => 'Alex Turner',
			)
		);
		update_post_meta( $player, Keys::PLAYER_TEAM, $team );

		$html = $this->blocks->render_roster( array( 'team' => $team ) );

		$this->assertStringContainsString( 'Alex Turner', $html );
	}

	/**
	 * An empty/invalid target renders a string, never a fatal.
	 *
	 * @return void
	 */
	public function test_match_block_handles_missing_id() {
		$html = $this->blocks->render_match( array( 'id' => 0 ) );

		$this->assertIsString( $html );
	}
}
