<?php
/**
 * Manual payment ledger.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;

/**
 * Records manual payments against an entity (e.g. a team's registration fee).
 *
 * Intentionally does NOT process cards or store payment credentials — real
 * gateways integrate via the `athletix/payment_recorded` event and the
 * `athletix/payment_gateways` filter. This keeps the plugin PCI-safe while
 * still providing a usable ledger.
 */
class Ledger {

	const META = '_ax_payments';

	/**
	 * Events.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Constructor.
	 *
	 * @param Events $events Events.
	 */
	public function __construct( Events $events ) {
		$this->events = $events;
	}

	/**
	 * Record a payment against an entity.
	 *
	 * @param int    $entity_id Post id (team/player).
	 * @param float  $amount    Amount.
	 * @param string $note      Optional note.
	 * @return void
	 */
	public function record( $entity_id, $amount, $note = '' ) {
		$entity_id = absint( $entity_id );
		$records   = $this->records( $entity_id );

		$records[] = array(
			'amount' => round( (float) $amount, 2 ),
			'note'   => sanitize_text_field( $note ),
			'user'   => get_current_user_id(),
			'time'   => time(),
		);

		update_post_meta( $entity_id, self::META, $records );

		$this->events->fire(
			'payment_recorded',
			array(
				'entity_id' => $entity_id,
				'amount'    => round( (float) $amount, 2 ),
			)
		);
	}

	/**
	 * Record a refund (a negative ledger entry).
	 *
	 * @param int    $entity_id Entity id.
	 * @param float  $amount    Positive amount to refund.
	 * @param string $note      Optional note.
	 * @return void
	 */
	public function refund( $entity_id, $amount, $note = '' ) {
		$amount = abs( (float) $amount );
		$this->record( $entity_id, -$amount, '' !== $note ? $note : __( 'Refund', 'athletix' ) );
	}

	/**
	 * All payment records for an entity.
	 *
	 * @param int $entity_id Post id.
	 * @return array[]
	 */
	public function records( $entity_id ) {
		$records = get_post_meta( absint( $entity_id ), self::META, true );
		return is_array( $records ) ? $records : array();
	}

	/**
	 * Total paid for an entity.
	 *
	 * @param int $entity_id Post id.
	 * @return float
	 */
	public function total( $entity_id ) {
		$total = 0.0;
		foreach ( $this->records( $entity_id ) as $record ) {
			$total += (float) $record['amount'];
		}
		return round( $total, 2 );
	}

	/**
	 * Whether the entity has met a fee threshold.
	 *
	 * @param int   $entity_id Post id.
	 * @param float $fee       Required amount.
	 * @return bool
	 */
	public function is_paid( $entity_id, $fee ) {
		return $this->total( $entity_id ) + 0.0001 >= (float) $fee;
	}
}
