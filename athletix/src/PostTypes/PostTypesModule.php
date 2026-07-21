<?php
/**
 * Content module: post types, taxonomies and meta boxes.
 *
 * @package Athletix
 */

namespace Athletix\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Meta\MetaBoxManager;
use Athletix\Plugin;
use Athletix\Taxonomies\Taxonomies;

/**
 * Registers the Athletix content types and their editors.
 */
class PostTypesModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'post-types';
	}

	/**
	 * Wire hooks.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$post_types = new PostTypes();
		$taxonomies = new Taxonomies();
		$meta_boxes = new MetaBoxManager( $plugin->validator() );

		$register = static function () use ( $post_types, $taxonomies ) {
			$post_types->register();
			$taxonomies->register();
		};

		add_action( 'init', $register );

		// Also register during activation so rewrite rules flush correctly.
		add_action( 'athletix/activate', $register );

		$meta_boxes->register();

		if ( is_admin() ) {
			( new \Athletix\Meta\MatchStatsMetaBox( $plugin ) )->register();
		}
	}
}
