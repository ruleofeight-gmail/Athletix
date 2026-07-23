<?php
/**
 * Single team template (plugin fallback).
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main class="athletix-single athletix-single--team">
		<article class="athletix-single__inner">
			<?php echo do_shortcode( sprintf( '[athletix_team id="%d"]', (int) get_the_ID() ) ); ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
