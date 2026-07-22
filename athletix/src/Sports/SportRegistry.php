<?php
/**
 * Sport profile registry.
 *
 * @package Athletix
 */

namespace Athletix\Sports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\SportProfile;
use Athletix\Sports\Profiles\GenericProfile;
use Athletix\Sports\Profiles\SoccerProfile;

/**
 * Holds the available sport profiles. Ships Soccer + a Generic fallback; third
 * parties add their own through the `athletix/register_sports` filter, so a new
 * sport never requires editing core.
 */
class SportRegistry {

	/**
	 * Profiles keyed by slug.
	 *
	 * @var array<string,SportProfile>
	 */
	private $profiles;

	/**
	 * Constructor — collect the profiles.
	 */
	public function __construct() {
		$defaults = array(
			'soccer'  => new SoccerProfile(),
			'generic' => new GenericProfile(),
		);

		/**
		 * Filter the registered sport profiles.
		 *
		 * @param array<string,SportProfile> $profiles Slug => profile.
		 */
		$profiles = (array) apply_filters( 'athletix/register_sports', $defaults );

		// Keep only valid profiles; guarantee the generic fallback exists.
		$this->profiles = array();
		foreach ( $profiles as $slug => $profile ) {
			if ( $profile instanceof SportProfile ) {
				$this->profiles[ (string) $slug ] = $profile;
			}
		}
		if ( ! isset( $this->profiles['generic'] ) ) {
			$this->profiles['generic'] = new GenericProfile();
		}
	}

	/**
	 * Get a profile by slug, falling back to the generic profile.
	 *
	 * @param string $slug Sport slug.
	 * @return SportProfile
	 */
	public function get( $slug ) {
		$slug = (string) $slug;

		return isset( $this->profiles[ $slug ] ) ? $this->profiles[ $slug ] : $this->profiles['generic'];
	}

	/**
	 * Whether a profile is registered for a slug.
	 *
	 * @param string $slug Sport slug.
	 * @return bool
	 */
	public function has( $slug ) {
		return isset( $this->profiles[ (string) $slug ] );
	}

	/**
	 * All registered profiles.
	 *
	 * @return array<string,SportProfile>
	 */
	public function all() {
		return $this->profiles;
	}
}
