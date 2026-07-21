<?php
/**
 * Notifications module.
 *
 * @package Athletix
 */

namespace Athletix\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Wires event-driven email notifications.
 */
class NotificationsModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'notifications';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new Notifier( $plugin->events(), $plugin->config() ) )->register();
		( new Announcements() )->register();

		if ( is_admin() ) {
			( new Preferences() )->register();
		}
	}
}
