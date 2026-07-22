<?php
/**
 * QuickAdd batch AJAX create endpoints integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Admin\QuickAdd;
use Athletix\Support\Keys;
use WP_Ajax_UnitTestCase;

/**
 * Exercises the athletix_create_team / athletix_create_player AJAX handlers that
 * back the "Add Another" batch flow: they create the posts, assign terms/meta,
 * validate the position against the team's sport, and reject a missing name.
 */
class QuickAddAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Register the QuickAdd hooks (the plugin only wires them under is_admin()).
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		( new QuickAdd( \Athletix\Plugin::instance() ) )->register();
	}

	/**
	 * Dispatch an AJAX action and decode the JSON response.
	 *
	 * @param string $action Action name.
	 * @return array
	 */
	private function dispatch( $action ) {
		$_POST['action'] = $action;
		$_POST['nonce']  = wp_create_nonce( QuickAdd::CREATE_NONCE );

		try {
			$this->_handleAjax( $action );
		} catch ( \WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Creating a team assigns the sport term and stores the meta.
	 *
	 * @return void
	 */
	public function test_create_team() {
		$this->_setRole( 'administrator' );

		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );

		$_POST['athletix_name']  = 'Rovers';
		$_POST['athletix_sport'] = (string) $sport['term_id'];
		$_POST['athletix_venue'] = 'Riverside';

		$res = $this->dispatch( QuickAdd::CREATE_TEAM );

		$this->assertTrue( $res['success'] );
		$team = (int) $res['data']['id'];
		$this->assertSame( 'Rovers', get_the_title( $team ) );
		$this->assertSame( 'Riverside', get_post_meta( $team, Keys::TEAM_VENUE, true ) );
		$this->assertTrue( has_term( (int) $sport['term_id'], Keys::TAX_SPORT, $team ) );
	}

	/**
	 * Creating a player binds the team and keeps a valid sport position.
	 *
	 * @return void
	 */
	public function test_create_player_validates_position() {
		$this->_setRole( 'administrator' );

		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );
		$team  = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		wp_set_object_terms( $team, array( (int) $sport['term_id'] ), Keys::TAX_SPORT, false );

		$_POST['athletix_name']     = 'Alex Turner';
		$_POST['athletix_team']     = (string) $team;
		$_POST['athletix_position'] = 'MF';
		$_POST['athletix_number']   = '9';

		$res = $this->dispatch( QuickAdd::CREATE_PLAYER );

		$this->assertTrue( $res['success'] );
		$player = (int) $res['data']['id'];
		$this->assertSame( $team, (int) get_post_meta( $player, Keys::PLAYER_TEAM, true ) );
		$this->assertSame( 'MF', get_post_meta( $player, Keys::PLAYER_POSITION, true ) );
		$this->assertSame( '9', get_post_meta( $player, Keys::PLAYER_NUMBER, true ) );
	}

	/**
	 * A position the team's sport does not recognise is dropped.
	 *
	 * @return void
	 */
	public function test_create_player_rejects_foreign_position() {
		$this->_setRole( 'administrator' );

		$sport = wp_insert_term( 'Soccer', Keys::TAX_SPORT, array( 'slug' => 'soccer' ) );
		$team  = self::factory()->post->create( array( 'post_type' => Keys::TEAM ) );
		wp_set_object_terms( $team, array( (int) $sport['term_id'] ), Keys::TAX_SPORT, false );

		$_POST['athletix_name']     = 'Sam Lee';
		$_POST['athletix_team']     = (string) $team;
		$_POST['athletix_position'] = 'Pitcher'; // Baseball position, not soccer.

		$res    = $this->dispatch( QuickAdd::CREATE_PLAYER );
		$player = (int) $res['data']['id'];

		$this->assertSame( '', get_post_meta( $player, Keys::PLAYER_POSITION, true ) );
	}

	/**
	 * A missing name is rejected without creating a post.
	 *
	 * @return void
	 */
	public function test_missing_name_is_rejected() {
		$this->_setRole( 'administrator' );

		$before = wp_count_posts( Keys::PLAYER )->publish;

		$_POST['athletix_name'] = '';
		$_POST['athletix_team'] = '0';

		$res = $this->dispatch( QuickAdd::CREATE_PLAYER );

		$this->assertFalse( $res['success'] );
		$this->assertSame( 'name', $res['data']['code'] );
		$this->assertSame( $before, wp_count_posts( Keys::PLAYER )->publish );
	}
}
