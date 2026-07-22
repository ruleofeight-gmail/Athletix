<?php
/**
 * Custom post type registration.
 *
 * @package Athletix
 */

namespace Athletix\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Registers the League, Season, Team, Player, Match and Division post types
 * from a single definition table.
 */
class PostTypes {

	/**
	 * Register all post types.
	 *
	 * @return void
	 */
	public function register() {
		// Team, Player and Match are content (post types); each is its own
		// top-level "Athletix - X" menu. League, Season and Division are
		// taxonomies (see Taxonomies), managed under the Athletix hub.
		$this->register_type(
			Keys::TEAM,
			__( 'Teams', 'athletix' ),
			__( 'Team', 'athletix' ),
			array(
				'menu_position' => 34,
				'menu_icon'     => 'dashicons-groups',
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'teams' ),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			)
		);

		$this->register_type(
			Keys::PLAYER,
			__( 'Players', 'athletix' ),
			__( 'Player', 'athletix' ),
			array(
				'menu_position' => 35,
				'menu_icon'     => 'dashicons-admin-users',
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'players' ),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			)
		);

		$this->register_type(
			Keys::MATCH,
			__( 'Matches', 'athletix' ),
			__( 'Match', 'athletix' ),
			array(
				'menu_position' => 36,
				'menu_icon'     => 'dashicons-clipboard',
				'has_archive'   => true,
				'rewrite'       => array( 'slug' => 'matches' ),
				'supports'      => array( 'title', 'editor', 'thumbnail' ),
			)
		);
	}

	/**
	 * Register a single post type from shared defaults + overrides.
	 *
	 * @param string $slug     Post type slug.
	 * @param string $plural   Plural label.
	 * @param string $singular Singular label.
	 * @param array  $args     Argument overrides.
	 * @return void
	 */
	private function register_type( $slug, $plural, $singular, array $args ) {
		$labels = array(
			'name'          => $plural,
			'singular_name' => $singular,
			/* translators: %s: plural post type name. */
			'menu_name'     => sprintf( __( 'Athletix - %s', 'athletix' ), $plural ),
			/* translators: %s: singular post type name. */
			'add_new'       => sprintf( __( 'Add New %s', 'athletix' ), $singular ),
			/* translators: %s: singular post type name. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'athletix' ), $singular ),
			/* translators: %s: singular post type name. */
			'edit_item'     => sprintf( __( 'Edit %s', 'athletix' ), $singular ),
			/* translators: %s: singular post type name. */
			'new_item'      => sprintf( __( 'New %s', 'athletix' ), $singular ),
			/* translators: %s: singular post type name. */
			'view_item'     => sprintf( __( 'View %s', 'athletix' ), $singular ),
			/* translators: %s: plural post type name. */
			'search_items'  => sprintf( __( 'Search %s', 'athletix' ), $plural ),
			// The top-level menu carries the "Athletix - " prefix, so the list
			// submenu drops "All" and just reads the plural name.
			'all_items'     => $plural,
			/* translators: %s: plural post type name. */
			'not_found'     => sprintf( __( 'No %s found', 'athletix' ), strtolower( $plural ) ),
		);

		$defaults = array(
			'labels'       => $labels,
			'public'       => true,
			'show_in_rest' => true,
		);

		register_post_type( $slug, array_merge( $defaults, $args ) );
	}
}
