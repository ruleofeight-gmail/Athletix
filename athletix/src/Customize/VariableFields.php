<?php
/**
 * Editor fields for the Customize variables.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Engine\Equation;
use Athletix\Support\Keys;

/**
 * Renders and saves the meta on a Standings Column / Outcome editor: the data
 * key, the free-form equation (with an "Available variables" helper), and — for
 * columns — precision, sort priority and direction, plus a sport scope. The
 * equation is validated at save time against the variables that type allows; an
 * invalid formula is still stored (it evaluates to 0 at runtime, never fatals)
 * but the admin gets a warning notice so they can fix it.
 */
class VariableFields {

	const NONCE = 'athletix_variable';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post_' . Keys::STANDING, array( $this, 'save' ), 10, 2 );
		add_action( 'save_post_' . Keys::OUTCOME, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Add the meta box to both variable types.
	 *
	 * @return void
	 */
	public function add() {
		foreach ( array( Keys::STANDING, Keys::OUTCOME ) as $post_type ) {
			add_meta_box(
				'athletix_variable',
				__( 'Variable', 'athletix' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function render( $post ) {
		$is_column = Keys::STANDING === $post->post_type;
		$allowed   = $is_column ? Defaults::base_variables() : Defaults::outcome_variables();

		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );

		$key       = (string) get_post_meta( $post->ID, Keys::VAR_KEY, true );
		$equation  = (string) get_post_meta( $post->ID, Keys::VAR_EQUATION, true );
		$precision = (int) get_post_meta( $post->ID, Keys::VAR_PRECISION, true );
		$sort      = (int) get_post_meta( $post->ID, Keys::VAR_SORT, true );
		$order     = get_post_meta( $post->ID, Keys::VAR_ORDER, true );
		$sport     = (int) get_post_meta( $post->ID, Keys::VAR_SPORT, true );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="ax_var_key"><?php esc_html_e( 'Key', 'athletix' ); ?></label></th>
					<td>
						<input type="text" id="ax_var_key" name="ax_var_key" class="regular-text code" value="<?php echo esc_attr( $key ); ?>" />
						<p class="description"><?php esc_html_e( 'The token other equations reference, e.g. points → $points. Lowercase letters, numbers and underscores.', 'athletix' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ax_var_equation"><?php esc_html_e( 'Equation', 'athletix' ); ?></label></th>
					<td>
						<textarea id="ax_var_equation" name="ax_var_equation" class="large-text code" rows="2"><?php echo esc_textarea( $equation ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Available variables:', 'athletix' ); ?>
							<?php
							$vars = array();
							foreach ( array_keys( $allowed ) as $var ) {
								$vars[] = '<code>$' . esc_html( $var ) . '</code>';
							}
							echo wp_kses_post( implode( ', ', $vars ) );
							?>
							<br />
							<?php esc_html_e( 'Operators: + - * / ( ) and > < >= <= == != · Functions: round(x,p), min(a,b), max(a,b), abs(x)', 'athletix' ); ?>
						</p>
					</td>
				</tr>
				<?php if ( $is_column ) : ?>
					<tr>
						<th scope="row"><label for="ax_var_precision"><?php esc_html_e( 'Precision', 'athletix' ); ?></label></th>
						<td><input type="number" id="ax_var_precision" name="ax_var_precision" class="small-text" min="0" max="4" value="<?php echo esc_attr( (string) $precision ); ?>" /> <span class="description"><?php esc_html_e( 'Decimal places.', 'athletix' ); ?></span></td>
					</tr>
					<tr>
						<th scope="row"><label for="ax_var_sort"><?php esc_html_e( 'Sort priority', 'athletix' ); ?></label></th>
						<td>
							<input type="number" id="ax_var_sort" name="ax_var_sort" class="small-text" min="0" value="<?php echo esc_attr( (string) $sort ); ?>" />
							<span class="description"><?php esc_html_e( '0 = display only. 1, 2, 3… form the default table sort order (1 first).', 'athletix' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ax_var_order"><?php esc_html_e( 'Sort direction', 'athletix' ); ?></label></th>
						<td>
							<select id="ax_var_order" name="ax_var_order">
								<option value="desc" <?php selected( 'asc' !== $order ); ?>><?php esc_html_e( 'Descending (high to low)', 'athletix' ); ?></option>
								<option value="asc" <?php selected( 'asc' === $order ); ?>><?php esc_html_e( 'Ascending (low to high)', 'athletix' ); ?></option>
							</select>
						</td>
					</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"><label for="ax_var_sport"><?php esc_html_e( 'Sport', 'athletix' ); ?></label></th>
					<td>
						<select id="ax_var_sport" name="ax_var_sport">
							<option value="0" <?php selected( 0, $sport ); ?>><?php esc_html_e( 'All sports (default)', 'athletix' ); ?></option>
							<?php
							$terms = get_terms(
								array(
									'taxonomy'   => Keys::TAX_SPORT,
									'hide_empty' => false,
									'number'     => 200,
								)
							);
							if ( is_array( $terms ) ) {
								foreach ( $terms as $term ) {
									printf(
										'<option value="%d" %s>%s</option>',
										(int) $term->term_id,
										selected( (int) $term->term_id, $sport, false ),
										esc_html( $term->name )
									);
								}
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Limit this variable to one sport, or leave as the default for all.', 'athletix' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the submitted meta.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
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

		$is_column = Keys::STANDING === $post->post_type;

		$key = isset( $_POST['ax_var_key'] ) ? sanitize_key( wp_unslash( $_POST['ax_var_key'] ) ) : '';
		update_post_meta( $post_id, Keys::VAR_KEY, $key );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in sanitize_equation() below.
		$equation = isset( $_POST['ax_var_equation'] ) ? $this->sanitize_equation( wp_unslash( $_POST['ax_var_equation'] ) ) : '';
		update_post_meta( $post_id, Keys::VAR_EQUATION, $equation );

		update_post_meta( $post_id, Keys::VAR_SPORT, isset( $_POST['ax_var_sport'] ) ? absint( wp_unslash( $_POST['ax_var_sport'] ) ) : 0 );

		if ( $is_column ) {
			update_post_meta( $post_id, Keys::VAR_PRECISION, isset( $_POST['ax_var_precision'] ) ? min( 4, absint( wp_unslash( $_POST['ax_var_precision'] ) ) ) : 0 );
			update_post_meta( $post_id, Keys::VAR_SORT, isset( $_POST['ax_var_sort'] ) ? absint( wp_unslash( $_POST['ax_var_sort'] ) ) : 0 );
			$order = isset( $_POST['ax_var_order'] ) && 'asc' === $_POST['ax_var_order'] ? 'asc' : 'desc';
			update_post_meta( $post_id, Keys::VAR_ORDER, $order );
		}

		// Validate the equation and warn (but do not block) on a bad formula.
		$allowed = array_keys( $is_column ? Defaults::base_variables() : Defaults::outcome_variables() );
		$result  = ( new Equation() )->validate( $equation, $allowed );
		if ( ! $result['ok'] ) {
			set_transient( 'athletix_var_error_' . get_current_user_id(), $result['error'], 60 );
		}
	}

	/**
	 * Show a warning notice if the last save had an invalid equation.
	 *
	 * @return void
	 */
	public function notice() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( Keys::STANDING, Keys::OUTCOME ), true ) ) {
			return;
		}

		$error = get_transient( 'athletix_var_error_' . get_current_user_id() );
		if ( ! $error ) {
			return;
		}
		delete_transient( 'athletix_var_error_' . get_current_user_id() );

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s <code>%s</code></p></div>',
			esc_html__( 'The equation could not be validated and will evaluate to 0 until fixed:', 'athletix' ),
			esc_html( (string) $error )
		);
	}

	/**
	 * Sanitize an equation string to the characters the parser accepts.
	 *
	 * @param string $raw Raw equation.
	 * @return string
	 */
	private function sanitize_equation( $raw ) {
		$raw = (string) $raw;
		// Allow letters/digits, $ tokens, math + comparison operators, parens,
		// comma, dot and whitespace. Everything else is stripped.
		return trim( preg_replace( '/[^a-z0-9_$+\-*\/()<>=!,.\s]/i', '', $raw ) );
	}
}
