<?php
/**
 * Automation module.
 *
 * @package Athletix
 */

namespace Athletix\Automation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Wires the daily WP-Cron automation.
 */
class AutomationModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'automation';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new Scheduler( $plugin ) )->register();

		$rules = new RuleManager( $plugin->events(), new TemplateEngine(), $plugin->logger() );
		$rules->register();

		if ( is_admin() ) {
			( new RulesAdmin( $rules ) )->register();
		}
	}
}
