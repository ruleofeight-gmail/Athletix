<?php
/**
 * Configuration store.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central settings access backed by a single wp_options row, with defaults
 * and an in-memory cache. Replaces scattered get_option() calls.
 */
class Config {

	/**
	 * Option key storing all Athletix settings.
	 */
	const OPTION = 'athletix_settings';

	/**
	 * Loaded settings (lazy).
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults;

	/**
	 * Constructor.
	 *
	 * @param array $defaults Default configuration values.
	 */
	public function __construct( array $defaults = array() ) {
		$this->defaults = $defaults;
	}

	/**
	 * Get a setting by key, or a default.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback if unset.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$this->load();

		if ( array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}

		if ( array_key_exists( $key, $this->defaults ) ) {
			return $this->defaults[ $key ];
		}

		return $default;
	}

	/**
	 * Set and persist a setting.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Value to store.
	 * @return void
	 */
	public function set( $key, $value ) {
		$this->load();
		$this->settings[ $key ] = $value;
		update_option( self::OPTION, $this->settings );
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array
	 */
	public function all() {
		$this->load();
		return array_merge( $this->defaults, $this->settings );
	}

	/**
	 * Lazily load settings from the database.
	 *
	 * @return void
	 */
	private function load() {
		if ( null === $this->settings ) {
			$stored         = get_option( self::OPTION, array() );
			$this->settings = is_array( $stored ) ? $stored : array();
		}
	}
}
