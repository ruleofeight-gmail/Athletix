<?php
/**
 * League report builder.
 *
 * @package Athletix
 */

namespace Athletix\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Assembles a league summary (standings + top scorers) and renders it via
 * [athletix_report].
 */
class ReportBuilder {

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
		add_shortcode( 'athletix_report', array( $this, 'render' ) );
	}

	/**
	 * Structured report data for a league/season.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return array{standings:array[],top_scorers:array[]}
	 */
	public function build( $league_id, $season_id = 0 ) {
		return array(
			'standings'   => $this->plugin->make( 'engine.standings' )->table( $league_id, $season_id ),
			'top_scorers' => $this->plugin->make( 'engine.statistics' )->leaderboard( 'goals', $season_id, 5 ),
		);
	}

	/**
	 * [athletix_report league="12" season="0"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
			),
			$atts,
			'athletix_report'
		);

		$league_id = absint( $atts['league'] );
		if ( ! $league_id ) {
			return '';
		}

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-report">';
		echo '<h3>' . esc_html__( 'Standings', 'athletix' ) . '</h3>';
		echo do_shortcode( sprintf( '[athletix_standings league="%d" season="%d"]', $league_id, absint( $atts['season'] ) ) );
		echo '<h3>' . esc_html__( 'Top Scorers', 'athletix' ) . '</h3>';
		echo do_shortcode( sprintf( '[athletix_leaderboard metric="goals" season="%d" limit="5"]', absint( $atts['season'] ) ) );
		echo '</div>';

		return (string) ob_get_clean();
	}
}
