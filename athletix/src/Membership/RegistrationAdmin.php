<?php
/**
 * Registration approvals admin screen.
 *
 * @package Athletix
 */

namespace Athletix\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Lists pending team registrations and lets an administrator approve (publish)
 * or reject (trash) them.
 */
class RegistrationAdmin {

	const PAGE           = 'athletix-registrations';
	const ACTION_APPROVE = 'athletix_reg_approve';
	const ACTION_REJECT  = 'athletix_reg_reject';

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION_APPROVE, array( $this, 'handle_approve' ) );
		add_action( 'admin_post_' . self::ACTION_REJECT, array( $this, 'handle_reject' ) );
	}

	/**
	 * Add the submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			Keys::MENU,
			__( 'Registrations', 'athletix' ),
			__( 'Registrations', 'athletix' ),
			Keys::capability(),
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the pending list.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}

		$pending     = get_posts(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'draft',
				'numberposts' => 100,
				'meta_key'    => '_ax_registration_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 'pending', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$action      = esc_url( admin_url( 'admin-post.php' ) );
		$eligibility = new Eligibility( $this->plugin );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pending Registrations', 'athletix' ); ?></h1>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Team', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Contact', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Eligibility', 'athletix' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $pending ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No pending registrations.', 'athletix' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $pending as $team ) : ?>
						<?php $check = $eligibility->check( $team->ID ); ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $team ) ); ?></td>
							<td><?php echo esc_html( (string) get_post_meta( $team->ID, '_ax_registration_contact', true ) ); ?></td>
							<td>
								<?php
								echo $check['eligible']
									? '<span style="color:#2e7d32;">' . esc_html__( 'Eligible', 'athletix' ) . '</span>'
									: esc_html( implode( ', ', $check['reasons'] ) );
								?>
							</td>
							<td>
								<form method="post" action="<?php echo esc_url( $action ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_APPROVE ); ?>" />
									<input type="hidden" name="team" value="<?php echo esc_attr( $team->ID ); ?>" />
									<?php wp_nonce_field( self::ACTION_APPROVE ); ?>
									<button class="button button-primary"><?php esc_html_e( 'Approve', 'athletix' ); ?></button>
								</form>
								<form method="post" action="<?php echo esc_url( $action ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_REJECT ); ?>" />
									<input type="hidden" name="team" value="<?php echo esc_attr( $team->ID ); ?>" />
									<?php wp_nonce_field( self::ACTION_REJECT ); ?>
									<button class="button-link delete"><?php esc_html_e( 'Reject', 'athletix' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Approve a registration (publish the team).
	 *
	 * @return void
	 */
	public function handle_approve() {
		check_admin_referer( self::ACTION_APPROVE );
		$this->authorize();

		$team = isset( $_POST['team'] ) ? absint( $_POST['team'] ) : 0;
		if ( $team ) {
			wp_update_post(
				array(
					'ID'          => $team,
					'post_status' => 'publish',
				)
			);
			update_post_meta( $team, '_ax_registration_status', 'approved' );
		}

		$this->redirect();
	}

	/**
	 * Reject a registration (trash the team).
	 *
	 * @return void
	 */
	public function handle_reject() {
		check_admin_referer( self::ACTION_REJECT );
		$this->authorize();

		$team = isset( $_POST['team'] ) ? absint( $_POST['team'] ) : 0;
		if ( $team ) {
			update_post_meta( $team, '_ax_registration_status', 'rejected' );
			wp_trash_post( $team );
		}

		$this->redirect();
	}

	/**
	 * Capability guard (nonce verified inline by handlers).
	 *
	 * @return void
	 */
	private function authorize() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
	}

	/**
	 * Redirect back to the screen.
	 *
	 * @return void
	 */
	private function redirect() {
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => Keys::TEAM,
					'page'      => self::PAGE,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
