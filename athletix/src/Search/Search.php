<?php
/**
 * Front-end search for teams and players.
 *
 * @package Athletix
 */

namespace Athletix\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Provides an [athletix_search] shortcode: a GET form plus results filtered
 * across teams and players by keyword.
 */
class Search {

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_search', array( $this, 'render' ) );
	}

	/**
	 * Render the form and (if queried) the results.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'placeholder' => __( 'Search teams &amp; players…', 'athletix' ) ), $atts, 'athletix_search' );

		// Read-only search term from the query string; no state change, nonce not required.
		$term = isset( $_GET['ax_q'] ) ? sanitize_text_field( wp_unslash( $_GET['ax_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_enqueue_style( 'athletix' );

		ob_start();
		$this->form( $term, $atts['placeholder'] );

		if ( '' !== $term ) {
			$this->results( $term );
		}

		return (string) ob_get_clean();
	}

	/**
	 * Search form.
	 *
	 * @param string $term        Current term.
	 * @param string $placeholder Placeholder text.
	 * @return void
	 */
	private function form( $term, $placeholder ) {
		?>
		<form class="athletix-search" method="get">
			<input type="search" name="ax_q" value="<?php echo esc_attr( $term ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
			<button type="submit"><?php esc_html_e( 'Search', 'athletix' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Render matching teams and players.
	 *
	 * @param string $term Search term.
	 * @return void
	 */
	private function results( $term ) {
		$query = new \WP_Query(
			array(
				'post_type'      => array( Keys::TEAM, Keys::PLAYER ),
				's'              => $term,
				'posts_per_page' => 20,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			)
		);

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
