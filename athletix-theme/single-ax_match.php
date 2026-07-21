<?php
/**
 * Single match (companion theme).
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_match_id = get_the_ID();
	?>
	<article <?php post_class( 'ax-card' ); ?>>
		<h1 class="ax-entry-title"><?php the_title(); ?></h1>
		<?php athletix_theme_shortcode( 'athletix_match', sprintf( '[athletix_match id="%d"]', $athletix_match_id ) ); ?>

		<?php if ( get_the_content() ) : ?>
			<div class="ax-entry-content"><?php the_content(); ?></div>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
