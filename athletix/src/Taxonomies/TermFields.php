<?php
/**
 * Term-meta fields for the League / Season / Division taxonomies.
 *
 * @package Athletix
 */

namespace Athletix\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Adds the extra fields these taxonomies need on their term add/edit screens —
 * the data that used to live on the League/Season/Division post types before
 * they became taxonomies (season start/end + parent league, division league,
 * league sport + colour) — and saves it as term meta.
 */
class TermFields {

	/**
	 * Field definitions keyed by taxonomy.
	 *
	 * @return array<string,array<string,array>>
	 */
	private function fields() {
		return array(
			Keys::LEAGUE   => array(
				Keys::LEAGUE_SPORT => array(
					'label' => __( 'Sport', 'athletix' ),
					'type'  => 'term',
					'tax'   => Keys::TAX_SPORT,
				),
				Keys::LEAGUE_COLOR => array(
					'label' => __( 'Primary colour', 'athletix' ),
					'type'  => 'color',
				),
			),
			Keys::SEASON   => array(
				Keys::SEASON_LEAGUE => array(
					'label' => __( 'League', 'athletix' ),
					'type'  => 'term',
					'tax'   => Keys::LEAGUE,
				),
				Keys::SEASON_START  => array(
					'label' => __( 'Start date', 'athletix' ),
					'type'  => 'date',
				),
				Keys::SEASON_END    => array(
					'label' => __( 'End date', 'athletix' ),
					'type'  => 'date',
				),
			),
			Keys::DIVISION => array(
				Keys::DIVISION_LEAGUE => array(
					'label' => __( 'League', 'athletix' ),
					'type'  => 'term',
					'tax'   => Keys::LEAGUE,
				),
			),
		);
	}

	/**
	 * Register the add/edit form fields and save handlers per taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( array_keys( $this->fields() ) as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( $this, 'render_add' ) );
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'render_edit' ), 10, 2 );
			add_action( 'created_' . $taxonomy, array( $this, 'save' ) );
			add_action( 'edited_' . $taxonomy, array( $this, 'save' ) );
		}
	}

	/**
	 * Render fields on the "Add new term" screen (stacked divs).
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function render_add( $taxonomy ) {
		$fields = $this->fields();
		if ( empty( $fields[ $taxonomy ] ) ) {
			return;
		}

		wp_nonce_field( 'athletix_term_fields', 'athletix_term_nonce' );

		foreach ( $fields[ $taxonomy ] as $key => $field ) {
			echo '<div class="form-field">';
			printf( '<label for="%s">%s</label>', esc_attr( $key ), esc_html( $field['label'] ) );
			$this->control( $key, $field, '' );
			echo '</div>';
		}
	}

	/**
	 * Render fields on the "Edit term" screen (table rows).
	 *
	 * @param \WP_Term $term     Term being edited.
	 * @param string   $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function render_edit( $term, $taxonomy ) {
		$fields = $this->fields();
		if ( empty( $fields[ $taxonomy ] ) ) {
			return;
		}

		wp_nonce_field( 'athletix_term_fields', 'athletix_term_nonce' );

		foreach ( $fields[ $taxonomy ] as $key => $field ) {
			$value = get_term_meta( $term->term_id, $key, true );
			echo '<tr class="form-field"><th scope="row">';
			printf( '<label for="%s">%s</label>', esc_attr( $key ), esc_html( $field['label'] ) );
			echo '</th><td>';
			$this->control( $key, $field, $value );
			echo '</td></tr>';
		}
	}

	/**
	 * Render a single control.
	 *
	 * @param string $key   Meta key / field name.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	private function control( $key, array $field, $value ) {
		switch ( $field['type'] ) {
			case 'term':
				$terms = get_terms(
					array(
						'taxonomy'   => $field['tax'],
						'hide_empty' => false,
						'number'     => 200,
					)
				);
				echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
				echo '<option value="0">' . esc_html__( '— None —', 'athletix' ) . '</option>';
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						printf(
							'<option value="%d" %s>%s</option>',
							(int) $term->term_id,
							selected( (int) $value, (int) $term->term_id, false ),
							esc_html( $term->name )
						);
					}
				}
				echo '</select>';
				break;

			case 'color':
				printf(
					'<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text" placeholder="#1a73e8" />',
					esc_attr( $key ),
					esc_attr( $value )
				);
				break;

			case 'date':
			default:
				printf(
					'<input type="date" name="%1$s" id="%1$s" value="%2$s" />',
					esc_attr( $key ),
					esc_attr( $value )
				);
				break;
		}//end switch
	}

	/**
	 * Save the submitted term-meta values.
	 *
	 * @param int $term_id Term id.
	 * @return void
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST['athletix_term_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['athletix_term_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'athletix_term_fields' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$fields = $this->fields();
		if ( empty( $fields[ $term->taxonomy ] ) ) {
			return;
		}

		foreach ( $fields[ $term->taxonomy ] as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per type below.

			if ( 'term' === $field['type'] ) {
				$clean = absint( $raw );
			} elseif ( 'color' === $field['type'] ) {
				$clean = sanitize_hex_color( $raw );
			} else {
				$clean = sanitize_text_field( $raw );
			}

			if ( '' === $clean || '0' === (string) $clean ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $clean );
			}
		}
	}
}
