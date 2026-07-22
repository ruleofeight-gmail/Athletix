<?php
/**
 * Competition scheduler — generates and persists fixtures.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Repositories\MatchRepository;
use Athletix\Engine\ScheduleCalculator;
use Athletix\Support\Keys;

/**
 * Turns a set of teams into real, saved match posts.
 *
 * Fixes the draft's create_matches(), which discarded the home/away data and
 * saved empty "Scheduled Match" posts. Here every fixture persists its teams,
 * league, season, round and a spaced-out date.
 */
class CompetitionScheduler {

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Fixture calculator.
	 *
	 * @var ScheduleCalculator
	 */
	private $calculator;

	/**
	 * Constructor.
	 *
	 * @param MatchRepository    $matches    Match storage.
	 * @param ScheduleCalculator $calculator Round-robin calculator.
	 */
	public function __construct( MatchRepository $matches, ScheduleCalculator $calculator ) {
		$this->matches    = $matches;
		$this->calculator = $calculator;
	}

	/**
	 * Generate a round-robin schedule and persist the matches.
	 *
	 * @param int    $league_id     League id.
	 * @param int    $season_id     Season id.
	 * @param int[]  $team_ids      Participating team ids.
	 * @param bool   $double_round  Home-and-away.
	 * @param string $start_date    First round date (Y-m-d); empty = today.
	 * @param int    $days_between  Days between rounds.
	 * @return int[] Created match ids.
	 */
	public function generate_round_robin( $league_id, $season_id, array $team_ids, $double_round = false, $start_date = '', $days_between = 7 ) {
		$league_id = absint( $league_id );
		$team_ids  = array_values( array_filter( array_map( 'absint', $team_ids ) ) );

		if ( count( $team_ids ) < 2 ) {
			return array();
		}

		$rounds       = $this->calculator->round_robin( $team_ids, (bool) $double_round );
		$created      = array();
		$base_ts      = $start_date ? strtotime( $start_date ) : current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$days_between = max( 1, absint( $days_between ) );

		foreach ( $rounds as $round_index => $round ) {
			$round_number = $round_index + 1;
			$round_date   = gmdate( 'Y-m-d', $base_ts + ( $round_index * $days_between * DAY_IN_SECONDS ) );

			foreach ( $round as $game ) {
				$match_id = $this->create_match( $league_id, $season_id, $game['home'], $game['away'], $round_number, $round_date );
				if ( $match_id ) {
					$created[] = $match_id;
				}
			}
		}

		return $created;
	}

	/**
	 * Persist a single fixture as a match post with all its meta.
	 *
	 * @param int    $league_id League id.
	 * @param int    $season_id Season id.
	 * @param int    $home      Home team id.
	 * @param int    $away      Away team id.
	 * @param int    $round     Round number.
	 * @param string $date      Match date (Y-m-d).
	 * @return int Match id, or 0 on failure.
	 */
	public function create_match( $league_id, $season_id, $home, $away, $round, $date ) {
		$home = absint( $home );
		$away = absint( $away );

		if ( ! $home || ! $away || $home === $away ) {
			return 0;
		}

		$title = sprintf(
			/* translators: 1: home team, 2: away team. */
			__( '%1$s vs %2$s', 'athletix' ),
			get_the_title( $home ),
			get_the_title( $away )
		);

		$match_id = $this->matches->create(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $match_id ) || ! $match_id ) {
			return 0;
		}

		$this->matches->set_scope( $match_id, $league_id, absint( $season_id ) );
		$this->matches->set_meta( $match_id, Keys::MATCH_HOME_TEAM, $home );
		$this->matches->set_meta( $match_id, Keys::MATCH_AWAY_TEAM, $away );
		$this->matches->set_meta( $match_id, Keys::MATCH_ROUND, absint( $round ) );
		$this->matches->set_meta( $match_id, Keys::MATCH_DATE, $date );
		$this->matches->set_meta( $match_id, Keys::MATCH_STATUS, Keys::STATUS_SCHEDULED );

		return (int) $match_id;
	}
}
