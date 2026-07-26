<?php
/**
 * Team recent-form badges (W/D/L), oldest to newest.
 *
 * @package Athletix
 * @var array[] $results Each: outcome ('w'|'d'|'l'), match (\WP_Post), label.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $results ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No recent results.', 'athletix' ) . '</p>';
	return;
}
?>
<ul class="athletix-form">
	<?php foreach ( $results as $result ) : ?>
		<li class="athletix-form__item athletix-form__item--<?php echo esc_attr( $result['outcome'] ); ?>">
			<a class="athletix-form__badge" href="<?php echo esc_url( get_permalink( $result['match'] ) ); ?>" title="<?php echo esc_attr( $result['label'] ); ?>">
				<?php echo esc_html( strtoupper( $result['outcome'] ) ); ?>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
