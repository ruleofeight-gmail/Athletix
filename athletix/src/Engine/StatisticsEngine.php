<?php
/**
 * Statistics engine.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;
use Athletix\Data\Repositories\PlayerStatsRepository;

/**
 * Player-statistics facade over the stats table. Recording is event-driven:
 * any module (a match-entry UI, an import) fires `record_player_stat`, and this
 * engine persists it, so match processing stays decoupled from stat capture.
 */
class StatisticsEngine {

	/**
	 * Events service.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Player stats repository.
	 *
	 * @var PlayerStatsRepository
	 */
	private $stats;

	/**
	 * Constructor.
	 *
	 * @param Events                $events Events.
	 * @param PlayerStatsRepository $stats  Stats storage.
	 */
	public function __construct( Events $events, PlayerStatsRepository $stats ) {
		$this->events = $events;
		$this->stats  = $stats;
	}

	/**
	 * Subscribe to stat-recording and match-edit events.
	 *
	 * @return void
	 */
	public function register() {
		$this->events->listen( 'record_player_stat', array( $this, 'on_record' ) );
	}

	/**
	 * Persist a recorded player stat.
	 *
	 * @param array $payload player, metric, value, season, match.
	 * @return void
	 */
	public function on_record( array $payload ) {
		$player = (int) ( $payload['player'] ?? 0 );
		$metric = (string) ( $payload['metric'] ?? '' );

		if ( ! $player || '' === $metric ) {
			return;
		}

		$this->stats->record(
			$player,
			$metric,
			(float) ( $payload['value'] ?? 0 ),
			(int) ( $payload['season'] ?? 0 ),
			(int) ( $payload['match'] ?? 0 )
		);
	}

	/**
	 * Leaderboard for a metric.
	 *
	 * @param string $metric    Metric slug.
	 * @param int    $season_id Season id.
	 * @param int    $limit     Rows.
	 * @return array[]
	 */
	public function leaderboard( $metric, $season_id = 0, $limit = 10 ) {
		return $this->stats->leaderboard( $metric, $season_id, $limit );
	}
}
