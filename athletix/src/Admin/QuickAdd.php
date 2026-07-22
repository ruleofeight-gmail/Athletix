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
 * Each screen has an "Add Another" flow for batch entry: the shared context
 * (Add Team → Sport/League/Division; Add Player → Team) stays put while the
 * per-item fields clear for the next record. With JavaScript this happens over
 * AJAX without a reload and a running "added this session" list builds up; with
 * JavaScript off, the form still posts normally and the context is carried back
 * through the redirect.
 *
 * Both screens create the same ax_team / ax_player posts the normal editor
 * would, so nothing downstream has to know these pages exist.
 */
class QuickAdd {

	const TEAM_PAGE      = 'athletix-add-team';
	const PLAYER_PAGE    = 'athletix-add-player';
	const TEAM_ACTION    = 'athletix_add_team';
	const PLAYER_ACTION  = 'athletix_add_player';
	const POSITIONS_AJAX = 'athletix_team_positions';
	const CREATE_TEAM    = 'athletix_create_team';
	const CREATE_PLAYER  = 'athletix_create_player';
	const CREATE_NONCE   = 'athletix_quick_add';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_' . self::TEAM_ACTION, array( $this, 'handle_team' ) );
		add_action( 'admin_post_' . self::PLAYER_ACTION, array( $this, 'handle_player' ) );
		add_action( 'wp_ajax_' . self::POSITIONS_AJAX, array( $this, 'ajax_positions' ) );
		add_action( 'wp_ajax_' . self::CREATE_TEAM, array( $this, 'ajax_create_team' ) );
		add_action( 'wp_ajax_' . self::CREATE_PLAYER, array( $this, 'ajax_create_player' ) );
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
	 * Enqueue the batch-add script + config on the two screens.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function assets( $hook ) {
		$kind = '';
		if ( false !== strpos( $hook, self::TEAM_PAGE ) ) {
			$kind = 'team';
		} elseif ( false !== strpos( $hook, self::PLAYER_PAGE ) ) {
			$kind = 'player';
		}

		if ( '' === $kind ) {
			return;
		}

		wp_enqueue_style( 'athletix-admin', ATHLETIX_URL . 'assets/css/admin.css', array(), ATHLETIX_VERSION );

		wp_enqueue_script(
			'athletix-quick-add',
			ATHLETIX_URL . 'assets/js/quick-add.js',
			array(),
			ATHLETIX_VERSION,
			true
		);

		wp_localize_script(
			'athletix-quick-add',
			'AthletixQuickAdd',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'kind'            => $kind,
				'createAction'    => 'team' === $kind ? self::CREATE_TEAM : self::CREATE_PLAYER,
				'nonce'           => wp_create_nonce( self::CREATE_NONCE ),
				'positionsAction' => self::POSITIONS_AJAX,
				'positionsNonce'  => wp_create_nonce( self::POSITIONS_AJAX ),
				'contextFields'   => 'team' === $kind
					? array( 'athletix_sport', 'athletix_league', 'athletix_division' )
					: array( 'athletix_team' ),
				'focusField'      => 'athletix_name',
				'i18n'            => array(
					'added'    => 'team' === $kind ? __( 'Team added.', 'athletix' ) : __( 'Player added.', 'athletix' ),
					'edit'     => __( 'Edit', 'athletix' ),
					'nameReq'  => __( 'A name is required.', 'athletix' ),
					'failed'   => __( 'Could not save. Please try again.', 'athletix' ),
					'none'     => __( '— None —', 'athletix' ),
					'free'     => __( 'This sport has no fixed positions — enter one if you like.', 'athletix' ),
					/* translators: %s: sport name. */
					'sport'    => __( 'Sport: %s', 'athletix' ),
					/* translators: %d: number of records added in this batch. */
					'sessionN' => __( 'Added this session (%d)', 'athletix' ),
				),
			)
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
				'preset'    => $this->preset( array( 'sport', 'league', 'division' ) ),
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
				'notice' => $this->notice( Keys::PLAYER ),
				'preset' => $this->preset( array( 'team' ) ),
			)
		);
	}

	/**
	 * Handle the Add Team submission (no-JS fallback).
	 *
	 * @return void
	 */
	public function handle_team() {
		$this->verify( self::TEAM_ACTION );

		$data = $this->read_team_input();
		$id   = $this->persist_team( $data );

		if ( is_wp_error( $id ) ) {
			$this->redirect( self::TEAM_PAGE, array( 'error' => $id->get_error_code() ) );
		}

		$args = array( 'created' => $id );
		if ( $this->wants_another() ) {
			$args['sport']    = $data['sport'];
			$args['league']   = $data['league'];
			$args['division'] = $data['division'];
		}

		$this->redirect( self::TEAM_PAGE, $args );
	}

	/**
	 * Handle the Add Player submission (no-JS fallback).
	 *
	 * @return void
	 */
	public function handle_player() {
		$this->verify( self::PLAYER_ACTION );

		$data = $this->read_player_input();
		$id   = $this->persist_player( $data );

		if ( is_wp_error( $id ) ) {
			$this->redirect( self::PLAYER_PAGE, array( 'error' => $id->get_error_code() ) );
		}

		$args = array( 'created' => $id );
		if ( $this->wants_another() ) {
			$args['team'] = $data['team'];
		}

		$this->redirect( self::PLAYER_PAGE, $args );
	}

	/**
	 * AJAX: create a team, returning JSON for the batch-add UI.
	 *
	 * @return void
	 */
	public function ajax_create_team() {
		$this->verify_ajax();

		$id = $this->persist_team( $this->read_team_input() );
		$this->send_created( $id );
	}

	/**
	 * AJAX: create a player, returning JSON for the batch-add UI.
	 *
	 * @return void
	 */
	public function ajax_create_player() {
		$this->verify_ajax();

		$id = $this->persist_player( $this->read_player_input() );
		$this->send_created( $id );
	}

	/**
	 * AJAX: the position list (and sport label) for a team.
	 *
	 * @return void
	 */
	public function ajax_positions() {
		check_ajax_referer( self::POSITIONS_AJAX, 'nonce' );

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
	 * Read + sanitize the Add Team form input into an array.
	 *
	 * Callers verify the nonce first (check_admin_referer / check_ajax_referer).
	 *
	 * @return array{name:string,sport:int,league:int,division:int,venue:string,founded:string,color:string}
	 */
	private function read_team_input() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by caller.
		return array(
			'name'     => isset( $_POST['athletix_name'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_name'] ) ) : '',
			'sport'    => isset( $_POST['athletix_sport'] ) ? absint( wp_unslash( $_POST['athletix_sport'] ) ) : 0,
			'league'   => isset( $_POST['athletix_league'] ) ? absint( wp_unslash( $_POST['athletix_league'] ) ) : 0,
			'division' => isset( $_POST['athletix_division'] ) ? absint( wp_unslash( $_POST['athletix_division'] ) ) : 0,
			'venue'    => isset( $_POST['athletix_venue'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_venue'] ) ) : '',
			'founded'  => isset( $_POST['athletix_founded'] ) && absint( wp_unslash( $_POST['athletix_founded'] ) ) ? (string) absint( wp_unslash( $_POST['athletix_founded'] ) ) : '',
			'color'    => isset( $_POST['athletix_color'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['athletix_color'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Read + sanitize the Add Player form input into an array.
	 *
	 * @return array{name:string,team:int,position:string,number:string,height:string,weight:string,country:string,dob:string}
	 */
	private function read_player_input() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by caller.
		$dob = isset( $_POST['athletix_dob'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_dob'] ) ) : '';

		return array(
			'name'     => isset( $_POST['athletix_name'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_name'] ) ) : '',
			'team'     => isset( $_POST['athletix_team'] ) ? absint( wp_unslash( $_POST['athletix_team'] ) ) : 0,
			'position' => isset( $_POST['athletix_position'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_position'] ) ) : '',
			'number'   => isset( $_POST['athletix_number'] ) && absint( wp_unslash( $_POST['athletix_number'] ) ) ? (string) absint( wp_unslash( $_POST['athletix_number'] ) ) : '',
			'height'   => isset( $_POST['athletix_height'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_height'] ) ) : '',
			'weight'   => isset( $_POST['athletix_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_weight'] ) ) : '',
			'country'  => isset( $_POST['athletix_country'] ) ? sanitize_text_field( wp_unslash( $_POST['athletix_country'] ) ) : '',
			'dob'      => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob ) ? $dob : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Create a team from sanitized data.
	 *
	 * @param array $data Team input.
	 * @return int|\WP_Error New team id or error.
	 */
	private function persist_team( array $data ) {
		if ( '' === $data['name'] ) {
			return new \WP_Error( 'name', __( 'A name is required.', 'athletix' ) );
		}

		$team_id = wp_insert_post(
			array(
				'post_type'   => Keys::TEAM,
				'post_status' => 'publish',
				'post_title'  => $data['name'],
			),
			true
		);

		if ( is_wp_error( $team_id ) ) {
			return new \WP_Error( 'save', $team_id->get_error_message() );
		}

		$this->set_term( $team_id, Keys::TAX_SPORT, $data['sport'] );
		$this->set_term( $team_id, Keys::LEAGUE, $data['league'] );
		$this->set_term( $team_id, Keys::DIVISION, $data['division'] );

		$this->set_meta( $team_id, Keys::TEAM_VENUE, $data['venue'] );
		$this->set_meta( $team_id, Keys::TEAM_FOUNDED, $data['founded'] );
		$this->set_meta( $team_id, Keys::TEAM_COLOR, $data['color'] );

		return (int) $team_id;
	}

	/**
	 * Create a player from sanitized data.
	 *
	 * @param array $data Player input.
	 * @return int|\WP_Error New player id or error.
	 */
	private function persist_player( array $data ) {
		if ( '' === $data['name'] ) {
			return new \WP_Error( 'name', __( 'A name is required.', 'athletix' ) );
		}

		$player_id = wp_insert_post(
			array(
				'post_type'   => Keys::PLAYER,
				'post_status' => 'publish',
				'post_title'  => $data['name'],
			),
			true
		);

		if ( is_wp_error( $player_id ) ) {
			return new \WP_Error( 'save', $player_id->get_error_message() );
		}

		if ( $data['team'] ) {
			update_post_meta( $player_id, Keys::PLAYER_TEAM, $data['team'] );
		}

		// Only accept a position the team's sport recognises; a sport with no
		// fixed positions keeps whatever free text was entered.
		$position = $data['position'];
		if ( '' !== $position ) {
			$allowed = $this->positions_for_team( $data['team'] );
			if ( $allowed && ! in_array( $position, $allowed, true ) ) {
				$position = '';
			}
		}

		$this->set_meta( $player_id, Keys::PLAYER_POSITION, $position );
		$this->set_meta( $player_id, Keys::PLAYER_NUMBER, $data['number'] );
		$this->set_meta( $player_id, Keys::PLAYER_HEIGHT, $data['height'] );
		$this->set_meta( $player_id, Keys::PLAYER_WEIGHT, $data['weight'] );
		$this->set_meta( $player_id, Keys::PLAYER_COUNTRY, $data['country'] );
		$this->set_meta( $player_id, Keys::PLAYER_DOB, $data['dob'] );

		return (int) $player_id;
	}

	/**
	 * Send the created-record JSON response (or an error).
	 *
	 * @param int|\WP_Error $id Created id or error.
	 * @return void
	 */
	private function send_created( $id ) {
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'code' => $id->get_error_code() ), 400 );
		}

		wp_send_json_success(
			array(
				'id'       => (int) $id,
				'title'    => get_the_title( $id ),
				'editLink' => (string) get_edit_post_link( $id, '' ),
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
	 * Assign a single term to a post (clearing when 0).
	 *
	 * @param int    $post_id  Post id.
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $term_id  Term id (0 = none).
	 * @return void
	 */
	private function set_term( $post_id, $taxonomy, $term_id ) {
		if ( $term_id ) {
			wp_set_object_terms( $post_id, array( (int) $term_id ), $taxonomy, false );
		}
	}

	/**
	 * Persist a meta value, deleting the key when the value is empty.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @param string $value   Sanitized value.
	 * @return void
	 */
	private function set_meta( $post_id, $key, $value ) {
		if ( '' === (string) $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Whether the "Add Another" button was used.
	 *
	 * @return bool
	 */
	private function wants_another() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify().
		return ! empty( $_POST['athletix_add_another'] );
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
	 * Verify nonce + capability for an AJAX create call or stop.
	 *
	 * @return void
	 */
	private function verify_ajax() {
		check_ajax_referer( self::CREATE_NONCE, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}
	}

	/**
	 * Read the carried-over context ids from the redirect query for prefilling.
	 *
	 * @param string[] $keys Context keys to read.
	 * @return array<string,int>
	 */
	private function preset( array $keys ) {
		$preset = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only prefill of ids from our own redirect.
		foreach ( $keys as $key ) {
			$preset[ $key ] = isset( $_GET[ $key ] ) ? absint( wp_unslash( $_GET[ $key ] ) ) : 0;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $preset;
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
