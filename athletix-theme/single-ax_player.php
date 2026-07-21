<?php
/**
 * Single player (companion theme).
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_player_id = get_the_ID();
	?>
	<article <?php post_class( 'ax-card' ); ?>>
		<?php athletix_theme_shortcode( 'athletix_player', sprintf( '[athletix_player id="%d"]', $athletix_player_id ) ); ?>

		<?php if ( get_the_content() ) : ?>
			<div class="ax-entry-content"><?php the_content(); ?></div>
		<?php endif; ?>

		<section class="ax-section">
			<h2><?php esc_html_e( 'Statistics', 'athletix-theme' ); ?></h2>
			<?php athletix_theme_shortcode( 'athletix_player_report', sprintf( '[athletix_player_report id="%d"]', $athletix_player_id ) ); ?>
		</section>
	</article>
	<?php
endwhile;

get_footer();
