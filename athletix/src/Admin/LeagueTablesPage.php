<?php
/**
 * "League Tables" submenu under the Leagues menu.
 *
 * @package Athletix
 */

namespace Athletix\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * A standings viewer registered as a submenu of the "Athletix - Leagues" menu.
 * Pick a league; the current standings render via the standings shortcode, so
 * the output matches the front end exactly.
 */
class LeagueTablesPage {

	const SLUG = 'athletix-league-tables';

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the submenu.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Add "League Tables" under the Leagues top-level menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Keys::LEAGUE,
			__( 'League Tables', 'athletix' ),
			__( 'League Tables', 'athletix' ),
			'edit_posts',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the standings viewer.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$leagues = $this->plugin->make( 'repo.league' )->all( array( 'posts_per_page' => 200 ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['league'] ) ? absint( wp_unslash( $_GET['league'] ) ) : ( $leagues ? (int) $leagues[0]->ID : 0 );
		?>
		<div class="wrap athletix-league-tables">
			<h1><?php esc_html_e( 'League Tables', 'athletix' ); ?></h1>

			<?php if ( ! $leagues ) : ?>
				<p><?php esc_html_e( 'Create a league first to see its table.', 'athletix' ); ?></p>
			<?php else : ?>
				<form method="get">
					<input type="hidden" name="post_type" value="<?php echo esc_attr( Keys::LEAGUE ); ?>" />
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
					<label for="ax-league-select"><strong><?php esc_html_e( 'League:', 'athletix' ); ?></strong></label>
					<select id="ax-league-select" name="league" onchange="this.form.submit()">
						<?php foreach ( $leagues as $league ) : ?>
							<option value="<?php echo esc_attr( $league->ID ); ?>" <?php selected( $current, $league->ID ); ?>>
								<?php echo esc_html( get_the_title( $league ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>

				<div class="athletix-card" style="margin-top:1rem;">
					<?php
					if ( $current ) {
						// Reuse the front-end standings renderer for identical output.
						echo do_shortcode( '[athletix_standings league="' . $current . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
