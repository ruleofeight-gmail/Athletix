<?php
/**
 * iCalendar (.ics) feed for match schedules.
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
 * Publishes a subscribable .ics feed of a league's fixtures at
 * ?athletix_ics=LEAGUE_ID, so users can add the schedule to any calendar app.
 */
class IcsFeed {

	const QUERY_VAR = 'athletix_ics';

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
		add_filter( 'query_vars', array( $this, 'query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_output' ) );
	}

	/**
	 * Register the query var.
	 *
	 * @param string[] $vars Vars.
	 * @return string[]
	 */
	public function query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * The feed URL for a league.
	 *
	 * @param int $league_id League id.
	 * @return string
	 */
	public static function url( $league_id ) {
		return add_query_arg( self::QUERY_VAR, absint( $league_id ), home_url( '/' ) );
	}

	/**
	 * Output the .ics feed if requested.
	 *
	 * @return void
	 */
	public function maybe_output() {
		$league_id = absint( get_query_var( self::QUERY_VAR ) );
		if ( ! $league_id ) {
			return;
		}

		$matches = $this->plugin->make( 'repo.match' );
		$posts   = $matches->all(
			array(
				'posts_per_page' => 500,
				'orderby'        => 'meta_value',
				'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::MATCH_LEAGUE,
						'value' => $league_id,
					),
				),
			)
		);

		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="athletix-league-' . $league_id . '.ics"' );

		$lines   = array();
		$lines[] = 'BEGIN:VCALENDAR';
		$lines[] = 'VERSION:2.0';
		$lines[] = 'PRODID:-//Athletix//Schedule//EN';
		$lines[] = 'CALSCALE:GREGORIAN';

		foreach ( $posts as $post ) {
			$details = $matches->details( $post->ID );
			if ( empty( $details['date'] ) ) {
				continue;
			}

			$stamp   = gmdate( 'Ymd', strtotime( $details['date'] ) );
			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:athletix-match-' . $post->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
			$lines[] = 'DTSTART;VALUE=DATE:' . $stamp;
			$lines[] = 'SUMMARY:' . $this->escape( get_the_title( $post ) );
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';

		// Output is a calendar feed, not HTML.
		echo implode( "\r\n", $lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Escape a value for an iCalendar text field.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function escape( $value ) {
		return addcslashes( wp_strip_all_tags( $value ), ",;\\" );
	}
}
