<?php
/**
 * Pure playoff-bracket structuring for rendering.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a flat list of playoff match rows into an ordered set of bracket rounds
 * ready for display: rounds sorted earliest-first, a human label per round
 * derived from its size (Final / Semifinals / Quarterfinals / Round of N), and
 * the winning side marked on each decided match. WordPress-free for unit testing.
 */
class BracketBuilder {

	/**
	 * Build the bracket rounds from playoff match rows.
	 *
	 * @param array[] $matches Rows, each with keys: round (int), home (int),
	 *                         away (int), home_score (int), away_score (int),
	 *                         status (string).
	 * @return array[] Rounds earliest-first: each [ 'round' => int,
	 *                 'label' => string, 'matches' => array[] ] where each match
	 *                 adds a 'winner' key ('home'|'away'|'').
	 */
	public function build( array $matches ) {
		$by_round = array();

		foreach ( $matches as $match ) {
			$round = isset( $match['round'] ) ? (int) $match['round'] : 0;

			$by_round[ $round ][] = array(
				'home'       => isset( $match['home'] ) ? (int) $match['home'] : 0,
				'away'       => isset( $match['away'] ) ? (int) $match['away'] : 0,
				'home_score' => isset( $match['home_score'] ) ? (int) $match['home_score'] : 0,
				'away_score' => isset( $match['away_score'] ) ? (int) $match['away_score'] : 0,
				'status'     => isset( $match['status'] ) ? (string) $match['status'] : '',
				'winner'     => $this->winner( $match ),
			);
		}

		ksort( $by_round );

		$rounds = array();
		foreach ( $by_round as $round => $round_matches ) {
			$rounds[] = array(
				'round'   => (int) $round,
				'label'   => $this->label( count( $round_matches ) ),
				'matches' => $round_matches,
			);
		}

		return $rounds;
	}

	/**
	 * Which side won a match, if it is decided.
	 *
	 * @param array $match Match row.
	 * @return string 'home', 'away' or '' (undecided/drawn).
	 */
	private function winner( array $match ) {
		$status = isset( $match['status'] ) ? (string) $match['status'] : '';
		if ( 'completed' !== $status ) {
			return '';
		}

		$home = isset( $match['home_score'] ) ? (int) $match['home_score'] : 0;
		$away = isset( $match['away_score'] ) ? (int) $match['away_score'] : 0;

		if ( $home > $away ) {
			return 'home';
		}
		if ( $away > $home ) {
			return 'away';
		}

		return '';
	}

	/**
	 * The conventional name of a round given how many matches it holds.
	 *
	 * @param int $match_count Matches in the round.
	 * @return string
	 */
	private function label( $match_count ) {
		switch ( (int) $match_count ) {
			case 1:
				return __( 'Final', 'athletix' );
			case 2:
				return __( 'Semifinals', 'athletix' );
			case 4:
				return __( 'Quarterfinals', 'athletix' );
			default:
				/* translators: %d: number of teams in the round. */
				return sprintf( __( 'Round of %d', 'athletix' ), $match_count * 2 );
		}
	}
}
