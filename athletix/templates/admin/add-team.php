<?php
/**
 * Admin "Add Team" form template.
 *
 * @package Athletix
 * @var \WP_Term[]                 $sports    Sport terms.
 * @var \WP_Term[]                 $leagues   League terms.
 * @var \WP_Term[]                 $divisions Division terms.
 * @var string                     $action    admin-post action.
 * @var array<string,int>          $preset    Carried-over context ids.
 * @var array{type:string,message:string,link:string}|null $notice Notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the <option> list for a term dropdown, preselecting a carried id.
 *
 * @param \WP_Term[] $terms    Terms.
 * @param int        $selected Preselected term id.
 * @return void
 */
$athletix_term_options = static function ( $terms, $selected ) {
	foreach ( $terms as $term ) {
		printf(
			'<option value="%d" %s>%s</option>',
			(int) $term->term_id,
			selected( (int) $selected, (int) $term->term_id, false ),
			esc_html( $term->name )
		);
	}
};
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

	<div id="athletix-quick-notice" class="notice" hidden></div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="athletix-quick-add__form">
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
							<?php $athletix_term_options( $sports, $preset['sport'] ); ?>
						</select>
						<p class="description"><?php esc_html_e( 'The sport this team plays. Players added to it inherit its positions.', 'athletix' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_league"><?php esc_html_e( 'League', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_league" id="athletix_league">
							<option value="0"><?php esc_html_e( '— None —', 'athletix' ); ?></option>
							<?php $athletix_term_options( $leagues, $preset['league'] ); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="athletix_division"><?php esc_html_e( 'Division', 'athletix' ); ?></label></th>
					<td>
						<select name="athletix_division" id="athletix_division">
							<option value="0"><?php esc_html_e( '— None —', 'athletix' ); ?></option>
							<?php $athletix_term_options( $divisions, $preset['division'] ); ?>
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

		<p class="submit athletix-quick-add__actions">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Team', 'athletix' ); ?></button>
			<button type="submit" name="athletix_add_another" value="1" class="button"><?php esc_html_e( 'Add Another', 'athletix' ); ?></button>
		</p>
	</form>

	<div id="athletix-added" class="athletix-added" hidden>
		<h2><?php esc_html_e( 'Added this session', 'athletix' ); ?> (<span id="athletix-added-count">0</span>)</h2>
		<ul id="athletix-added-list"></ul>
	</div>
</div>
