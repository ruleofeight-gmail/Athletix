<?php
/**
 * REST API module.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the athletix/v1 REST routes.
 */
class RestModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'rest';
	}

	/**
	 * Register the routes on rest_api_init.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		add_action(
			'rest_api_init',
			static function () use ( $plugin ) {
				$controllers = array(
					new StandingsController( $plugin->make( 'engine.standings' ) ),
					new ContentController(
						$plugin->make( 'repo.team' ),
						$plugin->make( 'repo.player' ),
						$plugin->make( 'repo.match' )
					),
				);

				foreach ( $controllers as $controller ) {
					$controller->register_routes();
				}
			}
		);
	}
}
