<?php
/**
 * Teams / players / matches REST controller.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Repositories\MatchRepository;
use Athletix\Data\Repositories\PlayerRepository;
use Athletix\Data\Repositories\TeamRepository;
use Athletix\Support\Keys;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Read endpoints for the core content entities.
 */
class ContentController extends AbstractController {

	/**
	 * Teams.
	 *
	 * @var TeamRepository
	 */
	private $teams;

	/**
	 * Players.
	 *
	 * @var PlayerRepository
	 */
	private $players;

	/**
	 * Matches.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Constructor.
	 *
	 * @param TeamRepository   $teams   Teams.
	 * @param PlayerRepository $players Players.
	 * @param MatchRepository  $matches Matches.
	 */
	public function __construct( TeamRepository $teams, PlayerRepository $players, MatchRepository $matches ) {
		$this->teams   = $teams;
		$this->players = $players;
		$this->matches = $matches;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$read = array( $this, 'public_read' );

		register_rest_route(
			self::NS,
			'/teams',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_teams' ),
				'permission_callback' => $read,
				'args'                => array(
					'league' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/players',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_players' ),
				'permission_callback' => $read,
				'args'                => array(
					'team' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/matches',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_matches' ),
				'permission_callback' => $read,
				'args'                => array(
					'league' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'season' => array(
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * GET /teams.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_teams( WP_REST_Request $request ) {
		$league = (int) $request['league'];
		$posts  = $league ? $this->teams->for_league( $league ) : $this->teams->all();

		return new WP_REST_Response( array_map( array( $this, 'basic_post' ), $posts ), 200 );
	}

	/**
	 * GET /players.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_players( WP_REST_Request $request ) {
		$team  = (int) $request['team'];
		$posts = $team ? $this->players->for_team( $team ) : $this->players->all();

		$data = array();
		foreach ( $posts as $post ) {
			$row             = $this->basic_post( $post );
			$row['position'] = $this->players->get_meta( $post->ID, Keys::PLAYER_POSITION, '' );
			$row['number']   = (int) $this->players->get_meta( $post->ID, Keys::PLAYER_NUMBER, 0 );
			$data[]          = $row;
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * GET /matches.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_matches( WP_REST_Request $request ) {
		$league = (int) $request['league'];
		$season = (int) $request['season'];

		$args = array( 'posts_per_page' => 100 );
		$meta = array();
		if ( $league ) {
			$meta[] = array(
				'key'   => Keys::MATCH_LEAGUE,
				'value' => $league,
			);
		}
		if ( $season ) {
			$meta[] = array(
				'key'   => Keys::MATCH_SEASON,
				'value' => $season,
			);
		}
		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$data = array();
		foreach ( $this->matches->all( $args ) as $post ) {
			$details = $this->matches->details( $post->ID );
			$data[]  = array(
				'id'         => $post->ID,
				'title'      => get_the_title( $post ),
				'date'       => $details['date'],
				'status'     => $details['status'],
				'home_team'  => get_the_title( $details['home'] ),
				'away_team'  => get_the_title( $details['away'] ),
				'home_score' => $details['home_score'],
				'away_score' => $details['away_score'],
			);
		}

		return new WP_REST_Response( $data, 200 );
	}
}
