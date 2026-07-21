<?php
/**
 * Team Roster Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Renders a roster grid for a team or league. Wraps [athletix_roster].
 */
class TeamRosterWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_team_roster';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Team Roster', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'roster', 'team', 'players', 'squad', 'athletix' );
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
			'team',
			array(
				'label'       => __( 'Team', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->team_options(),
				'label_block' => true,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'athletix' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Grid', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'athletix' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'max' => 80 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .athletix-roster' => 'gap: {{SIZE}}{{UNIT}};',
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
		$team     = isset( $settings['team'] ) ? absint( $settings['team'] ) : 0;
		$columns  = isset( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;

		if ( ! $team ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a team to display its roster.', 'athletix' ) . '</p>';
			}
			return;
		}

		echo do_shortcode( sprintf( '[athletix_roster team="%d" columns="%d"]', $team, $columns ) );
	}
}
