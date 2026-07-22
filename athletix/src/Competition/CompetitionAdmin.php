<?php
/**
 * Competition admin screen.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Admin\Hub;
use Athletix\Data\Repositories\LeagueRepository;
use Athletix\Data\Repositories\SeasonRepository;
use Athletix\Data\Repositories\TeamRepository;
use Athletix\Support\Keys;

/**
 * A working Competition Manager screen: generate a round-robin schedule or a
 * standings-seeded playoff bracket. Every action is nonce- and capability-
 * guarded and reports its result.
 */
class CompetitionAdmin {

	const PAGE            = 'athletix-competitions';
	const ACTION_SCHEDULE = 'athletix_generate_schedule';
	const ACTION_PLAYOFFS = 'athletix_generate_playoffs';

	/**
	 * Scheduler.
	 *
	 * @var CompetitionScheduler
	 */
	private $scheduler;

	/**
	 * Playoff seeder.
	 *
	 * @var PlayoffSeeder
	 */
	private $playoffs;

	/**
	 * Teams.
	 *
	 * @var TeamRepository
	 */
	private $teams;

	/**
	 * Leagues.
	 *
	 * @var LeagueRepository
	 */
	private $leagues;

	/**
	 * Seasons.
	 *
	 * @var SeasonRepository
	 */
	private $seasons;

