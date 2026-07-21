<?php
/**
 * Player profile template.
 *
 * @package Athletix
 * @var \WP_Post $player     Player post.
 * @var bool     $show_stats Whether to render the stats list.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

$fields = array(
	__( 'Position', 'athletix' ) => get_post_meta( $player->ID, Keys::PLAYER_POSITION, true ),
	__( 'Number', 'athletix' )   => get_post_meta( $player->ID, Keys::PLAYER_NUMBER, true ),
	__( 'Height', 'athletix' )   => get_post_meta( $player->ID, Keys::PLAYER_HEIGHT, true ),
	__( 'Weight', 'athletix' )   => get_post_meta( $player->ID, Keys::PLAYER_WEIGHT, true ),
	__( 'Country', 'athletix' )  => get_post_meta( $player->ID, Keys::PLAYER_COUNTRY, true ),
);

$team_id = (int) get_post_meta( $player->ID, Keys::PLAYER_TEAM, true );
?>
<div class="athletix-player">
	<?php if ( has_post_thumbnail( $player ) ) : ?>
		<div class="athletix-player__photo"><?php echo get_the_post_thumbnail( $player, 'medium' ); ?></div>
	<?php endif; ?>

	<h3 class="athletix-player__name"><?php echo esc_html( get_the_title( $player ) ); ?></h3>

	<?php if ( $team_id ) : ?>
		<p class="athletix-player__team">
			<a href="<?php echo esc_url( get_permalink( $team_id ) ); ?>"><?php echo esc_html( get_the_title( $team_id ) ); ?></a>
		</p>
	<?php endif; ?>

	<?php if ( $show_stats ) : ?>
		<ul class="athletix-player__stats">
			<?php foreach ( $fields as $label => $value ) : ?>
				<?php if ( '' !== $value ) : ?>
					<li class="athletix-player__stat">
						<span class="athletix-player__stat-label"><?php echo esc_html( $label ); ?></span>
						<span class="athletix-player__stat-value"><?php echo esc_html( $value ); ?></span>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
