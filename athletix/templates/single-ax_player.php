<?php
/**
 * Single player template (plugin fallback).
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_player_id = get_the_ID();
	?>
	<main class="athletix-single athletix-single--player">
		<article class="athletix-single__inner">
			<?php echo do_shortcode( sprintf( '[athletix_player id="%d"]', $athletix_player_id ) ); ?>

			<?php if ( get_the_content() ) : ?>
				<div class="athletix-single__content"><?php the_content(); ?></div>
			<?php endif; ?>

			<section class="athletix-single__section">
				<h2><?php esc_html_e( 'Season Statistics', 'athletix' ); ?></h2>
				<?php echo do_shortcode( sprintf( '[athletix_player_report id="%d"]', $athletix_player_id ) ); ?>
			</section>
		</article>
	</main>
	<?php
endwhile;

get_footer();
