<?php
/**
 * Player archive template (plugin fallback).
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="athletix-archive athletix-archive--player">
	<header class="athletix-archive__header">
		<h1><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="athletix-roster athletix-roster--cols-4">
			<?php
			while ( have_posts() ) :
				the_post();
				$athletix_pos = (string) get_post_meta( get_the_ID(), '_ax_player_position', true );
				?>
				<div class="athletix-roster__item">
					<a class="athletix-roster__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="athletix-roster__photo"><?php the_post_thumbnail( 'medium' ); ?></div>
						<?php endif; ?>
						<span class="athletix-roster__name"><?php the_title(); ?></span>
						<?php if ( '' !== $athletix_pos ) : ?>
							<span class="athletix-roster__position"><?php echo esc_html( $athletix_pos ); ?></span>
						<?php endif; ?>
					</a>
				</div>
			<?php endwhile; ?>
		</div>

		<div class="athletix-archive__nav"><?php the_posts_pagination(); ?></div>
	<?php else : ?>
		<p class="athletix-empty"><?php esc_html_e( 'No players found.', 'athletix' ); ?></p>
	<?php endif; ?>
</main>
<?php
get_footer();
