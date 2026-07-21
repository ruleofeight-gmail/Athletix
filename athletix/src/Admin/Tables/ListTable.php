<?php
/**
 * Standard WordPress list table, configured by data.
 *
 * @package Athletix
 */

namespace Athletix\Admin\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Table;

// WP_List_Table is only autoloaded in wp-admin; pull it in so this subclass can
// be declared wherever it is used.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Wraps WordPress's WP_List_Table so a screen gets core sorting, the search
 * box, pagination and (optional) bulk actions for free, while describing its
 * columns and data declaratively. The actual filter/sort/paginate work is done
 * by the WordPress-free DataSet, so behaviour matches the custom table exactly.
 */
class ListTable extends \WP_List_Table implements Table {

	/**
	 * Table configuration.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param array $config {
	 *     Table configuration.
	 *
	 *     @type string   $singular   Singular item name.
	 *     @type string   $plural     Plural item name.
	 *     @type array    $columns    Column slug => label.
	 *     @type string[] $sortable   Slugs that may be sorted.
	 *     @type string[] $searchable Slugs the search box matches.
	 *     @type callable $rows       Returns the full array of row arrays.
	 *     @type array    $render     Column slug => callable(row):string (escaped HTML).
	 *     @type int      $per_page   Rows per page.
	 * }
	 */
	public function __construct( array $config ) {
		$this->config = array_merge(
			array(
				'singular'   => 'item',
				'plural'     => 'items',
				'columns'    => array(),
				'sortable'   => array(),
				'searchable' => array(),
				'rows'       => array(),
				'render'     => array(),
				'per_page'   => 20,
			),
			$config
		);

		parent::__construct(
			array(
				'singular' => $this->config['singular'],
				'plural'   => $this->config['plural'],
				'ajax'     => false,
			)
		);
	}

	/**
	 * Column definitions.
	 *
	 * @return array
	 */
	public function get_columns() {
		return $this->config['columns'];
	}

	/**
	 * Sortable columns in WP_List_Table's expected shape.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array();
		foreach ( $this->config['sortable'] as $slug ) {
			$sortable[ $slug ] = array( $slug, false );
		}
		return $sortable;
	}

	/**
	 * Load, filter, sort and paginate the rows via the shared DataSet.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$rows = is_callable( $this->config['rows'] ) ? call_user_func( $this->config['rows'] ) : (array) $this->config['rows'];

		$per_page = (int) $this->config['per_page'];

		$result = DataSet::process(
			$rows,
			array(
				'search'     => $this->request( 's' ),
				'searchable' => $this->config['searchable'],
				'orderby'    => $this->request( 'orderby' ),
				'order'      => $this->request( 'order' ),
				'page'       => $this->get_pagenum(),
				'per_page'   => $per_page,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $result['items'];

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / max( 1, $per_page ) ),
			)
		);
	}

	/**
	 * Render a cell: a per-column callback if given, else the escaped value.
	 *
	 * @param array  $item        Row.
	 * @param string $column_name Column slug.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $this->config['render'][ $column_name ] ) && is_callable( $this->config['render'][ $column_name ] ) ) {
			return (string) call_user_func( $this->config['render'][ $column_name ], $item );
		}

		return esc_html( (string) ( isset( $item[ $column_name ] ) ? $item[ $column_name ] : '' ) );
	}

	/**
	 * Satisfy the Table contract: prepare and print the table with a search box.
	 *
	 * @return void
	 */
	public function render() {
		$this->prepare_items();
		?>
		<form method="get">
			<?php
			if ( isset( $_REQUEST['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				printf(
					'<input type="hidden" name="page" value="%s" />',
					esc_attr( sanitize_key( wp_unslash( $_REQUEST['page'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				);
			}
			if ( $this->config['searchable'] ) {
				$this->search_box( __( 'Search', 'athletix' ), 'athletix-table' );
			}
			$this->display();
			?>
		</form>
		<?php
	}

	/**
	 * Read a sanitized navigational request value (list-table GET params).
	 *
	 * @param string $key Request key.
	 * @return string
	 */
	private function request( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : '';
	}
}
