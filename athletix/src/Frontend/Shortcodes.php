<?php
/**
 * Front-end shortcodes.
 *
 * @package Athletix
 */

namespace Athletix\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders standings, rosters and schedules via shortcodes, delegating data to
 * the engines/repositories and markup to template partials.
 */
class Shortcodes {

	/**
	 * Plugin instance.
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
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_standings', array( $this, 'standings' ) );
		add_shortcode( 'athletix_roster', array( $this, 'roster' ) );
		add_shortcode( 'athletix_schedule', array( $this, 'schedule' ) );
	}

	/**
	 * [athletix_standings league="12" season="0"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function standings( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
			),
			$atts,
			'athletix_standings'
		);

		$rows = $this->plugin->make( 'engine.standings' )->table( absint( $atts['league'] ), absint( $atts['season'] ) );

		return $this->render( 'standings', array( 'rows' => $rows ) );
	}

	/**
	 * [athletix_roster team="5"] or [athletix_roster league="12"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function roster( $atts ) {
		$atts = shortcode_atts(
			array(
				'team'    => 0,
				'league'  => 0,
				'columns' => 3,
			),
			$atts,
			'athletix_roster'
		);

		$players = $this->plugin->make( 'repo.player' );

		if ( $atts['team'] ) {
			$posts = $players->for_team( absint( $atts['team'] ) );
		} elseif ( $atts['league'] ) {
			$teams   = wp_list_pluck( $this->plugin->make( 'repo.team' )->for_league( absint( $atts['league'] ) ), 'ID' );
			$posts   = empty( $teams ) ? array() : $players->all(
				array(
					'posts_per_page' => -1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => Keys::PLAYER_TEAM,
							'value'   => $teams,
							'compare' => 'IN',
						),
					),
				)
			);
		} else {
			$posts = $players->all();
		}

		return $this->render(
			'roster',
			array(
				'players' => $posts,
				'columns' => max( 1, absint( $atts['columns'] ) ),
			)
		);
	}

	/**
	 * [athletix_schedule league="12" scope="upcoming"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function schedule( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
				'limit'  => 20,
			),
			$atts,
			'athletix_schedule'
		);

		$args = array(
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'meta_value',
			'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
		);

		$meta = array();
		if ( $atts['league'] ) {
			$meta[] = array(
				'key'   => Keys::MATCH_LEAGUE,
				'value' => absint( $atts['league'] ),
			);
		}
		if ( $atts['season'] ) {
			$meta[] = array(
				'key'   => Keys::MATCH_SEASON,
				'value' => absint( $atts['season'] ),
			);
		}
		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$matches = $this->plugin->make( 'repo.match' );

		return $this->render(
			'schedule',
			array(
				'matches' => $matches->all( $args ),
				'repo'    => $matches,
			)
		);
	}

	/**
	 * Render a template partial with data, returning its output.
	 *
	 * @param string $template Template slug (templates/{slug}.php).
	 * @param array  $data     Data extracted into scope.
	 * @return string
	 */
	private function render( $template, array $data ) {
		wp_enqueue_style( 'athletix' );

		$file = ATHLETIX_PATH . 'templates/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );
		include $file;

		return (string) ob_get_clean();
	}
}
