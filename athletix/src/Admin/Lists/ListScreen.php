<?php
/**
 * Generic, config-driven admin list-table columns + filters.
 *
 * @package Athletix
 */

namespace Athletix\Admin\Lists;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds sortable, filterable meta columns to a post type's wp-admin list screen
 * using only the standard WordPress extension points — `manage_{type}_columns`,
 * `manage_edit-{type}_sortable_columns`, `pre_get_posts` and
 * `restrict_manage_posts` — so the columns appear in Screen Options and sort/
 * filter natively. One instance per post type, described entirely by a config
 * array, so any list reuses the same engine.
 */
class ListScreen {

	/**
	 * Post type this screen belongs to.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Column definitions keyed by column id.
	 *
	 * @var array<string,array>
	 */
	private $columns;

	/**
	 * Taxonomy slugs offered as filter dropdowns.
	 *
	 * @var string[]
	 */
	private $filters;

	/**
	 * Constructor.
	 *
	 * @param array $config List config: post_type (string), columns (id => [ label,
	 *                      meta, type, numeric ]) and filters (taxonomy slugs).
	 */
	public function __construct( array $config ) {
		$this->post_type = (string) ( $config['post_type'] ?? '' );
		$this->columns   = isset( $config['columns'] ) && is_array( $config['columns'] ) ? $config['columns'] : array();
		$this->filters   = isset( $config['filters'] ) && is_array( $config['filters'] ) ? $config['filters'] : array();
	}

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( '' === $this->post_type ) {
			return;
		}

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_query' ) );
	}

	/**
	 * Merge the configured columns in before the Date column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$extra = array();
		foreach ( $this->columns as $id => $column ) {
			$extra[ $id ] = isset( $column['label'] ) ? $column['label'] : $id;
		}

		if ( ! isset( $columns['date'] ) ) {
			return array_merge( $columns, $extra );
		}

		$before = array_slice( $columns, 0, array_search( 'date', array_keys( $columns ), true ), true );
		$after  = array_slice( $columns, array_search( 'date', array_keys( $columns ), true ), null, true );

		return array_merge( $before, $extra, $after );
	}

	/**
	 * Declare every configured column sortable.
	 *
	 * @param array $sortable Existing sortable map.
	 * @return array
	 */
	public function sortable_columns( $sortable ) {
		foreach ( array_keys( $this->columns ) as $id ) {
			$sortable[ $id ] = $id;
		}

		return $sortable;
	}

	/**
	 * Render a configured column's cell.
	 *
	 * @param string $column_id Column id.
	 * @param int    $post_id   Post id.
	 * @return void
	 */
	public function render_column( $column_id, $post_id ) {
		if ( ! isset( $this->columns[ $column_id ] ) ) {
			return;
		}

		$column = $this->columns[ $column_id ];
		$value  = get_post_meta( $post_id, (string) ( $column['meta'] ?? '' ), true );
		$type   = isset( $column['type'] ) ? $column['type'] : 'text';

		echo wp_kses_post( $this->format( $type, $value ) );
	}

	/**
	 * Format a cell value by column type.
	 *
	 * @param string $type  Column type.
	 * @param mixed  $value Meta value.
	 * @return string HTML.
	 */
	private function format( $type, $value ) {
		if ( '' === $value || null === $value ) {
			return '—';
		}

		switch ( $type ) {
			case 'post':
				$title = get_the_title( (int) $value );
				return $title ? esc_html( $title ) : '—';

			case 'date':
				$ts = strtotime( (string) $value );
				return $ts ? esc_html( date_i18n( get_option( 'date_format' ), $ts ) ) : esc_html( (string) $value );

			case 'color':
				return sprintf(
					'<span style="display:inline-block;width:1em;height:1em;border-radius:2px;vertical-align:middle;background:%s"></span> %s',
					esc_attr( (string) $value ),
					esc_html( (string) $value )
				);

			default:
				return esc_html( (string) $value );
		}
	}

	/**
	 * Render taxonomy filter dropdowns above the list.
	 *
	 * @param string $post_type Current list post type.
	 * @return void
	 */
	public function render_filters( $post_type ) {
		if ( $post_type !== $this->post_type ) {
			return;
		}

		foreach ( $this->filters as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			if ( ! $tax ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'number'     => 200,
				)
			);
			if ( ! is_array( $terms ) || ! $terms ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list-table filter state, read-only.
			$current = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

			printf( '<select name="%s">', esc_attr( $taxonomy ) );
			printf(
				'<option value="">%s</option>',
				esc_html( isset( $tax->labels->all_items ) ? $tax->labels->all_items : $tax->label )
			);
			foreach ( $terms as $term ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $term->slug ),
					selected( $current, $term->slug, false ),
					esc_html( $term->name )
				);
			}
			echo '</select>';
		}//end foreach
	}

	/**
	 * Translate a column sort into a meta-ordered query on the list screen.
	 *
	 * @param \WP_Query $query Query being prepared.
	 * @return void
	 */
	public function apply_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->get( 'post_type' ) !== $this->post_type ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( ! is_string( $orderby ) || ! isset( $this->columns[ $orderby ] ) ) {
			return;
		}

		$column = $this->columns[ $orderby ];
		$query->set( 'meta_key', (string) ( $column['meta'] ?? '' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', ! empty( $column['numeric'] ) ? 'meta_value_num' : 'meta_value' );
	}
}
