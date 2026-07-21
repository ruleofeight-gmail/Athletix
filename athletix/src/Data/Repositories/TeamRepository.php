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
	 * Teams belonging to a league.
	 *
	 * @param int $league_id League id.
	 * @return \WP_Post[]
	 */
	public function for_league( $league_id ) {
		return $this->find_by_meta( Keys::TEAM_LEAGUE, absint( $league_id ) );
	}

	/**
	 * The league id a team belongs to.
	 *
	 * @param int $team_id Team id.
	 * @return int
	 */
	public function league_of( $team_id ) {
		return $this->get_meta_id( $team_id, Keys::TEAM_LEAGUE );
	}
}
