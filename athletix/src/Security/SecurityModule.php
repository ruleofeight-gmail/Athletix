<?php
/**
 * Security module.
 *
 * @package Athletix
 */

namespace Athletix\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Wires roles/capabilities and the audit log.
 */
class SecurityModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'security';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$roles = new Roles();
		$caps  = new Capabilities();
		$audit = new AuditLog();

		$plugin->container()->instance( 'security.roles', $roles );
		$plugin->container()->instance( 'security.audit', $audit );
		$plugin->container()->instance( 'security.access', new AccessControl() );

		// Ensure roles and capabilities exist on activation and self-heal on load.
		// This runs on `init` (not `admin_init`) because `admin_menu` — where the
		// capability-gated Athletix menus are drawn — fires before `admin_init`;
		// granting on `init` guarantees the manage_athletix capability is present
		// before the menus are built, so a freshly installed or updated site never
		// hides the Leagues/Seasons/Divisions/Sports and Customize screens.
		$ensure = static function () use ( $roles, $caps ) {
			if ( ! is_admin() ) {
				return;
			}
			$roles->ensure();
			$caps->ensure();
		};
		add_action( 'athletix/activate', $ensure );
		add_action( 'init', $ensure );

		$audit->register();
	}
}
