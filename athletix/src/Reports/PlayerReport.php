<?php
/**
 * Player report.
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
 * Renders a single player's profile and career/season statistics via
 * [athletix_player_report].
 */
class PlayerReport {

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
		add_shortcode( 'athletix_player_report', array( $this, 'render' ) );
	}

	/**
	 * [athletix_player_report id="7" season="0"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'season' => 0,
			),
			$atts,
			'athletix_player_report'
		);

		$player_id = absint( $atts['id'] );
		if ( ! $player_id ) {
			$player_id = (int) get_the_ID();
		}

		$post = $player_id ? get_post( $player_id ) : null;
		if ( ! $post || Keys::PLAYER !== $post->post_type ) {
			return '';
		}

		$totals = $this->plugin->make( 'repo.player_stats' )->player_totals( $player_id, absint( $atts['season'] ) );
		$team   = (int) get_post_meta( $player_id, Keys::PLAYER_TEAM, true );

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-report athletix-player-report">';
		echo '<h3>' . esc_html( get_the_title( $player_id ) ) . '</h3>';

		if ( $team ) {
			echo '<p class="athletix-player__team"><a href="' . esc_url( get_permalink( $team ) ) . '">' . esc_html( get_the_title( $team ) ) . '</a></p>';
		}

		if ( empty( $totals ) ) {
			echo '<p class="athletix-empty">' . esc_html__( 'No statistics recorded.', 'athletix' ) . '</p>';
		} else {
			echo '<table class="athletix-standings"><tbody>';
			foreach ( $totals as $metric => $total ) {
				echo '<tr><td class="athletix-standings__team">' . esc_html( ucwords( str_replace( '_', ' ', $metric ) ) ) . '</td><td class="athletix-standings__pts">' . esc_html( (string) ( 0 + $total ) ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '</div>';

		return (string) ob_get_clean();
	}
}
