<?php
/**
 * Customize module.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Admin\Hub;
use Athletix\Contracts\Module;
use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Wires the Customize area: the admin-defined variable post types (Standings
 * Columns, Outcomes), their editor fields, the activation seeder, the
 * Athletix → Customize submenu group, and the repository other engines read
 * their configured variables from.
 */
class CustomizeModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'customize';
	}

	/**
	 * Register the module.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$variables = new Variables();

		add_action( 'init', array( $variables, 'register' ) );
		add_action( 'athletix/activate', array( $variables, 'register' ) );
		add_action(
			'athletix/activate',
			static function () {
				( new Seeder() )->seed();
			}
		);

		$plugin->container()->bind(
			'customize.variables',
			static function () {
				return new VariableRepository();
			}
		);

		$plugin->container()->bind(
			'customize.columns',
			static function () {
				return new ColumnRepository();
			}
		);

		if ( is_admin() ) {
			( new VariableFields() )->register();
			( new ColumnFields() )->register();
			add_action( 'admin_menu', array( $this, 'menu' ), 13 );
		}
	}

	/**
	 * Surface the variable types as an Athletix → Customize submenu group.
	 *
	 * @return void
	 */
	public function menu() {
		$items = array(
			Keys::STANDING    => __( 'Standings Columns', 'athletix' ),
			Keys::OUTCOME     => __( 'Outcomes', 'athletix' ),
			Keys::LIST_COLUMN => __( 'List Columns', 'athletix' ),
		);

		foreach ( $items as $post_type => $label ) {
			add_submenu_page(
				Hub::SLUG,
				$label,
				$label,
				Keys::capability(),
				'edit.php?post_type=' . $post_type
			);
		}
	}
}
