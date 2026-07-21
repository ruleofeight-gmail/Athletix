<?php
/**
 * Taxonomy registration.
 *
 * @package Athletix
 */

namespace Athletix\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Registers the Sport taxonomy shared across teams, players, matches and leagues.
 */
class Taxonomies {

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public function register() {
		register_taxonomy(
			Keys::TAX_SPORT,
			array( Keys::TEAM, Keys::PLAYER, Keys::MATCH, Keys::LEAGUE ),
			array(
				'labels'            => array(
					'name'          => _x( 'Sports', 'taxonomy general name', 'athletix' ),
					'singular_name' => _x( 'Sport', 'taxonomy singular name', 'athletix' ),
					'search_items'  => __( 'Search Sports', 'athletix' ),
					'all_items'     => __( 'All Sports', 'athletix' ),
					'edit_item'     => __( 'Edit Sport', 'athletix' ),
					'add_new_item'  => __( 'Add New Sport', 'athletix' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'sport' ),
			)
		);
	}
}
