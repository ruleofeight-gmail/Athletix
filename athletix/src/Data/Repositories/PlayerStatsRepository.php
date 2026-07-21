<?php
/**
 * Player statistics repository (custom table).
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Schema;

/**
 * Stores per-metric player statistics as rows, so metrics vary by sport
 * without schema changes and totals are computed with SQL aggregation.
 */
class PlayerStatsRepository {

	/**
	 * WordPress database.
	 *
	 * @var \wpdb
	 */
	private $db;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = Schema::table( Schema::PLAYER_STATS );
	}

	/**
	 * Record a metric value for a player in a match.
	 *
	 * @param int    $player_id Player id.
	 * @param string $metric    Metric slug (e.g. goals, assists).
	 * @param float  $value     Value.
	 * @param int    $season_id Season id.
	 * @param int    $match_id  Match id.
	 * @return void
	 */
	public function record( $player_id, $metric, $value, $season_id = 0, $match_id = 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->insert(
			$this->table,
			array(
				'player_id' => absint( $player_id ),
				'season_id' => absint( $season_id ),
				'match_id'  => absint( $match_id ),
				'metric'    => sanitize_key( $metric ),
				'value'     => (float) $value,
			)
		);
	}

	/**
	 * Sum a metric for a player, optionally scoped to a season.
	 *
	 * @param int    $player_id Player id.
	 * @param string $metric    Metric slug.
	 * @param int    $season_id Season id (0 = all).
	 * @return float
	 */
	public function total( $player_id, $metric, $season_id = 0 ) {
		$sql  = "SELECT COALESCE(SUM(value), 0) FROM {$this->table} WHERE player_id = %d AND metric = %s";
		$args = array( absint( $player_id ), sanitize_key( $metric ) );

		if ( $season_id ) {
			$sql   .= ' AND season_id = %d';
			$args[] = absint( $season_id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (float) $this->db->get_var( $this->db->prepare( $sql, $args ) );
	}

	/**
	 * Leaderboard for a metric: top players by summed value.
	 *
	 * @param string $metric    Metric slug.
	 * @param int    $season_id Season id (0 = all).
	 * @param int    $limit     Max rows.
	 * @return array[] Rows of player_id => total.
	 */
	public function leaderboard( $metric, $season_id = 0, $limit = 10 ) {
		$sql  = "SELECT player_id, SUM(value) AS total FROM {$this->table} WHERE metric = %s";
		$args = array( sanitize_key( $metric ) );

		if ( $season_id ) {
			$sql   .= ' AND season_id = %d';
			$args[] = absint( $season_id );
		}

		$sql   .= ' GROUP BY player_id ORDER BY total DESC LIMIT %d';
		$args[] = absint( $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->db->get_results( $this->db->prepare( $sql, $args ), ARRAY_A );
	}

	/**
	 * Delete all stats for a match (used before re-recording on edit).
	 *
	 * @param int $match_id Match id.
	 * @return void
	 */
	public function clear_match( $match_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->delete( $this->table, array( 'match_id' => absint( $match_id ) ) );
	}
}
