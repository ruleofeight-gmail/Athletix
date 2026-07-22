<?php
/**
 * PHPUnit bootstrap for pure (WordPress-free) unit tests.
 *
 * @package Athletix
 */

// The domain classes guard on ABSPATH; define it so they can be loaded
// outside of WordPress for isolated unit testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/../src/Support/Autoloader.php';
\Athletix\Support\Autoloader::register( 'Athletix\\', __DIR__ . '/../src/' );

// Minimal WordPress-function stubs so WordPress-free classes that only touch
// translation/filter helpers (e.g. sport profiles, the sport registry) can be
// unit-tested in isolation. Real WordPress is loaded by the integration suite.
if ( ! function_exists( '__' ) ) {
	/**
	 * Pass-through translation stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	/**
	 * Pass-through contextual translation stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function _x( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Identity filter stub — returns the value unchanged.
	 *
	 * @param string $tag   Filter name (unused).
	 * @param mixed  $value Value to return.
	 * @return mixed
	 */
	function apply_filters( $tag, $value = null ) {
		return $value;
	}
}
