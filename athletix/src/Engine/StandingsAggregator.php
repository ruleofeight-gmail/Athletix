<?php
/**
 * Pure standings aggregation driven by configurable outcomes.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregates completed matches into raw per-team rows (played, won, drawn, lost,
 * goals for/against). Which result counts as a win/draw/loss is decided by the
 * supplied Outcome equations (evaluated per match, per team), so the same math
 * serves any sport's rules. WordPress-free for unit testing.
 *
 * The three outcome buckets map to the stored columns by key: w|won → won,
 * d|drawn → drawn, l|lost → lost.
 */
class StandingsAggregator {

	/**
	 * Outcome definitions: each ['key' => string, 'equation' => string].
	 *
	 * @var array[]
	 */
	private $outcomes;

	/**
	 * Equation evaluator.
	 *
	 * @var Equation
	 */
	private $equation;

	/**
	 * Constructor.
	 *
	 * @param array[]  $outcomes Outcome definitions.
	 * @param Equation $equation Evaluator (a fresh one is made when omitted).
	 */
	public function __construct( array $outcomes, Equation $equation = null ) {
		$this->outcomes = $outcomes;
		$this->equation = $equation ? $equation : new Equation();
	}

	/**
	 * Aggregate matches into per-team standings rows.
	 *
	 * @param array $matches List of matches, each with home, away, home_score,
	 *                       away_score.
	 * @return array<int,array> Map of team_id => raw row.
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
	 * Initialize a team row.
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
	 * Apply one match result to a team row (by reference), classifying it with
	 * the first matching outcome.
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

		$context = array(
			'gf' => $scored,
			'ga' => $conceded,
		);

		foreach ( $this->outcomes as $outcome ) {
			$equation = isset( $outcome['equation'] ) ? (string) $outcome['equation'] : '';
			if ( '' === $equation ) {
				continue;
			}

			if ( $this->equation->evaluate( $equation, $context ) != 0.0 ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- numeric truthiness of an equation result.
				$bucket = $this->bucket( isset( $outcome['key'] ) ? (string) $outcome['key'] : '' );
				if ( '' !== $bucket ) {
					++$row[ $bucket ];
				}
				break;
			}
		}
	}

	/**
	 * Map an outcome key to a stored row bucket.
	 *
	 * @param string $key Outcome key.
	 * @return string 'won'|'drawn'|'lost'|''.
	 */
	private function bucket( $key ) {
		switch ( $key ) {
			case 'w':
			case 'won':
				return 'won';
			case 'd':
			case 'drawn':
				return 'drawn';
			case 'l':
			case 'lost':
				return 'lost';
			default:
				return '';
		}
	}
}
