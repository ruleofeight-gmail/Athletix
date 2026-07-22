<?php
/**
 * Team report.
 *
 * @package Athletix
 */

namespace Athletix\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders a team's league position, roster and recent results via
 * [athletix_team_report].
 */
class TeamReport {

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
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_team_report', array( $this, 'render' ) );
	}

	/**
	 * [athletix_team_report id="5"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'athletix_team_report' );

		$team_id = absint( $atts['id'] );
		if ( ! $team_id ) {
			$team_id = (int) get_the_ID();
		}

		$post = $team_id ? get_post( $team_id ) : null;
		if ( ! $post || Keys::TEAM !== $post->post_type ) {
			return '';
		}

		$league_terms = get_the_terms( $team_id, Keys::LEAGUE );
		$league       = ( is_array( $league_terms ) && $league_terms ) ? (int) $league_terms[0]->term_id : 0;
		$players      = $this->plugin->make( 'repo.player' )->for_team( $team_id );
		$row          = $this->standings_row( $league, $team_id );

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-report athletix-team-report">';
		echo '<h3>' . esc_html( get_the_title( $team_id ) ) . '</h3>';

		if ( $row ) {
			printf(
				'<p>%s</p>',
				esc_html(
					sprintf(
						/* translators: 1: position, 2: points, 3: played. */
						__( 'Position %1$d — %2$d pts from %3$d games', 'athletix' ),
						(int) $row['rank'],
						(int) $row['points'],
						(int) $row['played']
					)
				)
			);
		}

		echo '<h4>' . esc_html__( 'Squad', 'athletix' ) . '</h4>';
		if ( empty( $players ) ) {
			echo '<p class="athletix-empty">' . esc_html__( 'No players.', 'athletix' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $players as $player ) {
				echo '<li><a href="' . esc_url( get_permalink( $player ) ) . '">' . esc_html( get_the_title( $player ) ) . '</a></li>';
			}
			echo '</ul>';
		}

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Find a team's row (with rank) in its league standings.
	 *
	 * @param int $league_id League id.
	 * @param int $team_id   Team id.
	 * @return array|null
	 */
	private function standings_row( $league_id, $team_id ) {
		if ( ! $league_id ) {
			return null;
		}

		$rank = 0;
		foreach ( $this->plugin->make( 'engine.standings' )->table( $league_id, 0 ) as $row ) {
			++$rank;
			if ( (int) $row['team_id'] === $team_id ) {
				$row['rank'] = $rank;
				return $row;
			}
		}

		return null;
	}
}
