<?php
/**
 * Venue taxonomy for matches.
 *
 * @package Athletix
 */

namespace Athletix\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Registers a Venue taxonomy on matches, giving each fixture a location that
 * conflict detection and the calendar can use.
 */
class Venues {

	const TAXONOMY = 'ax_venue';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'athletix/activate', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Keys::MATCH ),
			array(
				'labels'            => array(
					'name'          => _x( 'Venues', 'taxonomy general name', 'athletix' ),
					'singular_name' => _x( 'Venue', 'taxonomy singular name', 'athletix' ),
					'add_new_item'  => __( 'Add New Venue', 'athletix' ),
					'edit_item'     => __( 'Edit Venue', 'athletix' ),
					'search_items'  => __( 'Search Venues', 'athletix' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'venue' ),
			)
		);
	}
}
