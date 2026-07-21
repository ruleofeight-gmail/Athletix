<?php
/**
 * Elementor integration module.
 *
 * @package Athletix
 */

namespace Athletix\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Loads the Elementor category, widgets and dynamic tags — but only when a
 * compatible Elementor is active. Otherwise shows a dismissible admin notice
 * and leaves the rest of the plugin fully functional.
 */
class ElementorModule implements Module {

	const CATEGORY = 'athletix';

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'elementor';
	}

	/**
	 * Register the integration.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing' ) );
			return;
		}

		if ( ! version_compare( ELEMENTOR_VERSION, ATHLETIX_MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_version' ) );
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( $this, 'category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'tags' ) );
	}

	/**
	 * Register the panel category.
	 *
	 * @param \Elementor\Elements_Manager $manager Elements manager.
	 * @return void
	 */
	public function category( $manager ) {
		$manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Athletix', 'athletix' ),
				'icon'  => 'eicon-trophy',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 * @return void
	 */
	public function widgets( $manager ) {
		$manager->register( new Widgets\LeagueTableWidget() );
		$manager->register( new Widgets\TeamRosterWidget() );
		$manager->register( new Widgets\ScheduleWidget() );
	}

	/**
	 * Register dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Dynamic tags manager.
	 * @return void
	 */
	public function tags( $manager ) {
		$manager->register_group( self::CATEGORY, array( 'title' => __( 'Athletix', 'athletix' ) ) );
		$manager->register( new Tags\PlayerFieldTag() );
	}

	/**
	 * Notice: Elementor missing.
	 *
	 * @return void
	 */
	public function notice_missing() {
		if ( current_user_can( 'activate_plugins' ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html__( 'Athletix Elementor widgets require Elementor to be installed and active.', 'athletix' )
			);
		}
	}

	/**
	 * Notice: Elementor too old.
	 *
	 * @return void
	 */
	public function notice_version() {
		if ( current_user_can( 'activate_plugins' ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %s: minimum version. */
					esc_html__( 'Athletix requires Elementor %s or newer.', 'athletix' ),
					esc_html( ATHLETIX_MINIMUM_ELEMENTOR_VERSION )
				)
			);
		}
	}
}
