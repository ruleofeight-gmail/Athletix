<?php
/**
 * Admin menu structure integration test.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Integration;

use Athletix\Admin\Hub;
use Athletix\Support\Keys;

/**
 * Verifies the top-level "Athletix - X" content menus and the tabbed hub.
 */
class AdminMenuStructureTest extends IntegrationTestCase {

	/**
	 * Each content type carries the "Athletix - X" menu name and a de-"All"ed
	 * list submenu label.
	 *
	 * @return void
	 */
	public function test_content_type_menu_labels() {
		$league = get_post_type_object( Keys::LEAGUE );

		$this->assertSame( 'Athletix - Leagues', $league->labels->menu_name );
		$this->assertSame( 'Leagues', $league->labels->all_items, 'The list submenu drops "All".' );
		$this->assertSame( 'Add New League', $league->labels->add_new );

		$team = get_post_type_object( Keys::TEAM );
		$this->assertSame( 'Athletix - Teams', $team->labels->menu_name );
		$this->assertSame( 'Teams', $team->labels->all_items );

		// Announcements is registered separately but follows the same pattern.
		$ann = get_post_type_object( 'ax_announcement' );
		$this->assertSame( 'Athletix - Announcements', $ann->labels->menu_name );
	}

	/**
	 * The hub orders tabs by their "order" and builds tab URLs.
	 *
	 * @return void
	 */
	public function test_hub_tab_ordering_and_urls() {
		$hub = new Hub( $this->plugin() );

		add_filter( 'athletix/admin_tabs', array( $hub, 'dashboard_tab' ), 0 );
		add_filter(
			'athletix/admin_tabs',
			static function ( $tabs ) {
				$tabs['late'] = array(
					'label'    => 'Late',
					'cap'      => 'edit_posts',
					'order'    => 90,
					'callback' => '__return_null',
				);
				$tabs['mid']  = array(
					'label'    => 'Mid',
					'cap'      => 'edit_posts',
					'order'    => 40,
					'callback' => '__return_null',
				);
				return $tabs;
			}
		);

		$slugs = array_keys( $hub->tabs() );

		$this->assertSame( 'dashboard', $slugs[0], 'Dashboard (order 0) is first.' );
		$this->assertSame( array( 'dashboard', 'mid', 'late' ), $slugs );

		$url = Hub::tab_url( 'settings' );
		$this->assertStringContainsString( 'page=athletix', $url );
		$this->assertStringContainsString( 'tab=settings', $url );
	}
}
