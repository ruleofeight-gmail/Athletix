<?php
/**
 * Recurring subscriptions.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records recurring charges and, on the daily automation tick, posts a ledger
 * entry for any that are due. No card processing occurs — settlement is manual
 * (or handled by a registered gateway).
 */
class Subscriptions {

	const OPTION = 'athletix_subscriptions';

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
	 * Register the daily processor.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'athletix/daily', array( $this, 'process_due' ) );
	}

	/**
	 * All subscriptions.
	 *
	 * @return array[]
	 */
	public function all() {
		$subs = get_option( self::OPTION, array() );
		return is_array( $subs ) ? $subs : array();
	}

	/**
	 * Create a subscription.
	 *
	 * @param int   $entity_id     Entity id.
	 * @param float $amount        Charge amount.
	 * @param int   $interval_days Billing interval in days.
	 * @return void
	 */
	public function subscribe( $entity_id, $amount, $interval_days = 30 ) {
		$subs   = $this->all();
		$subs[] = array(
			'entity'   => absint( $entity_id ),
			'amount'   => round( (float) $amount, 2 ),
			'interval' => max( 1, absint( $interval_days ) ),
			'next_due' => gmdate( 'Y-m-d' ),
			'active'   => true,
		);

		update_option( self::OPTION, array_values( $subs ) );
	}

	/**
	 * Cancel a subscription by index.
	 *
	 * @param int $index Subscription index.
	 * @return void
	 */
	public function cancel( $index ) {
		$subs = $this->all();
		if ( isset( $subs[ $index ] ) ) {
			$subs[ $index ]['active'] = false;
			update_option( self::OPTION, array_values( $subs ) );
		}
	}

	/**
	 * Post ledger entries for any due subscriptions and advance their dates.
	 *
	 * @return void
	 */
	public function process_due() {
		$subs    = $this->all();
		$today   = gmdate( 'Y-m-d' );
		$changed = false;

		foreach ( $subs as $index => $sub ) {
			if ( empty( $sub['active'] ) || $sub['next_due'] > $today ) {
				continue;
			}

			$this->ledger->record( $sub['entity'], $sub['amount'], __( 'Subscription charge', 'athletix' ) );
			$subs[ $index ]['next_due'] = gmdate( 'Y-m-d', strtotime( $today . ' +' . (int) $sub['interval'] . ' days' ) );
			$changed                    = true;
		}

		if ( $changed ) {
			update_option( self::OPTION, $subs );
		}
	}
}
