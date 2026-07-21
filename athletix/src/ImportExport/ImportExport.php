<?php
/**
 * CSV import/export for Athletix entities.
 *
 * @package Athletix
 */

namespace Athletix\ImportExport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Imports teams/players from CSV and exports standings as CSV, behind a
 * capability- and nonce-guarded admin screen.
 */
class ImportExport {

	const PAGE          = 'athletix-import-export';
	const ACTION_IMPORT = 'athletix_import_csv';
	const ACTION_EXPORT = 'athletix_export_standings';

	/**
	 * Plugin.
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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION_IMPORT, array( $this, 'handle_import' ) );
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( $this, 'handle_export' ) );
	}

	/**
	 * Add the submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Keys::TEAM,
			__( 'Import / Export', 'athletix' ),
			__( 'Import / Export', 'athletix' ),
			Keys::capability(),
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}

		$action  = esc_url( admin_url( 'admin-post.php' ) );
		$leagues = $this->plugin->make( 'repo.league' )->all( array( 'posts_per_page' => 200 ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import / Export', 'athletix' ); ?></h1>
			<?php if ( isset( $_GET['ax_imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p>
				<?php
				$imported = absint( $_GET['ax_imported'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				printf(
					/* translators: %d: number of rows imported. */
					esc_html( _n( 'Imported %d row.', 'Imported %d rows.', $imported, 'athletix' ) ),
					absint( $imported )
				);
				?>
				</p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Import CSV', 'athletix' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Teams CSV: one column "name". Players CSV: columns "name","team_id","position","number".', 'athletix' ); ?></p>
			<form method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_IMPORT ); ?>" />
				<?php wp_nonce_field( self::ACTION_IMPORT ); ?>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Type', 'athletix' ); ?></th>
						<td>
							<select name="import_type">
								<option value="team"><?php esc_html_e( 'Teams', 'athletix' ); ?></option>
								<option value="player"><?php esc_html_e( 'Players', 'athletix' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'CSV File', 'athletix' ); ?></th>
						<td><input type="file" name="csv" accept=".csv,text/csv" required /></td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Import', 'athletix' ) ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Export Standings', 'athletix' ); ?></h2>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_EXPORT ); ?>" />
				<?php wp_nonce_field( self::ACTION_EXPORT ); ?>
				<select name="league_id" required>
					<option value=""><?php esc_html_e( '— Select league —', 'athletix' ); ?></option>
					<?php foreach ( $leagues as $league ) : ?>
						<option value="<?php echo esc_attr( $league->ID ); ?>"><?php echo esc_html( $league->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Download CSV', 'athletix' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle a CSV import.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
		check_admin_referer( self::ACTION_IMPORT );

		$type = isset( $_POST['import_type'] ) ? sanitize_key( wp_unslash( $_POST['import_type'] ) ) : 'team';

		if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$this->redirect( array( 'ax_imported' => 0 ) );
		}

		$rows = $this->read_csv( sanitize_text_field( $_FILES['csv']['tmp_name'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$made = 0;

		foreach ( $rows as $row ) {
			$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}

			if ( 'player' === $type ) {
				$id = $this->plugin->make( 'repo.player' )->create( array( 'post_title' => $name ) );
				if ( ! is_wp_error( $id ) && $id ) {
					if ( ! empty( $row['team_id'] ) ) {
						update_post_meta( $id, Keys::PLAYER_TEAM, absint( $row['team_id'] ) );
					}
					if ( ! empty( $row['position'] ) ) {
						update_post_meta( $id, Keys::PLAYER_POSITION, sanitize_text_field( $row['position'] ) );
					}
					if ( isset( $row['number'] ) && '' !== $row['number'] ) {
						update_post_meta( $id, Keys::PLAYER_NUMBER, absint( $row['number'] ) );
					}
					++$made;
				}
			} else {
				$id = $this->plugin->make( 'repo.team' )->create( array( 'post_title' => $name ) );
				if ( ! is_wp_error( $id ) && $id ) {
					++$made;
				}
			}
		}//end foreach

		$this->plugin->logger()->info(
			'CSV import',
			array(
				'type' => $type,
				'rows' => $made,
			)
		);
		$this->redirect( array( 'ax_imported' => $made ) );
	}

	/**
	 * Handle a standings export (streams CSV).
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
		check_admin_referer( self::ACTION_EXPORT );

		$league_id = isset( $_POST['league_id'] ) ? absint( $_POST['league_id'] ) : 0;
		$rows      = $league_id ? $this->plugin->make( 'engine.standings' )->table( $league_id, 0 ) : array();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="athletix-standings-' . $league_id . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Team', 'P', 'W', 'D', 'L', 'GF', 'GA', 'GD', 'Pts' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					get_the_title( (int) $row['team_id'] ),
					$row['played'],
					$row['won'],
					$row['drawn'],
					$row['lost'],
					$row['goals_for'],
					$row['goals_against'],
					$row['goal_difference'],
					$row['points'],
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Read a CSV file into associative rows using the header line.
	 *
	 * @param string $path File path.
	 * @return array[]
	 */
	private function read_csv( $path ) {
		$rows   = array();
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			return $rows;
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $rows;
		}

		$header = array_map( 'strtolower', array_map( 'trim', $header ) );

		while ( ( $data = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			if ( count( $data ) === count( $header ) ) {
				$rows[] = array_combine( $header, $data );
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $rows;
	}

	/**
	 * Redirect back to the screen.
	 *
	 * @param array $args Query args.
	 * @return void
	 */
	private function redirect( array $args ) {
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					array(
						'post_type' => Keys::TEAM,
						'page'      => self::PAGE,
					),
					$args
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
