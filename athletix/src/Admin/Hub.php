<?php
/**
 * The tabbed "Athletix" hub screen.
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
 * Registers the single "Athletix" top-level menu and renders it as a
 * SportsPress-style tabbed screen. Tabs are contributed by modules through the
 * `athletix/admin_tabs` filter, so the hub does not need to know about each
 * tool page directly.
 */
class Hub {

	const SLUG = Keys::MENU;

	/**
	 * Plugin instance.
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
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'taxonomy_menus' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		// The hub always ships its own Dashboard tab first.
		add_filter( 'athletix/admin_tabs', array( $this, 'dashboard_tab' ), 0 );
	}

	/**
	 * Surface the organizing taxonomies (Leagues, Seasons, Divisions, Sports) as
	 * submenus of Athletix — the single place they are managed.
	 *
	 * @return void
	 */
	public function taxonomy_menus() {
		$taxonomies = array(
			Keys::LEAGUE    => __( 'Leagues', 'athletix' ),
			Keys::SEASON    => __( 'Seasons', 'athletix' ),
			Keys::DIVISION  => __( 'Divisions', 'athletix' ),
			Keys::TAX_SPORT => __( 'Sports', 'athletix' ),
		);

		foreach ( $taxonomies as $slug => $label ) {
			add_submenu_page(
				self::SLUG,
				$label,
				$label,
				'manage_categories',
				'edit-tags.php?taxonomy=' . $slug . '&post_type=' . Keys::TEAM
			);
		}
	}

	/**
	 * Register the top-level menu (position 30, just above the content menus).
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			__( 'Athletix', 'athletix' ),
			__( 'Athletix', 'athletix' ),
			'edit_posts',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-superhero',
			30
		);
	}

	/**
	 * Add the Dashboard tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function dashboard_tab( array $tabs ) {
		$plugin = $this->plugin;

		$tabs['dashboard'] = array(
			'label'    => __( 'Dashboard', 'athletix' ),
			'cap'      => 'edit_posts',
			'order'    => 0,
			'callback' => static function () use ( $plugin ) {
				( new DashboardPage( $plugin ) )->render();
			},
		);

		return $tabs;
	}

	/**
	 * Collected, capability-ordered tabs.
	 *
	 * @return array<string,array>
	 */
	public function tabs() {
		/**
		 * Filter the Athletix hub tabs.
		 *
		 * @param array $tabs Map of slug => [ label, cap, order, callback ].
		 */
		$tabs = (array) apply_filters( 'athletix/admin_tabs', array() );

		uasort(
			$tabs,
			static function ( $a, $b ) {
				return ( isset( $a['order'] ) ? $a['order'] : 50 ) <=> ( isset( $b['order'] ) ? $b['order'] : 50 );
			}
		);

		return $tabs;
	}

	/**
	 * Render the hub: heading, tab strip, and the active tab's panel.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$tabs = array_filter(
			$this->tabs(),
			static function ( $tab ) {
				return current_user_can( isset( $tab['cap'] ) ? $tab['cap'] : 'edit_posts' );
			}
		);

		if ( ! $tabs ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$current   = isset( $tabs[ $requested ] ) ? $requested : (string) array_key_first( $tabs );
		?>
		<div class="wrap athletix-hub">
			<h1 class="athletix-hub__title">
				<span class="dashicons dashicons-superhero" aria-hidden="true"></span>
				<?php esc_html_e( 'Athletix', 'athletix' ); ?>
			</h1>

			<nav class="nav-tab-wrapper athletix-hub__tabs" aria-label="<?php esc_attr_e( 'Athletix sections', 'athletix' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'page' => self::SLUG,
									'tab'  => $slug,
								),
								admin_url( 'admin.php' )
							)
						);
						?>
								"
						class="nav-tab <?php echo $slug === $current ? 'nav-tab-active' : ''; ?>"
					><?php echo esc_html( $tab['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="athletix-hub__panel">
				<?php
				if ( isset( $tabs[ $current ]['callback'] ) && is_callable( $tabs[ $current ]['callback'] ) ) {
					call_user_func( $tabs[ $current ]['callback'] );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * The URL of a hub tab.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	public static function tab_url( $tab ) {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Enqueue hub styles on the hub screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'athletix-admin', ATHLETIX_URL . 'assets/css/admin.css', array(), ATHLETIX_VERSION );
	}
}
