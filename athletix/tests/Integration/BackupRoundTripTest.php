<?php
/**
 * Backup export/import round-trip integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\ImportExport\Backup;
use Athletix\Support\Keys;

/**
 * Verifies that exporting the data set and importing it back recreates posts,
 * relational meta, custom-table relationships and player stats with the old→new
 * id remapping intact — the property that makes a backup actually restorable.
 */
class BackupRoundTripTest extends IntegrationTestCase {

	/**
	 * Find the imported (newest) post of a type with the given title.
	 *
	 * @param string $type    Post type.
	 * @param string $title   Post title.
	 * @param int    $exclude Original id to exclude.
	 * @return int New post id.
	 */
	private function imported_id( $type, $title, $exclude ) {
		$posts = get_posts(
			array(
				'post_type'   => $type,
				'title'       => $title,
				'post_status' => 'any',
				'numberposts' => -1,
				'exclude'     => array( $exclude ),
			)
		);

		$this->assertNotEmpty( $posts, "Imported {$type} '{$title}' should exist." );

		return (int) $posts[0]->ID;
	}

	/**
	 * A full export → import preserves links between remapped ids.
	 *
	 * @return void
	 */
	public function test_round_trip_preserves_relationships_and_stats() {
		$league = $this->make_term( Keys::LEAGUE, 'RT League' );
		$team   = self::factory()->post->create(
			array(
				'post_type'  => Keys::TEAM,
				'post_title' => 'RT Team',
			)
		);
		$player = self::factory()->post->create(
			array(
				'post_type'  => Keys::PLAYER,
				'post_title' => 'RT Player',
			)
		);

		wp_set_object_terms( $team, array( (int) $league ), Keys::LEAGUE, false );
		update_post_meta( $player, Keys::PLAYER_TEAM, $team );

		$relationships = $this->plugin()->make( 'repo.relationship' );
		$stats         = $this->plugin()->make( 'repo.player_stats' );

		$relationships->add( $team, $player, 'squad' );
		$stats->record( $player, 'goals', 5, 0, 0 );

		$backup = new Backup( $this->plugin() );
		$data   = $backup->export();

		// Sanity: the export captured our fixtures.
		$this->assertNotEmpty( $data['posts'] );
		$this->assertNotEmpty( $data['relationships'] );
		$this->assertNotEmpty( $data['player_stats'] );

		// Import re-creates everything under fresh ids. import() returns the
		// number of posts created; this round trip restores two (team + player) —
		// the league is a taxonomy term, not a post.
		$created = $backup->import( $data );
		$this->assertGreaterThanOrEqual( 2, $created );

		$new_team   = $this->imported_id( Keys::TEAM, 'RT Team', $team );
		$new_player = $this->imported_id( Keys::PLAYER, 'RT Player', $player );

		// The league taxonomy term followed the team across the round trip (by name).
		$league_names = wp_get_object_terms( $new_team, Keys::LEAGUE, array( 'fields' => 'names' ) );
		$this->assertContains( 'RT League', $league_names );

		// The post-id relationship meta points at the new player, not the original.
		$this->assertSame( $new_team, (int) get_post_meta( $new_player, Keys::PLAYER_TEAM, true ) );

		// The custom-table relationship was remapped.
		$this->assertContains( $new_player, $relationships->targets( $new_team, 'squad' ) );

		// Player stats followed the player to its new id.
		$this->assertSame( 5.0, (float) $stats->total( $new_player, 'goals' ) );
	}
}
