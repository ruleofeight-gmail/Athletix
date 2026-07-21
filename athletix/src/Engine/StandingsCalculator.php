<?php
/**
 * Pure standings computation.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes a standings table from a list of completed matches.
 *
 * Deliberately free of WordPress calls so the league math can be unit-tested
 * in isolation.
 */
class StandingsCalculator {

	/**
	 * Points awarded per result.
	 *
	 * @var array{win:int,draw:int,loss:int}
	 */
	private $points;

	/**
	 * Constructor.
	 *
	 * @param array $points Map with win/draw/loss point values.
	 */
	public function __construct( array $points ) {
		$this->points = array(
			'win'  => (int) ( $points['win'] ?? 3 ),
			'draw' => (int) ( $points['draw'] ?? 1 ),
			'loss' => (int) ( $points['loss'] ?? 0 ),
		);
	}

	/**
	 * Aggregate matches into per-team standings rows.
	 *
	 * @param array $matches List of matches, each with keys home, away,
	 *                       home_score, away_score (all ints).
	 * @return array<int,array> Map of team_id => aggregate row.
	 */
	public function compute( array $matches ) {
		$table = array();

		foreach ( $matches as $match ) {
			$home = (int) ( $match['home'] ?? 0 );
			$away = (int) ( $match['away'] ?? 0 );

			if ( ! $home || ! $away || $home === $away ) {
				continue;
			}

			$hs = (int) ( $match['home_score'] ?? 0 );
			$as = (int) ( $match['away_score'] ?? 0 );

			$table[ $home ] = $this->ensure_row( $table, $home );
			$table[ $away ] = $this->ensure_row( $table, $away );

			$this->apply( $table[ $home ], $hs, $as );
			$this->apply( $table[ $away ], $as, $hs );
		}

		return $table;
	}

	/**
	 * Initialize a row for a team if not present.
	 *
	 * @param array $table Working table.
	 * @param int   $team  Team id.
	 * @return array
	 */
	private function ensure_row( array $table, $team ) {
		if ( isset( $table[ $team ] ) ) {
			return $table[ $team ];
		}

		return array(
			'team_id'       => $team,
			'played'        => 0,
			'won'           => 0,
			'drawn'         => 0,
			'lost'          => 0,
			'goals_for'     => 0,
			'goals_against' => 0,
			'points'        => 0,
		);
	}

	/**
	 * Apply one match result to a team row (by reference).
	 *
	 * @param array $row      Team row (modified in place).
	 * @param int   $scored   Goals scored by this team.
	 * @param int   $conceded Goals conceded by this team.
	 * @return void
	 */
	private function apply( array &$row, $scored, $conceded ) {
		++$row['played'];
		$row['goals_for']     += $scored;
		$row['goals_against'] += $conceded;

		if ( $scored > $conceded ) {
			++$row['won'];
			$row['points'] += $this->points['win'];
		} elseif ( $scored === $conceded ) {
			++$row['drawn'];
			$row['points'] += $this->points['draw'];
		} else {
			++$row['lost'];
			$row['points'] += $this->points['loss'];
		}
	}
}
