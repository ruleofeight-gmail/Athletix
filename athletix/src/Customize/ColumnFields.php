<?php
/**
 * Editor field for a List Column variable.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * The editor for an ax_column: the post title is the column's label, and a
 * single grouped dropdown picks which list and which source field it maps to
 * (e.g. "Players → Position"). Column order comes from the Order attribute
 * (menu_order); every column is sortable and filter-aware through the list
 * engine, so nothing else needs configuring here.
 */
class ColumnFields {

	const NONCE = 'athletix_column';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post_' . Keys::LIST_COLUMN, array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Add the meta box.
	 *
	 * @return void
	 */
	public function add() {
		add_meta_box(
			'athletix_column',
			__( 'Column Source', 'athletix' ),
			array( $this, 'render' ),
			Keys::LIST_COLUMN,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );

		$current_list   = (string) get_post_meta( $post->ID, Keys::COL_LIST, true );
		$current_source = (string) get_post_meta( $post->ID, Keys::COL_SOURCE, true );
		$current        = $current_list . ':' . $current_source;
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="ax_col_field"><?php esc_html_e( 'List & field', 'athletix' ); ?></label></th>
					<td>
						<select id="ax_col_field" name="ax_col_field">
							<option value=""><?php esc_html_e( '— Select a field —', 'athletix' ); ?></option>
							<?php foreach ( ColumnCatalog::sources() as $list => $fields ) : ?>
								<optgroup label="<?php echo esc_attr( ColumnCatalog::list_label( $list ) ); ?>">
									<?php foreach ( $fields as $source => $field ) : ?>
										<?php $value = $list . ':' . $source; ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
											<?php echo esc_html( $field['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Which list this column appears on and which field it shows. The post title above is the column heading; the Order attribute sets its position.', 'athletix' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the submitted list/source, validated against the catalogue.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST['ax_col_field'] ) ? sanitize_text_field( wp_unslash( $_POST['ax_col_field'] ) ) : '';
		$parts = explode( ':', $value, 2 );
		$list  = isset( $parts[0] ) ? $parts[0] : '';
		$src   = isset( $parts[1] ) ? $parts[1] : '';

		$sources = ColumnCatalog::sources();
		if ( ! isset( $sources[ $list ][ $src ] ) ) {
			// Not a known list/field pair: clear it rather than store garbage.
			delete_post_meta( $post_id, Keys::COL_LIST );
			delete_post_meta( $post_id, Keys::COL_SOURCE );
			return;
		}

		update_post_meta( $post_id, Keys::COL_LIST, $list );
		update_post_meta( $post_id, Keys::COL_SOURCE, $src );
	}
}
