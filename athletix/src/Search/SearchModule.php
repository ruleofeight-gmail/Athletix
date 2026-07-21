<?php
/**
 * Search module.
 *
 * @package Athletix
 */

namespace Athletix\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the front-end search shortcode.
 */
class SearchModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'search';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		unset( $plugin );
		( new Search() )->register();
	}
}
