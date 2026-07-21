<?php
/**
 * Athlete Field dynamic tag.
 *
 * Exposes athlete meta (position, number, height, etc.) as an Elementor
 * dynamic tag so it can be bound into any text-capable control.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Athlete_Field_Tag
 */
class Athlete_Field_Tag extends Tag {

	/**
	 * Tag slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix-athlete-field';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Athlete Field', 'athletix' );
	}

	/**
	 * Tag group.
	 *
	 * @return string
	 */
	public function get_group() {
		return 'athletix';
	}

	/**
	 * Categories this tag can be used in (plain text).
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * Register the tag controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'athlete_id',
			array(
				'label'       => __( 'Athlete', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_athlete_options(),
				'description' => __( 'Leave empty to use the current athlete in the loop / single view.', 'athletix' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'athletix' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '_athletix_position',
				'options' => array(
					'_athletix_position' => __( 'Position', 'athletix' ),
					'_athletix_number'   => __( 'Jersey Number', 'athletix' ),
					'_athletix_height'   => __( 'Height', 'athletix' ),
					'_athletix_weight'   => __( 'Weight', 'athletix' ),
					'_athletix_country'  => __( 'Country', 'athletix' ),
					'_athletix_dob'      => __( 'Date of Birth', 'athletix' ),
				),
			)
		);
	}

	/**
	 * Output the resolved value.
	 *
	 * @return void
	 */
	public function render() {
		$settings   = $this->get_settings();
		$athlete_id = ! empty( $settings['athlete_id'] ) ? absint( $settings['athlete_id'] ) : get_the_ID();
		$field      = isset( $settings['field'] ) ? $settings['field'] : '';

		if ( ! $athlete_id || '' === $field ) {
			return;
		}

		$allowed = array(
			'_athletix_position',
			'_athletix_number',
			'_athletix_height',
			'_athletix_weight',
			'_athletix_country',
			'_athletix_dob',
		);

		if ( ! in_array( $field, $allowed, true ) ) {
			return;
		}

		$value = get_post_meta( $athlete_id, $field, true );

		echo wp_kses_post( $value );
	}

	/**
	 * Build a list of athletes for the control.
	 *
	 * @return array<int,string>
	 */
	private function get_athlete_options() {
		$options = array();
		$posts   = get_posts(
			array(
				'post_type'      => 'athletix_athlete',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title;
		}

		return $options;
	}
}
