<?php
/**
 * Single post template.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'ax-card' ); ?>>
		<h1 class="ax-entry-title"><?php the_title(); ?></h1>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="ax-entry-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
		<div class="ax-entry-content"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
