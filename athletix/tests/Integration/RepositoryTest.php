<?php
/**
 * Repository integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Support\Keys;

/**
 * Verifies the post-backed repositories against a real database.
 */
class RepositoryTest extends IntegrationTestCase {

	/**
	 * The create() method forces the post type and find() round-trips.
	 *
	 * @return void
	 */
	public function test_create_and_find() {
		$teams = $this->plugin()->make( 'repo.team' );

		$id = $teams->create( array( 'post_title' => 'Rovers' ) );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$post = $teams->find( $id );
		$this->assertNotNull( $post );
		$this->assertSame( Keys::TEAM, $post->post_type );
		$this->assertSame( 'Rovers', $post->post_title );
	}

	/**
	 * The find() method rejects a post of the wrong type.
	 *
	 * @return void
	 */
	public function test_find_wrong_type_returns_null() {
		$teams = $this->plugin()->make( 'repo.team' );
		$page  = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( $teams->find( $page ) );
	}

	/**
	 * The for_league() method filters by the assigned league term.
	 *
	 * @return void
	 */
	public function test_for_league_filter() {
		$teams  = $this->plugin()->make( 'repo.team' );
		$league = $this->make_term( Keys::LEAGUE, 'Premier' );

		$in  = $teams->create( array( 'post_title' => 'In League' ) );
		$out = $teams->create( array( 'post_title' => 'No League' ) );
		wp_set_object_terms( $in, array( (int) $league ), Keys::LEAGUE, false );

		$found_ids = wp_list_pluck( $teams->for_league( $league ), 'ID' );

		$this->assertContains( $in, $found_ids );
		$this->assertNotContains( $out, $found_ids );
	}

	/**
	 * The league repository stores and reads leagues as terms.
	 *
	 * @return void
	 */
	public function test_league_repository_terms() {
		$leagues = $this->plugin()->make( 'repo.league' );

		$id = $leagues->create( array( 'name' => 'Premier Division' ) );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( 'Premier Division', $leagues->name( $id ) );
		$this->assertContains( $id, wp_list_pluck( $leagues->all(), 'term_id' ) );
	}
}
