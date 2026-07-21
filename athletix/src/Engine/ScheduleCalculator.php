<?php
/**
 * Pure fixture generation (round-robin, circle method).
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates balanced round-robin fixtures using the circle method, grouped
 * into rounds where every team plays once per round. WordPress-free so the
 * scheduling logic is unit-testable.
 */
class ScheduleCalculator {

	/**
	 * Build round-robin rounds.
	 *
	 * @param int[] $teams        Team ids.
	 * @param bool  $double_round Whether to add reversed home/away second legs.
	 * @return array[] List of rounds; each round is a list of ['home'=>id,'away'=>id].
	 */
	public function round_robin( array $teams, $double_round = false ) {
		$teams = array_values( array_unique( array_map( 'intval', $teams ) ) );

		if ( count( $teams ) < 2 ) {
			return array();
		}

		// A bye (0) is added for an odd number of teams; those pairings are dropped.
		$bye = false;
		if ( 0 !== count( $teams ) % 2 ) {
			$teams[] = 0;
			$bye     = true;
		}

		$count    = count( $teams );
		$rounds   = array();
		$fixed    = $teams[0];
		$rotating = array_slice( $teams, 1 );

		for ( $r = 0; $r < $count - 1; $r++ ) {
			$round = array();
			$line  = array_merge( array( $fixed ), $rotating );

			for ( $i = 0; $i < $count / 2; $i++ ) {
				$home = $line[ $i ];
				$away = $line[ $count - 1 - $i ];

				if ( 0 === $home || 0 === $away ) {
					continue;
					// Bye — no match.
				}

				// Alternate home/away by round for fairness.
				if ( 0 === $r % 2 ) {
					$round[] = array(
						'home' => $home,
						'away' => $away,
					);
				} else {
					$round[] = array(
						'home' => $away,
						'away' => $home,
					);
				}
			}//end for

			$rounds[] = $round;

			// Rotate all but the fixed team clockwise.
			array_unshift( $rotating, array_pop( $rotating ) );
		}//end for

		if ( $double_round ) {
			$rounds = array_merge( $rounds, $this->mirror( $rounds ) );
		}

		unset( $bye );

		return $rounds;
	}

	/**
	 * Produce reversed-leg rounds (swap home/away).
	 *
	 * @param array[] $rounds First-leg rounds.
	 * @return array[]
	 */
	private function mirror( array $rounds ) {
		$mirrored = array();

		foreach ( $rounds as $round ) {
			$new = array();
			foreach ( $round as $game ) {
				$new[] = array(
					'home' => $game['away'],
					'away' => $game['home'],
				);
			}
			$mirrored[] = $new;
		}

		return $mirrored;
	}
}
