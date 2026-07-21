<?php
/**
 * Converted admin-table screens integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Automation\RuleManager;
use Athletix\Automation\RulesAdmin;
use Athletix\Automation\TemplateEngine;
use Athletix\Membership\RegistrationAdmin;
use Athletix\Support\Keys;

/**
 * Confirms the admin screens migrated onto the Table contract still produce the
 * right rows and per-row action forms.
 */
class AdminTablesConversionTest extends IntegrationTestCase {

	/**
	 * Registrations: rows list pending teams and actions carry the team id.
	 *
	 * @return void
	 */
	public function test_registration_admin_rows_and_actions() {
		$team = self::factory()->post->create(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'draft',
				'post_title'  => 'Pending Rovers',
			)
		);
		update_post_meta( $team, '_ax_registration_status', 'pending' );
		update_post_meta( $team, '_ax_registration_contact', 'coach@example.com' );

		$admin = new RegistrationAdmin( $this->plugin() );
		$rows  = $admin->rows();

		$this->assertNotEmpty( $rows );
		$row = $rows[0];
		$this->assertSame( 'Pending Rovers', $row['team'] );
		$this->assertSame( 'coach@example.com', $row['contact'] );
		$this->assertSame( $team, $row['team_id'] );

		$actions = $admin->render_actions( $row );
		$this->assertStringContainsString( RegistrationAdmin::ACTION_APPROVE, $actions );
		$this->assertStringContainsString( RegistrationAdmin::ACTION_REJECT, $actions );
		$this->assertStringContainsString( 'value="' . $team . '"', $actions );
	}

	/**
	 * Rules: rows map event/action to labels and keep the index for deletion.
	 *
	 * @return void
	 */
	public function test_rules_admin_rows_and_actions() {
		$manager = new RuleManager( $this->plugin()->events(), new TemplateEngine(), $this->plugin()->logger() );

		$events  = RuleManager::events();
		$actions = RuleManager::actions();
		$event   = (string) array_key_first( $events );
		$action  = (string) array_key_first( $actions );

		$manager->add(
			array(
				'event'   => $event,
				'action'  => $action,
				'subject' => 'A test rule',
			)
		);

		$admin = new RulesAdmin( $manager );
		$rows  = $admin->rows();

		$this->assertNotEmpty( $rows );
		$row = $rows[0];
		$this->assertSame( 0, $row['index'] );
		$this->assertSame( $event, $row['event'] );
		$this->assertSame( $events[ $event ], $row['when'] );
		$this->assertSame( 'A test rule', $row['details'] );

		$delete = $admin->render_actions( $row );
		$this->assertStringContainsString( RulesAdmin::ACTION_DELETE, $delete );
		$this->assertStringContainsString( 'name="index"', $delete );
	}
}
