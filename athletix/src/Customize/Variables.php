<?php
/**
 * Customize variable post types.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Registers the admin-defined "variable" post types that power the Customize
 * area: Standings Columns and Outcomes. They are private content (no front-end,
 * kept out of the per-type menus); the Athletix → Customize submenu surfaces
 * them, and they are ordered by menu_order (drag-sortable in the list).
 */
class Variables {

	/**
	 * Register the variable post types.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_type(
			Keys::STANDING,
			_x( 'Standings Columns', 'post type general name', 'athletix' ),
			_x( 'Standings Column', 'post type singular name', 'athletix' )
		);

		$this->register_type(
			Keys::OUTCOME,
			_x( 'Outcomes', 'post type general name', 'athletix' ),
			_x( 'Outcome', 'post type singular name', 'athletix' )
		);

		$this->register_type(
			Keys::LIST_COLUMN,
			_x( 'List Columns', 'post type general name', 'athletix' ),
			_x( 'List Column', 'post type singular name', 'athletix' )
		);
	}

	/**
	 * Register one variable post type from shared defaults.
	 *
	 * @param string $slug     Post type slug.
	 * @param string $plural   Plural label.
	 * @param string $singular Singular label.
	 * @return void
	 */
	private function register_type( $slug, $plural, $singular ) {
		register_post_type(
			$slug,
			array(
				'labels'          => array(
					'name'          => $plural,
					'singular_name' => $singular,
					/* translators: %s: singular variable name. */
					'add_new_item'  => sprintf( __( 'Add New %s', 'athletix' ), $singular ),
					/* translators: %s: singular variable name. */
					'edit_item'     => sprintf( __( 'Edit %s', 'athletix' ), $singular ),
					'menu_name'     => $plural,
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'hierarchical'    => false,
				'supports'        => array( 'title', 'page-attributes' ),
				// Standard post capabilities (held by editors and admins) rather
				// than the custom manage_athletix cap, so these editors stay
				// reachable even if a role/security plugin strips that cap.
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
}
