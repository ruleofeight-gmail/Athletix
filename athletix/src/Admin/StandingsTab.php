<?php
/**
 * "Tables" tab of the Athletix hub — an in-admin standings viewer.
 *
 * @package Athletix
 */

namespace Athletix\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * Adds a "Tables" tab that lets a manager pick a league and see its current
 * standings, reusing the front-end [athletix_standings] renderer so the output
 * matches the public table exactly.
 */
class StandingsTab {

	use HubTab;

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
	 * Register the tab.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_hub_tab( 'tables', __( 'Tables', 'athletix' ), 10, 'edit_posts' );
	}

	/**
	 * Render the league picker + standings.
	 *
	 * @return void
	 */
	public function render() {
		$leagues = $this->plugin->make( 'repo.league' )->all();

		if ( ! $leagues ) {
			echo '<p>' . esc_html__( 'Add a league (Athletix → Leagues) to see its table.', 'athletix' ) . '</p>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['league'] ) ? absint( wp_unslash( $_GET['league'] ) ) : (int) $leagues[0]->term_id;
		?>
		<form method="get" class="athletix-tables-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( Hub::SLUG ); ?>" />
			<input type="hidden" name="tab" value="tables" />
			<label for="ax-tables-league"><strong><?php esc_html_e( 'League', 'athletix' ); ?></strong></label>
			<select id="ax-tables-league" name="league" onchange="this.form.submit()">
				<?php foreach ( $leagues as $league ) : ?>
					<option value="<?php echo esc_attr( $league->term_id ); ?>" <?php selected( $current, (int) $league->term_id ); ?>>
						<?php echo esc_html( $league->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>

		<div class="athletix-card" style="margin-top:1rem;">
			<?php
			// Reuse the front-end renderer for identical output.
			echo do_shortcode( '[athletix_standings league="' . $current . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
			?>
		</div>
		<?php
	}
}
