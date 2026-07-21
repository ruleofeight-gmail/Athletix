<?php
/**
 * Player Field dynamic tag.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use Athletix\Support\Keys;

/**
 * Exposes player meta (position, number, height, weight, country, DOB) as a
 * dynamic tag bindable into any text control.
 */
class PlayerFieldTag extends Tag {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix-player-field';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Player Field', 'athletix' );
	}

	/**
	 * Group.
	 *
	 * @return string
	 */
	public function get_group() {
		return 'athletix';
	}

	/**
	 * Categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	/**
	 * Allowed fields map (control value => meta key).
	 *
	 * @return array<string,string>
	 */
	private function fields() {
		return array(
			'position' => Keys::PLAYER_POSITION,
			'number'   => Keys::PLAYER_NUMBER,
			'height'   => Keys::PLAYER_HEIGHT,
			'weight'   => Keys::PLAYER_WEIGHT,
			'country'  => Keys::PLAYER_COUNTRY,
			'dob'      => Keys::PLAYER_DOB,
		);
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'player_id',
			array(
				'label'       => __( 'Player', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->player_options(),
				'description' => __( 'Leave empty to use the current player in the loop.', 'athletix' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'field',
			array(
				'label'   => __( 'Field', 'athletix' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'position',
				'options' => array(
					'position' => __( 'Position', 'athletix' ),
					'number'   => __( 'Jersey Number', 'athletix' ),
					'height'   => __( 'Height', 'athletix' ),
					'weight'   => __( 'Weight', 'athletix' ),
					'country'  => __( 'Country', 'athletix' ),
					'dob'      => __( 'Date of Birth', 'athletix' ),
				),
			)
		);
	}

	/**
	 * Output the value.
	 *
	 * @return void
	 */
	public function render() {
		$settings  = $this->get_settings();
		$player_id = ! empty( $settings['player_id'] ) ? absint( $settings['player_id'] ) : get_the_ID();
		$field     = isset( $settings['field'] ) ? $settings['field'] : '';
		$fields    = $this->fields();

		if ( ! $player_id || ! isset( $fields[ $field ] ) ) {
			return;
		}

		echo esc_html( (string) get_post_meta( $player_id, $fields[ $field ], true ) );
	}

	/**
	 * Player options.
	 *
	 * @return array<int,string>
	 */
	private function player_options() {
		$options = array();

		$players = get_posts(
			array(
				'post_type'      => Keys::PLAYER,
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $players as $player ) {
			$options[ $player->ID ] = $player->post_title;
		}

		return $options;
	}
}
