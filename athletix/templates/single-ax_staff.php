<?php
/**
 * Single staff template (plugin fallback).
 *
 * @package Athletix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$athletix_staff_id = get_the_ID();
	$athletix_role     = (string) get_post_meta( $athletix_staff_id, '_ax_staff_role', true );
	$athletix_country  = (string) get_post_meta( $athletix_staff_id, '_ax_staff_country', true );
	$athletix_team     = (int) get_post_meta( $athletix_staff_id, '_ax_staff_team', true );
	?>
	<main class="athletix-single athletix-single--staff">
		<article class="athletix-single__inner">
			<header class="athletix-single__header">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="athletix-single__logo"><?php the_post_thumbnail( 'medium' ); ?></div>
				<?php endif; ?>
				<h1 class="athletix-single__title"><?php the_title(); ?></h1>
				<?php if ( '' !== $athletix_role ) : ?>
					<p class="athletix-single__meta athletix-single__meta--role"><?php echo esc_html( $athletix_role ); ?></p>
				<?php endif; ?>
			</header>

			<table class="athletix-table athletix-team__detail-table">
				<tbody>
					<?php if ( $athletix_team && 'ax_team' === get_post_type( $athletix_team ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Team', 'athletix' ); ?></th>
							<td><a href="<?php echo esc_url( get_permalink( $athletix_team ) ); ?>"><?php echo esc_html( get_the_title( $athletix_team ) ); ?></a></td>
						</tr>
					<?php endif; ?>
					<?php if ( '' !== $athletix_country ) : ?>
						<tr><th><?php esc_html_e( 'Nationality', 'athletix' ); ?></th><td><?php echo esc_html( $athletix_country ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( get_the_content() ) : ?>
				<div class="athletix-single__content"><?php the_content(); ?></div>
			<?php endif; ?>
		</article>
	</main>
	<?php
endwhile;

get_footer();
