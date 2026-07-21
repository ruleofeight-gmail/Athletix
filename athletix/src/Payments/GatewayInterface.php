<?php
/**
 * Payment gateway contract.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The integration surface for payment gateways.
 *
 * The plugin ships only an offline gateway and never handles card data itself;
 * a real processor (Stripe, PayPal, …) is added by implementing this interface
 * and registering it on the `athletix/payment_gateways` filter.
 */
interface GatewayInterface {

	/**
	 * Machine id for the gateway.
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Begin a charge for an entity. Implementations return a result array with
	 * at least a 'status' key (paid|pending|failed) and an optional 'redirect'.
	 *
	 * @param int   $entity_id Entity being charged (e.g. a team).
	 * @param float $amount    Amount.
	 * @param array $context   Extra context.
	 * @return array{status:string,redirect?:string,message?:string}
	 */
	public function charge( $entity_id, $amount, array $context = array() );
}
