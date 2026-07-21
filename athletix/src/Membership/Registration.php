<?php
/**
 * Front-end team/player registration.
 *
 * @package Athletix
 */

namespace Athletix\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Provides an [athletix_register_team] front-end form that creates a pending
 * (draft) team for admin review. Nonce-guarded; submissions are sanitized and
 * never auto-published.
 */
class Registration {

	const NONCE_ACTION = 'athletix_register_team';
	const NONCE_NAME   = 'athletix_register_nonce';

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the shortcode + handler.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_register_team', array( $this, 'render' ) );
		add_action( 'init', array( $this, 'maybe_handle' ) );
	}

	/**
	 * Handle a submission early on init (before output).
	 *
	 * @return void
	 */
	public function maybe_handle() {
		if ( empty( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$name    = isset( $_POST['team_name'] ) ? sanitize_text_field( wp_unslash( $_POST['team_name'] ) ) : '';
		$contact = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';

		if ( '' === $name ) {
			$this->flash = __( 'Please enter a team name.', 'athletix' );
			return;
		}

		$team_id = $this->plugin->make( 'repo.team' )->create(
			array(
				'post_title'  => $name,
				'post_status' => 'draft',
			// Pending admin review.
			)
		);

		if ( is_wp_error( $team_id ) || ! $team_id ) {
			$this->flash = __( 'Sorry, your registration could not be saved.', 'athletix' );
			return;
		}

		if ( $contact ) {
			update_post_meta( $team_id, '_ax_registration_contact', $contact );
		}
		update_post_meta( $team_id, '_ax_registration_status', 'pending' );

		$this->plugin->events()->fire(
			'team_registered',
			array(
				'team_id' => (int) $team_id,
				'contact' => $contact,
			)
		);

		$this->flash   = __( 'Thanks! Your team has been submitted for review.', 'athletix' );
		$this->success = true;
	}

	/**
	 * Flash message set during handling.
	 *
	 * @var string
	 */
	private $flash = '';

	/**
	 * Whether the last submission succeeded.
	 *
	 * @var bool
	 */
	private $success = false;

	/**
	 * Render the form (and any flash message).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		unset( $atts );
		wp_enqueue_style( 'athletix' );

		ob_start();

		if ( '' !== $this->flash ) {
			printf(
				'<p class="athletix-notice %s">%s</p>',
				esc_attr( $this->success ? 'is-success' : 'is-error' ),
				esc_html( $this->flash )
			);
		}

		if ( ! $this->success ) {
			?>
			<form class="athletix-register" method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<p>
					<label for="ax-team-name"><?php esc_html_e( 'Team Name', 'athletix' ); ?></label><br />
					<input type="text" id="ax-team-name" name="team_name" required />
				</p>
				<p>
					<label for="ax-contact"><?php esc_html_e( 'Contact Email', 'athletix' ); ?></label><br />
					<input type="email" id="ax-contact" name="contact_email" />
				</p>
				<p><button type="submit"><?php esc_html_e( 'Register Team', 'athletix' ); ?></button></p>
			</form>
			<?php
		}

		return (string) ob_get_clean();
	}
}
