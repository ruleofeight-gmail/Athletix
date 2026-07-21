<?php
/**
 * Archive template.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<header class="ax-card">
	<h1 class="ax-entry-title"><?php the_archive_title(); ?></h1>
	<?php the_archive_description(); ?>
</header>
<?php
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
