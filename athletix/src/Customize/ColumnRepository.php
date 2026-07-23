<?php
/**
 * Resolves admin-defined list columns into ListScreen configs.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Builds the column config a list screen renders. Admin-defined List Columns
 * (ax_column posts) win — ordered by menu_order, labelled by their title,
 * resolved to a real source field through the ColumnCatalog. With none defined
 * for a list, it falls back to the full catalogue, so lists always show columns
 * even before anything is configured.
 */
class ColumnRepository {

	/**
	 * The full ListScreen config for a list (columns + taxonomy filters).
	 *
	 * @param string $post_type List post type.
	 * @return array
	 */
	public function config( $post_type ) {
		$filters = ColumnCatalog::filters();

		return array(
			'post_type' => $post_type,
			'columns'   => $this->columns( $post_type ),
			'filters'   => isset( $filters[ $post_type ] ) ? $filters[ $post_type ] : array(),
		);
	}

	/**
	 * The ordered columns for a list, keyed by column id.
	 *
	 * @param string $post_type List post type.
	 * @return array<string,array>
	 */
	public function columns( $post_type ) {
		$sources = ColumnCatalog::sources();
		$catalog = isset( $sources[ $post_type ] ) ? $sources[ $post_type ] : array();

		if ( ! $catalog ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => Keys::LIST_COLUMN,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small bounded config set.
					array(
						'key'   => Keys::COL_LIST,
						'value' => $post_type,
					),
				),
			)
		);

		if ( ! $posts ) {
			return $this->from_catalog( $catalog );
		}

		$columns = array();
		foreach ( $posts as $post ) {
			$source = (string) get_post_meta( $post->ID, Keys::COL_SOURCE, true );
			if ( ! isset( $catalog[ $source ] ) ) {
				continue;
			}

			$field                       = $catalog[ $source ];
			$columns[ 'axl_' . $source ] = array(
				'label'   => get_the_title( $post ),
				'meta'    => $field['meta'],
				'type'    => isset( $field['type'] ) ? $field['type'] : 'text',
				'numeric' => ! empty( $field['numeric'] ),
			);
		}

		return $columns ? $columns : $this->from_catalog( $catalog );
	}

	/**
	 * Build the default column set straight from a catalogue slice.
	 *
	 * @param array $catalog Catalogue sources for one list.
	 * @return array<string,array>
	 */
	private function from_catalog( array $catalog ) {
		$columns = array();
		foreach ( $catalog as $source => $field ) {
			$columns[ 'axl_' . $source ] = array(
				'label'   => $field['label'],
				'meta'    => $field['meta'],
				'type'    => isset( $field['type'] ) ? $field['type'] : 'text',
				'numeric' => ! empty( $field['numeric'] ),
			);
		}

		return $columns;
	}
}
