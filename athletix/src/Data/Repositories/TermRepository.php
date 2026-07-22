<?php
/**
 * Base taxonomy-term repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared read/write for entities stored as taxonomy terms (League, Season,
 * Division). Mirrors the shape of the post-backed BaseRepository so callers
 * work with a small, predictable surface.
 */
abstract class TermRepository {

	/**
	 * Taxonomy handled by the repository.
	 *
	 * @var string
	 */
	protected $taxonomy = '';

	/**
	 * Find one term by id (must belong to this taxonomy).
	 *
	 * @param int $id Term id.
	 * @return \WP_Term|null
	 */
	public function find( $id ) {
		$term = get_term( absint( $id ), $this->taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		return $term;
	}

	/**
	 * Term name, or '' when the term is missing.
	 *
	 * @param int $id Term id.
	 * @return string
	 */
	public function name( $id ) {
		$term = $this->find( $id );

		return $term ? $term->name : '';
	}

	/**
	 * All terms of this taxonomy (bounded, includes empty terms).
	 *
	 * @param array $args get_terms args (merged over defaults).
	 * @return \WP_Term[]
	 */
	public function all( array $args = array() ) {
		$defaults = array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			'number'     => 200,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$args             = array_merge( $defaults, $args );
		$args['taxonomy'] = $this->taxonomy;

		$terms = get_terms( $args );

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * The terms of this taxonomy assigned to a post.
	 *
	 * @param int $post_id Post id.
	 * @return \WP_Term[]
	 */
	public function for_object( $post_id ) {
		$terms = get_the_terms( absint( $post_id ), $this->taxonomy );

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * The id of the first term of this taxonomy assigned to a post (0 if none).
	 *
	 * @param int $post_id Post id.
	 * @return int
	 */
	public function object_term_id( $post_id ) {
		$terms = $this->for_object( $post_id );

		return $terms ? (int) $terms[0]->term_id : 0;
	}

	/**
	 * Assign a single term of this taxonomy to a post (replacing any existing).
	 *
	 * @param int $post_id Post id.
	 * @param int $term_id Term id (0 clears the assignment).
	 * @return void
	 */
	public function assign( $post_id, $term_id ) {
		$term_id = absint( $term_id );
		wp_set_object_terms( absint( $post_id ), $term_id ? array( $term_id ) : array(), $this->taxonomy, false );
	}

	/**
	 * Create a term.
	 *
	 * @param array $data Term data: 'name' (required), optional 'args' for wp_insert_term.
	 * @return int|\WP_Error New term id or error.
	 */
	public function create( array $data ) {
		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return new \WP_Error( 'athletix_term_name', 'A term name is required.' );
		}

		$result = wp_insert_term( $name, $this->taxonomy, isset( $data['args'] ) ? (array) $data['args'] : array() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return (int) $result['term_id'];
	}
}
