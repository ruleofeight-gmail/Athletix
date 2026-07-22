<?php
/**
 * Sport-aware "Add Team" / "Add Player" admin pages.
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
 * Guided, sport-specific creation screens under the Athletix menu. "Add Team"
 * ties a team to a sport (and optionally a league/division); "Add Player" binds
 * a player to a team and offers the position list that belongs to that team's
 * sport profile — so the form always speaks the language of the sport in play.
 *
 * Both screens are thin: they collect a few fields, then create the same
 * ax_team / ax_player posts the normal editor would, so nothing downstream has
 * to know these pages exist.
 */
class QuickAdd {

	const TEAM_PAGE     = 'athletix-add-team';
	const PLAYER_PAGE   = 'athletix-add-player';
	const TEAM_ACTION   = 'athletix_add_team';
	const PLAYER_ACTION = 'athletix_add_player';
	const AJAX_ACTION   = 'athletix_team_positions';

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ), 12 );
		add_action( 'admin_post_' . self::TEAM_ACTION, array( $this, 'handle_team' ) );
		add_action( 'admin_post_' . self::PLAYER_ACTION, array( $this, 'handle_player' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_positions' ) );
	}

	/**
	 * Add the two creation pages as Athletix submenus.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			Hub::SLUG,
			__( 'Add Team', 'athletix' ),
			__( 'Add Team', 'athletix' ),
			'edit_posts',
			self::TEAM_PAGE,
			array( $this, 'render_team_page' )
		);

		add_submenu_page(
			Hub::SLUG,
			__( 'Add Player', 'athletix' ),
			__( 'Add Player', 'athletix' ),
			'edit_posts',
			self::PLAYER_PAGE,
			array( $this, 'render_player_page' )
		);
	}

	/**
	 * Render the Add Team screen.
	 *
	 * @return void
	 */
	public function render_team_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$this->render(
			'add-team',
			array(
				'sports'    => $this->terms( Keys::TAX_SPORT ),
				'leagues'   => $this->terms( Keys::LEAGUE ),
				'divisions' => $this->terms( Keys::DIVISION ),
				'action'    => self::TEAM_ACTION,
				'notice'    => $this->notice( Keys::TEAM ),
			)
		);
	}

	/**
	 * Render the Add Player screen.
	 *
	 * @return void
	 */
	public function render_player_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$teams = get_posts(
			array(
				'post_type'      => Keys::TEAM,
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$this->render(
			'add-player',
			array(
				'teams'  => $teams,
				'action' => self::PLAYER_ACTION,
				'ajax'   => self::AJAX_ACTION,
				'nonce'  => wp_create_nonce( self::AJAX_ACTION ),
				'notice' => $this->notice( Keys::PLAYER ),
			)
		);
	}

	/**
	 * Handle the Add Team submission.
	 *
	 * @return void
	 */
	public function handle_team() {
		$this->verify( self::TEAM_ACTION );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
		$name = isset( $_POST['athletix_name'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_name'] ) ) : '';
		if ( '' === $name ) {
			$this->redirect( self::TEAM_PAGE, array( 'error' => 'name' ) );
		}

		$team_id = wp_insert_post(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'publish',
				'post_title'  => $name,
			),
			true
		);

		if ( is_wp_error( $team_id ) ) {
			$this->redirect( self::TEAM_PAGE, array( 'error' => 'save' ) );
		}

		$this->assign_term( $team_id, Keys::TAX_SPORT, 'athletix_sport' );
		$this->assign_term( $team_id, Keys::LEAGUE, 'athletix_league' );
		$this->assign_term( $team_id, Keys::DIVISION, 'athletix_division' );

		$this->save_meta( $team_id, Keys::TEAM_VENUE, 'athletix_venue', 'text' );
		$this->save_meta( $team_id, Keys::TEAM_FOUNDED, 'athletix_founded', 'int' );
		$this->save_meta( $team_id, Keys::TEAM_COLOR, 'athletix_color', 'color' );

		$this->redirect( self::TEAM_PAGE, array( 'created' => $team_id ) );
	}

	/**
	 * Handle the Add Player submission.
	 *
	 * @return void
	 */
	public function handle_player() {
		$this->verify( self::PLAYER_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
		$name    = isset( $_POST['athletix_name'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_name'] ) ) : '';
		$team_id = isset( $_POST['athletix_team'] ) ? absint( wp_unslash( $_POST['athletix_team'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $name ) {
			$this->redirect( self::PLAYER_PAGE, array( 'error' => 'name' ) );
		}

		$player_id = wp_insert_post(
			array(
				'post_type'   => Keys::PLAYER,
				'post_status' => 'publish',
				'post_title'  => $name,
			),
			true
		);

		if ( is_wp_error( $player_id ) ) {
			$this->redirect( self::PLAYER_PAGE, array( 'error' => 'save' ) );
		}

		if ( $team_id ) {
			update_post_meta( $player_id, Keys::PLAYER_TEAM, $team_id );
		}

		// Only accept a position the team's sport actually recognises; anything
		// else (or a sport with no fixed positions) is stored as free text.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
		$position = isset( $_POST['athletix_position'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_position'] ) ) : '';
		if ( '' !== $position ) {
			$allowed = $this->positions_for_team( $team_id );
			if ( $allowed && ! in_array( $position, $allowed, true ) ) {
				$position = '';
			}
			if ( '' !== $position ) {
				update_post_meta( $player_id, Keys::PLAYER_POSITION, $position );
			}
		}

		$this->save_meta( $player_id, Keys::PLAYER_NUMBER, 'athletix_number', 'int' );
		$this->save_meta( $player_id, Keys::PLAYER_HEIGHT, 'athletix_height', 'text' );
		$this->save_meta( $player_id, Keys::PLAYER_WEIGHT, 'athletix_weight', 'text' );
		$this->save_meta( $player_id, Keys::PLAYER_COUNTRY, 'athletix_country', 'text' );
		$this->save_meta( $player_id, Keys::PLAYER_DOB, 'athletix_dob', 'date' );

		$this->redirect( self::PLAYER_PAGE, array( 'created' => $player_id ) );
	}

	/**
	 * AJAX: the position list (and sport label) for a team.
	 *
	 * @return void
	 */
	public function ajax_positions() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$team_id = isset( $_GET['team'] ) ? absint( wp_unslash( $_GET['team'] ) ) : 0;
		$engine  = $this->plugin->make( 'engine.sport' );
		$sport   = $engine->for_team( $team_id );

		wp_send_json_success(
			array(
				'sport'     => $sport,
				'label'     => $engine->profile( $sport )->label(),
				'positions' => $engine->profile( $sport )->positions(),
			)
		);
	}

	/**
	 * The position slugs valid for a team's sport (empty = no fixed positions).
	 *
	 * @param int $team_id Team id.
	 * @return string[]
	 */
	private function positions_for_team( $team_id ) {
		$engine = $this->plugin->make( 'engine.sport' );

		return (array) $engine->profile( $engine->for_team( $team_id ) )->positions();
	}

	/**
	 * Assign a single submitted term to a post.
	 *
	 * @param int    $post_id  Post id.
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $field    POST field carrying the term id.
	 * @return void
	 */
	private function assign_term( $post_id, $taxonomy, $field ) {
		$term_id = isset( $_POST[ $field ] ) ? absint( wp_unslash( $_POST[ $field ] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
		if ( $term_id ) {
			wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, false );
		}
	}

	/**
	 * Sanitize and persist one meta value from POST.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @param string $field   POST field name.
	 * @param string $type    Sanitizer: int|color|date|text.
	 * @return void
	 */
	private function save_meta( $post_id, $key, $field, $type ) {
		if ( ! isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
			return;
		}

		$raw = wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in verify(); sanitized below.

		switch ( $type ) {
			case 'int':
				$value = (string) absint( $raw );
				$value = '0' === $value ? '' : $value;
				break;
			case 'color':
				$value = sanitize_hex_color( $raw );
				break;
			case 'date':
				$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $raw ) ? $raw : '';
				break;
			default:
				$value = sanitize_text_field( $raw );
				break;
		}

		if ( '' === (string) $value || null === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Verify nonce + capability for a form action or stop.
	 *
	 * @param string $action Action / nonce name.
	 * @return void
	 */
	private function verify( $action ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'athletix' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Redirect back to an admin page with query args, then exit.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query args.
	 * @return void
	 */
	private function redirect( $page, array $args ) {
		$url = add_query_arg(
			array_merge( array( 'page' => $page ), $args ),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Build the success/error notice for a screen from the redirect query args.
	 *
	 * @param string $post_type Post type being created.
	 * @return array{type:string,message:string,link:string}|null
	 */
	private function notice( $post_type ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of redirect args.
		if ( isset( $_GET['created'] ) ) {
			$id = absint( wp_unslash( $_GET['created'] ) );

			return array(
				'type'    => 'success',
				'message' => Keys::TEAM === $post_type
					? __( 'Team created.', 'athletix' )
					: __( 'Player created.', 'athletix' ),
				'link'    => $id ? (string) get_edit_post_link( $id, '' ) : '',
			);
		}

		if ( isset( $_GET['error'] ) ) {
			$error = sanitize_key( wp_unslash( $_GET['error'] ) );

			return array(
				'type'    => 'error',
				'message' => 'name' === $error
					? __( 'A name is required.', 'athletix' )
					: __( 'Could not save. Please try again.', 'athletix' ),
				'link'    => '',
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return null;
	}

	/**
	 * Fetch terms of a taxonomy for a dropdown.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \WP_Term[]
	 */
	private function terms( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 300,
			)
		);

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Render an admin template partial.
	 *
	 * @param string $template Template slug under templates/admin/.
	 * @param array  $data     Data extracted into scope.
	 * @return void
	 */
	private function render( $template, array $data ) {
		$file = ATHLETIX_PATH . 'templates/admin/' . $template . '.php';
		if ( ! is_readable( $file ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );
		include $file;
	}
}
