<?php
/**
 * WP-CLI module.
 *
 * @package Athletix
 */

namespace Athletix\Cli;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the `wp athletix` command family, but only when running under
 * WP-CLI so the class never loads during a normal web request.
 */
class CliModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'cli';
	}

	/**
	 * Register the commands when WP-CLI is available.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		\WP_CLI::add_command( 'athletix recompute', array( new Commands( $plugin ), 'recompute' ) );
	}
}
