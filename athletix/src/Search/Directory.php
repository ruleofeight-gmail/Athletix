<?php
/**
 * Faceted directory shortcode.
 *
 * @package Athletix
 */

namespace Athletix\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Renders a filterable directory of teams and players via
 * [athletix_directory]. Filters come from the query string (GET), so results
 * are shareable/bookmarkable; no state is changed, so no nonce is required.
 */
class Directory {

	/**
	 * Filter engine.
	 *
	 * @var FilterEngine
	 */
	private $filters;

	/**
	 * Constructor.
	 *
	 * @param FilterEngine $filters Filter engine.
	 */
	public function __construct( FilterEngine $filters ) {
		$this->filters = $filters;
	}

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_directory', array( $this, 'render' ) );
	}

	/**
	 * Read and sanitize the current filters from the query string.
	 *
	 * @return array
	 */
	private function current_filters() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET filters.
		return array(
			'q'        => isset( $_GET['ax_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ax_q'] ) ) : '',
			'type'     => isset( $_GET['ax_type'] ) ? sanitize_key( wp_unslash( $_GET['ax_type'] ) ) : '',
			'sport'    => isset( $_GET['ax_sport'] ) ? absint( $_GET['ax_sport'] ) : 0,
			'position' => isset( $_GET['ax_position'] ) ? sanitize_text_field( wp_unslash( $_GET['ax_position'] ) ) : '',
			'team'     => isset( $_GET['ax_team'] ) ? absint( $_GET['ax_team'] ) : 0,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render the directory.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		unset( $atts );
		wp_enqueue_style( 'athletix' );

		$filters = $this->current_filters();

		ob_start();
		$this->form( $filters );

		$has_filters = ( '' !== $filters['q'] || $filters['type'] || $filters['sport'] || '' !== $filters['position'] || $filters['team'] );
		if ( $has_filters ) {
			$this->results( $this->filters->query( $filters ) );
		}

		return (string) ob_get_clean();
	}

	/**
	 * Render the filter form.
	 *
	 * @param array $filters Current filters.
	 * @return void
	 */
	private function form( array $filters ) {
		$sports = get_terms(
			array(
				'taxonomy'   => Keys::TAX_SPORT,
				'hide_empty' => false,
			)
		);
		?>
		<form class="athletix-directory" method="get">
			<input type="search" name="ax_q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Keyword…', 'athletix' ); ?>" />
			<select name="ax_type">
				<option value=""><?php esc_html_e( 'All', 'athletix' ); ?></option>
				<option value="<?php echo esc_attr( Keys::TEAM ); ?>" <?php selected( $filters['type'], Keys::TEAM ); ?>><?php esc_html_e( 'Teams', 'athletix' ); ?></option>
				<option value="<?php echo esc_attr( Keys::PLAYER ); ?>" <?php selected( $filters['type'], Keys::PLAYER ); ?>><?php esc_html_e( 'Players', 'athletix' ); ?></option>
			</select>
			<?php if ( ! is_wp_error( $sports ) && $sports ) : ?>
				<select name="ax_sport">
					<option value="0"><?php esc_html_e( 'Any sport', 'athletix' ); ?></option>
					<?php foreach ( $sports as $sport ) : ?>
						<option value="<?php echo esc_attr( $sport->term_id ); ?>" <?php selected( $filters['sport'], $sport->term_id ); ?>><?php echo esc_html( $sport->name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<input type="text" name="ax_position" value="<?php echo esc_attr( $filters['position'] ); ?>" placeholder="<?php esc_attr_e( 'Position', 'athletix' ); ?>" />
			<button type="submit"><?php esc_html_e( 'Filter', 'athletix' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Render the results.
	 *
	 * @param \WP_Query $query Results query.
	 * @return void
	 */
	private function results( $query ) {
		if ( ! $query->have_posts() ) {
			echo '<p class="athletix-empty">' . esc_html__( 'No results found.', 'athletix' ) . '</p>';
			wp_reset_postdata();
			return;
		}

		echo '<ul class="athletix-search__results">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$type = ( get_post_type() === Keys::TEAM ) ? __( 'Team', 'athletix' ) : __( 'Player', 'athletix' );
			printf(
				'<li><a href="%s">%s</a> <span class="athletix-search__type">%s</span></li>',
				esc_url( get_permalink() ),
				esc_html( get_the_title() ),
				esc_html( $type )
			);
		}
		echo '</ul>';

		wp_reset_postdata();
	}
}
