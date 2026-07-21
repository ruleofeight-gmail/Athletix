<?php
/**
 * Team eligibility checks.
 *
 * @package Athletix
 */

namespace Athletix\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Determines whether a team meets the requirements to compete: waiver accepted
 * and the registration fee (if any) paid.
 */
class Eligibility {

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
	 * Whether a team is eligible, with the reasons it is not.
	 *
	 * @param int $team_id Team id.
	 * @return array{eligible:bool,reasons:string[]}
	 */
	public function check( $team_id ) {
		$team_id = absint( $team_id );
		$reasons = array();

		if ( ! get_post_meta( $team_id, '_ax_waiver_accepted', true ) ) {
			$reasons[] = __( 'Waiver not accepted', 'athletix' );
		}

		$fee = (float) $this->plugin->config()->get( 'registration_fee', 0 );
		if ( $fee > 0 && $this->plugin->container()->has( 'payments.ledger' ) ) {
			$paid = $this->plugin->make( 'payments.ledger' )->is_paid( $team_id, $fee );
			if ( ! $paid ) {
				$reasons[] = __( 'Registration fee unpaid', 'athletix' );
			}
		}

		/**
		 * Filter a team's eligibility reasons.
		 *
		 * @param string[] $reasons Blocking reasons (empty = eligible).
		 * @param int      $team_id Team id.
		 */
		$reasons = apply_filters( 'athletix/eligibility_reasons', $reasons, $team_id );

		return array(
			'eligible' => empty( $reasons ),
			'reasons'  => $reasons,
		);
	}

	/**
	 * Convenience boolean check.
	 *
	 * @param int $team_id Team id.
	 * @return bool
	 */
	public function is_eligible( $team_id ) {
		$result = $this->check( $team_id );
		return $result['eligible'];
	}
}
