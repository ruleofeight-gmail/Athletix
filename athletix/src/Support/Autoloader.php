<?php
/**
 * Lightweight PSR-4 autoloader (Composer fallback).
 *
 * @package Athletix
 */

namespace Athletix\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps a namespace prefix to a base directory and loads classes on demand.
 *
 * Unlike a hardcoded folder list, this derives one deterministic path per
 * class from its fully-qualified name, so new modules never require touching
 * the loader.
 */
final class Autoloader {

	/**
	 * Registered prefix => base directory pairs.
	 *
	 * @var array<string,string>
	 */
	private static $prefixes = array();

	/**
	 * Register the autoloader for a namespace prefix.
	 *
	 * @param string $prefix   Namespace prefix, e.g. "Athletix\\".
	 * @param string $base_dir Absolute base directory for that prefix.
	 * @return void
	 */
	public static function register( $prefix, $base_dir ) {
		self::$prefixes[ $prefix ] = rtrim( $base_dir, '/\\' ) . '/';

		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Attempt to load the file for a class.
	 *
	 * @param string $class Fully-qualified class name.
	 * @return void
	 */
	public static function load( $class ) {
		foreach ( self::$prefixes as $prefix => $base_dir ) {
			if ( 0 !== strpos( $class, $prefix ) ) {
				continue;
			}

			$relative = substr( $class, strlen( $prefix ) );
			$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $file ) ) {
				require $file;
			}

			return;
		}
	}
}
