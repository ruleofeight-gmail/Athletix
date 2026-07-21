<?php
/**
 * Division management.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Repositories\RelationshipRepository;
use Athletix\Support\Keys;

/**
 * Manages divisions and their team membership through the relationships table
 * (rel_type "division_team") rather than ad-hoc post meta.
 */
class Divisions {

	const REL_DIVISION_TEAM = 'division_team';

	/**
	 * Relationship repository.
	 *
	 * @var RelationshipRepository
	 */
	private $relationships;

	/**
	 * Constructor.
	 *
	 * @param RelationshipRepository $relationships Relationship storage.
	 */
	public function __construct( RelationshipRepository $relationships ) {
		$this->relationships = $relationships;
	}

	/**
	 * Divisions in a league (by division meta league link).
	 *
	 * @param int $league_id League id.
	 * @return \WP_Post[]
	 */
	public function in_league( $league_id ) {
		return get_posts(
			array(
				'post_type'      => Keys::DIVISION,
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => Keys::DIVISION_LEAGUE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => absint( $league_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
	}

	/**
	 * Assign a team to a division.
	 *
	 * @param int $division_id Division id.
	 * @param int $team_id     Team id.
	 * @return void
	 */
	public function add_team( $division_id, $team_id ) {
		$this->relationships->add( $division_id, $team_id, self::REL_DIVISION_TEAM );
	}

	/**
	 * Remove a team from a division.
	 *
	 * @param int $division_id Division id.
	 * @param int $team_id     Team id.
	 * @return void
	 */
	public function remove_team( $division_id, $team_id ) {
		$this->relationships->remove( $division_id, $team_id, self::REL_DIVISION_TEAM );
	}

	/**
	 * Team ids in a division.
	 *
	 * @param int $division_id Division id.
	 * @return int[]
	 */
	public function team_ids( $division_id ) {
		return $this->relationships->targets( $division_id, self::REL_DIVISION_TEAM );
	}
}
