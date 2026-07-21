<?php
/**
 * Per-user notification preferences.
 *
 * @package Athletix
 */

namespace Athletix\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a notification opt-in to the user profile and exposes a helper the
 * notifier can consult before emailing a given user.
 */
class Preferences {

	const META = 'athletix_notify_results';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'show_user_profile', array( $this, 'field' ) );
		add_action( 'edit_user_profile', array( $this, 'field' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	/**
	 * Whether a user wants match-result emails (default: yes).
	 *
	 * @param int $user_id User id.
	 * @return bool
	 */
	public static function wants_results( $user_id ) {
		$value = get_user_meta( absint( $user_id ), self::META, true );
		return '' === $value ? true : (bool) $value;
	}

	/**
	 * Render the profile field.
	 *
	 * @param \WP_User $user User being edited.
	 * @return void
	 */
	public function field( $user ) {
		?>
		<h2><?php esc_html_e( 'Athletix Notifications', 'athletix' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Match results', 'athletix' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( self::META ); ?>" value="1" <?php checked( self::wants_results( $user->ID ) ); ?> />
						<?php esc_html_e( 'Email me when match results are posted', 'athletix' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the profile field.
	 *
	 * @param int $user_id User id.
	 * @return void
	 */
	public function save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		check_admin_referer( 'update-user_' . $user_id );

		$value = isset( $_POST[ self::META ] ) ? 1 : 0;
		update_user_meta( $user_id, self::META, $value );
	}
}
