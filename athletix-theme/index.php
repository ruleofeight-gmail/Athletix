<?php
/**
 * Main template.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'ax-card' ); ?>>
			<h2 class="ax-entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<div class="ax-entry-excerpt"><?php the_excerpt(); ?></div>
		</article>
		<?php
	endwhile;

	the_posts_pagination();
else :
	?>
	<article class="ax-card"><p><?php esc_html_e( 'Nothing found.', 'athletix-theme' ); ?></p></article>
	<?php
endif;

get_footer();
