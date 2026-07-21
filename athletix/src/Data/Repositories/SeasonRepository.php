<?php
/**
 * Season repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for seasons.
 */
class SeasonRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::SEASON;

	/**
	 * Seasons for a league, newest start first.
	 *
	 * @param int $league_id League id.
	 * @return \WP_Post[]
	 */
	public function for_league( $league_id ) {
		return $this->find_by_meta(
			Keys::SEASON_LEAGUE,
			absint( $league_id ),
			array(
				'orderby'  => 'meta_value',
				'meta_key' => Keys::SEASON_START, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'    => 'DESC',
			)
		);
	}
}
