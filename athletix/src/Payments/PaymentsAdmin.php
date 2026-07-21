<?php
/**
 * Payments admin meta box.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * A "Registration Payments" meta box on team edit screens: shows the ledger
 * and lets a manager record a manual payment (nonce + capability guarded).
 */
class PaymentsAdmin {

	const ACTION     = 'athletix_record_payment';
	const NONCE_NAME = 'athletix_payment_nonce';

	/**
	 * Ledger.
	 *
	 * @var Ledger
	 */
	private $ledger;

	/**
	 * Constructor.
	 *
	 * @param Ledger $ledger Payment ledger.
	 */
	public function __construct( Ledger $ledger ) {
		$this->ledger = $ledger;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Add the meta box to teams.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box(
			'athletix_payments',
			__( 'Registration Payments', 'athletix' ),
			array( $this, 'render' ),
			Keys::TEAM,
			'side'
		);
	}

	/**
	 * Render the ledger and record form.
	 *
	 * @param \WP_Post $post Team post.
	 * @return void
	 */
	public function render( $post ) {
		$records = $this->ledger->records( $post->ID );
		$total   = $this->ledger->total( $post->ID );

		echo '<p><strong>' . esc_html__( 'Total paid:', 'athletix' ) . '</strong> ' . esc_html( number_format_i18n( $total, 2 ) ) . '</p>';

		if ( $records ) {
			echo '<ul style="margin:0 0 1em;">';
			foreach ( $records as $record ) {
				printf(
					'<li>%s — %s</li>',
					esc_html( number_format_i18n( (float) $record['amount'], 2 ) ),
					esc_html( wp_date( 'Y-m-d', (int) $record['time'] ) )
				);
			}
			echo '</ul>';
		}

		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<input type="hidden" name="entity_id" value="<?php echo esc_attr( $post->ID ); ?>" />
			<?php wp_nonce_field( self::ACTION, self::NONCE_NAME ); ?>
			<p>
				<label><?php esc_html_e( 'Amount', 'athletix' ); ?>
					<input type="number" step="0.01" min="0" name="amount" class="small-text" required />
				</label>
			</p>
			<p><button type="submit" class="button"><?php esc_html_e( 'Record Payment', 'athletix' ); ?></button></p>
		</form>
		<p class="description"><?php esc_html_e( 'Manual entry only — no card data is processed or stored.', 'athletix' ); ?></p>
		<?php
	}

	/**
	 * Handle the record-payment submission.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}

		check_admin_referer( self::ACTION, self::NONCE_NAME );

		$entity_id = isset( $_POST['entity_id'] ) ? absint( $_POST['entity_id'] ) : 0;
		$amount    = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0.0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( $entity_id && $amount > 0 ) {
			$this->ledger->record( $entity_id, $amount, __( 'Manual entry', 'athletix' ) );
		}

		$redirect = get_edit_post_link( $entity_id, 'redirect' );
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}
}
