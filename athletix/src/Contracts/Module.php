<?php
/**
 * Module contract.
 *
 * @package Athletix
 */

namespace Athletix\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Every feature module implements this contract. Modules are collected by the
 * Plugin and registered on boot — they never edit the bootstrap or autoloader.
 */
interface Module {

	/**
	 * Unique module identifier (slug), e.g. "competition".
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Wire the module's hooks and services.
	 *
	 * @param Plugin $plugin Shared plugin instance (access to container/services).
	 * @return void
	 */
	public function register( Plugin $plugin );
}
