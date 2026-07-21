<?php
/**
 * Single team template (plugin fallback).
 *
 * @package Athletix
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
	<main class="athletix-single athletix-single--team">
		<article class="athletix-single__inner">
			<header class="athletix-single__header">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="athletix-single__logo"><?php the_post_thumbnail( 'medium' ); ?></div>
				<?php endif; ?>
				<h1 class="athletix-single__title"><?php the_title(); ?></h1>
				<?php if ( $athletix_league ) : ?>
					<p class="athletix-single__meta">
						<a href="<?php echo esc_url( get_permalink( $athletix_league ) ); ?>"><?php echo esc_html( get_the_title( $athletix_league ) ); ?></a>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( get_the_content() ) : ?>
				<div class="athletix-single__content"><?php the_content(); ?></div>
			<?php endif; ?>

			<section class="athletix-single__section">
				<h2><?php esc_html_e( 'Recent Form', 'athletix' ); ?></h2>
				<?php echo do_shortcode( sprintf( '[athletix_team_form id="%d"]', $athletix_team_id ) ); ?>
			</section>

			<section class="athletix-single__section">
				<h2><?php esc_html_e( 'Squad', 'athletix' ); ?></h2>
				<?php echo do_shortcode( sprintf( '[athletix_roster team="%d"]', $athletix_team_id ) ); ?>
			</section>

			<?php if ( $athletix_league ) : ?>
				<section class="athletix-single__section">
					<h2><?php esc_html_e( 'Fixtures & Results', 'athletix' ); ?></h2>
					<?php echo do_shortcode( sprintf( '[athletix_schedule league="%d"]', $athletix_league ) ); ?>
				</section>
			<?php endif; ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
