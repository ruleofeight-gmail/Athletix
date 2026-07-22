<?php
/**
 * Sport profile contract.
 *
 * @package Athletix
 */

namespace Athletix\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A sport profile declares the differences between sports — scoring, standings
 * tie-breakers, the statistics tracked, player positions and terminology — so
 * the rest of the plugin reads sport-specific behaviour from one place. Profiles
 * are registered in the SportRegistry and resolved per league.
 */
interface SportProfile {

	/**
	 * Machine slug, e.g. "soccer".
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * Human label, e.g. "Soccer".
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Points scheme: win/draw/loss ints plus draws_allowed bool.
	 *
	 * @return array
	 */
	public function scoring();

	/**
	 * Ordered standings tie-break chain (StandingsSorter field names).
	 *
	 * @return string[]
	 */
	public function tiebreakers();

	/**
	 * Per-match statistics metrics: slug => [ 'label' => string ].
	 *
	 * @return array<string,array>
	 */
	public function metrics();

	/**
	 * Player positions offered for this sport.
	 *
	 * @return string[]
	 */
	public function positions();

	/**
	 * Terminology overrides, e.g. [ 'score' => 'Goals', 'match' => 'Match' ].
	 *
	 * @return array<string,string>
	 */
	public function labels();
}
