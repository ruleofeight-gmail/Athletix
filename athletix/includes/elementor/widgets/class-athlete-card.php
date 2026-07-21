<?php
/**
 * Athlete Card Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a single athlete as a card with photo, name and selected stats.
 */
class Athlete_Card extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_athlete_card';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Athlete Card', 'athletix' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Panel categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'athletix' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'athlete', 'player', 'profile', 'card', 'athletix' );
	}

	/**
	 * Stylesheet dependencies.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'athletix' );
	}

	/**
	 * Register the widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'athletix' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'athlete_id',
			array(
				'label'       => __( 'Select Athlete', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_athlete_options(),
				'label_block' => true,
			)
		);

		$this->add_control(
			'show_photo',
			array(
				'label'        => __( 'Show Photo', 'athletix' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => __( 'Yes', 'athletix' ),
				'label_off'    => __( 'No', 'athletix' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_stats',
			array(
				'label'        => __( 'Show Stats', 'athletix' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		// Style tab.
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Card', 'athletix' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'athletix' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => __( 'Left', 'athletix' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'athletix' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'athletix' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .athletix-card' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'name_color',
			array(
				'label'     => __( 'Name Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-card__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'name_typography',
				'selector' => '{{WRAPPER}} .athletix-card__name',
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'     => __( 'Background', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build a list of athletes for the SELECT2 control.
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

	/**
	 * Render the widget output on the front end.
	 *
	 * @return void
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$athlete_id = isset( $settings['athlete_id'] ) ? absint( $settings['athlete_id'] ) : 0;

		if ( ! $athlete_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="athletix-card athletix-card--empty">' .
					esc_html__( 'Select an athlete to display.', 'athletix' ) . '</div>';
			}
			return;
		}

		$post = get_post( $athlete_id );
		if ( ! $post || 'athletix_athlete' !== $post->post_type ) {
			return;
		}

		$stats = array(
			__( 'Position', 'athletix' ) => get_post_meta( $athlete_id, '_athletix_position', true ),
			__( 'Number', 'athletix' )   => get_post_meta( $athlete_id, '_athletix_number', true ),
			__( 'Height', 'athletix' )   => get_post_meta( $athlete_id, '_athletix_height', true ),
			__( 'Weight', 'athletix' )   => get_post_meta( $athlete_id, '_athletix_weight', true ),
			__( 'Country', 'athletix' )  => get_post_meta( $athlete_id, '_athletix_country', true ),
		);
		?>
		<div class="athletix-card">
			<?php if ( 'yes' === $settings['show_photo'] && has_post_thumbnail( $athlete_id ) ) : ?>
				<div class="athletix-card__photo">
					<?php echo get_the_post_thumbnail( $athlete_id, 'medium' ); ?>
				</div>
			<?php endif; ?>

			<h3 class="athletix-card__name">
				<a href="<?php echo esc_url( get_permalink( $athlete_id ) ); ?>">
					<?php echo esc_html( get_the_title( $athlete_id ) ); ?>
				</a>
			</h3>

			<?php if ( 'yes' === $settings['show_stats'] ) : ?>
				<ul class="athletix-card__stats">
					<?php foreach ( $stats as $label => $value ) : ?>
						<?php if ( '' !== $value ) : ?>
							<li class="athletix-card__stat">
								<span class="athletix-card__stat-label"><?php echo esc_html( $label ); ?></span>
								<span class="athletix-card__stat-value"><?php echo esc_html( $value ); ?></span>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
