<?php
/**
 * Import/Export module.
 *
 * @package Athletix
 */

namespace Athletix\ImportExport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the CSV import/export admin screen.
 */
class ImportExportModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'import-export';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$backup = new Backup( $plugin );
		$backup->register();

		if ( is_admin() ) {
			( new ImportExport( $plugin ) )->register();
		}
	}
}
