<?php
/**
 * Taxonomy-term helpers.
 *
 * @package Athletix
 */

namespace Athletix\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small helpers for the League/Season/Division/Sport taxonomies — chiefly
 * resolving a term id to its display name (the term equivalent of
 * get_the_title() for the retired post types).
 */
final class Terms {

	/**
	 * Term name for an id, or '' when missing.
	 *
	 * @param int    $term_id  Term id.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public static function name( $term_id, $taxonomy ) {
		$term = get_term( absint( $term_id ), $taxonomy );

		return ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
	}
}
