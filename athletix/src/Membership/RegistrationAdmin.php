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

use Athletix\Admin\Hub;
use Athletix\Admin\Tables\FilterableTable;
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
		add_filter( 'athletix/admin_tabs', array( $this, 'tab' ) );
		add_action( 'admin_post_' . self::ACTION_APPROVE, array( $this, 'handle_approve' ) );
		add_action( 'admin_post_' . self::ACTION_REJECT, array( $this, 'handle_reject' ) );
	}

	/**
	 * Contribute the Registrations tab to the hub.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function tab( array $tabs ) {
		$tabs['registrations'] = array(
			'label'    => __( 'Registrations', 'athletix' ),
			'cap'      => Keys::capability(),
			'order'    => 40,
			'callback' => array( $this, 'render' ),
		);

		return $tabs;
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

		$table = new FilterableTable(
			array(
				'columns'    => array(
					'team'        => __( 'Team', 'athletix' ),
					'contact'     => __( 'Contact', 'athletix' ),
					'eligibility' => __( 'Eligibility', 'athletix' ),
					'actions'     => '',
				),
				'sortable'   => array( 'team', 'contact' ),
				'searchable' => array( 'team', 'contact' ),
				'per_page'   => 20,
				'base_url'   => Hub::tab_url( 'registrations' ),
				'rows'       => array( $this, 'rows' ),
				'render'     => array(
					'eligibility' => array( $this, 'render_eligibility' ),
					'actions'     => array( $this, 'render_actions' ),
				),
			)
		);
		?>
		<h2><?php esc_html_e( 'Pending Registrations', 'athletix' ); ?></h2>
		<?php $table->render(); ?>
		<?php
	}

	/**
	 * Build the pending-registration rows.
	 *
	 * @return array[]
	 */
	public function rows() {
		$pending = get_posts(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'draft',
				'numberposts' => 100,
				'meta_key'    => '_ax_registration_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => 'pending', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$eligibility = new Eligibility( $this->plugin );
		$rows        = array();

		foreach ( $pending as $team ) {
			$check  = $eligibility->check( $team->ID );
			$rows[] = array(
				'team_id'  => (int) $team->ID,
				'team'     => get_the_title( $team ),
				'contact'  => (string) get_post_meta( $team->ID, '_ax_registration_contact', true ),
				'eligible' => ! empty( $check['eligible'] ),
				'reasons'  => implode( ', ', (array) $check['reasons'] ),
			);
		}

		return $rows;
	}

	/**
	 * Render the eligibility cell.
	 *
	 * @param array $row Row.
	 * @return string
	 */
	public function render_eligibility( array $row ) {
		if ( ! empty( $row['eligible'] ) ) {
			return '<span style="color:#2e7d32;">' . esc_html__( 'Eligible', 'athletix' ) . '</span>';
		}

		return esc_html( (string) $row['reasons'] );
	}

	/**
	 * Render the approve/reject action cell.
	 *
	 * @param array $row Row.
	 * @return string
	 */
	public function render_actions( array $row ) {
		$action  = esc_url( admin_url( 'admin-post.php' ) );
		$team_id = (int) $row['team_id'];

		$approve = sprintf(
			'<form method="post" action="%1$s" style="display:inline;">
				<input type="hidden" name="action" value="%2$s" />
				<input type="hidden" name="team" value="%3$d" />
				%4$s
				<button class="button button-primary">%5$s</button>
			</form>',
			$action,
			esc_attr( self::ACTION_APPROVE ),
			$team_id,
			wp_nonce_field( self::ACTION_APPROVE, '_wpnonce', true, false ),
			esc_html__( 'Approve', 'athletix' )
		);

		$reject = sprintf(
			'<form method="post" action="%1$s" style="display:inline;">
				<input type="hidden" name="action" value="%2$s" />
				<input type="hidden" name="team" value="%3$d" />
				%4$s
				<button class="button-link delete">%5$s</button>
			</form>',
			$action,
			esc_attr( self::ACTION_REJECT ),
			$team_id,
			wp_nonce_field( self::ACTION_REJECT, '_wpnonce', true, false ),
			esc_html__( 'Reject', 'athletix' )
		);

		return $approve . $reject;
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
		wp_safe_redirect( Hub::tab_url( 'registrations' ) );
		exit;
	}
}
