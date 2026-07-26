<?php
/**
 * Single team (companion theme).
 *
 * @package Athletix_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_team_id = get_the_ID();
	?>
	<article <?php post_class( 'ax-card' ); ?>>
		<?php if ( shortcode_exists( 'athletix_team' ) ) : ?>
			<div class="ax-plugin-embed">
				<?php echo do_shortcode( sprintf( '[athletix_team id="%d"]', $athletix_team_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output. ?>
			</div>
		<?php else : ?>
			<header class="ax-hero">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="ax-hero__logo"><?php the_post_thumbnail( 'medium' ); ?></div>
				<?php endif; ?>
				<h1 class="ax-entry-title"><?php the_title(); ?></h1>
			</header>
			<?php if ( get_the_content() ) : ?>
				<div class="ax-entry-content"><?php the_content(); ?></div>
			<?php endif; ?>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
