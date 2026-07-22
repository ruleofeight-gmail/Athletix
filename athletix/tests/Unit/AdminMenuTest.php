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
	 * @param string $cap  Capability.
	 * @return array
	 */
	private function item( $slug, $cap = 'edit_posts' ) {
		return array( 'Label ' . $slug, $cap, $slug );
	}

	/**
	 * Grouping injects a heading row before each non-empty labelled group and
	 * appends unlisted items.
	 *
	 * @return void
	 */
	public function test_build_grouped_injects_headers() {
		$groups = array(
			array(
				'label' => '',
				'slugs' => array( 'athletix' ),
			),
			array(
				'label' => 'Structure',
				'slugs' => array( 'edit.php?post_type=ax_team', 'edit.php?post_type=ax_player' ),
			),
			array(
				'label' => 'Tools',
				'slugs' => array( 'athletix-settings' ),
			),
		);

		$items = array(
			$this->item( 'athletix-settings', 'manage_athletix' ),
			$this->item( 'edit.php?post_type=ax_player' ),
			$this->item( 'athletix' ),
			$this->item( 'edit.php?post_type=ax_team' ),
			$this->item( 'zzz-third-party' ), // not in any group
		);

		$out   = AdminMenu::build_grouped( $items, $groups );
		$slugs = array_column( $out, 2 );

		// Dashboard first (no heading for empty-label group), then Structure heading,
		// its two items, Tools heading, its item, then the unlisted item last.
		$this->assertSame(
			array(
				'athletix',
				'edit.php?post_type=ax_team',   // heading slug mirrors first member
				'edit.php?post_type=ax_team',
				'edit.php?post_type=ax_player',
				'athletix-settings',            // Tools heading
				'athletix-settings',
				'zzz-third-party',
			),
			$slugs
		);

		// Heading rows carry the section CSS class at index 4; real items do not.
		$this->assertSame( AdminMenu::SECTION_CLASS, $out[1][4] );
		$this->assertArrayNotHasKey( 4, $out[2] );

		// The Structure heading inherits its first member's capability.
		$this->assertSame( 'edit_posts', $out[1][1] );
		// The Tools heading inherits the manager capability.
		$this->assertSame( 'manage_athletix', $out[4][1] );
	}

	/**
	 * A group with no present items produces no heading.
	 *
	 * @return void
	 */
	public function test_build_grouped_skips_empty_groups() {
		$groups = array(
			array(
				'label' => 'Structure',
				'slugs' => array( 'edit.php?post_type=ax_team' ),
			),
			array(
				'label' => 'Empty',
				'slugs' => array( 'nonexistent' ),
			),
		);

		$out = AdminMenu::build_grouped( array( $this->item( 'edit.php?post_type=ax_team' ) ), $groups );

		$this->assertSame(
			array( 'edit.php?post_type=ax_team', 'edit.php?post_type=ax_team' ),
			array_column( $out, 2 )
		);
		$this->assertCount( 2, $out, 'One heading + one item; the empty group is skipped.' );
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
