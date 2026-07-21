<?php
/**
 * Top-level Athletix admin menu and submenu grouping.
 *
 * @package Athletix
 */

namespace Athletix\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Dashboard\DashboardPage;
use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Owns the single "Athletix" top-level menu. Every plugin screen — the content
 * post types and the manager tool pages — hangs off it, and this class sorts
 * those submenus into logical groups (overview, structure, competition, people
 * & payments, communication, then tools) regardless of the order in which they
 * happen to register.
 */
class AdminMenu {

	/**
	 * Capability that reveals the top-level menu. Kept at the post-editing
	 * capability so content editors still reach the post types; the individual
	 * manager tool pages enforce their own stricter capability.
	 */
	const MENU_CAP = 'edit_posts';

	/**
	 * Plugin instance (used to render the dashboard landing page).
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Priority 9: create the parent before the tool pages (priority 10) attach.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 9 );
		// Very late: regroup the whole submenu once every screen has registered.
		add_action( 'admin_menu', array( $this, 'group_submenus' ), PHP_INT_MAX );
	}

	/**
	 * Register the top-level menu. Its landing page is the dashboard.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Athletix', 'athletix' ),
			__( 'Athletix', 'athletix' ),
			self::MENU_CAP,
			Keys::MENU,
			array( $this, 'render_dashboard' ),
			'dashicons-awards',
			26
		);
	}

	/**
	 * Render the dashboard as the menu's landing page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		( new DashboardPage( $this->plugin ) )->render();
	}

	/**
	 * The desired submenu order, by submenu slug.
	 *
	 * @return string[]
	 */
	private function order() {
		$order = array(
			// Overview.
			Keys::MENU,
			// Competition structure.
			'edit.php?post_type=' . Keys::LEAGUE,
			'edit.php?post_type=' . Keys::SEASON,
			'edit.php?post_type=' . Keys::DIVISION,
			'edit.php?post_type=' . Keys::TEAM,
			'edit.php?post_type=' . Keys::PLAYER,
			'edit.php?post_type=' . Keys::MATCH,
			// Competition management.
			'athletix-competitions',
			// People & payments.
			'athletix-registrations',
			'athletix-financials',
			// Communication.
			'edit.php?post_type=ax_announcement',
			// Automation & tools.
			'athletix-rules',
			'athletix-import-export',
			'athletix-settings',
		);

		/**
		 * Filter the Athletix submenu order (list of submenu slugs).
		 *
		 * @param string[] $order Submenu slugs in display order.
		 */
		return (array) apply_filters( 'athletix/admin_menu_order', $order );
	}

	/**
	 * Relabel the landing item and sort the submenu into the grouped order.
	 *
	 * @return void
	 */
	public function group_submenus() {
		global $submenu;

		if ( empty( $submenu[ Keys::MENU ] ) ) {
			return;
		}

		// The auto-created first item duplicates the menu title; name it "Dashboard".
		foreach ( $submenu[ Keys::MENU ] as &$item ) {
			if ( isset( $item[2] ) && Keys::MENU === $item[2] ) {
				$item[0] = __( 'Dashboard', 'athletix' );
			}
		}
		unset( $item );

		// Reordering the admin menu is the whole point of this method.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ Keys::MENU ] = self::sort_items( $submenu[ Keys::MENU ], $this->order() );
	}

	/**
	 * Stable-sort submenu items by their slug against a desired order.
	 *
	 * Items whose slug is not listed keep their relative position at the end, so
	 * a screen added by a third party is never dropped — only ungrouped.
	 *
	 * @param array[]  $items Submenu item arrays (slug at index 2).
	 * @param string[] $order Desired slug order.
	 * @return array[]
	 */
	public static function sort_items( array $items, array $order ) {
		$rank = array_flip( $order );

		$decorated = array();
		foreach ( $items as $index => $item ) {
			$slug        = isset( $item[2] ) ? $item[2] : '';
			$position    = isset( $rank[ $slug ] ) ? $rank[ $slug ] : count( $order ) + $index;
			$decorated[] = array( $position, $index, $item );
		}

		usort(
			$decorated,
			static function ( $a, $b ) {
				$primary = $a[0] <=> $b[0];

				return 0 !== $primary ? $primary : $a[1] <=> $b[1];
			}
		);

		return array_column( $decorated, 2 );
	}
}
