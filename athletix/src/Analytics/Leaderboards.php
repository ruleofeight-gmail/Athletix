<?php
/**
 * Statistical leaderboards.
 *
 * @package Athletix
 */

namespace Athletix\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Renders a player leaderboard for a metric via [athletix_leaderboard].
 */
class Leaderboards {

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
		add_shortcode( 'athletix_leaderboard', array( $this, 'render' ) );
	}

	/**
	 * [athletix_leaderboard metric="goals" season="0" limit="10"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'metric' => 'goals',
				'season' => 0,
				'limit'  => 10,
				'title'  => '',
			),
			$atts,
			'athletix_leaderboard'
		);

		$rows = $this->plugin->make( 'engine.statistics' )->leaderboard(
			sanitize_key( $atts['metric'] ),
			absint( $atts['season'] ),
			absint( $atts['limit'] )
		);

		wp_enqueue_style( 'athletix' );

		if ( empty( $rows ) ) {
			return '<p class="athletix-empty">' . esc_html__( 'No statistics available yet.', 'athletix' ) . '</p>';
		}

		ob_start();
		if ( '' !== $atts['title'] ) {
			echo '<h3 class="athletix-leaderboard__title">' . esc_html( $atts['title'] ) . '</h3>';
		}
		echo '<ol class="athletix-leaderboard">';
		foreach ( $rows as $row ) {
			$player_id = (int) $row['player_id'];
			printf(
				'<li><a href="%s">%s</a> <span class="athletix-leaderboard__value">%s</span></li>',
				esc_url( get_permalink( $player_id ) ),
				esc_html( get_the_title( $player_id ) ),
				esc_html( (string) ( 0 + $row['total'] ) )
			);
		}
		echo '</ol>';

		return (string) ob_get_clean();
	}
}
