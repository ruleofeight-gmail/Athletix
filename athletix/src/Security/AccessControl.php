<?php
/**
 * Access control gate.
 *
 * @package Athletix
 */

namespace Athletix\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single, filterable permission gate. Modules call can() instead of
 * current_user_can() directly, so a site can layer entity-level rules (e.g. a
 * team manager who may only edit their own team) via the athletix/user_can
 * filter without those modules changing.
 */
class AccessControl {

	/**
	 * Whether the current user may perform a capability, optionally against a
	 * specific object.
	 *
	 * @param string $capability Capability slug.
	 * @param int    $object_id  Optional entity id the action targets.
	 * @return bool
	 */
	public function can( $capability, $object_id = 0 ) {
		$allowed = current_user_can( $capability );

		/**
		 * Filter an Athletix permission decision.
		 *
		 * @param bool   $allowed    Whether WordPress granted the capability.
		 * @param string $capability Capability slug.
		 * @param int    $object_id  Target entity id (0 if none).
		 * @param int    $user_id    Current user id.
		 */
		return (bool) apply_filters( 'athletix/user_can', $allowed, $capability, absint( $object_id ), get_current_user_id() );
	}

	/**
	 * Convenience: whether the current user may manage the plugin at all.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return $this->can( Roles::CAP );
	}
}
