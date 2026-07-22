<?php
/**
 * Admin "Add Player" form template.
 *
 * The position control is sport-aware: choosing a team fetches that team's
 * sport profile and rebuilds the Position field as a dropdown of the sport's
 * positions (or a free-text input when the sport defines none). That behaviour,
 * plus the batch "Add Another" flow, lives in assets/js/quick-add.js.
 *
 * @package Athletix
 * @var \WP_Post[]                 $teams  Team posts.
 * @var string                     $action admin-post action.
 * @var array<string,int>          $preset Carried-over context ids.
 * @var array{type:string,message:string,link:string}|null $notice Notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap athletix-quick-add">
	<h1><?php esc_html_e( 'Add Player', 'athletix' ); ?></h1>

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

	<div id="athletix-quick-notice" class="notice" hidden></div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="athletix-quick-add__form">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
		<?php wp_nonce_field( $action ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="athletix_name"><?php esc_html_e( 'Player name', 'athletix' ); ?> <span class="description">(<?php esc_html_e( 'required', 'athletix' ); ?>)</span></label></th>
					<td><input name="athletix_name" id="athletix_name" type="text" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_team"><?php esc_html_e( 'Team', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_team" id="athletix_team">
							<option value="0"><?php esc_html_e( '— Select a team —', 'athletix' ); ?></option>
							<?php foreach ( $teams as $athletix_team_post ) : ?>
								<option value="<?php echo esc_attr( $athletix_team_post->ID ); ?>" <?php selected( (int) $preset['team'], (int) $athletix_team_post->ID ); ?>><?php echo esc_html( get_the_title( $athletix_team_post ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<span id="athletix_sport_label" class="description"></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_position"><?php esc_html_e( 'Position', 'athletix' ); ?></label></th>
					<td id="athletix_position_field">
						<input name="athletix_position" id="athletix_position" type="text" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Pick a team to load its sport’s positions.', 'athletix' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_number"><?php esc_html_e( 'Jersey number', 'athletix' ); ?></label></th>
					<td><input name="athletix_number" id="athletix_number" type="number" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_height"><?php esc_html_e( 'Height', 'athletix' ); ?></label></th>
					<td><input name="athletix_height" id="athletix_height" type="text" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_weight"><?php esc_html_e( 'Weight', 'athletix' ); ?></label></th>
					<td><input name="athletix_weight" id="athletix_weight" type="text" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_country"><?php esc_html_e( 'Country', 'athletix' ); ?></label></th>
					<td><input name="athletix_country" id="athletix_country" type="text" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_dob"><?php esc_html_e( 'Date of birth', 'athletix' ); ?></label></th>
					<td><input name="athletix_dob" id="athletix_dob" type="date" /></td>
				</tr>
			</tbody>
		</table>

		<p class="submit athletix-quick-add__actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Player', 'athletix' ); ?></button>
			<button type="submit" name="athletix_add_another" value="1" class="button"><?php esc_html_e( 'Add Another', 'athletix' ); ?></button>
		</p>
	</form>

	<div id="athletix-added" class="athletix-added" hidden>
		<h2><?php esc_html_e( 'Added this session', 'athletix' ); ?> (<span id="athletix-added-count">0</span>)</h2>
		<ul id="athletix-added-list"></ul>
	</div>
</div>
