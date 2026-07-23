<?php
/**
 * Soccer sport profile.
 *
 * @package Athletix
 */

namespace Athletix\Sports\Profiles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\SportProfile;

/**
 * The default profile — encodes Athletix's historical soccer behaviour (3/1/0
 * points, goal-based tie-breakers and metrics), so shipping the sport-profile
 * system changes nothing on an existing site.
 */
class SoccerProfile implements SportProfile {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function slug() {
		return 'soccer';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Soccer', 'athletix' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,array>
	 */
	public function metrics() {
		return array(
			'goals'        => array( 'label' => __( 'Goals', 'athletix' ) ),
			'assists'      => array( 'label' => __( 'Assists', 'athletix' ) ),
			'yellow_cards' => array( 'label' => __( 'Yellow Cards', 'athletix' ) ),
			'red_cards'    => array( 'label' => __( 'Red Cards', 'athletix' ) ),
			'saves'        => array( 'label' => __( 'Saves', 'athletix' ) ),
			'minutes'      => array( 'label' => __( 'Minutes', 'athletix' ) ),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string[]
	 */
	public function positions() {
		return array( 'GK', 'DF', 'MF', 'FW' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,string>
	 */
	public function labels() {
		return array(
			'match' => __( 'Match', 'athletix' ),
			'score' => __( 'Goals', 'athletix' ),
		);
	}
}
