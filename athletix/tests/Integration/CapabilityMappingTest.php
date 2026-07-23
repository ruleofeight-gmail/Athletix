<?php
/**
 * Capability-mapping integration test for manage_athletix.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Security\Roles;

/**
 * Confirms manage_athletix is reliably available to administrators through the
 * user_has_cap mapping, even if the stored role grant is missing — the gate the
 * hub tabs and Customize/taxonomy menus depend on.
 */
class CapabilityMappingTest extends IntegrationTestCase {

	/**
	 * An administrator resolves manage_athletix even with the role cap stripped.
	 *
	 * @return void
	 */
	public function test_admin_gets_manage_athletix_via_mapping() {
		// Strip the stored grant so only the user_has_cap mapping can supply it.
		get_role( 'administrator' )->remove_cap( Roles::CAP );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->assertTrue( current_user_can( 'manage_options' ) );
		$this->assertTrue( current_user_can( Roles::CAP ), 'Admins map to manage_athletix via manage_options.' );
	}

	/**
	 * A subscriber (no manage_options) does not gain manage_athletix.
	 *
	 * @return void
	 */
	public function test_subscriber_does_not_get_manage_athletix() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( current_user_can( 'manage_options' ) );
		$this->assertFalse( current_user_can( Roles::CAP ) );
	}

	/**
	 * The mapping itself only adds the cap when manage_options is present.
	 *
	 * @return void
	 */
	public function test_grant_to_admins_is_conditional() {
		$roles = new Roles();

		$with    = $roles->grant_to_admins( array( 'manage_options' => true ) );
		$without = $roles->grant_to_admins( array( 'read' => true ) );

		$this->assertTrue( $with[ Roles::CAP ] );
		$this->assertArrayNotHasKey( Roles::CAP, $without );
	}
}
