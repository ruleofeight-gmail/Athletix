<?php
/**
 * Admin "Add Player" form template.
 *
 * The position control is sport-aware: choosing a team fetches that team's
 * sport profile and rebuilds the Position field as a dropdown of the sport's
 * positions (or a free-text input when the sport defines none).
 *
 * @package Athletix
 * @var \WP_Post[]                 $teams  Team posts.
 * @var string                     $action admin-post action.
 * @var string                     $ajax   AJAX action name.
 * @var string                     $nonce  AJAX nonce.
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

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
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
								<option value="<?php echo esc_attr( $athletix_team_post->ID ); ?>"><?php echo esc_html( get_the_title( $athletix_team_post ) ); ?></option>
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

		<?php submit_button( __( 'Add Player', 'athletix' ) ); ?>
	</form>
</div>

<script>
( function () {
	var team  = document.getElementById( 'athletix_team' );
	var field = document.getElementById( 'athletix_position_field' );
	var label = document.getElementById( 'athletix_sport_label' );
	if ( ! team || ! field ) {
		return;
	}

	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var action  = <?php echo wp_json_encode( $ajax ); ?>;
	var nonce   = <?php echo wp_json_encode( $nonce ); ?>;
	var strings = {
		pick:   <?php echo wp_json_encode( __( 'Pick a team to load its sport’s positions.', 'athletix' ) ); ?>,
		none:   <?php echo wp_json_encode( __( '— None —', 'athletix' ) ); ?>,
		free:   <?php echo wp_json_encode( __( 'This sport has no fixed positions — enter one if you like.', 'athletix' ) ); ?>,
		sport:  <?php /* translators: %s: sport name. */ echo wp_json_encode( __( 'Sport: %s', 'athletix' ) ); ?>
	};

	function esc( value ) {
		var d = document.createElement( 'div' );
		d.textContent = value;
		return d.innerHTML;
	}

	function renderText( hint ) {
		field.innerHTML = '<input name="athletix_position" id="athletix_position" type="text" class="regular-text" />' +
			'<p class="description">' + esc( hint ) + '</p>';
	}

	function renderSelect( positions ) {
		var html = '<select name="athletix_position" id="athletix_position">';
		html += '<option value="">' + esc( strings.none ) + '</option>';
		positions.forEach( function ( pos ) {
			html += '<option value="' + esc( pos ) + '">' + esc( pos ) + '</option>';
		} );
		html += '</select>';
		field.innerHTML = html;
	}

	team.addEventListener( 'change', function () {
		var id = parseInt( team.value, 10 ) || 0;
		if ( label ) {
			label.textContent = '';
		}
		if ( ! id ) {
			renderText( strings.pick );
			return;
		}

		var url = ajaxUrl + '?action=' + encodeURIComponent( action ) +
			'&nonce=' + encodeURIComponent( nonce ) + '&team=' + id;

		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					renderText( strings.pick );
					return;
				}
				if ( label && res.data.label ) {
					label.textContent = strings.sport.replace( '%s', res.data.label );
				}
				if ( res.data.positions && res.data.positions.length ) {
					renderSelect( res.data.positions );
				} else {
					renderText( strings.free );
				}
			} )
			.catch( function () { renderText( strings.pick ); } );
	} );
}() );
</script>
