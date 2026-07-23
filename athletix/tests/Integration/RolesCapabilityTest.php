<?php
/**
 * Roles / capability grant integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Security\Roles;

/**
 * Guards the capability that gates the Athletix admin menus (Leagues, Seasons,
 * Divisions, Sports and the Customize screens): administrators must hold
 * manage_athletix, and Roles::ensure() must (re)grant it — otherwise a whole
 * block of the admin UI disappears.
 */
class RolesCapabilityTest extends IntegrationTestCase {

	/**
	 * Ensuring roles grants manage_athletix to the administrator, idempotently.
	 *
	 * @return void
	 */
	public function test_ensure_grants_manage_athletix_to_admin() {
		$admin = get_role( 'administrator' );
		$admin->remove_cap( Roles::CAP );
		$this->assertFalse( get_role( 'administrator' )->has_cap( Roles::CAP ) );

		( new Roles() )->ensure();

		$this->assertTrue( get_role( 'administrator' )->has_cap( Roles::CAP ), 'Administrator regains manage_athletix.' );
	}

	/**
	 * The League Manager role exists and carries the capability.
	 *
	 * @return void
	 */
	public function test_league_manager_role_has_capability() {
		( new Roles() )->ensure();

		$manager = get_role( Roles::ROLE );
		$this->assertNotNull( $manager, 'The League Manager role exists.' );
		$this->assertTrue( $manager->has_cap( Roles::CAP ) );
	}

	/**
	 * A subscriber does not get the capability.
	 *
	 * @return void
	 */
	public function test_subscriber_lacks_capability() {
		( new Roles() )->ensure();

		$subscriber = get_role( 'subscriber' );
		$this->assertFalse( $subscriber->has_cap( Roles::CAP ) );
	}
}
