<?php
/**
 * Plugin-provided single/archive templates.
 *
 * @package Athletix
 */

namespace Athletix\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Supplies full-page templates for the Athletix post types so entities render
 * nicely with ANY theme — but only when the active theme does not provide its
 * own more specific template, so theme overrides always win.
 */
class Templates {

	/**
	 * Register the filter.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'template_include', array( $this, 'include_template' ) );
	}

	/**
	 * Map of context => post type => plugin template file.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function map() {
		return array(
			'single'  => array(
				Keys::TEAM   => 'single-ax_team.php',
				Keys::PLAYER => 'single-ax_player.php',
				Keys::MATCH  => 'single-ax_match.php',
				Keys::STAFF  => 'single-ax_staff.php',
			),
			'archive' => array(
				Keys::TEAM   => 'archive-ax_team.php',
				Keys::PLAYER => 'archive-ax_player.php',
			),
		);
	}

	/**
	 * Swap in a plugin template when appropriate.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function include_template( $template ) {
		$map = $this->map();

		foreach ( $map['single'] as $type => $file ) {
			if ( is_singular( $type ) && ! locate_template( array( $file ) ) ) {
				$candidate = ATHLETIX_PATH . 'templates/' . $file;
				if ( is_readable( $candidate ) ) {
					return $candidate;
				}
			}
		}

		foreach ( $map['archive'] as $type => $file ) {
			if ( is_post_type_archive( $type ) && ! locate_template( array( $file ) ) ) {
				$candidate = ATHLETIX_PATH . 'templates/' . $file;
				if ( is_readable( $candidate ) ) {
					return $candidate;
				}
			}
		}

		return $template;
	}
}
