<?php
/**
 * Generic sport profile (Null Object).
 *
 * @package Athletix
 */

namespace Athletix\Sports\Profiles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\SportProfile;
use Athletix\Engine\StandingsSorter;

/**
 * The safe fallback returned when a requested sport slug is unknown, so no code
 * path ever fatals on a missing profile. Sensible neutral defaults.
 */
class GenericProfile implements SportProfile {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function slug() {
		return 'generic';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function label() {
		return __( 'Generic', 'athletix' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function scoring() {
		return array(
			'win'           => 3,
			'draw'          => 1,
			'loss'          => 0,
			'draws_allowed' => true,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string[]
	 */
	public function tiebreakers() {
		return StandingsSorter::DEFAULT_CHAIN;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,array>
	 */
	public function metrics() {
		return array(
			'points' => array( 'label' => __( 'Points', 'athletix' ) ),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string[]
	 */
	public function positions() {
		return array();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,string>
	 */
	public function labels() {
		return array(
			'match' => __( 'Match', 'athletix' ),
			'score' => __( 'Score', 'athletix' ),
		);
	}
}
