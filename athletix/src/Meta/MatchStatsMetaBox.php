<?php
/**
 * Player statistics entry on the match editor.
 *
 * @package Athletix
 */

namespace Athletix\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * A "Player Statistics" meta box on the match editor: enter per-player metric
 * values (goals, assists, …) for the match. On save the match's stats are
 * cleared and re-recorded, so leaderboards, analytics and reports stay in sync.
 */
class MatchStatsMetaBox {

	const NONCE_ACTION = 'athletix_match_stats';
	const NONCE_NAME   = 'athletix_match_stats_nonce';

	/**
	 * Suggested metric slugs offered in the datalist.
	 */
	const METRICS = array( 'goals', 'assists', 'yellow_cards', 'red_cards', 'minutes', 'saves' );

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post_' . Keys::MATCH, array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Add the meta box.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box(
			'athletix_match_stats',
			__( 'Player Statistics', 'athletix' ),
			array( $this, 'render' ),
			Keys::MATCH,
			'normal',
			'default'
		);
	}

	/**
	 * Players eligible for this match (both teams' squads).
	 *
	 * @param int $match_id Match id.
	 * @return \WP_Post[]
	 */
	private function eligible_players( $match_id ) {
		$players = $this->plugin->make( 'repo.player' );
		$home    = (int) get_post_meta( $match_id, Keys::MATCH_HOME_TEAM, true );
		$away    = (int) get_post_meta( $match_id, Keys::MATCH_AWAY_TEAM, true );

		$posts = array();
		if ( $home ) {
			$posts = array_merge( $posts, $players->for_team( $home ) );
		}
		if ( $away ) {
			$posts = array_merge( $posts, $players->for_team( $away ) );
		}

		return $posts;
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Match post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$players = $this->eligible_players( $post->ID );

		if ( empty( $players ) ) {
			echo '<p>' . esc_html__( 'Set the home and away teams (and give them players) to record statistics.', 'athletix' ) . '</p>';
			return;
		}

		$existing = $this->plugin->make( 'repo.player_stats' )->for_match( $post->ID );
		$rows     = array();
		foreach ( $existing as $row ) {
			$rows[] = array(
				'player' => (int) $row['player_id'],
				'metric' => (string) $row['metric'],
				'value'  => (float) $row['value'],
			);
		}
		// Append blank rows for new entries.
		for ( $i = 0; $i < 5; $i++ ) {
			$rows[] = array(
				'player' => 0,
				'metric' => '',
				'value'  => '',
			);
		}

		echo '<datalist id="athletix-metrics">';
		foreach ( self::METRICS as $metric ) {
			echo '<option value="' . esc_attr( $metric ) . '"></option>';
		}
		echo '</datalist>';

		echo '<table class="widefat"><thead><tr>';
		echo '<th>' . esc_html__( 'Player', 'athletix' ) . '</th>';
		echo '<th>' . esc_html__( 'Metric', 'athletix' ) . '</th>';
		echo '<th>' . esc_html__( 'Value', 'athletix' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $index => $row ) {
			echo '<tr>';
			echo '<td><select name="ax_stat[' . esc_attr( $index ) . '][player]">';
			echo '<option value="0">' . esc_html__( '—', 'athletix' ) . '</option>';
			foreach ( $players as $player ) {
				echo '<option value="' . esc_attr( $player->ID ) . '" ' . selected( $row['player'], $player->ID, false ) . '>' . esc_html( get_the_title( $player ) ) . '</option>';
			}
			echo '</select></td>';
			echo '<td><input type="text" list="athletix-metrics" name="ax_stat[' . esc_attr( $index ) . '][metric]" value="' . esc_attr( $row['metric'] ) . '" /></td>';
			echo '<td><input type="number" step="any" name="ax_stat[' . esc_attr( $index ) . '][value]" value="' . esc_attr( (string) $row['value'] ) . '" class="small-text" /></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Leave a row blank to ignore it. Saving replaces all statistics for this match.', 'athletix' ) . '</p>';
	}

	/**
	 * Save submitted statistics.
	 *
	 * @param int      $post_id Match id.
	 * @param \WP_Post $post    Match post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || Keys::MATCH !== $post->post_type ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$stats  = $this->plugin->make( 'repo.player_stats' );
		$season = (int) get_post_meta( $post_id, Keys::MATCH_SEASON, true );

		// Replace this match's statistics wholesale.
		$stats->clear_match( $post_id );

		if ( empty( $_POST['ax_stat'] ) || ! is_array( $_POST['ax_stat'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each field sanitized below.
		$rows = wp_unslash( $_POST['ax_stat'] );

		foreach ( $rows as $row ) {
			$player = isset( $row['player'] ) ? absint( $row['player'] ) : 0;
			$metric = isset( $row['metric'] ) ? sanitize_key( $row['metric'] ) : '';
			$value  = isset( $row['value'] ) && '' !== $row['value'] ? (float) $row['value'] : null;

			if ( $player && '' !== $metric && null !== $value ) {
				$stats->record( $player, $metric, $value, $season, $post_id );
			}
		}
	}
}
