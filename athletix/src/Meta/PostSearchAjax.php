<?php
/**
 * Admin AJAX post search for relationship pickers.
 *
 * @package Athletix
 */

namespace Athletix\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Backs the searchable relationship picker: returns a small, filtered page of
 * matching posts as JSON so the editor never has to load hundreds of options
 * into a native <select>. Guarded by a nonce and the edit_posts capability.
 */
class PostSearchAjax {

	const ACTION = 'athletix_post_search';
	const NONCE  = 'athletix_post_search';
	const LIMIT  = 20;

	/**
	 * Register the AJAX handler.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Search posts of an Athletix type by title and return JSON results.
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( ! in_array( $post_type, Keys::post_types(), true ) ) {
			wp_send_json_error( array( 'message' => 'invalid_post_type' ), 400 );
		}

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page   = isset( $_GET['page'] ) ? max( 1, absint( wp_unslash( $_GET['page'] ) ) ) : 1;

		wp_send_json_success( $this->results( $post_type, $search, $page ) );
	}

	/**
	 * Search a post type by title and shape the paged result set.
	 *
	 * Kept separate from the AJAX plumbing so the query behaviour is unit
	 * testable without dispatching (and dying inside) a real AJAX request.
	 *
	 * @param string $post_type Post type slug (assumed already validated).
	 * @param string $search    Search term.
	 * @param int    $page      1-based page number.
	 * @return array{results:array<int,array{id:int,text:string}>,more:bool}
	 */
	public function results( $post_type, $search = '', $page = 1 ) {
		$page = max( 1, (int) $page );

		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => self::LIMIT,
				'paged'                  => $page,
				's'                      => $search,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'   => (int) $post->ID,
				'text' => html_entity_decode( get_the_title( $post ), ENT_QUOTES ),
			);
		}

		return array(
			'results' => $results,
			'more'    => $page < (int) $query->max_num_pages,
		);
	}
}
