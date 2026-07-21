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
	$athletix_league  = (int) get_post_meta( $athletix_team_id, '_ax_team_league', true );
	?>
	<article <?php post_class( 'ax-card' ); ?>>
		<header class="ax-hero">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="ax-hero__logo"><?php the_post_thumbnail( 'medium' ); ?></div>
			<?php endif; ?>
			<h1 class="ax-entry-title"><?php the_title(); ?></h1>
			<?php if ( $athletix_league ) : ?>
				<p><a href="<?php echo esc_url( get_permalink( $athletix_league ) ); ?>"><?php echo esc_html( get_the_title( $athletix_league ) ); ?></a></p>
			<?php endif; ?>
		</header>

		<?php if ( get_the_content() ) : ?>
			<div class="ax-entry-content"><?php the_content(); ?></div>
		<?php endif; ?>

		<section class="ax-section">
			<h2><?php esc_html_e( 'Recent Form', 'athletix-theme' ); ?></h2>
			<?php athletix_theme_shortcode( 'athletix_team_form', sprintf( '[athletix_team_form id="%d"]', $athletix_team_id ) ); ?>
		</section>

		<section class="ax-section">
			<h2><?php esc_html_e( 'Squad', 'athletix-theme' ); ?></h2>
			<?php athletix_theme_shortcode( 'athletix_roster', sprintf( '[athletix_roster team="%d"]', $athletix_team_id ) ); ?>
		</section>

		<?php if ( $athletix_league ) : ?>
			<section class="ax-section">
				<h2><?php esc_html_e( 'Fixtures &amp; Results', 'athletix-theme' ); ?></h2>
				<?php athletix_theme_shortcode( 'athletix_schedule', sprintf( '[athletix_schedule league="%d"]', $athletix_league ) ); ?>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
