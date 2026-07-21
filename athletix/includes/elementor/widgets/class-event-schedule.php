<?php
/**
 * Event Schedule Elementor widget.
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
 * Displays upcoming or past events as a schedule / results table.
 */
class Event_Schedule extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'athletix_event_schedule';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Event Schedule', 'athletix' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-table-of-contents';
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
		return array( 'event', 'schedule', 'fixture', 'results', 'calendar', 'athletix' );
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
			'scope',
			array(
				'label'   => __( 'Show', 'athletix' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'upcoming',
				'options' => array(
					'upcoming' => __( 'Upcoming events', 'athletix' ),
					'past'     => __( 'Past results', 'athletix' ),
					'all'      => __( 'All events', 'athletix' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of Events', 'athletix' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'show_location',
			array(
				'label'        => __( 'Show Location', 'athletix' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
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

		$this->add_control(
			'row_border',
			array(
				'label'     => __( 'Row Border Color', 'athletix' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .athletix-schedule tbody tr' => 'border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the schedule table.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$limit    = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 10;
		$scope    = isset( $settings['scope'] ) ? $settings['scope'] : 'upcoming';
		$today    = gmdate( 'Y-m-d' );

		$args = array(
			'post_type'      => 'athletix_event',
			'posts_per_page' => $limit,
			'meta_key'       => '_athletix_event_date',
			'orderby'        => 'meta_value',
			'meta_type'      => 'DATE',
		);

		if ( 'upcoming' === $scope ) {
			$args['order']      = 'ASC';
			$args['meta_query'] = array(
				array(
					'key'     => '_athletix_event_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
		} elseif ( 'past' === $scope ) {
			$args['order']      = 'DESC';
			$args['meta_query'] = array(
				array(
					'key'     => '_athletix_event_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			);
		} else {
			$args['order'] = 'ASC';
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'No events found.', 'athletix' ) . '</p>';
			}
			return;
		}

		$show_location = 'yes' === $settings['show_location'];
		?>
		<table class="athletix-schedule">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Event', 'athletix' ); ?></th>
					<?php if ( $show_location ) : ?>
						<th><?php esc_html_e( 'Location', 'athletix' ); ?></th>
					<?php endif; ?>
					<th><?php esc_html_e( 'Result', 'athletix' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					$date     = get_post_meta( get_the_ID(), '_athletix_event_date', true );
					$location = get_post_meta( get_the_ID(), '_athletix_event_location', true );
					$score    = get_post_meta( get_the_ID(), '_athletix_event_score', true );

					$display_date = $date
						? date_i18n( get_option( 'date_format' ), strtotime( $date ) )
						: '';
					?>
					<tr>
						<td class="athletix-schedule__date"><?php echo esc_html( $display_date ); ?></td>
						<td class="athletix-schedule__event">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</td>
						<?php if ( $show_location ) : ?>
							<td class="athletix-schedule__location"><?php echo esc_html( $location ); ?></td>
						<?php endif; ?>
						<td class="athletix-schedule__result"><?php echo esc_html( $score ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
		wp_reset_postdata();
	}
}
