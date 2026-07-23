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

use Athletix\Admin\HubTab;
use Athletix\Admin\Tables\ListTable;
use Athletix\Plugin;
use Athletix\Security\Capabilities;

/**
 * Summarizes payments collected per team with a grand total.
 */
class FinancialReport {

	use HubTab;

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
		$this->register_hub_tab( 'financials', __( 'Financials', 'athletix' ), 50, Capabilities::MANAGE_PAYMENTS );
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

		$rows  = $this->rows();
		$grand = array_sum( wp_list_pluck( $rows, 'total' ) );

		$table = new ListTable(
			array(
				'singular'   => __( 'team', 'athletix' ),
				'plural'     => __( 'teams', 'athletix' ),
				'columns'    => array(
					'team'  => __( 'Team', 'athletix' ),
					'total' => __( 'Total collected', 'athletix' ),
				),
				'sortable'   => array( 'team', 'total' ),
				'searchable' => array( 'team' ),
				'per_page'   => 20,
				'rows'       => $rows,
				'render'     => array(
					'total' => static function ( $row ) {
						return esc_html( number_format_i18n( (float) $row['total'], 2 ) );
					},
				),
			)
		);
		?>
		<h2><?php esc_html_e( 'Financial Report', 'athletix' ); ?></h2>
		<?php $table->render(); ?>
		<p class="athletix-grand-total">
			<strong><?php esc_html_e( 'Grand total:', 'athletix' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $grand, 2 ) ); ?>
		</p>
		<?php
	}

	/**
	 * Build the per-team collected-total rows (teams with a non-zero total).
	 *
	 * @return array[] Rows of [ 'team' => string, 'total' => float ].
	 */
	private function rows() {
		$teams = $this->plugin->make( 'repo.team' )->all( array( 'posts_per_page' => 500 ) );
		$rows  = array();

		foreach ( $teams as $team ) {
			$total = (float) $this->ledger->total( $team->ID );
			if ( 0.0 === $total ) {
				continue;
			}
			$rows[] = array(
				'team'  => get_the_title( $team ),
				'total' => $total,
			);
		}

		return $rows;
	}
}
