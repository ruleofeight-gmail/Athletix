<?php
/**
 * Player trend analytics.
 *
 * @package Athletix
 */

namespace Athletix\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders a player's recent per-match trend for a metric via
 * [athletix_player_trend] as a small inline bar sparkline.
 */
class PlayerAnalytics {

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
		add_shortcode( 'athletix_player_trend', array( $this, 'render' ) );
	}

	/**
	 * [athletix_player_trend id="7" metric="goals" limit="10"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'metric' => 'goals',
				'limit'  => 10,
			),
			$atts,
			'athletix_player_trend'
		);

		$player_id = absint( $atts['id'] );
		if ( ! $player_id ) {
			$player_id = (int) get_the_ID();
		}

		if ( ! $player_id || get_post_type( $player_id ) !== Keys::PLAYER ) {
			return '';
		}

		$rows = $this->plugin->make( 'repo.player_stats' )->timeline( $player_id, sanitize_key( $atts['metric'] ), absint( $atts['limit'] ) );
		if ( empty( $rows ) ) {
			return '<p class="athletix-empty">' . esc_html__( 'No data.', 'athletix' ) . '</p>';
		}

		$values = array_map(
			static function ( $row ) {
				return (float) $row['value'];
			},
			$rows
		);
		$max    = max( $values );
		$max    = $max > 0 ? $max : 1;

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-trend" role="img" aria-label="' . esc_attr__( 'Performance trend', 'athletix' ) . '">';
		foreach ( $values as $value ) {
			$height = (int) round( ( $value / $max ) * 100 );
			printf(
				'<span class="athletix-trend__bar" style="height:%d%%" title="%s"></span>',
				(int) $height,
				esc_attr( (string) ( 0 + $value ) )
			);
		}
		echo '</div>';

		return (string) ob_get_clean();
	}
}
