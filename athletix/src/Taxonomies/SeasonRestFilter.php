<?php
/**
 * REST query filter: scope Seasons to a League.
 *
 * @package Athletix
 */

namespace Athletix\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Lets the Seasons taxonomy REST endpoint (`/wp/v2/ax_season`) be narrowed to a
 * single league via an `athletix_league` query argument, matched against each
 * season term's League term-meta (Keys::SEASON_LEAGUE). This is what makes the
 * editor's Season picker dependent on the chosen League.
 */
class SeasonRestFilter {

	const PARAM = 'athletix_league';

	/**
	 * Register the collection param and the query filter for the Season taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'rest_' . Keys::SEASON . '_collection_params', array( $this, 'collection_params' ) );
		add_filter( 'rest_' . Keys::SEASON . '_query', array( $this, 'filter_query' ), 10, 2 );
	}

	/**
	 * Advertise the `athletix_league` collection param so it is documented and
	 * validated as a non-negative integer.
	 *
	 * @param array $params Existing collection params.
	 * @return array
	 */
	public function collection_params( $params ) {
		$params[ self::PARAM ] = array(
			'description'       => __( 'Limit result set to seasons belonging to the given league term id.', 'athletix' ),
			'type'              => 'integer',
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Add a League meta filter to the term query when the param is present.
	 *
	 * @param array            $args    get_terms() args.
	 * @param \WP_REST_Request $request REST request.
	 * @return array
	 */
	public function filter_query( $args, $request ) {
		$league = absint( $request[ self::PARAM ] );
		if ( ! $league ) {
			return $args;
		}

		$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		$meta_query[] = array(
			'key'   => Keys::SEASON_LEAGUE,
			'value' => $league,
		);

		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded term meta lookup for the editor picker.

		return $args;
	}
}
