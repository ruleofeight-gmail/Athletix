<?php
/**
 * Ranking engine.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Derives an ordered ranking (team ids, best first) from the standings table.
 * Kept separate from StandingsEngine so alternative ranking schemes (power
 * rankings, Elo) can be layered without touching standings storage.
 */
class RankingEngine {

	/**
	 * Standings engine.
	 *
	 * @var StandingsEngine
	 */
	private $standings;

	/**
	 * Constructor.
	 *
	 * @param StandingsEngine $standings Standings engine.
	 */
	public function __construct( StandingsEngine $standings ) {
		$this->standings = $standings;
	}

	/**
	 * Ordered team ids for a league/season.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return int[]
	 */
	public function ranked_team_ids( $league_id, $season_id = 0 ) {
		$ids = array();

		foreach ( $this->standings->table( $league_id, $season_id ) as $row ) {
			$ids[] = (int) $row['team_id'];
		}

		return $ids;
	}

	/**
	 * The top N teams by standings.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @param int $count     How many.
	 * @return int[]
	 */
	public function top( $league_id, $season_id, $count ) {
		return array_slice( $this->ranked_team_ids( $league_id, $season_id ), 0, absint( $count ) );
	}
}
