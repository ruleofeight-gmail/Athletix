<?php
/**
 * Front-end asset registration.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the shared front-end stylesheet used by the Elementor widgets.
 */
final class Assets {

	/**
	 * Shared instance.
	 *
	 * @var Assets|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the shared instance.
	 *
	 * @return Assets
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
		// Ensure the style is available inside the Elementor editor preview.
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue' ) );
	}

	/**
	 * Register (but do not force-enqueue) the widget styles. Widgets enqueue
	 * the handle on demand so the CSS only loads where it is used.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_style(
			'athletix',
			ATHLETIX_URL . 'assets/css/athletix.css',
			array(),
			ATHLETIX_VERSION
		);
	}

	/**
	 * Force enqueue (used by the editor preview).
	 *
	 * @return void
	 */
	public function enqueue() {
		$this->register();
		wp_enqueue_style( 'athletix' );
	}
}
