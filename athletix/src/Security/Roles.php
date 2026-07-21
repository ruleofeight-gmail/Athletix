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
					'read'            => true,
					'upload_files'    => true,
					self::CAP         => true,
					'edit_posts'      => true,
					'publish_posts'   => true,
					'delete_posts'    => true,
				)
			);
		}
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
