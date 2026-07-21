<?php
/**
 * Elementor integration bootstrapper.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the custom widget category, widgets and dynamic tags — but only when a
 * compatible version of Elementor is active.
 */
final class Elementor {

	/**
	 * Shared instance.
	 *
	 * @var Elementor|null
	 */
	private static $instance = null;

	/**
	 * Custom widget category slug.
	 */
	const CATEGORY = 'athletix';

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Elementor
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Boot the integration once all plugins are loaded.
	 *
	 * @return void
	 */
	public function init() {
		// Elementor must be present.
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_elementor' ) );
			return;
		}

		// Version guard.
		if ( ! version_compare( ELEMENTOR_VERSION, ATHLETIX_MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_minimum_version' ) );
			return;
		}

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tags' ) );
	}

	/**
	 * Register the "Athletix" panel category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Athletix', 'athletix' ),
				'icon'  => 'eicon-person',
			)
		);
	}

	/**
	 * Register the custom widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once ATHLETIX_PATH . 'includes/elementor/widgets/class-athlete-card.php';
		require_once ATHLETIX_PATH . 'includes/elementor/widgets/class-team-roster.php';
		require_once ATHLETIX_PATH . 'includes/elementor/widgets/class-event-schedule.php';

		$widgets_manager->register( new Elementor\Widgets\Athlete_Card() );
		$widgets_manager->register( new Elementor\Widgets\Team_Roster() );
		$widgets_manager->register( new Elementor\Widgets\Event_Schedule() );
	}

	/**
	 * Register the dynamic tags.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Dynamic tags manager.
	 * @return void
	 */
	public function register_tags( $dynamic_tags ) {
		require_once ATHLETIX_PATH . 'includes/elementor/tags/class-athlete-field-tag.php';

		// Register a dedicated group so our tags cluster together.
		$dynamic_tags->register_group(
			self::CATEGORY,
			array( 'title' => __( 'Athletix', 'athletix' ) )
		);

		$dynamic_tags->register( new Elementor\Tags\Athlete_Field_Tag() );
	}

	/**
	 * Admin notice: Elementor is not installed / active.
	 *
	 * @return void
	 */
	public function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'Athletix requires Elementor to be installed and activated for its widgets and dynamic tags to work.', 'athletix' )
		);
	}

	/**
	 * Admin notice: Elementor version is too old.
	 *
	 * @return void
	 */
	public function notice_minimum_version() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: %s: minimum Elementor version. */
				esc_html__( 'Athletix requires Elementor version %s or greater.', 'athletix' ),
				esc_html( ATHLETIX_MINIMUM_ELEMENTOR_VERSION )
			)
		);
	}
}
