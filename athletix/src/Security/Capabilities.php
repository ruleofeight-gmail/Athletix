<?php
/**
 * Granular capabilities.
 *
 * @package Athletix
 */

namespace Athletix\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines fine-grained capabilities and grants them to the appropriate roles,
 * layered on top of the broad manage_athletix capability from Roles.
 */
class Capabilities {

	const MANAGE_COMPETITIONS = 'athletix_manage_competitions';
	const MANAGE_PAYMENTS     = 'athletix_manage_payments';
	const MANAGE_MEMBERS      = 'athletix_manage_members';
	const EDIT_MATCHES        = 'athletix_edit_matches';
	const VIEW_REPORTS        = 'athletix_view_reports';

	/**
	 * Every granular capability.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array(
			self::MANAGE_COMPETITIONS,
			self::MANAGE_PAYMENTS,
			self::MANAGE_MEMBERS,
			self::EDIT_MATCHES,
			self::VIEW_REPORTS,
		);
	}

	/**
	 * Grant capabilities to roles. Idempotent.
	 *
	 * @return void
	 */
	public function ensure() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::all() as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}

		// League managers get everything except payments.
		$manager = get_role( Roles::ROLE );
		$for_mgr = array_diff( self::all(), array( self::MANAGE_PAYMENTS ) );
		if ( $manager ) {
			foreach ( $for_mgr as $cap ) {
				if ( ! $manager->has_cap( $cap ) ) {
					$manager->add_cap( $cap );
				}
			}
		}
	}
}
