<?php
/**
 * Configurable standings ordering.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders standings rows by a configurable chain of tie-breakers.
 *
 * The SQL table read applies a sensible default order, but competitions differ
 * on how ties are broken (goal difference first vs. wins first, head-to-head,
 * etc.). This class expresses that ordering as pure, testable PHP so the chain
 * can be reconfigured per sport without touching queries.
 */
class StandingsSorter {

	/**
	 * Sort direction per supported field. "desc" ranks a larger value higher
	 * (points, wins); "asc" ranks a smaller value higher (losses, goals against).
	 *
	 * @var array<string,string>
	 */
	const DIRECTIONS = array(
		'points'          => 'desc',
		'goal_difference' => 'desc',
		'goals_for'       => 'desc',
		'goals_against'   => 'asc',
		'won'             => 'desc',
		'drawn'           => 'desc',
		'lost'            => 'asc',
		'played'          => 'asc',
	);

	/**
	 * Default tie-break chain (association-football convention).
	 *
	 * @var string[]
	 */
	const DEFAULT_CHAIN = array( 'points', 'goal_difference', 'goals_for', 'won' );

	/**
	 * The active tie-break chain (validated field names, in priority order).
	 *
	 * @var string[]
	 */
	private $chain;

	/**
	 * Constructor.
	 *
	 * @param string[]|null $chain Ordered field names; unknown fields are dropped
	 *                             and an empty result falls back to the default.
	 */
	public function __construct( $chain = null ) {
		$chain = is_array( $chain ) ? $chain : self::DEFAULT_CHAIN;

		$valid = array();
		foreach ( $chain as $field ) {
			if ( is_string( $field ) && isset( self::DIRECTIONS[ $field ] ) ) {
				$valid[] = $field;
			}
		}

		$this->chain = $valid ? $valid : self::DEFAULT_CHAIN;
	}

	/**
	 * Return the resolved tie-break chain.
	 *
	 * @return string[]
	 */
	public function chain() {
		return $this->chain;
	}

	/**
	 * Sort rows and stamp a 1-based rank on each.
	 *
	 * Ties that survive the whole chain fall back to team id so the order is
	 * deterministic regardless of the input order.
	 *
	 * @param array[] $rows Standings rows (each with the aggregate columns).
	 * @return array[] Sorted rows, each gaining a `rank` key.
	 */
	public function sort( array $rows ) {
		foreach ( $rows as &$row ) {
			$row['goal_difference'] = isset( $row['goal_difference'] )
				? (int) $row['goal_difference']
				: (int) ( $row['goals_for'] ?? 0 ) - (int) ( $row['goals_against'] ?? 0 );
		}
		unset( $row );

		usort( $rows, array( $this, 'compare' ) );

		$rank = 0;
		foreach ( $rows as &$row ) {
			$row['rank'] = ++$rank;
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Compare two rows through the tie-break chain.
	 *
	 * @param array $a First row.
	 * @param array $b Second row.
	 * @return int
	 */
	private function compare( array $a, array $b ) {
		foreach ( $this->chain as $field ) {
			$av = (int) ( $a[ $field ] ?? 0 );
			$bv = (int) ( $b[ $field ] ?? 0 );

			if ( $av === $bv ) {
				continue;
			}

			return 'asc' === self::DIRECTIONS[ $field ] ? $av <=> $bv : $bv <=> $av;
		}

		return (int) ( $a['team_id'] ?? 0 ) <=> (int) ( $b['team_id'] ?? 0 );
	}
}
