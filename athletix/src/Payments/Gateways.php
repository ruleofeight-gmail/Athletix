<?php
/**
 * Gateway registry.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects the available payment gateways. The offline gateway is always
 * present; others register via the `athletix/payment_gateways` filter.
 */
class Gateways {

	/**
	 * All registered gateways keyed by id.
	 *
	 * @return GatewayInterface[]
	 */
	public static function all() {
		$gateways = array( ( new OfflineGateway() ) );

		/**
		 * Filter the registered payment gateways.
		 *
		 * @param GatewayInterface[] $gateways Gateway instances.
		 */
		$gateways = apply_filters( 'athletix/payment_gateways', $gateways );

		$map = array();
		foreach ( $gateways as $gateway ) {
			if ( $gateway instanceof GatewayInterface ) {
				$map[ $gateway->id() ] = $gateway;
			}
		}

		return $map;
	}
}
