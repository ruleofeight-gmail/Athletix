<?php
/**
 * Recurring fixture generation.
 *
 * @package Athletix
 */

namespace Athletix\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Competition\CompetitionScheduler;

/**
 * Creates a repeating fixture (same two teams) at a fixed interval — useful for
 * training sessions or recurring derbies. Reuses the competition scheduler so
 * every generated match is a real, fully-populated match post.
 */
class RecurringScheduler {

	/**
	 * Scheduler.
	 *
	 * @var CompetitionScheduler
	 */
	private $scheduler;

	/**
	 * Constructor.
	 *
	 * @param CompetitionScheduler $scheduler Match scheduler.
	 */
	public function __construct( CompetitionScheduler $scheduler ) {
		$this->scheduler = $scheduler;
	}

	/**
	 * Generate a recurring fixture.
	 *
	 * @param int    $home          Home team id.
	 * @param int    $away          Away team id.
	 * @param int    $league        League id.
	 * @param int    $season        Season id.
	 * @param string $start_date    First date (Y-m-d).
	 * @param int    $count         Number of occurrences.
	 * @param int    $interval_days Days between occurrences.
	 * @return int[] Created match ids.
	 */
	public function generate( $home, $away, $league, $season, $start_date, $count, $interval_days = 7 ) {
		$created  = array();
		$base     = $start_date ? strtotime( $start_date ) : time();
		$count    = max( 1, absint( $count ) );
		$interval = max( 1, absint( $interval_days ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$date     = gmdate( 'Y-m-d', $base + ( $i * $interval * DAY_IN_SECONDS ) );
			$match_id = $this->scheduler->create_match( $league, $season, $home, $away, 0, $date );
			if ( $match_id ) {
				$created[] = $match_id;
			}
		}

		return $created;
	}
}
