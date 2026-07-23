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
 * A sport profile declares the descriptive differences between sports — the
 * statistics tracked, player positions and terminology — so the rest of the
 * plugin reads sport-specific metadata from one place. Standings scoring and
 * tie-breaks are configured separately (Athletix → Customize). Profiles are
 * registered in the SportRegistry and resolved per league.
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
