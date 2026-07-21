<?php
/**
 * Cache helper.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps the WordPress object cache / transients with a remember() helper and a
 * namespaced key + group so all plugin cache entries can be flushed together.
 */
class Cache {

	/**
	 * Cache group.
	 */
	const GROUP = 'athletix';

	/**
	 * Default TTL in seconds.
	 *
	 * @var int
	 */
	private $default_ttl;

	/**
	 * Constructor.
	 *
	 * @param int $default_ttl Default time-to-live in seconds.
	 */
	public function __construct( $default_ttl = 300 ) {
		$this->default_ttl = (int) $default_ttl;
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $default Value returned on miss.
	 * @return mixed
	 */
	public function get( $key, $default = false ) {
		$found = false;
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		return $found ? $value : $default;
	}

	/**
	 * Store a value.
	 *
	 * @param string   $key   Cache key.
	 * @param mixed    $value Value to store.
	 * @param int|null $ttl   TTL in seconds; null uses the default.
	 * @return void
	 */
	public function set( $key, $value, $ttl = null ) {
		wp_cache_set( $key, $value, self::GROUP, null === $ttl ? $this->default_ttl : (int) $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public function delete( $key ) {
		wp_cache_delete( $key, self::GROUP );
	}

	/**
	 * Return a cached value or compute, store and return it.
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Producer invoked on a miss.
	 * @param int|null $ttl      TTL in seconds; null uses the default.
	 * @return mixed
	 */
	public function remember( $key, callable $callback, $ttl = null ) {
		$found = false;
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		if ( $found ) {
			return $value;
		}

		$value = call_user_func( $callback );
		$this->set( $key, $value, $ttl );

		return $value;
	}
}
