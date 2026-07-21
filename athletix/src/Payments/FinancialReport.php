<?php
/**
 * Financial report admin screen.
 *
 * @package Athletix
 */

namespace Athletix\Payments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Summarizes payments collected per team with a grand total.
 */
class FinancialReport {

	const PAGE = 'athletix-financials';

	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Ledger.
	 *
	 * @var Ledger
	 */
	private $ledger;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @param Ledger $ledger Payment ledger.
	 */
	public function __construct( Plugin $plugin, Ledger $ledger ) {
		$this->plugin = $plugin;
		$this->ledger = $ledger;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Add the submenu (only for users who may manage payments).
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Keys::TEAM,
			__( 'Financials', 'athletix' ),
			__( 'Financials', 'athletix' ),
			Capabilities::MANAGE_PAYMENTS,
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the report.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Capabilities::MANAGE_PAYMENTS ) ) {
			return;
		}

		$teams = $this->plugin->make( 'repo.team' )->all( array( 'posts_per_page' => 500 ) );
		$grand = 0.0;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Financial Report', 'athletix' ); ?></h1>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Team', 'athletix' ); ?></th>
					<th><?php esc_html_e( 'Total collected', 'athletix' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				foreach ( $teams as $team ) :
					$total = $this->ledger->total( $team->ID );
					if ( 0.0 === $total ) {
						continue;
					}
					$grand += $total;
					?>
					<tr>
						<td><?php echo esc_html( get_the_title( $team ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $total, 2 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				<tfoot><tr>
					<th><?php esc_html_e( 'Grand total', 'athletix' ); ?></th>
					<th><?php echo esc_html( number_format_i18n( $grand, 2 ) ); ?></th>
				</tr></tfoot>
			</table>
		</div>
		<?php
	}
}
