<?php
/**
 * SportsPress-style single team page composite.
 *
 * @package Athletix
 * @var \WP_Post $team   Team post.
 * @var int      $league Team's league term id (0 if none).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$athletix_team_id = (int) $team->ID;
$athletix_venue   = (string) get_post_meta( $athletix_team_id, '_ax_team_venue', true );
$athletix_founded = (int) get_post_meta( $athletix_team_id, '_ax_team_founded', true );
$athletix_color   = (string) get_post_meta( $athletix_team_id, '_ax_team_color', true );

$athletix_sports    = get_the_terms( $athletix_team_id, 'ax_sport' );
$athletix_divisions = get_the_terms( $athletix_team_id, 'ax_division' );
$athletix_sport     = ( is_array( $athletix_sports ) && $athletix_sports ) ? $athletix_sports[0] : null;
$athletix_division  = ( is_array( $athletix_divisions ) && $athletix_divisions ) ? $athletix_divisions[0] : null;

$athletix_style = '' !== $athletix_color ? ' style="--athletix-team-color:' . esc_attr( $athletix_color ) . '"' : '';
?>
<div class="athletix-team"<?php echo $athletix_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above. ?>>
	<header class="athletix-team__header">
		<?php if ( has_post_thumbnail( $team ) ) : ?>
			<div class="athletix-team__badge"><?php echo get_the_post_thumbnail( $team, 'medium' ); ?></div>
		<?php endif; ?>
		<div class="athletix-team__heading">
			<h2 class="athletix-team__name"><?php echo esc_html( get_the_title( $team ) ); ?></h2>
			<?php if ( $league ) : ?>
				<p class="athletix-team__league">
					<a href="<?php echo esc_url( get_term_link( $league, 'ax_league' ) ); ?>">
						<?php echo esc_html( get_term_field( 'name', $league, 'ax_league' ) ); ?>
					</a>
				</p>
			<?php endif; ?>
			<div class="athletix-team__form">
				<?php echo do_shortcode( sprintf( '[athletix_team_form team="%d"]', $athletix_team_id ) ); ?>
			</div>
		</div>
	</header>

	<div class="athletix-team__details">
		<table class="athletix-table athletix-team__detail-table">
			<tbody>
				<?php if ( $athletix_sport ) : ?>
					<tr><th><?php esc_html_e( 'Sport', 'athletix' ); ?></th><td><?php echo esc_html( $athletix_sport->name ); ?></td></tr>
				<?php endif; ?>
				<?php if ( $athletix_division ) : ?>
					<tr><th><?php esc_html_e( 'Division', 'athletix' ); ?></th><td><?php echo esc_html( $athletix_division->name ); ?></td></tr>
				<?php endif; ?>
				<?php if ( '' !== $athletix_venue ) : ?>
					<tr><th><?php esc_html_e( 'Home Venue', 'athletix' ); ?></th><td><?php echo esc_html( $athletix_venue ); ?></td></tr>
				<?php endif; ?>
				<?php if ( $athletix_founded ) : ?>
					<tr><th><?php esc_html_e( 'Founded', 'athletix' ); ?></th><td><?php echo esc_html( $athletix_founded ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( get_the_content( null, false, $team ) ) : ?>
		<div class="athletix-team__bio"><?php echo wp_kses_post( apply_filters( 'the_content', $team->post_content ) ); ?></div>
	<?php endif; ?>

	<?php if ( $league ) : ?>
		<section class="athletix-team__section athletix-team__section--table">
			<h3 class="athletix-team__section-title"><?php esc_html_e( 'League Table', 'athletix' ); ?></h3>
			<?php echo do_shortcode( sprintf( '[athletix_standings league="%d"]', $league ) ); ?>
		</section>
	<?php endif; ?>

	<section class="athletix-team__section athletix-team__section--fixtures">
		<h3 class="athletix-team__section-title"><?php esc_html_e( 'Fixtures &amp; Results', 'athletix' ); ?></h3>
		<?php echo do_shortcode( sprintf( '[athletix_schedule team="%d"]', $athletix_team_id ) ); ?>
	</section>

	<section class="athletix-team__section athletix-team__section--squad">
		<h3 class="athletix-team__section-title"><?php esc_html_e( 'Squad', 'athletix' ); ?></h3>
		<?php echo do_shortcode( sprintf( '[athletix_roster team="%d" group="position"]', $athletix_team_id ) ); ?>
	</section>

	<section class="athletix-team__section athletix-team__section--staff">
		<h3 class="athletix-team__section-title"><?php esc_html_e( 'Staff', 'athletix' ); ?></h3>
		<?php echo do_shortcode( sprintf( '[athletix_staff team="%d"]', $athletix_team_id ) ); ?>
	</section>
</div>
