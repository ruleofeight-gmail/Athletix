<?php
/**
 * Offline (manual) payment gateway.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records a payment as pending manual settlement — no card data is processed.
 * This is the default, always-available gateway.
 */
class OfflineGateway implements GatewayInterface {

	/**
	 * Gateway id.
	 *
	 * @return string
	 */
	public function id() {
		return 'offline';
	}

	/**
	 * Gateway label.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Offline / Manual', 'athletix' );
	}

	/**
	 * "Charge" simply marks the payment as pending manual entry.
	 *
	 * @param int   $entity_id Entity id.
	 * @param float $amount    Amount.
	 * @param array $context   Context.
	 * @return array
	 */
	public function charge( $entity_id, $amount, array $context = array() ) {
		unset( $entity_id, $amount, $context );

		return array(
			'status'  => 'pending',
			'message' => __( 'Awaiting manual payment.', 'athletix' ),
		);
	}
}
