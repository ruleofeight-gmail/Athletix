<?php
/**
 * Taxonomy registration.
 *
 * @package Athletix
 */

namespace Athletix\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Registers Athletix's organizing taxonomies — Sport, League, Season and
 * Division — shared across teams, players and matches. All are kept out of the
 * per-post-type menus (`show_in_menu => false`); the Admin hub surfaces them
 * once, under "Athletix".
 */
class Taxonomies {

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_taxonomy(
			Keys::TAX_SPORT,
			array( Keys::TEAM, Keys::PLAYER, Keys::MATCH ),
			_x( 'Sports', 'taxonomy general name', 'athletix' ),
			_x( 'Sport', 'taxonomy singular name', 'athletix' ),
			'sport'
		);

		$this->register_taxonomy(
			Keys::LEAGUE,
			array( Keys::TEAM, Keys::PLAYER, Keys::MATCH ),
			_x( 'Leagues', 'taxonomy general name', 'athletix' ),
			_x( 'League', 'taxonomy singular name', 'athletix' ),
			'leagues'
		);

		$this->register_taxonomy(
			Keys::SEASON,
			array( Keys::TEAM, Keys::MATCH ),
			_x( 'Seasons', 'taxonomy general name', 'athletix' ),
			_x( 'Season', 'taxonomy singular name', 'athletix' ),
			'seasons'
		);

		$this->register_taxonomy(
			Keys::DIVISION,
			array( Keys::TEAM, Keys::MATCH ),
			_x( 'Divisions', 'taxonomy general name', 'athletix' ),
			_x( 'Division', 'taxonomy singular name', 'athletix' ),
			'divisions'
		);
	}

	/**
	 * Register one Athletix taxonomy from shared defaults.
	 *
	 * @param string   $slug     Taxonomy slug.
	 * @param string[] $objects  Object types it applies to.
	 * @param string   $plural   Plural label.
	 * @param string   $singular Singular label.
	 * @param string   $rewrite  Rewrite slug.
	 * @return void
	 */
	private function register_taxonomy( $slug, array $objects, $plural, $singular, $rewrite ) {
		register_taxonomy(
			$slug,
			$objects,
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
					/* translators: %s: plural taxonomy name. */
					'search_items'  => sprintf( __( 'Search %s', 'athletix' ), $plural ),
					/* translators: %s: plural taxonomy name. */
					'all_items'     => $plural,
					/* translators: %s: singular taxonomy name. */
					'edit_item'     => sprintf( __( 'Edit %s', 'athletix' ), $singular ),
					/* translators: %s: singular taxonomy name. */
					'add_new_item'  => sprintf( __( 'Add New %s', 'athletix' ), $singular ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'show_in_menu'      => false,
				'rewrite'           => array( 'slug' => $rewrite ),
			)
		);
	}
}
