<?php
/**
 * Player repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for players.
 */
class PlayerRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::PLAYER;

	/**
	 * Players on a team.
	 *
	 * @param int $team_id Team id.
	 * @return \WP_Post[]
	 */
	public function for_team( $team_id ) {
		return $this->find_by_meta( Keys::PLAYER_TEAM, absint( $team_id ) );
	}
}
