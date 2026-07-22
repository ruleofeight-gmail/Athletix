<?php
/**
 * Team repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for teams.
 */
class TeamRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::TEAM;

	/**
	 * Teams belonging to a league (league is a term assigned to the team).
	 *
	 * @param int $league_id League term id.
	 * @return \WP_Post[]
	 */
	public function for_league( $league_id ) {
		return $this->all(
			array(
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => Keys::LEAGUE,
						'field'    => 'term_id',
						'terms'    => absint( $league_id ),
					),
				),
			)
		);
	}

	/**
	 * The league term id a team belongs to (0 if none).
	 *
	 * @param int $team_id Team id.
	 * @return int
	 */
	public function league_of( $team_id ) {
		$terms = get_the_terms( absint( $team_id ), Keys::LEAGUE );

		return ( is_array( $terms ) && $terms ) ? (int) $terms[0]->term_id : 0;
	}
}
