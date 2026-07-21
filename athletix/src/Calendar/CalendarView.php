<?php
/**
 * Month calendar shortcode.
 *
 * @package Athletix
 */

namespace Athletix\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders a month grid of fixtures via [athletix_calendar].
 */
class CalendarView {

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
		add_shortcode( 'athletix_calendar', array( $this, 'render' ) );
	}

	/**
	 * [athletix_calendar league="12" month="2026-07"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'month'  => gmdate( 'Y-m' ),
			),
			$atts,
			'athletix_calendar'
		);

		$month = preg_match( '/^\d{4}-\d{2}$/', (string) $atts['month'] ) ? $atts['month'] : gmdate( 'Y-m' );
		$first = strtotime( $month . '-01' );
		$days  = (int) gmdate( 't', $first );
		$lead  = (int) gmdate( 'w', $first );

		$by_day = $this->matches_by_day( absint( $atts['league'] ), $month, $days );

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-calendar">';
		echo '<h3>' . esc_html( date_i18n( 'F Y', $first ) ) . '</h3>';
		echo '<table class="athletix-calendar__grid"><thead><tr>';
		foreach ( array( __( 'Sun', 'athletix' ), __( 'Mon', 'athletix' ), __( 'Tue', 'athletix' ), __( 'Wed', 'athletix' ), __( 'Thu', 'athletix' ), __( 'Fri', 'athletix' ), __( 'Sat', 'athletix' ) ) as $dow ) {
			echo '<th>' . esc_html( $dow ) . '</th>';
		}
		echo '</tr></thead><tbody><tr>';

		// Leading blanks.
		for ( $i = 0; $i < $lead; $i++ ) {
			echo '<td class="athletix-calendar__empty"></td>';
		}

		$col = $lead;
		for ( $day = 1; $day <= $days; $day++ ) {
			echo '<td class="athletix-calendar__day"><span class="athletix-calendar__num">' . esc_html( (string) $day ) . '</span>';
			if ( ! empty( $by_day[ $day ] ) ) {
				echo '<ul>';
				foreach ( $by_day[ $day ] as $title ) {
					echo '<li>' . esc_html( $title ) . '</li>';
				}
				echo '</ul>';
			}
			echo '</td>';

			++$col;
			if ( 0 === $col % 7 && $day < $days ) {
				echo '</tr><tr>';
			}
		}

		// Trailing blanks.
		while ( 0 !== $col % 7 ) {
			echo '<td class="athletix-calendar__empty"></td>';
			++$col;
		}

		echo '</tr></tbody></table></div>';

		return (string) ob_get_clean();
	}

	/**
	 * Map day-of-month => list of match titles.
	 *
	 * @param int    $league_id League id (0 = all).
	 * @param string $month     Year-month (Y-m).
	 * @param int    $days      Days in month.
	 * @return array<int,string[]>
	 */
	private function matches_by_day( $league_id, $month, $days ) {
		$repo = $this->plugin->make( 'repo.match' );

		$meta = array(
			array(
				'key'     => Keys::MATCH_DATE,
				'value'   => array( $month . '-01', $month . '-' . str_pad( (string) $days, 2, '0', STR_PAD_LEFT ) ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		);

		if ( $league_id ) {
			$meta[] = array(
				'key'   => Keys::MATCH_LEAGUE,
				'value' => $league_id,
			);
		}

		$posts  = $repo->all(
			array(
				'posts_per_page' => 200,
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);
		$by_day = array();

		foreach ( $posts as $post ) {
			$date             = get_post_meta( $post->ID, Keys::MATCH_DATE, true );
			$day              = (int) gmdate( 'j', strtotime( $date ) );
			$by_day[ $day ][] = get_the_title( $post );
		}

		return $by_day;
	}
}
