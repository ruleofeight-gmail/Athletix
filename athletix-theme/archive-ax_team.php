<?php
/**
 * Team archive (companion theme).
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<header class="ax-card">
	<h1 class="ax-entry-title"><?php post_type_archive_title(); ?></h1>
</header>

<?php if ( have_posts() ) : ?>
	<div class="ax-card">
		<div class="athletix-roster athletix-roster--cols-4">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<div class="athletix-roster__item">
					<a class="athletix-roster__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="athletix-roster__photo"><?php the_post_thumbnail( 'medium' ); ?></div>
						<?php endif; ?>
						<span class="athletix-roster__name"><?php the_title(); ?></span>
					</a>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
	<?php the_posts_pagination(); ?>
<?php else : ?>
	<article class="ax-card"><p><?php esc_html_e( 'No teams found.', 'athletix-theme' ); ?></p></article>
<?php endif; ?>

<?php
get_footer();
