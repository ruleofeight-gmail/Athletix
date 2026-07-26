<?php
/**
 * Roster grid template.
 *
 * @package Athletix
 * @var \WP_Post[] $players Players.
 * @var int        $columns Column count.
 * @var string     $group   '' for a flat grid, 'position' to group by position.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $players ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No players found.', 'athletix' ) . '</p>';
	return;
}

$columns = isset( $columns ) ? (int) $columns : 3;
$group   = isset( $group ) ? (string) $group : '';

/**
 * Render one player card.
 *
 * @param \WP_Post $player Player post.
 * @return void
 */
$athletix_render_player = static function ( $player ) {
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
	<?php
};

if ( 'position' === $group ) {
	// Bucket players by position, keeping first-seen order; the unlabelled ones
	// fall into an "Other" group at the end.
	$athletix_groups = array();
	foreach ( $players as $player ) {
		$position = trim( (string) get_post_meta( $player->ID, '_ax_player_position', true ) );
		$label    = '' !== $position ? $position : __( 'Other', 'athletix' );

		if ( ! isset( $athletix_groups[ $label ] ) ) {
			$athletix_groups[ $label ] = array();
		}
		$athletix_groups[ $label ][] = $player;
	}

	foreach ( $athletix_groups as $label => $bucket ) :
		?>
		<div class="athletix-roster-group">
			<h3 class="athletix-roster-group__title"><?php echo esc_html( $label ); ?></h3>
			<div class="athletix-roster athletix-roster--cols-<?php echo esc_attr( $columns ); ?>">
				<?php
				foreach ( $bucket as $player ) {
					$athletix_render_player( $player );
				}
				?>
			</div>
		</div>
		<?php
	endforeach;

	return;
}
?>
<div class="athletix-roster athletix-roster--cols-<?php echo esc_attr( $columns ); ?>">
	<?php
	foreach ( $players as $player ) {
		$athletix_render_player( $player );
	}
	?>
</div>
