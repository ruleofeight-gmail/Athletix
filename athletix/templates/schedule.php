<?php
/**
 * Schedule / results table template.
 *
 * @package Athletix
 * @var \WP_Post[]                                     $matches Matches.
 * @var \Athletix\Data\Repositories\MatchRepository   $repo    Match repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $matches ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No matches scheduled.', 'athletix' ) . '</p>';
	return;
}
?>
<table class="athletix-schedule">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Date', 'athletix' ); ?></th>
			<th><?php esc_html_e( 'Home', 'athletix' ); ?></th>
			<th><?php esc_html_e( 'Score', 'athletix' ); ?></th>
			<th><?php esc_html_e( 'Away', 'athletix' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $matches as $match ) : ?>
			<?php
			$d            = $repo->details( $match->ID );
			$display_date = $d['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $d['date'] ) ) : '';
			$is_done      = ( 'completed' === $d['status'] );
			$score        = $is_done ? ( (int) $d['home_score'] . ' – ' . (int) $d['away_score'] ) : esc_html__( 'vs', 'athletix' );
			?>
			<tr>
				<td class="athletix-schedule__date"><?php echo esc_html( $display_date ); ?></td>
				<td class="athletix-schedule__home"><?php echo esc_html( get_the_title( $d['home'] ) ); ?></td>
				<td class="athletix-schedule__score"><?php echo esc_html( $score ); ?></td>
				<td class="athletix-schedule__away"><?php echo esc_html( get_the_title( $d['away'] ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
