<?php
/**
 * Roster grid template.
 *
 * @package Athletix
 * @var \WP_Post[] $players Players.
 * @var int        $columns Column count.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $players ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No players found.', 'athletix' ) . '</p>';
	return;
}

$columns = isset( $columns ) ? (int) $columns : 3;
?>
<div class="athletix-roster athletix-roster--cols-<?php echo esc_attr( $columns ); ?>">
	<?php foreach ( $players as $player ) : ?>
		<?php
		$number   = (int) get_post_meta( $player->ID, '_ax_player_number', true );
		$position = (string) get_post_meta( $player->ID, '_ax_player_position', true );
		?>
		<div class="athletix-roster__item">
			<a class="athletix-roster__link" href="<?php echo esc_url( get_permalink( $player ) ); ?>">
				<?php if ( has_post_thumbnail( $player ) ) : ?>
					<div class="athletix-roster__photo"><?php echo get_the_post_thumbnail( $player, 'medium' ); ?></div>
				<?php endif; ?>
				<span class="athletix-roster__name">
					<?php if ( $number ) : ?>
						<span class="athletix-roster__number">#<?php echo esc_html( $number ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( get_the_title( $player ) ); ?>
				</span>
				<?php if ( '' !== $position ) : ?>
					<span class="athletix-roster__position"><?php echo esc_html( $position ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
