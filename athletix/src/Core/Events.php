<?php
/**
 * Event dispatcher.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin, debuggable event layer built on top of WordPress hooks.
 *
 * Firing "match_processed" dispatches `athletix/match_processed`, so listeners
 * can subscribe with either this API or a plain add_action(), and every event
 * is visible to standard hook-debugging tools.
 */
class Events {

	/**
	 * Hook prefix for all Athletix events.
	 */
	const PREFIX = 'athletix/';

	/**
	 * Subscribe to an event.
	 *
	 * @param string   $event    Event name (without prefix).
	 * @param callable $listener Listener callback receiving the payload array.
	 * @param int      $priority Hook priority.
	 * @return void
	 */
	public function listen( $event, callable $listener, $priority = 10 ) {
		add_action( self::PREFIX . $event, $listener, $priority, 1 );
	}

	/**
	 * Dispatch an event with a payload.
	 *
	 * @param string $event   Event name (without prefix).
	 * @param array  $payload Associative payload passed to listeners.
	 * @return void
	 */
	public function fire( $event, array $payload = array() ) {
		do_action( self::PREFIX . $event, $payload );
	}

	/**
	 * Apply listeners as a filter, letting them transform a value.
	 *
	 * @param string $event   Event name (without prefix).
	 * @param mixed  $value   Value to filter.
	 * @param array  $context Extra context passed to listeners.
	 * @return mixed Filtered value.
	 */
	public function filter( $event, $value, array $context = array() ) {
		return apply_filters( self::PREFIX . $event, $value, $context );
	}
}
