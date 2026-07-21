<?php
/**
 * Bespoke filterable table.
 *
 * @package Athletix
 */

namespace Athletix\Admin\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Table;

/**
 * A from-the-ground-up table that renders its own filter bar (keyword search
 * plus dropdown filters), sortable column headers and pagination. It implements
 * the same Table contract as the WP_List_Table wrapper and shares the DataSet
 * engine, so a screen can choose the standard or custom table interchangeably.
 */
class FilterableTable implements Table {

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
	 *     @type array    $columns    Column slug => label.
	 *     @type string[] $sortable   Sortable column slugs.
	 *     @type string[] $searchable Columns the keyword matches.
	 *     @type array    $filters    Slug => [ 'label' => string, 'options' => value => label ].
	 *     @type callable $rows       Returns the full array of row arrays.
	 *     @type array    $render     Column slug => callable(row):string (escaped HTML).
	 *     @type int      $per_page   Rows per page.
	 *     @type string   $base_url   URL the form posts to / sort links extend.
	 * }
	 */
	public function __construct( array $config ) {
		$this->config = array_merge(
			array(
				'columns'    => array(),
				'sortable'   => array(),
				'searchable' => array(),
				'filters'    => array(),
				'rows'       => array(),
				'render'     => array(),
				'per_page'   => 20,
				'base_url'   => '',
			),
			$config
		);
	}

	/**
	 * Read a sanitized navigational request value.
	 *
	 * @param string $key Request key.
	 * @return string
	 */
	private function request( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	/**
	 * The URL that filter forms submit to and sort links extend.
	 *
	 * @return string
	 */
	private function base_url() {
		if ( $this->config['base_url'] ) {
			return $this->config['base_url'];
		}

		$page = $this->request( 'page' );

		return admin_url( 'admin.php?page=' . rawurlencode( $page ) );
	}

	/**
	 * Render the filter bar and table.
	 *
	 * @return void
	 */
	public function render() {
		$search  = $this->request( 's' );
		$orderby = $this->request( 'orderby' );
		$order   = 'desc' === strtolower( $this->request( 'order' ) ) ? 'DESC' : 'ASC';
		$page    = max( 1, (int) $this->request( 'paged' ) );

		$filters = array();
		foreach ( array_keys( $this->config['filters'] ) as $slug ) {
			$filters[ $slug ] = $this->request( 'filter_' . $slug );
		}

		$rows   = is_callable( $this->config['rows'] ) ? call_user_func( $this->config['rows'] ) : (array) $this->config['rows'];
		$result = DataSet::process(
			$rows,
			array(
				'filters'    => $filters,
				'search'     => $search,
				'searchable' => $this->config['searchable'],
				'orderby'    => in_array( $orderby, $this->config['sortable'], true ) ? $orderby : '',
				'order'      => $order,
				'page'       => $page,
				'per_page'   => (int) $this->config['per_page'],
			)
		);

		$this->render_filter_bar( $filters, $search );
		$this->render_table( $result['items'], $orderby, $order );
		$this->render_pagination( $page, (int) $result['total'] );
	}

	/**
	 * Render the keyword + dropdown filter form.
	 *
	 * @param array  $filters Active filter values.
	 * @param string $search  Active keyword.
	 * @return void
	 */
	private function render_filter_bar( array $filters, $search ) {
		?>
		<form method="get" class="athletix-table-filters">
			<input type="hidden" name="page" value="<?php echo esc_attr( $this->request( 'page' ) ); ?>" />
			<?php if ( $this->config['searchable'] ) : ?>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'athletix' ); ?>" />
			<?php endif; ?>
			<?php foreach ( $this->config['filters'] as $slug => $filter ) : ?>
				<label>
					<span class="screen-reader-text"><?php echo esc_html( isset( $filter['label'] ) ? $filter['label'] : $slug ); ?></span>
					<select name="filter_<?php echo esc_attr( $slug ); ?>">
						<option value=""><?php echo esc_html( isset( $filter['label'] ) ? $filter['label'] : $slug ); ?></option>
						<?php foreach ( (array) ( isset( $filter['options'] ) ? $filter['options'] : array() ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $filters[ $slug ] ) ? $filters[ $slug ] : '', (string) $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'athletix' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Render the data table with sortable headers.
	 *
	 * @param array[] $items   Page of rows.
	 * @param string  $orderby Active sort column.
	 * @param string  $order   Active sort direction.
	 * @return void
	 */
	private function render_table( array $items, $orderby, $order ) {
		echo '<table class="widefat striped athletix-table"><thead><tr>';
		foreach ( $this->config['columns'] as $slug => $label ) {
			echo '<th>';
			if ( in_array( $slug, $this->config['sortable'], true ) ) {
				$next = ( $slug === $orderby && 'ASC' === $order ) ? 'desc' : 'asc';
				$url  = add_query_arg(
					array(
						'orderby' => $slug,
						'order'   => $next,
					),
					$this->base_url()
				);
				printf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
			} else {
				echo esc_html( $label );
			}
			echo '</th>';
		}
		echo '</tr></thead><tbody>';

		if ( ! $items ) {
			printf(
				'<tr><td colspan="%d">%s</td></tr>',
				count( $this->config['columns'] ),
				esc_html__( 'No items found.', 'athletix' )
			);
		}

		foreach ( $items as $item ) {
			echo '<tr>';
			foreach ( array_keys( $this->config['columns'] ) as $slug ) {
				echo '<td>';
				if ( isset( $this->config['render'][ $slug ] ) && is_callable( $this->config['render'][ $slug ] ) ) {
					// Callback is responsible for escaping its own output.
					echo call_user_func( $this->config['render'][ $slug ], $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo esc_html( (string) ( isset( $item[ $slug ] ) ? $item[ $slug ] : '' ) );
				}
				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render simple prev/next pagination.
	 *
	 * @param int $page  Current page.
	 * @param int $total Total rows (pre-pagination).
	 * @return void
	 */
	private function render_pagination( $page, $total ) {
		$per_page = max( 1, (int) $this->config['per_page'] );
		$pages    = (int) ceil( $total / $per_page );

		if ( $pages < 2 ) {
			return;
		}

		echo '<div class="tablenav"><div class="tablenav-pages">';
		printf(
			'<span class="displaying-num">%s</span> ',
			esc_html(
				sprintf(
					/* translators: %d: total number of items. */
					_n( '%d item', '%d items', $total, 'athletix' ),
					$total
				)
			)
		);

		if ( $page > 1 ) {
			printf(
				'<a class="button" href="%s">%s</a> ',
				esc_url( add_query_arg( 'paged', $page - 1, $this->base_url() ) ),
				esc_html__( '‹ Previous', 'athletix' )
			);
		}
		if ( $page < $pages ) {
			printf(
				'<a class="button" href="%s">%s</a>',
				esc_url( add_query_arg( 'paged', $page + 1, $this->base_url() ) ),
				esc_html__( 'Next ›', 'athletix' )
			);
		}

		echo '</div></div>';
	}
}
