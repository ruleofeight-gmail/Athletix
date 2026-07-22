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
		// Style the section headers injected into the submenu.
		add_action( 'admin_head', array( $this, 'print_styles' ) );
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
	 * CSS class placed on the section-header submenu rows (WordPress applies a
	 * submenu item's 5th element as a class on its <li>).
	 */
	const SECTION_CLASS = 'athletix-menu-section';

	/**
	 * The submenu groups, in display order: a heading label plus the slugs that
	 * belong under it. An empty label renders no heading (used for the dashboard).
	 *
	 * @return array[]
	 */
	private function groups() {
		$groups = array(
			array(
				'label' => '',
				'slugs' => array( Keys::MENU ),
			),
			array(
				'label' => __( 'Structure', 'athletix' ),
				'slugs' => array(
					'edit.php?post_type=' . Keys::LEAGUE,
					'edit.php?post_type=' . Keys::SEASON,
					'edit.php?post_type=' . Keys::DIVISION,
					'edit.php?post_type=' . Keys::TEAM,
					'edit.php?post_type=' . Keys::PLAYER,
					'edit.php?post_type=' . Keys::MATCH,
				),
			),
			array(
				'label' => __( 'Competition', 'athletix' ),
				'slugs' => array( 'athletix-competitions' ),
			),
			array(
				'label' => __( 'People & Payments', 'athletix' ),
				'slugs' => array( 'athletix-registrations', 'athletix-financials' ),
			),
			array(
				'label' => __( 'Communication', 'athletix' ),
				'slugs' => array( 'edit.php?post_type=ax_announcement' ),
			),
			array(
				'label' => __( 'Automation & Tools', 'athletix' ),
				'slugs' => array( 'athletix-rules', 'athletix-import-export', 'athletix-settings' ),
			),
		);

		/**
		 * Filter the Athletix submenu groups (ordered [ 'label' => …, 'slugs' => [] ]).
		 *
		 * @param array[] $groups Group definitions.
		 */
		return (array) apply_filters( 'athletix/admin_menu_groups', $groups );
	}

	/**
	 * Relabel the landing item and regroup the submenu with section headings.
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

		// Regrouping the admin menu is the whole point of this method.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ Keys::MENU ] = self::build_grouped( $submenu[ Keys::MENU ], $this->groups() );
	}

	/**
	 * Reorder submenu items into groups and inject a non-clickable heading row
	 * before each non-empty, labelled group.
	 *
	 * Items not listed in any group are appended (never dropped), so a screen
	 * added by a third party still appears.
	 *
	 * @param array[] $items  Submenu item arrays (slug at index 2, cap at index 1).
	 * @param array[] $groups Ordered group definitions.
	 * @return array[]
	 */
	public static function build_grouped( array $items, array $groups ) {
		$by_slug = array();
		foreach ( $items as $item ) {
			$by_slug[ isset( $item[2] ) ? $item[2] : '' ] = $item;
		}

		$result = array();
		$used   = array();

		foreach ( $groups as $group ) {
			$members = array();
			foreach ( $group['slugs'] as $slug ) {
				if ( isset( $by_slug[ $slug ] ) ) {
					$members[]     = $by_slug[ $slug ];
					$used[ $slug ] = true;
				}
			}

			if ( ! $members ) {
				continue;
			}

			if ( '' !== $group['label'] ) {
				// Header row: [ title, cap (matches first member so it hides with it), slug, page_title, css_class ].
				$result[] = array( $group['label'], $members[0][1], $members[0][2], '', self::SECTION_CLASS );
			}

			foreach ( $members as $member ) {
				$result[] = $member;
			}
		}//end foreach

		// Append anything not assigned to a group, preserving original order.
		foreach ( $items as $item ) {
			$slug = isset( $item[2] ) ? $item[2] : '';
			if ( empty( $used[ $slug ] ) ) {
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * Print the CSS that turns the header rows into muted, non-clickable section
	 * labels within the Athletix submenu.
	 *
	 * @return void
	 */
	public function print_styles() {
		?>
		<style id="athletix-admin-menu">
			#adminmenu .<?php echo esc_html( self::SECTION_CLASS ); ?> {
				border-top: 1px solid rgba( 255, 255, 255, 0.12 );
				margin-top: 5px;
				padding-top: 3px;
			}
			#adminmenu .<?php echo esc_html( self::SECTION_CLASS ); ?> > a,
			#adminmenu .<?php echo esc_html( self::SECTION_CLASS ); ?>.current > a,
			#adminmenu .<?php echo esc_html( self::SECTION_CLASS ); ?> > a:hover,
			#adminmenu .<?php echo esc_html( self::SECTION_CLASS ); ?> > a:focus {
				pointer-events: none;
				cursor: default;
				color: #9aa0a6 !important;
				background: transparent !important;
				box-shadow: none !important;
				text-transform: uppercase;
				font-size: 10px;
				font-weight: 700;
				letter-spacing: 0.05em;
				opacity: 0.8;
			}
		</style>
		<?php
	}

	/**
	 * Stable-sort submenu items by their slug against a desired order.
	 *
	 * Retained as a reusable utility (used by tests and available to consumers);
	 * the live menu now uses {@see self::build_grouped()}.
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
