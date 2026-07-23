<?php
/**
 * Computes and orders standings columns from admin equations.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns raw standings rows into display rows: it evaluates each configured
 * column's equation against the row's base facts, then orders the table by the
 * columns' sort-priority chain (each with its own direction), stamping a rank.
 * WordPress-free so the calculation + default sort can be unit-tested.
 */
class StandingsColumns {

	/**
	 * Column definitions: key, equation, precision, sort, order.
	 *
	 * @var array[]
	 */
	private $columns;

	/**
	 * Equation evaluator.
	 *
	 * @var Equation
	 */
	private $equation;

	/**
	 * Constructor.
	 *
	 * @param array[]  $columns  Column definitions.
	 * @param Equation $equation Evaluator (a fresh one is made when omitted).
	 */
	public function __construct( array $columns, Equation $equation = null ) {
		$this->columns  = $columns;
		$this->equation = $equation ? $equation : new Equation();
	}

	/**
	 * Compute column values for every row and order the table.
	 *
	 * @param array[] $rows Raw rows (team_id, played, won, drawn, lost,
	 *                      goals_for, goals_against).
	 * @return array[] Ordered rows with each column key filled in, plus 'rank'.
	 */
	public function build( array $rows ) {
		$rows = array_values( $rows );

		foreach ( $rows as &$row ) {
			$context = $this->context( $row );
			foreach ( $this->columns as $column ) {
				$key = isset( $column['key'] ) ? (string) $column['key'] : '';
				if ( '' === $key ) {
					continue;
				}
				$precision   = isset( $column['precision'] ) ? (int) $column['precision'] : 0;
				$value       = $this->equation->evaluate( (string) ( $column['equation'] ?? '' ), $context, $precision );
				$row[ $key ] = 0 === $precision ? (int) $value : $value;
			}
		}
		unset( $row );

		return $this->sort( $rows );
	}

	/**
	 * Build the equation context (base facts plus short/long aliases) for a row.
	 *
	 * @param array $row Raw row.
	 * @return array<string,float>
	 */
	private function context( array $row ) {
		$played = (float) ( $row['played'] ?? 0 );
		$won    = (float) ( $row['won'] ?? 0 );
		$drawn  = (float) ( $row['drawn'] ?? 0 );
		$lost   = (float) ( $row['lost'] ?? 0 );
		$gf     = (float) ( $row['goals_for'] ?? 0 );
		$ga     = (float) ( $row['goals_against'] ?? 0 );

		return array(
			'played'        => $played,
			'w'             => $won,
			'won'           => $won,
			'd'             => $drawn,
			'drawn'         => $drawn,
			'l'             => $lost,
			'lost'          => $lost,
			'gf'            => $gf,
			'goals_for'     => $gf,
			'ga'            => $ga,
			'goals_against' => $ga,
		);
	}

	/**
	 * Order rows by the columns' sort-priority chain, then stamp rank.
	 *
	 * @param array[] $rows Rows with column values filled in.
	 * @return array[]
	 */
	private function sort( array $rows ) {
		$chain = array();
		foreach ( $this->columns as $column ) {
			$priority = isset( $column['sort'] ) ? (int) $column['sort'] : 0;
			if ( $priority > 0 && ! empty( $column['key'] ) ) {
				$chain[] = array(
					'key'   => (string) $column['key'],
					'order' => isset( $column['order'] ) && 'asc' === $column['order'] ? 'asc' : 'desc',
					'pri'   => $priority,
				);
			}
		}

		usort(
			$chain,
			static function ( $a, $b ) {
				return $a['pri'] <=> $b['pri'];
			}
		);

		usort(
			$rows,
			function ( $a, $b ) use ( $chain ) {
				foreach ( $chain as $rule ) {
					$av  = (float) ( $a[ $rule['key'] ] ?? 0 );
					$bv  = (float) ( $b[ $rule['key'] ] ?? 0 );
					$cmp = $av <=> $bv;
					if ( 'desc' === $rule['order'] ) {
						$cmp = -$cmp;
					}
					if ( 0 !== $cmp ) {
						return $cmp;
					}
				}

				// Stable, deterministic fallback.
				return (int) ( $a['team_id'] ?? 0 ) <=> (int) ( $b['team_id'] ?? 0 );
			}
		);

		$rank = 0;
		foreach ( $rows as &$row ) {
			++$rank;
			$row['rank'] = $rank;
		}
		unset( $row );

		return $rows;
	}
}
