<?php
/**
 * Capabilities and roles.
 *
 * @package Athletix
 */

namespace Athletix\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Introduces a dedicated `manage_athletix` capability (granted to
 * administrators) and a "League Manager" role, so league operations are not
 * tied to the broad `manage_options`.
 */
class Roles {

	const CAP  = 'manage_athletix';
	const ROLE = 'athletix_manager';

	/**
	 * Grant the capability/role. Idempotent; safe to run on every load.
	 *
	 * @return void
	 */
	public function ensure() {
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::CAP ) ) {
			$admin->add_cap( self::CAP );
		}

		if ( ! get_role( self::ROLE ) ) {
			add_role(
				self::ROLE,
				__( 'League Manager', 'athletix' ),
				array(
					'read'          => true,
					'upload_files'  => true,
					self::CAP       => true,
					'edit_posts'    => true,
					'publish_posts' => true,
					'delete_posts'  => true,
				)
			);
		}
	}

	/**
	 * Dynamically grant manage_athletix to anyone who can manage_options.
	 *
	 * The role-based grant in ensure() writes the capability to the administrator
	 * role, but that write can be missed (an install where the activation/boot
	 * grant never ran, a migrated or role-managed site). Because every part of the
	 * admin UI is gated on manage_athletix, a missing grant silently hides whole
	 * screens and hub tabs. Mapping the capability here — so any user with the
	 * core manage_options capability is treated as having manage_athletix — makes
	 * that gate reliable for every administrator, on every request, regardless of
	 * what the stored role option says.
	 *
	 * @param array $allcaps All capabilities the user currently has.
	 * @return array
	 */
	public function grant_to_admins( $allcaps ) {
		if ( ! empty( $allcaps['manage_options'] ) ) {
			$allcaps[ self::CAP ] = true;
		}

		return $allcaps;
	}

	/**
	 * Remove the custom role (used on uninstall paths). Capability on the
	 * administrator is left intact.
	 *
	 * @return void
	 */
	public function remove() {
		remove_role( self::ROLE );
	}
}
