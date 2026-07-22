<?php
/**
 * Archive template — card grid.
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<header class="ax-page-header">
	<p class="ax-eyebrow"><?php esc_html_e( 'Browse', 'athletix-theme' ); ?></p>
	<h1 class="ax-entry-title"><?php the_archive_title(); ?></h1>
	<div class="ax-archive-desc"><?php the_archive_description(); ?></div>
</header>

<?php if ( have_posts() ) : ?>
	<div class="ax-grid ax-grid--3">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<a class="ax-card ax-card--link" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<span class="ax-card__media"><?php the_post_thumbnail( 'medium_large' ); ?></span>
				<?php endif; ?>
				<h2 class="ax-card__title"><?php the_title(); ?></h2>
				<p class="ax-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
			</a>
			<?php
		endwhile;
		?>
	</div>
	<?php
	the_posts_pagination(
		array(
			'mid_size'  => 1,
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
