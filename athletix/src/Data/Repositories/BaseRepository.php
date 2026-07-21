<?php
/**
 * Base post-backed repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared CRUD for entities stored as custom posts. Forces the post type on
 * writes, bounds reads, and returns WP_Error on failure instead of silently
 * inserting into the wrong type.
 */
abstract class BaseRepository {

	/**
	 * Post type handled by the repository.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Find one entity by id (must match this repository's post type).
	 *
	 * @param int $id Post id.
	 * @return \WP_Post|null
	 */
	public function find( $id ) {
		$post = get_post( absint( $id ) );

		if ( ! $post || $post->post_type !== $this->post_type ) {
			return null;
		}

		return $post;
	}

	/**
	 * Query entities with sane, bounded defaults.
	 *
	 * @param array $args WP_Query args (merged over defaults).
	 * @return \WP_Post[]
	 */
	public function all( array $args = array() ) {
		$defaults = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		$args              = array_merge( $defaults, $args );
		$args['post_type'] = $this->post_type;
		// Never overridable.

		return get_posts( $args );
	}

	/**
	 * Find entities matching a single meta key/value.
	 *
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @param array  $args       Extra query args.
	 * @return \WP_Post[]
	 */
	public function find_by_meta( $meta_key, $meta_value, array $args = array() ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => $meta_key,
				'value' => $meta_value,
			),
		);

		return $this->all( $args );
	}

	/**
	 * Create an entity of this post type.
	 *
	 * @param array $data Post array (post_type is forced).
	 * @return int|\WP_Error New post id or error.
	 */
	public function create( array $data ) {
		$data['post_type']   = $this->post_type;
		$data['post_status'] = isset( $data['post_status'] ) ? $data['post_status'] : 'publish';

		if ( isset( $data['post_title'] ) ) {
			$data['post_title'] = sanitize_text_field( $data['post_title'] );
		}

		return wp_insert_post( $data, true );
	}

	/**
	 * Update a meta value.
	 *
	 * @param int    $id    Post id.
	 * @param string $key   Meta key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function set_meta( $id, $key, $value ) {
		update_post_meta( absint( $id ), $key, $value );
	}

	/**
	 * Read a meta value with a default.
	 *
	 * @param int    $id      Post id.
	 * @param string $key     Meta key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get_meta( $id, $key, $default = '' ) {
		$value = get_post_meta( absint( $id ), $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * Read a meta value as an integer id.
	 *
	 * @param int    $id  Post id.
	 * @param string $key Meta key.
	 * @return int
	 */
	public function get_meta_id( $id, $key ) {
		return (int) get_post_meta( absint( $id ), $key, true );
	}
}
