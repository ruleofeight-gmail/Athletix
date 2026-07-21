<?php
/**
 * Statistics (leaderboard) Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Displays a player statistics leaderboard for a metric. Wraps
 * [athletix_leaderboard].
 */
class StatisticsWidget extends BaseWidget {

	/**
	 * Slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_statistics';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Statistics Leaderboard', 'athletix' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-number-field';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'statistics', 'stats', 'leaderboard', 'top', 'scorers', 'athletix' );
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
			'metric',
			array(
				'label'       => __( 'Metric', 'athletix' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'goals',
				'description' => __( 'The stat slug to rank by, e.g. goals or assists.', 'athletix' ),
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'   => __( 'Heading', 'athletix' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Top Scorers', 'athletix' ),
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

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'How many', 'athletix' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'List', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'value_color',
			array(
				'label'     => __( 'Value Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-leaderboard__value' => 'color: {{VALUE}};',
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
		$metric   = isset( $settings['metric'] ) ? sanitize_key( $settings['metric'] ) : 'goals';
		$heading  = isset( $settings['heading'] ) ? $settings['heading'] : '';
		$season   = isset( $settings['season'] ) ? absint( $settings['season'] ) : 0;
		$limit    = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 10;

		echo do_shortcode(
			sprintf(
				'[athletix_leaderboard metric="%s" season="%d" limit="%d" title="%s"]',
				$metric,
				$season,
				$limit,
				esc_attr( $heading )
			)
		);
	}
}
