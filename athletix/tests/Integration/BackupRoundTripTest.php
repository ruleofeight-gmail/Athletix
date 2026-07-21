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
		$league = self::factory()->post->create(
			array(
				'post_type'  => Keys::LEAGUE,
				'post_title' => 'RT League',
			)
		);
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

		update_post_meta( $team, Keys::TEAM_LEAGUE, $league );
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

		// Import re-creates everything under fresh ids.
		$created = $backup->import( $data );
		$this->assertGreaterThanOrEqual( 3, $created );

		$new_league = $this->imported_id( Keys::LEAGUE, 'RT League', $league );
		$new_team   = $this->imported_id( Keys::TEAM, 'RT Team', $team );
		$new_player = $this->imported_id( Keys::PLAYER, 'RT Player', $player );

		// Relational meta points at the new ids, not the originals.
		$this->assertSame( $new_league, (int) get_post_meta( $new_team, Keys::TEAM_LEAGUE, true ) );
		$this->assertSame( $new_team, (int) get_post_meta( $new_player, Keys::PLAYER_TEAM, true ) );

		// The custom-table relationship was remapped.
		$this->assertContains( $new_player, $relationships->targets( $new_team, 'squad' ) );

		// Player stats followed the player to its new id.
		$this->assertSame( 5.0, (float) $stats->total( $new_player, 'goals' ) );
	}
}
