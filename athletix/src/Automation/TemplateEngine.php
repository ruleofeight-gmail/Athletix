<?php
/**
 * Simple mustache-style template rendering.
 *
 * @package Athletix
 */

namespace Athletix\Automation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces {placeholder} tokens in a string with values from a context array.
 * WordPress-free so it can be unit-tested in isolation.
 */
class TemplateEngine {

	/**
	 * Render a template against a flat context.
	 *
	 * @param string $template Template with {key} tokens.
	 * @param array  $context  Map of key => scalar value.
	 * @return string
	 */
	public function render( $template, array $context ) {
		return preg_replace_callback(
			'/\{([a-z0-9_]+)\}/i',
			static function ( $matches ) use ( $context ) {
				$key = $matches[1];
				if ( ! array_key_exists( $key, $context ) ) {
					return '';
				}
				$value = $context[ $key ];
				return is_scalar( $value ) ? (string) $value : '';
			},
			(string) $template
		);
	}
}
