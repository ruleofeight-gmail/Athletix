<?php
/**
 * Match repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for matches, including a typed value object accessor.
 */
class MatchRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::MATCH;

	/**
	 * Completed matches for a league/season (league & season are terms).
	 *
	 * @param int $league_id League term id.
	 * @param int $season_id Season term id (0 = any).
	 * @return \WP_Post[]
	 */
	public function completed( $league_id, $season_id = 0 ) {
		$tax = array(
			array(
				'taxonomy' => Keys::LEAGUE,
				'field'    => 'term_id',
				'terms'    => absint( $league_id ),
			),
		);

		if ( $season_id ) {
			$tax[] = array(
				'taxonomy' => Keys::SEASON,
				'field'    => 'term_id',
				'terms'    => absint( $season_id ),
			);
		}

		return $this->all(
			array(
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::MATCH_STATUS,
						'value' => Keys::STATUS_COMPLETED,
					),
				),
				'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPress.WP.DiscouragedFunctions
			)
		);
	}

	/**
	 * Extract the meaningful fields of a match as a plain array.
	 *
	 * @param int $match_id Match id.
	 * @return array{home:int,away:int,home_score:int,away_score:int,status:string,league:int,season:int,date:string}
	 */
	public function details( $match_id ) {
		$match_id = absint( $match_id );

		return array(
			'home'       => $this->get_meta_id( $match_id, Keys::MATCH_HOME_TEAM ),
			'away'       => $this->get_meta_id( $match_id, Keys::MATCH_AWAY_TEAM ),
			'home_score' => (int) $this->get_meta( $match_id, Keys::MATCH_HOME_SCORE, 0 ),
			'away_score' => (int) $this->get_meta( $match_id, Keys::MATCH_AWAY_SCORE, 0 ),
			'status'     => (string) $this->get_meta( $match_id, Keys::MATCH_STATUS, Keys::STATUS_SCHEDULED ),
			'league'     => $this->term_id( $match_id, Keys::LEAGUE ),
			'season'     => $this->term_id( $match_id, Keys::SEASON ),
			'date'       => (string) $this->get_meta( $match_id, Keys::MATCH_DATE, '' ),
		);
	}

	/**
	 * The id of the first term of a taxonomy assigned to a match (0 if none).
	 *
	 * @param int    $match_id Match id.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	private function term_id( $match_id, $taxonomy ) {
		$terms = get_the_terms( $match_id, $taxonomy );

		return ( is_array( $terms ) && $terms ) ? (int) $terms[0]->term_id : 0;
	}

	/**
	 * Assign a match's league and season terms (replacing existing).
	 *
	 * @param int $match_id  Match id.
	 * @param int $league_id League term id (0 clears).
	 * @param int $season_id Season term id (0 clears).
	 * @return void
	 */
	public function set_scope( $match_id, $league_id, $season_id ) {
		wp_set_object_terms( absint( $match_id ), $league_id ? array( absint( $league_id ) ) : array(), Keys::LEAGUE, false );
		wp_set_object_terms( absint( $match_id ), $season_id ? array( absint( $season_id ) ) : array(), Keys::SEASON, false );
	}
}
