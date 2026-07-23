<?php
/**
 * Staff repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for staff (coaches, managers and other team personnel).
 */
class StaffRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::STAFF;

	/**
	 * Staff attached to a team.
	 *
	 * @param int $team_id Team id.
	 * @return \WP_Post[]
	 */
	public function for_team( $team_id ) {
		return $this->find_by_meta( Keys::STAFF_TEAM, absint( $team_id ) );
	}
}
