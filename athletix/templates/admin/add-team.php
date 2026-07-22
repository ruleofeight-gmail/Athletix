<?php
/**
 * Admin "Add Team" form template.
 *
 * @package Athletix
 * @var \WP_Term[]                 $sports    Sport terms.
 * @var \WP_Term[]                 $leagues   League terms.
 * @var \WP_Term[]                 $divisions Division terms.
 * @var string                     $action    admin-post action.
 * @var array{type:string,message:string,link:string}|null $notice Notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap athletix-quick-add">
	<h1><?php esc_html_e( 'Add Team', 'athletix' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
			<p>
				<?php echo esc_html( $notice['message'] ); ?>
				<?php if ( ! empty( $notice['link'] ) ) : ?>
					<a href="<?php echo esc_url( $notice['link'] ); ?>"><?php esc_html_e( 'Edit', 'athletix' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
		<?php wp_nonce_field( $action ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="athletix_name"><?php esc_html_e( 'Team name', 'athletix' ); ?> <span class="description">(<?php esc_html_e( 'required', 'athletix' ); ?>)</span></label></th>
					<td><input name="athletix_name" id="athletix_name" type="text" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_sport"><?php esc_html_e( 'Sport', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_sport" id="athletix_sport">
							<option value="0"><?php esc_html_e( '— Select a sport —', 'athletix' ); ?></option>
							<?php foreach ( $sports as $athletix_term ) : ?>
								<option value="<?php echo esc_attr( $athletix_term->term_id ); ?>"><?php echo esc_html( $athletix_term->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'The sport this team plays. Players added to it inherit its positions.', 'athletix' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_league"><?php esc_html_e( 'League', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_league" id="athletix_league">
							<option value="0"><?php esc_html_e( '— None —', 'athletix' ); ?></option>
							<?php foreach ( $leagues as $athletix_term ) : ?>
								<option value="<?php echo esc_attr( $athletix_term->term_id ); ?>"><?php echo esc_html( $athletix_term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_division"><?php esc_html_e( 'Division', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_division" id="athletix_division">
							<option value="0"><?php esc_html_e( '— None —', 'athletix' ); ?></option>
							<?php foreach ( $divisions as $athletix_term ) : ?>
								<option value="<?php echo esc_attr( $athletix_term->term_id ); ?>"><?php echo esc_html( $athletix_term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_venue"><?php esc_html_e( 'Home venue', 'athletix' ); ?></label></th>
					<td><input name="athletix_venue" id="athletix_venue" type="text" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_founded"><?php esc_html_e( 'Founded (year)', 'athletix' ); ?></label></th>
					<td><input name="athletix_founded" id="athletix_founded" type="number" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_color"><?php esc_html_e( 'Team color', 'athletix' ); ?></label></th>
					<td><input name="athletix_color" id="athletix_color" type="text" class="regular-text" placeholder="#1a73e8" /></td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Add Team', 'athletix' ) ); ?>
	</form>
</div>
