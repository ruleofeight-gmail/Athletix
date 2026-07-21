<?php
/**
 * Announcements.
 *
 * @package Athletix
 */

namespace Athletix\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A lightweight announcements post type with a shortcode to list the latest
 * entries, for club/league news.
 */
class Announcements {

	const POST_TYPE = 'ax_announcement';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_type' ) );
		add_action( 'athletix/activate', array( $this, 'register_type' ) );
		add_shortcode( 'athletix_announcements', array( $this, 'render' ) );
	}

	/**
	 * Register the announcement post type.
	 *
	 * @return void
	 */
	public function register_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => _x( 'Announcements', 'post type general name', 'athletix' ),
					'singular_name' => _x( 'Announcement', 'post type singular name', 'athletix' ),
					'add_new_item'  => __( 'Add New Announcement', 'athletix' ),
					'edit_item'     => __( 'Edit Announcement', 'athletix' ),
					'all_items'     => __( 'Announcements', 'athletix' ),
				),
				'public'       => true,
				'has_archive'  => true,
				'show_in_menu' => 'edit.php?post_type=ax_team',
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-megaphone',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
				'rewrite'      => array( 'slug' => 'announcements' ),
			)
		);
	}

	/**
	 * [athletix_announcements limit="5"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 5 ), $atts, 'athletix_announcements' );

		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => absint( $atts['limit'] ),
				'post_status'    => 'publish',
			)
		);

		if ( empty( $posts ) ) {
			return '';
		}

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-announcements">';
		foreach ( $posts as $post ) {
			echo '<article class="athletix-announcement">';
			echo '<h3><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3>';
			echo '<div class="athletix-announcement__date">' . esc_html( get_the_date( '', $post ) ) . '</div>';
			echo '<div class="athletix-announcement__excerpt">' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) ) . '</div>';
			echo '</article>';
		}
		echo '</div>';

		return (string) ob_get_clean();
	}
}
