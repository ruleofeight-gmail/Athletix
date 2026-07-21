<?php
/**
 * Full data backup and restore.
 *
 * @package Athletix
 */

namespace Athletix\ImportExport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Schema;
use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Exports every Athletix entity (posts + meta + sport terms) and the
 * relationship / player-stats tables to a portable JSON document, and restores
 * it with old→new id remapping so relationships survive the round trip.
 * Standings are intentionally omitted — they are recomputed from matches.
 */
class Backup {

	const ACTION_EXPORT = 'athletix_backup_export';
	const ACTION_IMPORT = 'athletix_backup_import';
	const FORMAT        = 1;

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Meta keys whose values are post ids and must be remapped on import.
	 *
	 * @return string[]
	 */
	private function relational_meta() {
		return array(
			Keys::TEAM_LEAGUE,
			Keys::PLAYER_TEAM,
			Keys::MATCH_LEAGUE,
			Keys::MATCH_SEASON,
			Keys::MATCH_HOME_TEAM,
			Keys::MATCH_AWAY_TEAM,
			Keys::SEASON_LEAGUE,
			Keys::DIVISION_LEAGUE,
		);
	}

	/**
	 * Register admin-post handlers.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( $this, 'handle_export' ) );
		add_action( 'admin_post_' . self::ACTION_IMPORT, array( $this, 'handle_import' ) );
	}

	/**
	 * Build the backup payload.
	 *
	 * @return array
	 */
	public function export() {
		$data = array(
			'format'        => self::FORMAT,
			'generated'     => time(),
			'posts'         => array(),
			'relationships' => array(),
			'player_stats'  => array(),
		);

		foreach ( Keys::post_types() as $type ) {
			$posts = get_posts(
				array(
					'post_type'        => $type,
					'post_status'      => 'any',
					'numberposts'      => -1,
					'suppress_filters' => true,
				)
			);

			foreach ( $posts as $post ) {
				$data['posts'][] = array(
					'ref'     => (int) $post->ID,
					'type'    => $post->post_type,
					'title'   => $post->post_title,
					'content' => $post->post_content,
					'excerpt' => $post->post_excerpt,
					'status'  => $post->post_status,
					'meta'    => $this->export_meta( $post->ID ),
					'sports'  => wp_get_object_terms( $post->ID, Keys::TAX_SPORT, array( 'fields' => 'names' ) ),
				);
			}
		}//end foreach

		global $wpdb;
		$rel   = Schema::table( Schema::RELATIONSHIPS );
		$stats = Schema::table( Schema::PLAYER_STATS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['relationships'] = $wpdb->get_results( "SELECT from_id, to_id, rel_type FROM {$rel}", ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['player_stats'] = $wpdb->get_results( "SELECT player_id, season_id, match_id, metric, value FROM {$stats}", ARRAY_A );

		return $data;
	}

	/**
	 * Collect the plugin meta for a post (keys prefixed _ax_).
	 *
	 * @param int $post_id Post id.
	 * @return array<string,mixed>
	 */
	private function export_meta( $post_id ) {
		$all  = get_post_meta( $post_id );
		$keep = array();

		foreach ( $all as $key => $values ) {
			if ( 0 === strpos( $key, '_ax_' ) ) {
				$keep[ $key ] = maybe_unserialize( $values[0] );
			}
		}

		return $keep;
	}

	/**
	 * Restore from a backup payload.
	 *
	 * @param array $data Decoded backup.
	 * @return int Number of posts created.
	 */
	public function import( array $data ) {
		if ( empty( $data['posts'] ) || ! is_array( $data['posts'] ) ) {
			return 0;
		}

		$relational = $this->relational_meta();
		$id_map     = array();
		$deferred   = array();

		// First pass: create posts, capturing the old→new id map.
		foreach ( $data['posts'] as $entry ) {
			if ( ! in_array( $entry['type'], Keys::post_types(), true ) ) {
				continue;
			}

			$new_id = wp_insert_post(
				array(
					'post_type'    => $entry['type'],
					'post_title'   => sanitize_text_field( $entry['title'] ),
					'post_content' => wp_kses_post( $entry['content'] ),
					'post_excerpt' => sanitize_text_field( $entry['excerpt'] ),
					'post_status'  => sanitize_key( $entry['status'] ),
				),
				true
			);

			if ( is_wp_error( $new_id ) ) {
				continue;
			}

			$id_map[ (int) $entry['ref'] ] = (int) $new_id;
			$deferred[ (int) $new_id ]     = $entry;
		}//end foreach

		// Second pass: write meta (remapping relational ids) and terms.
		foreach ( $deferred as $new_id => $entry ) {
			foreach ( (array) $entry['meta'] as $key => $value ) {
				if ( in_array( $key, $relational, true ) ) {
					$value = isset( $id_map[ (int) $value ] ) ? $id_map[ (int) $value ] : 0;
				}
				update_post_meta( $new_id, $key, $value );
			}

			if ( ! empty( $entry['sports'] ) ) {
				wp_set_object_terms( $new_id, array_map( 'sanitize_text_field', (array) $entry['sports'] ), Keys::TAX_SPORT );
			}
		}

		$this->import_relationships( $data, $id_map );
		$this->import_player_stats( $data, $id_map );

		return count( $id_map );
	}

	/**
	 * Recreate relationships through the id map.
	 *
	 * @param array $data   Backup.
	 * @param array $id_map Old→new post ids.
	 * @return void
	 */
	private function import_relationships( array $data, array $id_map ) {
		if ( empty( $data['relationships'] ) ) {
			return;
		}

		$repo = $this->plugin->make( 'repo.relationship' );

		foreach ( $data['relationships'] as $row ) {
			$from = isset( $id_map[ (int) $row['from_id'] ] ) ? $id_map[ (int) $row['from_id'] ] : 0;
			$to   = isset( $id_map[ (int) $row['to_id'] ] ) ? $id_map[ (int) $row['to_id'] ] : 0;

			if ( $from && $to ) {
				$repo->add( $from, $to, $row['rel_type'] );
			}
		}
	}

	/**
	 * Recreate player stats through the id map.
	 *
	 * @param array $data   Backup.
	 * @param array $id_map Old→new post ids.
	 * @return void
	 */
	private function import_player_stats( array $data, array $id_map ) {
		if ( empty( $data['player_stats'] ) ) {
			return;
		}

		$repo = $this->plugin->make( 'repo.player_stats' );

		foreach ( $data['player_stats'] as $row ) {
			$player = isset( $id_map[ (int) $row['player_id'] ] ) ? $id_map[ (int) $row['player_id'] ] : 0;
			if ( ! $player ) {
				continue;
			}

			$season = isset( $id_map[ (int) $row['season_id'] ] ) ? $id_map[ (int) $row['season_id'] ] : 0;
			$match  = isset( $id_map[ (int) $row['match_id'] ] ) ? $id_map[ (int) $row['match_id'] ] : 0;

			$repo->record( $player, $row['metric'], (float) $row['value'], $season, $match );
		}
	}

	/**
	 * Stream the backup as a JSON download.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
		check_admin_referer( self::ACTION_EXPORT );

		$json = wp_json_encode( $this->export() );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="athletix-backup-' . gmdate( 'Ymd-His' ) . '.json"' );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download, not HTML.
		exit;
	}

	/**
	 * Handle a backup upload and restore.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
		check_admin_referer( self::ACTION_IMPORT );

		$created = 0;

		if ( ! empty( $_FILES['backup']['tmp_name'] ) && is_uploaded_file( $_FILES['backup']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents, WordPress.Security.ValidatedSanitizedInput
			$raw  = file_get_contents( $_FILES['backup']['tmp_name'] );
			$data = json_decode( $raw, true );

			if ( is_array( $data ) && ! empty( $data['format'] ) ) {
				$created = $this->import( $data );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'   => Keys::TEAM,
					'page'        => ImportExport::PAGE,
					'ax_restored' => $created,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
