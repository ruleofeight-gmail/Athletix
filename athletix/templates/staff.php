<?php
/**
 * Staff list template.
 *
 * @package Athletix
 * @var \WP_Post[] $staff Staff members.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $staff ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No staff found.', 'athletix' ) . '</p>';
	return;
}
?>
<div class="athletix-staff">
	<?php foreach ( $staff as $member ) : ?>
		<?php
		$role    = (string) get_post_meta( $member->ID, '_ax_staff_role', true );
		$country = (string) get_post_meta( $member->ID, '_ax_staff_country', true );
		?>
		<div class="athletix-staff__item">
			<a class="athletix-staff__link" href="<?php echo esc_url( get_permalink( $member ) ); ?>">
				<?php if ( has_post_thumbnail( $member ) ) : ?>
					<div class="athletix-staff__photo"><?php echo get_the_post_thumbnail( $member, 'medium' ); ?></div>
				<?php endif; ?>
				<span class="athletix-staff__name"><?php echo esc_html( get_the_title( $member ) ); ?></span>
				<?php if ( '' !== $role ) : ?>
					<span class="athletix-staff__role"><?php echo esc_html( $role ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $country ) : ?>
					<span class="athletix-staff__country"><?php echo esc_html( $country ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
