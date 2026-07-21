<?php
/**
 * Standings engine.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Cache;
use Athletix\Core\Events;
use Athletix\Data\Repositories\MatchRepository;
use Athletix\Data\Repositories\StandingsRepository;

/**
 * Recomputes and serves league standings. On any match change it clears the
 * affected (league, season) scope and replays every completed match, so the
 * table can never drift from the underlying results.
 */
class StandingsEngine {

	/**
	 * Events service.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Standings repository.
	 *
	 * @var StandingsRepository
	 */
	private $standings;

	/**
	 * Sport engine.
	 *
	 * @var SportEngine
	 */
	private $sport;

	/**
	 * Cache.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Events              $events    Events.
	 * @param MatchRepository     $matches   Matches.
	 * @param StandingsRepository $standings Standings storage.
	 * @param SportEngine         $sport     Sport rules.
	 * @param Cache               $cache     Cache.
	 */
	public function __construct( Events $events, MatchRepository $matches, StandingsRepository $standings, SportEngine $sport, Cache $cache ) {
		$this->events    = $events;
		$this->matches   = $matches;
		$this->standings = $standings;
		$this->sport     = $sport;
		$this->cache     = $cache;
	}

	/**
	 * Subscribe to match events.
	 *
	 * @return void
	 */
	public function register() {
		$this->events->listen( 'match_saved', array( $this, 'on_match_saved' ) );
	}

	/**
	 * Recompute the scope of a saved/deleted match.
	 *
	 * @param array $payload Match payload with league/season.
	 * @return void
	 */
	public function on_match_saved( array $payload ) {
		$league = (int) ( $payload['league'] ?? 0 );
		$season = (int) ( $payload['season'] ?? 0 );

		if ( $league ) {
			$this->recompute( $league, $season );
		}
	}

	/**
	 * Rebuild the standings table for a league/season from completed matches.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return void
	 */
	public function recompute( $league_id, $season_id = 0 ) {
		$league_id = absint( $league_id );
		$season_id = absint( $season_id );

		$details = array();
		foreach ( $this->matches->completed( $league_id, $season_id ) as $match ) {
			$details[] = $this->matches->details( $match->ID );
		}

		$calculator = new StandingsCalculator( $this->sport->points() );
		$rows       = $calculator->compute( $details );

		$this->standings->clear( $league_id, $season_id );

		foreach ( $rows as $row ) {
			$row['league_id'] = $league_id;
			$row['season_id'] = $season_id;
			$this->standings->upsert( $row );
		}

		$this->cache->delete( $this->cache_key( $league_id, $season_id ) );

		$this->events->fire(
			'standings_updated',
			array(
				'league' => $league_id,
				'season' => $season_id,
			)
		);
	}

	/**
	 * Get the ordered standings table (cached).
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return array[]
	 */
	public function table( $league_id, $season_id = 0 ) {
		$league_id = absint( $league_id );
		$season_id = absint( $season_id );

		return $this->cache->remember(
			$this->cache_key( $league_id, $season_id ),
			function () use ( $league_id, $season_id ) {
				return $this->standings->table( $league_id, $season_id );
			}
		);
	}

	/**
	 * Cache key for a scope.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return string
	 */
	private function cache_key( $league_id, $season_id ) {
		return 'standings_' . $league_id . '_' . $season_id;
	}
}
