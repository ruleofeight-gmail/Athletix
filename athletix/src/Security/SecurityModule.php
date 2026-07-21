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
		$audit = new AuditLog();

		$plugin->container()->instance( 'security.roles', $roles );
		$plugin->container()->instance( 'security.audit', $audit );

		// Ensure caps exist on activation and self-heal on admin load.
		add_action( 'athletix/activate', array( $roles, 'ensure' ) );
		add_action( 'admin_init', array( $roles, 'ensure' ) );

		$audit->register();
	}
}
