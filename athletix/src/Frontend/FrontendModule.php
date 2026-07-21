<?php
/**
 * Frontend module.
 *
 * @package Athletix
 */

namespace Athletix\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers front-end shortcodes and the shared stylesheet.
 */
class FrontendModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'frontend';
	}

	/**
	 * Register frontend hooks.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		add_action(
			'wp_enqueue_scripts',
			static function () {
				wp_register_style( 'athletix', ATHLETIX_URL . 'assets/css/athletix.css', array(), ATHLETIX_VERSION );
			}
		);

		// Ensure the handle is also available inside the Elementor editor preview.
		add_action(
			'elementor/preview/enqueue_styles',
			static function () {
				wp_enqueue_style( 'athletix' );
			}
		);

		$shortcodes = new Shortcodes( $plugin );
		$shortcodes->register();

		// Ensure the stylesheet is present on plugin single/archive templates.
		add_action(
			'wp_enqueue_scripts',
			static function () {
				if ( is_singular( array( 'ax_team', 'ax_player', 'ax_match' ) ) || is_post_type_archive( array( 'ax_team', 'ax_player' ) ) ) {
					wp_enqueue_style( 'athletix' );
				}
			}
		);

		( new Templates() )->register();
	}
}
