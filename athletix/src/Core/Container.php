<?php
/**
 * Minimal service container.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A small dependency container: bind factories, resolve shared singletons.
 *
 * Deliberately tiny — enough to decouple services from hard class names
 * without pulling in a framework.
 */
class Container {

	/**
	 * Factory callbacks keyed by id.
	 *
	 * @var array<string,callable>
	 */
	private $bindings = array();

	/**
	 * Resolved shared instances keyed by id.
	 *
	 * @var array<string,mixed>
	 */
	private $instances = array();

	/**
	 * Bind a factory for an id. The factory receives the container.
	 *
	 * @param string   $id      Service id.
	 * @param callable $factory Factory returning the service.
	 * @return void
	 */
	public function bind( $id, callable $factory ) {
		$this->bindings[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Register an already-built instance.
	 *
	 * @param string $id       Service id.
	 * @param mixed  $instance Service instance.
	 * @return void
	 */
	public function instance( $id, $instance ) {
		$this->instances[ $id ] = $instance;
	}

	/**
	 * Whether an id is known to the container.
	 *
	 * @param string $id Service id.
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->instances[ $id ] ) || isset( $this->bindings[ $id ] );
	}

	/**
	 * Resolve a service, building and caching it on first use.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 * @throws \RuntimeException When the id is not bound.
	 */
	public function get( $id ) {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->bindings[ $id ] ) ) {
			throw new \RuntimeException( esc_html( sprintf( 'Athletix container has no binding for "%s".', $id ) ) );
		}

		$this->instances[ $id ] = call_user_func( $this->bindings[ $id ], $this );

		return $this->instances[ $id ];
	}
}
