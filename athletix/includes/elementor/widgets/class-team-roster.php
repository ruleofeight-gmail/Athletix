<?php
/**
 * Team Roster Elementor widget.
 *
 * @package Athletix
 */

namespace Athletix\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a grid of athletes filtered by sport taxonomy.
 */
class Team_Roster extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_team_roster';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Team Roster', 'athletix' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
		return array( 'team', 'roster', 'squad', 'players', 'grid', 'athletix' );
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
			'sport',
			array(
				'label'       => __( 'Filter by Sport', 'athletix' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_sport_options(),
				'label_block' => true,
				'description' => __( 'Leave empty to show all athletes.', 'athletix' ),
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

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of Athletes', 'athletix' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
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
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 20,
				),
				'selectors'  => array(
					'{{WRAPPER}} .athletix-roster' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build the sport taxonomy options.
	 *
	 * @return array<int,string>
	 */
	private function get_sport_options() {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'athletix_sport',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Render the roster.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$columns  = isset( $settings['columns'] ) ? absint( $settings['columns'] ) : 3;
		$limit    = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 12;

		$args = array(
			'post_type'      => 'athletix_athlete',
			'posts_per_page' => $limit,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $settings['sport'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'athletix_sport',
					'field'    => 'term_id',
					'terms'    => absint( $settings['sport'] ),
				),
			);
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No athletes found.', 'athletix' ) . '</p>';
			}
			return;
		}

		printf(
			'<div class="athletix-roster athletix-roster--cols-%d">',
			esc_attr( $columns )
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			$position = get_post_meta( get_the_ID(), '_athletix_position', true );
			$number   = get_post_meta( get_the_ID(), '_athletix_number', true );
			?>
			<div class="athletix-roster__item">
				<a class="athletix-roster__link" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="athletix-roster__photo"><?php the_post_thumbnail( 'medium' ); ?></div>
					<?php endif; ?>
					<span class="athletix-roster__name">
						<?php if ( '' !== $number ) : ?>
							<span class="athletix-roster__number">#<?php echo esc_html( $number ); ?></span>
						<?php endif; ?>
						<?php the_title(); ?>
					</span>
					<?php if ( '' !== $position ) : ?>
						<span class="athletix-roster__position"><?php echo esc_html( $position ); ?></span>
					<?php endif; ?>
				</a>
			</div>
			<?php
		}

		echo '</div>';

		wp_reset_postdata();
	}
}
