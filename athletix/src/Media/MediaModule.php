<?php
/**
 * Media module.
 *
 * @package Athletix
 */

namespace Athletix\Media;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the media gallery shortcode.
 */
class MediaModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'media';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new Gallery() )->register();
		( new MediaShortcodes( $plugin ) )->register();
	}
}
