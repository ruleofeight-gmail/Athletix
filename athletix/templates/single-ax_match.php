<?php
/**
 * Single match template (plugin fallback).
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_match_id = get_the_ID();
	?>
	<main class="athletix-single athletix-single--match">
		<article class="athletix-single__inner">
			<h1 class="athletix-single__title"><?php the_title(); ?></h1>

			<?php echo do_shortcode( sprintf( '[athletix_match id="%d"]', $athletix_match_id ) ); ?>

			<?php if ( get_the_content() ) : ?>
				<div class="athletix-single__content"><?php the_content(); ?></div>
			<?php endif; ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
