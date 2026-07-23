<?php
/**
 * Seeds the default Customize variables.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Plants editable copies of the soccer Defaults (standings columns + outcomes)
 * on activation, but only the first time — so admins get a working, editable
 * starting point without clobbering anything they later customize.
 */
class Seeder {

	/**
	 * Seed the defaults if the site has none yet.
	 *
	 * @return void
	 */
	public function seed() {
		$this->seed_type( Keys::STANDING, Defaults::columns() );
		$this->seed_type( Keys::OUTCOME, Defaults::outcomes() );
	}

	/**
	 * Seed one variable type if no posts of it exist.
	 *
	 * @param string  $post_type Variable post type.
	 * @param array[] $defaults  Default definitions.
	 * @return void
	 */
	private function seed_type( $post_type, array $defaults ) {
		$existing = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( $existing ) {
			return;
		}

		$order = 0;
		foreach ( $defaults as $definition ) {
			$this->create( $post_type, $definition, $order );
			++$order;
		}
	}

	/**
	 * Create one variable post from a definition.
	 *
	 * @param string $post_type  Variable post type.
	 * @param array  $definition Definition (label, key, equation, …).
	 * @param int    $order      menu_order.
	 * @return void
	 */
	private function create( $post_type, array $definition, $order ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => (string) ( $definition['label'] ?? '' ),
				'menu_order'  => $order,
			)
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, Keys::VAR_KEY, (string) ( $definition['key'] ?? '' ) );
		update_post_meta( $post_id, Keys::VAR_EQUATION, (string) ( $definition['equation'] ?? '' ) );
		update_post_meta( $post_id, Keys::VAR_SPORT, 0 );

		if ( Keys::STANDING === $post_type ) {
			update_post_meta( $post_id, Keys::VAR_PRECISION, (int) ( $definition['precision'] ?? 0 ) );
			update_post_meta( $post_id, Keys::VAR_SORT, (int) ( $definition['sort'] ?? 0 ) );
			$order = isset( $definition['order'] ) && 'asc' === $definition['order'] ? 'asc' : 'desc';
			update_post_meta( $post_id, Keys::VAR_ORDER, $order );
		}
	}
}
