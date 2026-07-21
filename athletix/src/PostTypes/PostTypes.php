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
		// Every post type lives under the single top-level Athletix menu.
		$parent = Keys::MENU;

		$this->register_type(
			Keys::TEAM,
			__( 'Teams', 'athletix' ),
			__( 'Team', 'athletix' ),
			array(
				'show_in_menu' => $parent,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'teams' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			)
		);

		$this->register_type(
			Keys::PLAYER,
			__( 'Players', 'athletix' ),
			__( 'Player', 'athletix' ),
			array(
				'show_in_menu' => $parent,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'players' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			)
		);

		$this->register_type(
			Keys::MATCH,
			__( 'Matches', 'athletix' ),
			__( 'Match', 'athletix' ),
			array(
				'show_in_menu' => $parent,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'matches' ),
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
			)
		);

		$this->register_type(
			Keys::LEAGUE,
			__( 'Leagues', 'athletix' ),
			__( 'League', 'athletix' ),
			array(
				'show_in_menu' => $parent,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'leagues' ),
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
			)
		);

		$this->register_type(
			Keys::SEASON,
			__( 'Seasons', 'athletix' ),
			__( 'Season', 'athletix' ),
			array(
				'show_in_menu' => $parent,
				'has_archive'  => false,
				'rewrite'      => array( 'slug' => 'seasons' ),
				'supports'     => array( 'title' ),
			)
		);

		$this->register_type(
			Keys::DIVISION,
			__( 'Divisions', 'athletix' ),
			__( 'Division', 'athletix' ),
			array(
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => $parent,
				'supports'     => array( 'title' ),
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
			/* translators: %s: plural post type name. */
			'all_items'     => sprintf( __( 'All %s', 'athletix' ), $plural ),
			/* translators: %s: plural post type name. */
			'not_found'     => sprintf( __( 'No %s found', 'athletix' ), strtolower( $plural ) ),
		);

		$defaults = array(
			'labels'       => $labels,
			'public'       => true,
			'show_in_rest' => true,
			'menu_icon'    => null,
		);

		register_post_type( $slug, array_merge( $defaults, $args ) );
	}
}
