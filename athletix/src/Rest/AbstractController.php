<?php
/**
 * Base REST controller.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared behaviour for Athletix REST controllers: namespace, versioning and a
 * public read permission callback (overridable).
 */
abstract class AbstractController {

	/**
	 * REST namespace.
	 */
	const NS = 'athletix/v1';

	/**
	 * Register this controller's routes.
	 *
	 * @return void
	 */
	abstract public function register_routes();

	/**
	 * Public read permission. Filterable so a site can lock the API down.
	 *
	 * @return bool
	 */
	public function public_read() {
		/**
		 * Filter whether Athletix REST reads are public.
		 *
		 * @param bool $allowed Default true.
		 */
		return (bool) apply_filters( 'athletix/rest_public_read', true );
	}

	/**
	 * Shape a post as a compact REST array.
	 *
	 * @param \WP_Post $post Post.
	 * @return array
	 */
	protected function basic_post( $post ) {
		return array(
			'id'        => $post->ID,
			'title'     => get_the_title( $post ),
			'permalink' => get_permalink( $post ),
			'thumbnail' => get_the_post_thumbnail_url( $post, 'medium' ) ?: '', // phpcs:ignore WordPress.PHP.DisallowShortTernary.Found
		);
	}
}
