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
	}

	/**
	 * The block definitions: name => [attributes, shortcode callback].
	 *
	 * @return array<string,array>
	 */
	private function definitions() {
		return array(
			'standings' => array(
				'attributes' => array(
					'league' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'season' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'render'     => array( $this, 'render_standings' ),
			),
			'roster'    => array(
				'attributes' => array(
					'team'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'league'  => array(
						'type'    => 'number',
						'default' => 0,
					),
					'columns' => array(
						'type'    => 'number',
						'default' => 3,
					),
				),
				'render'     => array( $this, 'render_roster' ),
			),
			'schedule'  => array(
				'attributes' => array(
					'league' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'season' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'limit'  => array(
						'type'    => 'number',
						'default' => 20,
					),
				),
				'render'     => array( $this, 'render_schedule' ),
			),
			'match'     => array(
				'attributes' => array(
					'id' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'render'     => array( $this, 'render_match' ),
			),
			'player'    => array(
				'attributes' => array(
					'id'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'stats' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'render'     => array( $this, 'render_player' ),
			),
		);
	}

	/**
	 * Register every block plus the shared editor script.
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

		foreach ( $this->definitions() as $name => $def ) {
			register_block_type(
				'athletix/' . $name,
				array(
					'api_version'     => 2,
					'editor_script'   => 'athletix-blocks',
					'style'           => 'athletix',
					'editor_style'    => 'athletix',
					'attributes'      => $def['attributes'],
					'render_callback' => $def['render'],
				)
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
