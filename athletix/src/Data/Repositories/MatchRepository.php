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
	 * Completed matches for a league/season.
	 *
	 * @param int $league_id League id.
	 * @param int $season_id Season id (0 = any).
	 * @return \WP_Post[]
	 */
	public function completed( $league_id, $season_id = 0 ) {
		$meta = array(
			'relation' => 'AND',
			array(
				'key'   => Keys::MATCH_LEAGUE,
				'value' => absint( $league_id ),
			),
			array(
				'key'   => Keys::MATCH_STATUS,
				'value' => Keys::STATUS_COMPLETED,
			),
		);

		if ( $season_id ) {
			$meta[] = array(
				'key'   => Keys::MATCH_SEASON,
				'value' => absint( $season_id ),
			);
		}

		return $this->all(
			array(
				'posts_per_page' => -1,
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
			'league'     => $this->get_meta_id( $match_id, Keys::MATCH_LEAGUE ),
			'season'     => $this->get_meta_id( $match_id, Keys::MATCH_SEASON ),
			'date'       => (string) $this->get_meta( $match_id, Keys::MATCH_DATE, '' ),
		);
	}
}
