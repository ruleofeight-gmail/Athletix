<?php
/**
 * Match card template.
 *
 * @package Athletix
 * @var \WP_Post $match   Match post.
 * @var array    $details Match details (home, away, scores, status, date).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_done      = ( 'completed' === $details['status'] );
$display_date = $details['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $details['date'] ) ) : '';
?>
<div class="athletix-match-card athletix-match-card--<?php echo esc_attr( $details['status'] ); ?>">
	<?php if ( $display_date ) : ?>
		<div class="athletix-match-card__date"><?php echo esc_html( $display_date ); ?></div>
	<?php endif; ?>

	<div class="athletix-match-card__teams">
		<div class="athletix-match-card__team athletix-match-card__team--home">
			<a href="<?php echo esc_url( get_permalink( $details['home'] ) ); ?>"><?php echo esc_html( get_the_title( $details['home'] ) ); ?></a>
		</div>

		<div class="athletix-match-card__score">
			<?php if ( $is_done ) : ?>
				<span class="athletix-match-card__num"><?php echo esc_html( (int) $details['home_score'] ); ?></span>
				<span class="athletix-match-card__sep">–</span>
				<span class="athletix-match-card__num"><?php echo esc_html( (int) $details['away_score'] ); ?></span>
			<?php else : ?>
				<span class="athletix-match-card__vs"><?php esc_html_e( 'vs', 'athletix' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="athletix-match-card__team athletix-match-card__team--away">
			<a href="<?php echo esc_url( get_permalink( $details['away'] ) ); ?>"><?php echo esc_html( get_the_title( $details['away'] ) ); ?></a>
		</div>
	</div>

	<div class="athletix-match-card__status">
		<?php echo esc_html( $is_done ? __( 'Full time', 'athletix' ) : __( 'Scheduled', 'athletix' ) ); ?>
	</div>
</div>
