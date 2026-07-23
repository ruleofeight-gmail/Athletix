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
use Athletix\Customize\VariableRepository;
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
	 * Customize variable repository (columns + outcomes).
	 *
	 * @var VariableRepository
	 */
	private $variables;

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
	 * @param VariableRepository  $variables Configured columns + outcomes.
	 * @param Cache               $cache     Cache.
	 */
	public function __construct( Events $events, MatchRepository $matches, StandingsRepository $standings, SportEngine $sport, VariableRepository $variables, Cache $cache ) {
		$this->events    = $events;
		$this->matches   = $matches;
		$this->standings = $standings;
		$this->sport     = $sport;
		$this->variables = $variables;
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

		$sport_id = $this->sport->sport_term_for_league( $league_id );
		$outcomes = $this->variables->outcomes( $sport_id );
		$columns  = $this->variables->columns( $sport_id );

		// Aggregate raw facts (outcomes decide win/draw/loss), then compute the
		// configured columns so the stored 'points' reflects the admin equation.
		$raw      = ( new StandingsAggregator( $outcomes ) )->compute( $details );
		$computed = ( new StandingsColumns( $columns ) )->build( $raw );

		$this->standings->clear( $league_id, $season_id );

		foreach ( $computed as $row ) {
			$row['league_id'] = $league_id;
			$row['season_id'] = $season_id;
			$row['points']    = isset( $row['points'] ) ? $row['points'] : 0;
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
				$rows     = $this->standings->table( $league_id, $season_id );
				$sport_id = $this->sport->sport_term_for_league( $league_id );
				$columns  = $this->variables->columns( $sport_id );

				return ( new StandingsColumns( $columns ) )->build( $rows );
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
