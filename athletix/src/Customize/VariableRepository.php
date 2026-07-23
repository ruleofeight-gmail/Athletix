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
class VariableRepository extends ConfigRepository {

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
		$posts = $this->fetch_by_meta( $post_type, Keys::VAR_SPORT, $sport_id );

		if ( ! $posts && $sport_id ) {
			$posts = $this->fetch_by_meta( $post_type, Keys::VAR_SPORT, 0 );
		}

		return $posts;
	}
}
