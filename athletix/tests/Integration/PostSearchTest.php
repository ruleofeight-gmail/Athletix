<?php
/**
 * Relationship-picker search integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Meta\PostSearchAjax;
use Athletix\Support\Keys;

/**
 * Exercises the query behind the searchable relationship picker: title matching,
 * the per-page cap and the "more results" flag.
 */
class PostSearchTest extends IntegrationTestCase {

	/**
	 * Titles are matched and non-matches excluded.
	 *
	 * @return void
	 */
	public function test_search_matches_titles() {
		self::factory()->post->create(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'publish',
				'post_title'  => 'Riverside Rovers',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'publish',
				'post_title'  => 'Northgate United',
			)
		);

		$search = new PostSearchAjax();
		$out    = $search->results( Keys::TEAM, 'Riverside' );

		$titles = wp_list_pluck( $out['results'], 'text' );
		$this->assertContains( 'Riverside Rovers', $titles );
		$this->assertNotContains( 'Northgate United', $titles );
		$this->assertFalse( $out['more'] );
	}

	/**
	 * Results are capped at the page size and flag that more exist.
	 *
	 * @return void
	 */
	public function test_results_are_paged() {
		for ( $i = 0; $i < PostSearchAjax::LIMIT + 5; $i++ ) {
			self::factory()->post->create(
				array(
					'post_type'   => Keys::PLAYER,
					'post_status' => 'publish',
					'post_title'  => 'Player ' . str_pad( (string) $i, 3, '0', STR_PAD_LEFT ),
				)
			);
		}

		$search = new PostSearchAjax();

		$first = $search->results( Keys::PLAYER, 'Player', 1 );
		$this->assertCount( PostSearchAjax::LIMIT, $first['results'] );
		$this->assertTrue( $first['more'], 'A full first page signals more results.' );

		$second = $search->results( Keys::PLAYER, 'Player', 2 );
		$this->assertCount( 5, $second['results'] );
		$this->assertFalse( $second['more'] );
	}

	/**
	 * Only published posts are returned.
	 *
	 * @return void
	 */
	public function test_excludes_drafts() {
		self::factory()->post->create(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'draft',
				'post_title'  => 'Hidden Draft FC',
			)
		);

		$out    = ( new PostSearchAjax() )->results( Keys::TEAM, 'Hidden' );
		$titles = wp_list_pluck( $out['results'], 'text' );

		$this->assertNotContains( 'Hidden Draft FC', $titles );
	}
}
