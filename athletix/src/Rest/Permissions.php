<?php
/**
 * REST permission callbacks.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Centralizes REST permission logic so controllers share one policy: reads are
 * public (filterable), writes require the manage capability.
 */
class Permissions {

	/**
	 * Whether the request may read.
	 *
	 * @return bool
	 */
	public function can_read() {
		/**
		 * Filter whether Athletix REST reads are public.
		 *
		 * @param bool $allowed Default true.
		 */
		return (bool) apply_filters( 'athletix/rest_public_read', true );
	}

	/**
	 * Whether the request may write/manage.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( Keys::capability() );
	}
}
