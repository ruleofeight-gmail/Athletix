<?php
/**
 * Main plugin class.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Athletix
 *
 * Singleton bootstrapper that wires up post types, meta boxes and the
 * Elementor integration.
 */
final class Athletix {

	/**
	 * Single shared instance.
	 *
	 * @var Athletix|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Athletix
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor. Private to enforce the singleton.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Load dependencies.
	 *
	 * @return void
	 */
	private function includes() {
		require_once ATHLETIX_PATH . 'includes/post-types/class-post-types.php';
		require_once ATHLETIX_PATH . 'includes/class-meta-boxes.php';
		require_once ATHLETIX_PATH . 'includes/class-assets.php';
		require_once ATHLETIX_PATH . 'includes/class-elementor.php';
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Feature modules.
		Post_Types::instance();
		Meta_Boxes::instance();
		Assets::instance();
		Elementor::instance();
	}

	/**
	 * Load the plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'athletix',
			false,
			dirname( ATHLETIX_BASENAME ) . '/languages'
		);
	}

	/**
	 * Activation callback: register post types then flush rewrite rules so
	 * the custom permalinks work immediately.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once ATHLETIX_PATH . 'includes/post-types/class-post-types.php';
		Post_Types::instance()->register();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback: flush rewrite rules to remove the custom
	 * permalink structure.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
