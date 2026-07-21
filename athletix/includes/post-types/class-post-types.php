<?php
/**
 * Custom post types and taxonomies.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Athlete, Team and Event post types and their taxonomies.
 */
final class Post_Types {

	/**
	 * Shared instance.
	 *
	 * @var Post_Types|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Post_Types
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register every post type and taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_athlete();
		$this->register_team();
		$this->register_event();
		$this->register_taxonomies();
	}

	/**
	 * Athlete post type.
	 *
	 * @return void
	 */
	private function register_athlete() {
		$labels = array(
			'name'               => _x( 'Athletes', 'post type general name', 'athletix' ),
			'singular_name'      => _x( 'Athlete', 'post type singular name', 'athletix' ),
			'menu_name'          => _x( 'Athletix', 'admin menu', 'athletix' ),
			'add_new'            => __( 'Add New', 'athletix' ),
			'add_new_item'       => __( 'Add New Athlete', 'athletix' ),
			'edit_item'          => __( 'Edit Athlete', 'athletix' ),
			'new_item'           => __( 'New Athlete', 'athletix' ),
			'view_item'          => __( 'View Athlete', 'athletix' ),
			'search_items'       => __( 'Search Athletes', 'athletix' ),
			'not_found'          => __( 'No athletes found', 'athletix' ),
			'not_found_in_trash' => __( 'No athletes found in Trash', 'athletix' ),
			'all_items'          => __( 'All Athletes', 'athletix' ),
		);

		register_post_type(
			'athletix_athlete',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-universal-access',
				'menu_position' => 26,
				'rewrite'      => array( 'slug' => 'athletes' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Team post type.
	 *
	 * @return void
	 */
	private function register_team() {
		$labels = array(
			'name'          => _x( 'Teams', 'post type general name', 'athletix' ),
			'singular_name' => _x( 'Team', 'post type singular name', 'athletix' ),
			'add_new_item'  => __( 'Add New Team', 'athletix' ),
			'edit_item'     => __( 'Edit Team', 'athletix' ),
			'all_items'     => __( 'All Teams', 'athletix' ),
			'search_items'  => __( 'Search Teams', 'athletix' ),
			'not_found'     => __( 'No teams found', 'athletix' ),
		);

		register_post_type(
			'athletix_team',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_menu' => 'edit.php?post_type=athletix_athlete',
				'rewrite'      => array( 'slug' => 'teams' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Event / fixture post type.
	 *
	 * @return void
	 */
	private function register_event() {
		$labels = array(
			'name'          => _x( 'Events', 'post type general name', 'athletix' ),
			'singular_name' => _x( 'Event', 'post type singular name', 'athletix' ),
			'add_new_item'  => __( 'Add New Event', 'athletix' ),
			'edit_item'     => __( 'Edit Event', 'athletix' ),
			'all_items'     => __( 'All Events', 'athletix' ),
			'search_items'  => __( 'Search Events', 'athletix' ),
			'not_found'     => __( 'No events found', 'athletix' ),
		);

		register_post_type(
			'athletix_event',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_menu' => 'edit.php?post_type=athletix_athlete',
				'rewrite'      => array( 'slug' => 'events' ),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Shared taxonomies (sport + season).
	 *
	 * @return void
	 */
	private function register_taxonomies() {
		register_taxonomy(
			'athletix_sport',
			array( 'athletix_athlete', 'athletix_team', 'athletix_event' ),
			array(
				'labels'            => array(
					'name'          => _x( 'Sports', 'taxonomy general name', 'athletix' ),
					'singular_name' => _x( 'Sport', 'taxonomy singular name', 'athletix' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'sport' ),
			)
		);

		register_taxonomy(
			'athletix_season',
			array( 'athletix_team', 'athletix_event' ),
			array(
				'labels'            => array(
					'name'          => _x( 'Seasons', 'taxonomy general name', 'athletix' ),
					'singular_name' => _x( 'Season', 'taxonomy singular name', 'athletix' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'season' ),
			)
		);
	}
}
