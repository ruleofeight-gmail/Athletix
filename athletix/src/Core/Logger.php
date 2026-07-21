<?php
/**
 * Logger.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Level-based logger. Writes to the PHP error log when WP_DEBUG is on and
 * always fires an `athletix/log` action so other modules (e.g. an audit log)
 * can capture entries.
 */
class Logger {

	const DEBUG   = 'debug';
	const INFO    = 'info';
	const WARNING = 'warning';
	const ERROR   = 'error';

	/**
	 * Log a debug message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function debug( $message, array $context = array() ) {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function info( $message, array $context = array() ) {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Log a warning.
	 *
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function warning( $message, array $context = array() ) {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Log an error.
	 *
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function error( $message, array $context = array() ) {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Core log routine.
	 *
	 * @param string $level   Severity level.
	 * @param string $message Message.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) {
		/**
		 * Fires for every Athletix log entry.
		 *
		 * @param string $level   Severity.
		 * @param string $message Message.
		 * @param array  $context Context data.
		 */
		do_action( 'athletix/log', $level, $message, $context );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$suffix = empty( $context ) ? '' : ' ' . wp_json_encode( $context );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[Athletix][%s] %s%s', strtoupper( $level ), $message, $suffix ) );
		}
	}
}
