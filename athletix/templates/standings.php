<?php
/**
 * Standings table template.
 *
 * @package Athletix
 * @var array[] $rows Ordered standings rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $rows ) ) {
	echo '<p class="athletix-empty">' . esc_html__( 'No standings available yet.', 'athletix' ) . '</p>';
	return;
}
?>
<table class="athletix-standings">
	<thead>
		<tr>
			<th class="athletix-standings__pos">#</th>
			<th class="athletix-standings__team"><?php esc_html_e( 'Team', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Played', 'athletix' ); ?>"><?php esc_html_e( 'P', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Won', 'athletix' ); ?>"><?php esc_html_e( 'W', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Drawn', 'athletix' ); ?>"><?php esc_html_e( 'D', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Lost', 'athletix' ); ?>"><?php esc_html_e( 'L', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Goals For', 'athletix' ); ?>"><?php esc_html_e( 'GF', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Goals Against', 'athletix' ); ?>"><?php esc_html_e( 'GA', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Goal Difference', 'athletix' ); ?>"><?php esc_html_e( 'GD', 'athletix' ); ?></th>
			<th title="<?php esc_attr_e( 'Points', 'athletix' ); ?>"><?php esc_html_e( 'Pts', 'athletix' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $pos = 0; ?>
		<?php foreach ( $rows as $row ) : ?>
			<?php ++$pos; ?>
			<tr>
				<td class="athletix-standings__pos"><?php echo esc_html( $pos ); ?></td>
				<td class="athletix-standings__team">
					<a href="<?php echo esc_url( get_permalink( (int) $row['team_id'] ) ); ?>">
						<?php echo esc_html( get_the_title( (int) $row['team_id'] ) ); ?>
					</a>
				</td>
				<td><?php echo esc_html( $row['played'] ); ?></td>
				<td><?php echo esc_html( $row['won'] ); ?></td>
				<td><?php echo esc_html( $row['drawn'] ); ?></td>
				<td><?php echo esc_html( $row['lost'] ); ?></td>
				<td><?php echo esc_html( $row['goals_for'] ); ?></td>
				<td><?php echo esc_html( $row['goals_against'] ); ?></td>
				<td><?php echo esc_html( $row['goal_difference'] ); ?></td>
				<td class="athletix-standings__pts"><?php echo esc_html( $row['points'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
