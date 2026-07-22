<?php
/**
 * Season repository (taxonomy term).
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for seasons, stored as terms of the ax_season taxonomy. A season
 * may reference the league it belongs to via the `_ax_season_league` term meta.
 */
class SeasonRepository extends TermRepository {

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = Keys::SEASON;

	/**
	 * Seasons belonging to a league (via term meta).
	 *
	 * @param int $league_id League term id.
	 * @return \WP_Term[]
	 */
	public function for_league( $league_id ) {
		return $this->all(
			array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::SEASON_LEAGUE,
						'value' => absint( $league_id ),
					),
				),
			)
		);
	}
}
