<?php
/**
 * Gutenberg blocks module.
 *
 * @package Athletix
 */

namespace Athletix\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Frontend\Shortcodes;
use Athletix\Plugin;

/**
 * Registers the Athletix editor blocks as server-rendered dynamic blocks whose
 * render callbacks delegate to the existing (tested) shortcode handlers, so the
 * block output and the shortcode output can never drift apart.
 */
class BlocksModule implements Module {

	/**
	 * Shortcode renderer reused by every block.
	 *
	 * @var Shortcodes
	 */
	private $shortcodes;

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'blocks';
	}

	/**
	 * Register the blocks on init.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$this->shortcodes = new Shortcodes( $plugin );

		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'block_category' ) );
	}

	/**
	 * Register an "Athletix" block category so the blocks group in the inserter.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public function block_category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'athletix',
				'title' => __( 'Athletix', 'athletix' ),
				'icon'  => null,
			)
		);

		return $categories;
	}

	/**
	 * Server-side render callbacks keyed by block slug. Attributes, category,
	 * script/style handles and metadata now live in each block's block.json;
	 * only the PHP render callback (which cannot live in JSON) is supplied here.
	 *
	 * @return array<string,callable>
	 */
	private function render_callbacks() {
		return array(
			'standings' => array( $this, 'render_standings' ),
			'roster'    => array( $this, 'render_roster' ),
			'schedule'  => array( $this, 'render_schedule' ),
			'bracket'   => array( $this, 'render_bracket' ),
			'match'     => array( $this, 'render_match' ),
			'player'    => array( $this, 'render_player' ),
		);
	}

	/**
	 * Register every block from its block.json metadata, plus the shared editor
	 * script and style the metadata references.
	 *
	 * @return void
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'athletix-blocks',
			ATHLETIX_URL . 'assets/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-api-fetch' ),
			ATHLETIX_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'athletix-blocks', 'athletix', ATHLETIX_PATH . 'languages' );
		}

		wp_register_style( 'athletix', ATHLETIX_URL . 'assets/css/athletix.css', array(), ATHLETIX_VERSION );

		foreach ( $this->render_callbacks() as $slug => $callback ) {
			register_block_type(
				ATHLETIX_PATH . 'blocks/' . $slug,
				array( 'render_callback' => $callback )
			);
		}
	}

	/**
	 * Render the standings block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_standings( $attributes ) {
		return $this->shortcodes->standings(
			array(
				'league' => (int) ( $attributes['league'] ?? 0 ),
				'season' => (int) ( $attributes['season'] ?? 0 ),
			)
		);
	}

	/**
	 * Render the roster block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_roster( $attributes ) {
		return $this->shortcodes->roster(
			array(
				'team'    => (int) ( $attributes['team'] ?? 0 ),
				'league'  => (int) ( $attributes['league'] ?? 0 ),
				'columns' => (int) ( $attributes['columns'] ?? 3 ),
			)
		);
	}

	/**
	 * Render the schedule block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_schedule( $attributes ) {
		return $this->shortcodes->schedule(
			array(
				'league' => (int) ( $attributes['league'] ?? 0 ),
				'season' => (int) ( $attributes['season'] ?? 0 ),
				'limit'  => (int) ( $attributes['limit'] ?? 20 ),
			)
		);
	}

	/**
	 * Render the bracket block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_bracket( $attributes ) {
		return $this->shortcodes->bracket(
			array(
				'league' => (int) ( $attributes['league'] ?? 0 ),
				'season' => (int) ( $attributes['season'] ?? 0 ),
			)
		);
	}

	/**
	 * Render the match block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_match( $attributes ) {
		return $this->shortcodes->match_card(
			array( 'id' => (int) ( $attributes['id'] ?? 0 ) )
		);
	}

	/**
	 * Render the player block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_player( $attributes ) {
		$stats = ! isset( $attributes['stats'] ) || $attributes['stats'];

		return $this->shortcodes->player(
			array(
				'id'    => (int) ( $attributes['id'] ?? 0 ),
				'stats' => $stats ? 'yes' : 'no',
			)
		);
	}
}
