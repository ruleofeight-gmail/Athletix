<?php
/**
 * Playoff bracket template.
 *
 * @package Athletix
 * @var array[] $rounds Bracket rounds from BracketBuilder::build().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $rounds ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No playoff matches yet.', 'athletix' ) . '</p>';
	return;
}

/**
 * Render one competitor line inside a bracket match.
 *
 * @param int    $team_id Team post id (0 = to be decided).
 * @param int    $score   Score.
 * @param bool   $is_done Whether the match is completed.
 * @param bool   $is_win  Whether this side won.
 * @return void
 */
$athletix_competitor = static function ( $team_id, $score, $is_done, $is_win ) {
	$name    = $team_id ? get_the_title( $team_id ) : __( 'TBD', 'athletix' );
	$classes = 'athletix-bracket__team' . ( $is_win ? ' is-winner' : '' );
	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<span class="athletix-bracket__name"><?php echo esc_html( $name ); ?></span>
		<span class="athletix-bracket__score"><?php echo $is_done ? esc_html( (int) $score ) : ''; ?></span>
	</div>
	<?php
};
?>
<div class="athletix-bracket" role="table" aria-label="<?php esc_attr_e( 'Playoff bracket', 'athletix' ); ?>">
	<?php foreach ( $rounds as $round ) : ?>
		<div class="athletix-bracket__round">
			<h3 class="athletix-bracket__round-title"><?php echo esc_html( $round['label'] ); ?></h3>
			<div class="athletix-bracket__matches">
				<?php foreach ( $round['matches'] as $match ) : ?>
					<?php $is_done = ( 'completed' === $match['status'] ); ?>
					<div class="athletix-bracket__match">
						<?php
						$athletix_competitor( $match['home'], $match['home_score'], $is_done, 'home' === $match['winner'] );
						$athletix_competitor( $match['away'], $match['away_score'], $is_done, 'away' === $match['winner'] );
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