	/**
	 * Constructor.
	 *
	 * @param CompetitionScheduler $scheduler Scheduler.
	 * @param PlayoffSeeder        $playoffs  Playoff seeder.
	 * @param TeamRepository       $teams     Teams.
	 * @param LeagueRepository     $leagues   Leagues.
	 * @param SeasonRepository     $seasons   Seasons.
	 */
	public function __construct( CompetitionScheduler $scheduler, PlayoffSeeder $playoffs, TeamRepository $teams, LeagueRepository $leagues, SeasonRepository $seasons ) {
		$this->scheduler = $scheduler;
		$this->playoffs  = $playoffs;
		$this->teams     = $teams;
		$this->leagues   = $leagues;
		$this->seasons   = $seasons;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'athletix/admin_tabs', array( $this, 'tab' ) );
		add_action( 'admin_post_' . self::ACTION_SCHEDULE, array( $this, 'handle_schedule' ) );
		add_action( 'admin_post_' . self::ACTION_PLAYOFFS, array( $this, 'handle_playoffs' ) );
	}

	/**
	 * Contribute the Competitions tab to the hub.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function tab( array $tabs ) {
		$tabs['competitions'] = array(
			'label'    => __( 'Competitions', 'athletix' ),
			'cap'      => Keys::capability(),
			'order'    => 30,
			'callback' => array( $this, 'render' ),
		);

		return $tabs;
	}

	/**
	 * Render the management screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Keys::capability() ) ) {
			return;
		}

		$leagues = $this->leagues->all( array( 'posts_per_page' => 200 ) );
		$seasons = $this->seasons->all( array( 'posts_per_page' => 200 ) );
		$action  = esc_url( admin_url( 'admin-post.php' ) );
		?>
		<h2><?php esc_html_e( 'Competition Manager', 'athletix' ); ?></h2>
		<?php $this->notice(); ?>

			<h2><?php esc_html_e( 'Generate Round-Robin Schedule', 'athletix' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Creates fixtures for every team in the selected league.', 'athletix' ); ?></p>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SCHEDULE ); ?>" />
				<?php wp_nonce_field( self::ACTION_SCHEDULE ); ?>
				<table class="form-table" role="presentation"><tbody>
					<?php $this->league_row( $leagues ); ?>
					<?php $this->season_row( $seasons ); ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'athletix' ); ?></th>
						<td>
							<label><input type="checkbox" name="double_round" value="1" /> <?php esc_html_e( 'Home &amp; away (double round-robin)', 'athletix' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ax-start"><?php esc_html_e( 'Start Date', 'athletix' ); ?></label></th>
						<td><input type="date" id="ax-start" name="start_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
							<label style="margin-left:1em;"><?php esc_html_e( 'Days between rounds', 'athletix' ); ?>
							<input type="number" name="days_between" value="7" min="1" class="small-text" /></label>
						</td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Generate Schedule', 'athletix' ) ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Generate Playoffs', 'athletix' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Seeds a single-elimination bracket from the current standings.', 'athletix' ); ?></p>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_PLAYOFFS ); ?>" />
				<?php wp_nonce_field( self::ACTION_PLAYOFFS ); ?>
				<table class="form-table" role="presentation"><tbody>
					<?php $this->league_row( $leagues ); ?>
					<?php $this->season_row( $seasons ); ?>
					<tr>
						<th scope="row"><label for="ax-teams"><?php esc_html_e( 'Qualifying Teams', 'athletix' ); ?></label></th>
						<td>
							<select id="ax-teams" name="teams">
								<?php foreach ( array( 2, 4, 8, 16 ) as $n ) : ?>
									<option value="<?php echo esc_attr( $n ); ?>" <?php selected( 8, $n ); ?>><?php echo esc_html( $n ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Generate Playoffs', 'athletix' ) ); ?>
			</form>
		<?php
	}

	/**
	 * League select row.
	 *
	 * @param \WP_Post[] $leagues Leagues.
	 * @return void
	 */
	private function league_row( $leagues ) {
		?>
		<tr>
			<th scope="row"><label for="ax-league"><?php esc_html_e( 'League', 'athletix' ); ?></label></th>
			<td>
				<select id="ax-league" name="league_id" required>
					<option value=""><?php esc_html_e( '— Select —', 'athletix' ); ?></option>
					<?php foreach ( $leagues as $league ) : ?>
						<option value="<?php echo esc_attr( $league->ID ); ?>"><?php echo esc_html( $league->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Season select row.
	 *
	 * @param \WP_Post[] $seasons Seasons.
	 * @return void
	 */
	private function season_row( $seasons ) {
		?>
		<tr>
			<th scope="row"><label for="ax-season"><?php esc_html_e( 'Season', 'athletix' ); ?></label></th>
			<td>
				<select id="ax-season" name="season_id">
					<option value="0"><?php esc_html_e( '— None —', 'athletix' ); ?></option>
					<?php foreach ( $seasons as $season ) : ?>
						<option value="<?php echo esc_attr( $season->ID ); ?>"><?php echo esc_html( $season->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Handle the schedule form.
	 *
	 * @return void
	 */
	public function handle_schedule() {
		check_admin_referer( self::ACTION_SCHEDULE );
		$this->authorize();

		$league_id = isset( $_POST['league_id'] ) ? absint( $_POST['league_id'] ) : 0;
		$season_id = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;
		$double    = ! empty( $_POST['double_round'] );
		$start     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$days      = isset( $_POST['days_between'] ) ? absint( $_POST['days_between'] ) : 7;

		if ( ! $league_id ) {
			$this->redirect( array( 'ax_error' => 'league' ) );
		}

		$team_ids = wp_list_pluck( $this->teams->for_league( $league_id ), 'ID' );

		if ( count( $team_ids ) < 2 ) {
			$this->redirect( array( 'ax_error' => 'teams' ) );
		}

		$created = $this->scheduler->generate_round_robin( $league_id, $season_id, $team_ids, $double, $start, $days );

		$this->redirect(
			array(
				'ax_done'    => 'schedule',
				'ax_matches' => count( $created ),
			)
		);
	}

	/**
	 * Handle the playoffs form.
	 *
	 * @return void
	 */
	public function handle_playoffs() {
		check_admin_referer( self::ACTION_PLAYOFFS );
		$this->authorize();

		$league_id = isset( $_POST['league_id'] ) ? absint( $_POST['league_id'] ) : 0;
		$season_id = isset( $_POST['season_id'] ) ? absint( $_POST['season_id'] ) : 0;
		$teams     = isset( $_POST['teams'] ) ? absint( $_POST['teams'] ) : 8;

		if ( ! $league_id ) {
			$this->redirect( array( 'ax_error' => 'league' ) );
		}

		$result = $this->playoffs->generate( $league_id, $season_id, $teams );

		$this->redirect(
			array(
				'ax_done'    => 'playoffs',
				'ax_matches' => count( $result['matches'] ),
				'ax_byes'    => count( $result['byes'] ),
			)
		);
	}

	/**
	 * Ensure the current user may manage competitions, or die.
	 *
	 * The nonce is verified by each handler via check_admin_referer() before
	 * this call, keeping the check visible at the point of form processing.
	 *
	 * @return void
	 */
	private function authorize() {
		if ( ! current_user_can( Keys::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'athletix' ) );
		}
	}

	/**
	 * Redirect back to the page with query args.
	 *
	 * @param array $args Query args.
	 * @return void
	 */
	private function redirect( array $args ) {
		wp_safe_redirect( add_query_arg( $args, Hub::tab_url( 'competitions' ) ) );
		exit;
	}

	/**
	 * Render a result/error notice from query args.
	 *
	 * @return void
	 */
	private function notice() {
		if ( isset( $_GET['ax_done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$matches = isset( $_GET['ax_matches'] ) ? absint( $_GET['ax_matches'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = sprintf(
				/* translators: %d: number of matches created. */
				_n( 'Created %d match.', 'Created %d matches.', $matches, 'athletix' ),
				$matches
			);
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
			return;
		}

		if ( isset( $_GET['ax_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$error   = sanitize_key( wp_unslash( $_GET['ax_error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = ( 'teams' === $error )
				? __( 'That league needs at least two teams.', 'athletix' )
				: __( 'Please select a league.', 'athletix' );
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}
}
