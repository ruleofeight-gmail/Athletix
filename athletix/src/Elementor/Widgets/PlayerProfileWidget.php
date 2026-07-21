<?php
/**
 * Player Profile Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Athletix\Support\Keys;

/**
 * Displays a single player's profile (photo, name, team, stats). Wraps
 * [athletix_player] and defaults to the current player on a single view.
 */
class PlayerProfileWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_player_profile';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Player Profile', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'player', 'profile', 'athlete', 'card', 'athletix' );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array( 'label' => __( 'Content', 'athletix' ) )
		);

		$this->add_control(
			'player',
			array(
				'label'       => __( 'Player', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->post_options( Keys::PLAYER ),
				'label_block' => true,
				'description' => __( 'Leave empty to use the current player on a single player page.', 'athletix' ),
			)
		);

		$this->add_control(
			'stats',
			array(
				'label'        => __( 'Show Stats', 'athletix' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Profile', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => __( 'Name Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-player__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .athletix-player__name',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$player   = isset( $settings['player'] ) ? absint( $settings['player'] ) : 0;
		$stats    = ( isset( $settings['stats'] ) && 'yes' === $settings['stats'] ) ? 'yes' : 'no';

		if ( ! $player && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<p>' . esc_html__( 'Select a player to display.', 'athletix' ) . '</p>';
			return;
		}

		echo do_shortcode( sprintf( '[athletix_player id="%d" stats="%s"]', $player, esc_attr( $stats ) ) );
	}
}
