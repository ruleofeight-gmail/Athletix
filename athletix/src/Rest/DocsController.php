<?php
/**
 * API documentation endpoint.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Response;

/**
 * Lists the plugin's own REST routes and their methods at
 * /athletix/v1/docs — a self-describing, always-current API index.
 */
class DocsController extends AbstractController {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/docs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'docs' ),
				'permission_callback' => array( $this, 'public_read' ),
			)
		);
	}

	/**
	 * Return the athletix/v1 routes.
	 *
	 * @return WP_REST_Response
	 */
	public function docs() {
		$routes = rest_get_server()->get_routes();
		$out    = array();

		foreach ( $routes as $route => $handlers ) {
			if ( 0 !== strpos( ltrim( $route, '/' ), self::NS ) ) {
				continue;
			}

			$methods = array();
			foreach ( $handlers as $handler ) {
				if ( ! empty( $handler['methods'] ) ) {
					$methods = array_merge( $methods, array_keys( array_filter( $handler['methods'] ) ) );
				}
			}

			$out[] = array(
				'route'   => $route,
				'methods' => array_values( array_unique( $methods ) ),
			);
		}

		return new WP_REST_Response( $out, 200 );
	}
}
