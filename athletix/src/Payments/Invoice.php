<?php
/**
 * Invoice rendering.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Renders a simple invoice for a team from its payment ledger via
 * [athletix_invoice], visible only to users who may manage the plugin.
 */
class Invoice {

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
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_invoice', array( $this, 'render' ) );
	}

	/**
	 * [athletix_invoice team="5"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'team' => 0 ), $atts, 'athletix_invoice' );

		$team = absint( $atts['team'] );
		if ( ! $team || ! Keys::can_manage() ) {
			return '';
		}

		$records = $this->ledger->records( $team );
		if ( empty( $records ) ) {
			return '<p class="athletix-empty">' . esc_html__( 'No payments recorded.', 'athletix' ) . '</p>';
		}

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-invoice">';
		echo '<h3>' . esc_html( sprintf( /* translators: %s: team name. */ __( 'Invoice — %s', 'athletix' ), get_the_title( $team ) ) ) . '</h3>';
		echo '<table class="athletix-standings"><thead><tr><th class="athletix-standings__team">' . esc_html__( 'Date', 'athletix' ) . '</th><th>' . esc_html__( 'Note', 'athletix' ) . '</th><th>' . esc_html__( 'Amount', 'athletix' ) . '</th></tr></thead><tbody>';
		foreach ( $records as $record ) {
			printf(
				'<tr><td class="athletix-standings__team">%s</td><td>%s</td><td class="athletix-standings__pts">%s</td></tr>',
				esc_html( wp_date( 'Y-m-d', (int) $record['time'] ) ),
				esc_html( (string) $record['note'] ),
				esc_html( number_format_i18n( (float) $record['amount'], 2 ) )
			);
		}
		echo '</tbody><tfoot><tr><th class="athletix-standings__team" colspan="2">' . esc_html__( 'Total', 'athletix' ) . '</th><th class="athletix-standings__pts">' . esc_html( number_format_i18n( $this->ledger->total( $team ), 2 ) ) . '</th></tr></tfoot>';
		echo '</table></div>';

		return (string) ob_get_clean();
	}
}
