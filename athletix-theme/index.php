<?php
/**
 * Main / blog template — card grid.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<header class="ax-page-header">
	<h1 class="ax-entry-title">
		<?php
		if ( is_home() && ! is_front_page() ) {
			single_post_title();
		} else {
			bloginfo( 'name' );
		}
		?>
	</h1>
</header>

<?php if ( have_posts() ) : ?>
	<div class="ax-grid ax-grid--3">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'ax-card ax-card--link' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="ax-card__media" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
				<?php endif; ?>
				<h2 class="ax-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<p class="ax-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
				<div class="ax-muted"><?php the_excerpt(); ?></div>
			</article>
			<?php
		endwhile;
		?>
	</div>
	<?php
	the_posts_pagination(
		array(
			'prev_text' => __( '‹ Previous', 'athletix-theme' ),
			'next_text' => __( 'Next ›', 'athletix-theme' ),
		)
	);
else :
	?>
	<div class="ax-card"><p><?php esc_html_e( 'Nothing found.', 'athletix-theme' ); ?></p></div>
	<?php
endif;

get_footer();
