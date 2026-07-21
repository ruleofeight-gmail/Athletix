<?php
/**
 * Base Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Athletix\Support\Keys;

/**
 * Shared widget behaviour: Athletix category, shared stylesheet dependency and
 * a helper to list posts of a type for SELECT2 controls.
 */
abstract class BaseWidget extends Widget_Base {

	/**
	 * Panel category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'athletix' );
	}

	/**
	 * Shared stylesheet.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'athletix' );
	}

	/**
	 * Options list of posts for a control.
	 *
	 * @param string $post_type Post type.
	 * @return array<int,string>
	 */
	protected function post_options( $post_type ) {
		$options = array( 0 => __( '— Select —', 'athletix' ) );

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title;
		}

		return $options;
	}

	/**
	 * League options.
	 *
	 * @return array<int,string>
	 */
	protected function league_options() {
		return $this->post_options( Keys::LEAGUE );
	}

	/**
	 * Team options.
	 *
	 * @return array<int,string>
	 */
	protected function team_options() {
		return $this->post_options( Keys::TEAM );
	}
}
