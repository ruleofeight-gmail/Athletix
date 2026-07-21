<?php
/**
 * Playoff generation from standings.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Engine\RankingEngine;

/**
 * Generates a playoff bracket seeded by final standings.
 *
 * Fixes the draft, where playoffs forwarded a raw team list to a broken
 * bracket function with no connection to results. Here the top N teams are
 * taken in standings order, seeded properly, and first-round matches are
 * created (byes auto-advance and produce no match).
 */
class PlayoffSeeder {

	/**
	 * Ranking engine (standings order).
	 *
	 * @var RankingEngine
	 */
	private $ranking;

	/**
	 * Bracket seeder.
	 *
	 * @var BracketSeeder
	 */
	private $seeder;

	/**
	 * Scheduler (to persist matches).
	 *
	 * @var CompetitionScheduler
	 */
	private $scheduler;

	/**
	 * Constructor.
	 *
	 * @param RankingEngine        $ranking   Standings ranking.
	 * @param BracketSeeder        $seeder    Bracket seeding.
	 * @param CompetitionScheduler $scheduler Match persistence.
	 */
	public function __construct( RankingEngine $ranking, BracketSeeder $seeder, CompetitionScheduler $scheduler ) {
		$this->ranking   = $ranking;
		$this->seeder    = $seeder;
		$this->scheduler = $scheduler;
	}

	/**
	 * Generate the first playoff round for a league/season.
	 *
	 * @param int    $league_id League id.
	 * @param int    $season_id Season id.
	 * @param int    $teams     Number of qualifying teams.
	 * @param string $date      First-round date (Y-m-d); empty = today.
	 * @return array{matches:int[],byes:int[],pairings:array[]}
	 */
	public function generate( $league_id, $season_id, $teams = 8, $date = '' ) {
		$league_id  = absint( $league_id );
		$season_id  = absint( $season_id );
		$qualifiers = $this->ranking->top( $league_id, $season_id, absint( $teams ) );

		$result = array(
			'matches'  => array(),
			'byes'     => array(),
			'pairings' => array(),
		);

		if ( count( $qualifiers ) < 2 ) {
			return $result;
		}

		$pairings           = $this->seeder->first_round( $qualifiers );
		$result['pairings'] = $pairings;
		$round_number       = 1000;
		// Playoff rounds numbered high to sort after the regular season.

		foreach ( $pairings as $pair ) {
			if ( $pair['bye'] ) {
				$result['byes'][] = (int) $pair['home'];
				continue;
			}

			$match_id = $this->scheduler->create_match(
				$league_id,
				$season_id,
				$pair['home'],
				$pair['away'],
				$round_number,
				$date ? $date : gmdate( 'Y-m-d' )
			);

			if ( $match_id ) {
				update_post_meta( $match_id, '_ax_playoff', 1 );
				$result['matches'][] = $match_id;
			}
		}

		return $result;
	}
}
