<?php
/**
 * Tests for AdminMenu submenu grouping.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Admin\AdminMenu;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the pure submenu ordering that groups the Athletix admin menu.
 */
class AdminMenuTest extends TestCase {

	/**
	 * Build a fake submenu item (label, cap, slug).
	 *
	 * @param string $slug Menu slug.
	 * @return array
	 */
	private function item( $slug ) {
		return array( 'Label ' . $slug, 'edit_posts', $slug );
	}

	/**
	 * Items are reordered to match the desired grouping.
	 *
	 * @return void
	 */
	public function test_sorts_into_desired_order() {
		$order = array( 'athletix', 'edit.php?post_type=ax_league', 'athletix-settings' );

		// Deliberately shuffled input.
		$items = array(
			$this->item( 'athletix-settings' ),
			$this->item( 'edit.php?post_type=ax_league' ),
			$this->item( 'athletix' ),
		);

		$sorted = AdminMenu::sort_items( $items, $order );

		$this->assertSame(
			array( 'athletix', 'edit.php?post_type=ax_league', 'athletix-settings' ),
			array_column( $sorted, 2 )
		);
	}

	/**
	 * Unlisted items keep their relative order and land after the grouped ones.
	 *
	 * @return void
	 */
	public function test_unlisted_items_go_last_in_stable_order() {
		$order = array( 'athletix', 'athletix-settings' );

		$items = array(
			$this->item( 'zzz-third-party-a' ),
			$this->item( 'athletix-settings' ),
			$this->item( 'zzz-third-party-b' ),
			$this->item( 'athletix' ),
		);

		$sorted = AdminMenu::sort_items( $items, $order );

		$this->assertSame(
			array( 'athletix', 'athletix-settings', 'zzz-third-party-a', 'zzz-third-party-b' ),
			array_column( $sorted, 2 )
		);
	}

	/**
	 * An empty menu sorts to an empty menu without error.
	 *
	 * @return void
	 */
	public function test_empty_is_safe() {
		$this->assertSame( array(), AdminMenu::sort_items( array(), array( 'athletix' ) ) );
	}
}
