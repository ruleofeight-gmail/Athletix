<?php
/**
 * Shared query base for Customize config repositories.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Common lookup for the admin-defined "config object" post types (standings
 * columns, outcomes, list columns). Each is a small, drag-ordered set of posts
 * scoped by one meta value; this base holds the single get_posts() shape they
 * all share so the concrete repositories only describe how to read and fall back.
 */
abstract class ConfigRepository {

	/**
	 * Fetch published config posts of a type filtered by one meta value, ordered
	 * by menu_order (then title).
	 *
	 * @param string     $post_type  Config post type.
	 * @param string     $meta_key   Scope meta key.
	 * @param string|int $meta_value Scope meta value.
	 * @return \WP_Post[]
	 */
	protected function fetch_by_meta( $post_type, $meta_key, $meta_value ) {
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
						'key'   => $meta_key,
						'value' => (string) $meta_value,
					),
				),
			)
		);
	}
}
