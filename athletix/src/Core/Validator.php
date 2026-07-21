<?php
/**
 * Input validation & sanitization helper.
 *
 * @package Athletix
 */

namespace Athletix\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes and validates arrays of input against a simple field schema, so
 * write paths (meta boxes, REST, admin forms) share one consistent routine.
 */
class Validator {

	/**
	 * Sanitize a value by declared type.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  One of: text, textarea, int, float, bool, email, url, key, date, array_int.
	 * @return mixed
	 */
	public function sanitize( $value, $type = 'text' ) {
		switch ( $type ) {
			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'int':
				return (int) $value;

			case 'float':
				return (float) $value;

			case 'bool':
				return (bool) $value && 'false' !== $value && '0' !== $value;

			case 'email':
				return sanitize_email( (string) $value );

			case 'url':
				return esc_url_raw( (string) $value );

			case 'key':
				return sanitize_key( (string) $value );

			case 'date':
				$value = trim( (string) $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			case 'array_int':
				return array_values( array_map( 'absint', (array) $value ) );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Sanitize an input array against a schema of key => type.
	 *
	 * @param array $input  Raw input (already unslashed by the caller).
	 * @param array $schema Map of key => type.
	 * @return array Sanitized values for the declared keys only.
	 */
	public function sanitize_array( array $input, array $schema ) {
		$clean = array();

		foreach ( $schema as $key => $type ) {
			if ( array_key_exists( $key, $input ) ) {
				$clean[ $key ] = $this->sanitize( $input[ $key ], $type );
			}
		}

		return $clean;
	}

	/**
	 * Whether a value is a positive integer id.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	public function is_id( $value ) {
		return is_numeric( $value ) && (int) $value > 0;
	}
}
