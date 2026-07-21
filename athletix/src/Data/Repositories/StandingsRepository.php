<?php
/**
 * Standings repository (custom table).
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Schema;

/**
 * Reads and writes league standings in a dedicated, indexed table.
 *
 * A standings row is keyed by (league, season, team). Recomputation clears the
 * scope and re-inserts, so tables never drift from the underlying matches.
 */
class StandingsRepository {

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
		$this->table = Schema::table( Schema::STANDINGS );
	}

	/**
	 * Replace the aggregate row for a team.
	 *
	 * @param array $row Row data (league_id, season_id, division_id, team_id, played, won, drawn, lost, goals_for, goals_against, points).
	 * @return void
	 */
	public function upsert( array $row ) {
		$data = array(
			'league_id'     => absint( $row['league_id'] ?? 0 ),
			'season_id'     => absint( $row['season_id'] ?? 0 ),
			'division_id'   => absint( $row['division_id'] ?? 0 ),
			'team_id'       => absint( $row['team_id'] ?? 0 ),
			'played'        => (int) ( $row['played'] ?? 0 ),
			'won'           => (int) ( $row['won'] ?? 0 ),
			'drawn'         => (int) ( $row['drawn'] ?? 0 ),
			'lost'          => (int) ( $row['lost'] ?? 0 ),
			'goals_for'     => (int) ( $row['goals_for'] ?? 0 ),
			'goals_against' => (int) ( $row['goals_against'] ?? 0 ),
			'points'        => (int) ( $row['points'] ?? 0 ),
			'updated_at'    => current_time( 'mysql' ),
		);

		$existing = $this->db->get_var(
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$this->table} WHERE league_id = %d AND season_id = %d AND team_id = %d",
				$data['league_id'],
				$data['season_id'],
				$data['team_id']
			)
		);

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->db->update( $this->table, $data, array( 'id' => (int) $existing ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->db->insert( $this->table, $data );
		}
	}

	/**
	 * Ordered standings table for a league/season.
	 *
	 * Sorted by points, then goal difference, then goals for.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id (0 = aggregate across seasons).
	 * @return array[] Rows as associative arrays with a computed goal_difference.
	 */
	public function table( $league_id, $season_id = 0 ) {
		$league_id = absint( $league_id );
		$season_id = absint( $season_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $this->db->get_results(
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table} WHERE league_id = %d AND season_id = %d
				 ORDER BY points DESC, (goals_for - goals_against) DESC, goals_for DESC",
				$league_id,
				$season_id
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['goal_difference'] = (int) $row['goals_for'] - (int) $row['goals_against'];
		}

		return $rows;
	}

	/**
	 * Delete every standings row for a league/season scope.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id.
	 * @return void
	 */
	public function clear( $league_id, $season_id = 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->delete(
			$this->table,
			array(
				'league_id' => absint( $league_id ),
				'season_id' => absint( $season_id ),
			)
		);
	}
}
