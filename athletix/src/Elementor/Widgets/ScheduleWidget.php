<?php
/**
 * Schedule Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Renders a fixtures/results table for a league. Wraps [athletix_schedule].
 */
class ScheduleWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_schedule';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Match Schedule', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'schedule', 'fixtures', 'results', 'matches', 'athletix' );
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
			'league',
			array(
				'label'       => __( 'League', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->league_options(),
				'label_block' => true,
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Max Matches', 'athletix' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 20,
				'min'     => 1,
				'max'     => 200,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Table', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'header_bg',
			array(
				'label'     => __( 'Header Background', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-schedule thead th' => 'background-color: {{VALUE}};',
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
		$league   = isset( $settings['league'] ) ? absint( $settings['league'] ) : 0;
		$limit    = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 20;

		if ( ! $league ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a league to display its schedule.', 'athletix' ) . '</p>';
			}
			return;
		}

		echo do_shortcode( sprintf( '[athletix_schedule league="%d" limit="%d"]', $league, $limit ) );
	}
}
