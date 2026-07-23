<?php
/**
 * Reads admin-defined Customize variables.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Loads the standings columns and outcomes an admin configured, resolving them
 * for a sport: sport-specific variables win, else the global (sport 0) set, else
 * the built-in soccer Defaults — so standings always have something to compute
 * and order by, even before anything is seeded.
 */
class VariableRepository {

	/**
	 * Ordered standings columns for a sport.
	 *
	 * @param int $sport_id Sport term id (0 = global).
	 * @return array[] Each: label, key, equation, precision, sort, order.
	 */
	public function columns( $sport_id = 0 ) {
		$posts = $this->fetch( Keys::STANDING, absint( $sport_id ) );

		if ( ! $posts ) {
			return Defaults::columns();
		}

		return array_map(
			function ( $post ) {
				return array(
					'label'     => get_the_title( $post ),
					'key'       => (string) get_post_meta( $post->ID, Keys::VAR_KEY, true ),
					'equation'  => (string) get_post_meta( $post->ID, Keys::VAR_EQUATION, true ),
					'precision' => (int) get_post_meta( $post->ID, Keys::VAR_PRECISION, true ),
					'sort'      => (int) get_post_meta( $post->ID, Keys::VAR_SORT, true ),
					'order'     => 'asc' === get_post_meta( $post->ID, Keys::VAR_ORDER, true ) ? 'asc' : 'desc',
				);
			},
			$posts
		);
	}

	/**
	 * Ordered outcomes for a sport.
	 *
	 * @param int $sport_id Sport term id (0 = global).
	 * @return array[] Each: label, key, equation.
	 */
	public function outcomes( $sport_id = 0 ) {
		$posts = $this->fetch( Keys::OUTCOME, absint( $sport_id ) );

		if ( ! $posts ) {
			return Defaults::outcomes();
		}

		return array_map(
			function ( $post ) {
				return array(
					'label'    => get_the_title( $post ),
					'key'      => (string) get_post_meta( $post->ID, Keys::VAR_KEY, true ),
					'equation' => (string) get_post_meta( $post->ID, Keys::VAR_EQUATION, true ),
				);
			},
			$posts
		);
	}

	/**
	 * Fetch the variable posts for a post type, scoped to a sport with a global
	 * fallback.
	 *
	 * @param string $post_type Variable post type.
	 * @param int    $sport_id  Sport term id.
	 * @return \WP_Post[]
	 */
	private function fetch( $post_type, $sport_id ) {
		$posts = $this->query( $post_type, $sport_id );

		if ( ! $posts && $sport_id ) {
			$posts = $this->query( $post_type, 0 );
		}

		return $posts;
	}

	/**
	 * Query variable posts scoped to an exact sport id.
	 *
	 * @param string $post_type Variable post type.
	 * @param int    $sport_id  Sport term id.
	 * @return \WP_Post[]
	 */
	private function query( $post_type, $sport_id ) {
		return get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small bounded config set.
					array(
						'key'   => Keys::VAR_SPORT,
						'value' => (string) $sport_id,
					),
				),
			)
		);
	}
}
