<?php
/**
 * Builds filtered queries for the directory.
 *
 * @package Athletix
 */

namespace Athletix\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Translates a set of user-supplied filters (keyword, type, sport, position,
 * team) into a bounded WP_Query.
 */
class FilterEngine {

	/**
	 * Run a filtered query.
	 *
	 * @param array $filters Filter values (already sanitized).
	 * @return \WP_Query
	 */
	public function query( array $filters ) {
		$type = isset( $filters['type'] ) && in_array( $filters['type'], array( Keys::TEAM, Keys::PLAYER ), true )
			? $filters['type']
			: array( Keys::TEAM, Keys::PLAYER );

		$args = array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $filters['q'] ) ) {
			$args['s'] = $filters['q'];
		}

		if ( ! empty( $filters['sport'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Keys::TAX_SPORT,
					'field'    => 'term_id',
					'terms'    => absint( $filters['sport'] ),
				),
			);
		}

		$meta = array();
		if ( ! empty( $filters['position'] ) ) {
			$meta[] = array(
				'key'     => Keys::PLAYER_POSITION,
				'value'   => $filters['position'],
				'compare' => 'LIKE',
			);
		}
		if ( ! empty( $filters['team'] ) ) {
			$meta[] = array(
				'key'   => Keys::PLAYER_TEAM,
				'value' => absint( $filters['team'] ),
			);
		}
		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return new \WP_Query( $args );
	}
}
