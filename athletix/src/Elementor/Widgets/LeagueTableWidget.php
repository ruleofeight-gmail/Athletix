<?php
/**
 * League Table Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Renders the standings table for a league/season. Thin wrapper over the
 * [athletix_standings] shortcode so markup stays in one place.
 */
class LeagueTableWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_league_table';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'League Table', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-table-of-contents';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'standings', 'league', 'table', 'athletix' );
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
			'season',
			array(
				'label'   => __( 'Season ID (optional)', 'athletix' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
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
					'{{WRAPPER}} .athletix-standings thead th' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'points_color',
			array(
				'label'     => __( 'Points Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-standings__pts' => 'color: {{VALUE}};',
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
		$season   = isset( $settings['season'] ) ? absint( $settings['season'] ) : 0;

		if ( ! $league ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a league to display standings.', 'athletix' ) . '</p>';
			}
			return;
		}

		echo do_shortcode( sprintf( '[athletix_standings league="%d" season="%d"]', $league, $season ) );
	}
}
