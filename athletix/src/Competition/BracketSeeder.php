<?php
/**
 * Pure single-elimination bracket seeding.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds a correctly seeded single-elimination bracket.
 *
 * Replaces the draft's broken array_slice approach: teams are padded to the
 * next power of two with byes, seeded in standard order (so the top two seeds
 * can only meet in the final), and a team drawn against a bye auto-advances.
 * WordPress-free for unit testing.
 */
class BracketSeeder {

	/**
	 * Generate the first-round pairings from seeded team ids.
	 *
	 * @param int[] $team_ids Team ids in seed order (best first).
	 * @return array[] List of pairings: each ['home'=>id, 'away'=>id|null,
	 *                 'seed_home'=>int, 'seed_away'=>int, 'bye'=>bool].
	 */
	public function first_round( array $team_ids ) {
		$team_ids = array_values( array_map( 'intval', $team_ids ) );
		$count    = count( $team_ids );

		if ( $count < 2 ) {
			return array();
		}

		$size  = $this->next_power_of_two( $count );
		$order = $this->seed_order( $size );

		$pairs = array();
		for ( $i = 0; $i < $size; $i += 2 ) {
			$seed_home = $order[ $i ];
			$seed_away = $order[ $i + 1 ];

			$home = isset( $team_ids[ $seed_home - 1 ] ) ? $team_ids[ $seed_home - 1 ] : null;
			$away = isset( $team_ids[ $seed_away - 1 ] ) ? $team_ids[ $seed_away - 1 ] : null;

			// Always keep the present team as "home" for bye clarity.
			if ( null === $home && null !== $away ) {
				$home      = $away;
				$away      = null;
				$tmp       = $seed_home;
				$seed_home = $seed_away;
				$seed_away = $tmp;
			}

			$pairs[] = array(
				'home'      => $home,
				'away'      => $away,
				'seed_home' => $seed_home,
				'seed_away' => $seed_away,
				'bye'       => ( null === $away ),
			);
		}//end for

		return $pairs;
	}

	/**
	 * Number of rounds a bracket of this many teams needs.
	 *
	 * @param int $team_count Team count.
	 * @return int
	 */
	public function rounds_needed( $team_count ) {
		if ( $team_count < 2 ) {
			return 0;
		}
		return (int) ceil( log( $this->next_power_of_two( $team_count ), 2 ) );
	}

	/**
	 * Smallest power of two >= n.
	 *
	 * @param int $n Value.
	 * @return int
	 */
	private function next_power_of_two( $n ) {
		$size = 1;
		while ( $size < $n ) {
			$size <<= 1;
		}
		return $size;
	}

	/**
	 * Standard bracket seed order for a power-of-two size.
	 *
	 * Produces the 1-indexed seed positions so that, read in pairs, the
	 * strongest seeds are kept apart until the latest possible round.
	 *
	 * @param int $size Power-of-two bracket size.
	 * @return int[]
	 */
	private function seed_order( $size ) {
		$rounds = (int) log( $size, 2 );
		$seeds  = array( 1 );

		for ( $r = 0; $r < $rounds; $r++ ) {
			$sum  = count( $seeds ) * 2 + 1;
			$next = array();
			foreach ( $seeds as $seed ) {
				$next[] = $seed;
				$next[] = $sum - $seed;
			}
			$seeds = $next;
		}

		return $seeds;
	}
}
