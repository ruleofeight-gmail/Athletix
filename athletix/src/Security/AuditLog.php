<?php
/**
 * Lightweight audit log.
 *
 * @package Athletix
 */

namespace Athletix\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Captures notable plugin events into a capped ring buffer stored as an option.
 * Deliberately storage-light; high-volume sites can swap the backend by
 * unhooking and listening to `athletix/log` directly.
 */
class AuditLog {

	const OPTION = 'athletix_audit_log';
	const MAX    = 200;

	/**
	 * Subscribe to the log stream.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'athletix/log', array( $this, 'capture' ), 10, 3 );
	}

	/**
	 * Persist a log entry, trimming to the cap.
	 *
	 * @param string $level   Severity.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public function capture( $level, $message, $context = array() ) {
		$entries   = $this->recent();
		$entries[] = array(
			'time'    => time(),
			'user'    => get_current_user_id(),
			'level'   => sanitize_key( $level ),
			'message' => sanitize_text_field( $message ),
			'context' => array_map( 'sanitize_text_field', wp_parse_args( $context ) ),
		);

		if ( count( $entries ) > self::MAX ) {
			$entries = array_slice( $entries, -self::MAX );
		}

		update_option( self::OPTION, $entries, false );
	}

	/**
	 * Recent entries, newest last.
	 *
	 * @return array[]
	 */
	public function recent() {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Clear the log.
	 *
	 * @return void
	 */
	public function clear() {
		delete_option( self::OPTION );
	}
}
