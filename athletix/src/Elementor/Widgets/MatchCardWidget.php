<?php
/**
 * Match Card Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Athletix\Support\Keys;

/**
 * Displays a single match as a scoreboard card. Wraps [athletix_match].
 */
class MatchCardWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_match_card';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Match Card', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-post-title';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'match', 'game', 'fixture', 'score', 'athletix' );
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
			'match',
			array(
				'label'       => __( 'Match', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->post_options( Keys::MATCH ),
				'label_block' => true,
				'description' => __( 'Leave empty to use the current match on a single match page.', 'athletix' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Card', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'score_color',
			array(
				'label'     => __( 'Score Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-match-card__num' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => __( 'Background', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-match-card' => 'background-color: {{VALUE}};',
				),
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
		$match    = isset( $settings['match'] ) ? absint( $settings['match'] ) : 0;

		if ( ! $match && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<p>' . esc_html__( 'Select a match to display.', 'athletix' ) . '</p>';
			return;
		}

		echo do_shortcode( sprintf( '[athletix_match id="%d"]', $match ) );
	}
}
