<?php
/**
 * Compact mobile REST endpoints.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Engine\StandingsEngine;
use Athletix\Data\Repositories\MatchRepository;
use Athletix\Support\Keys;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Slim endpoints tuned for mobile clients: a single call returns a league's
 * top standings plus upcoming fixtures.
 */
class MobileController extends AbstractController {

	/**
	 * Standings engine.
	 *
	 * @var StandingsEngine
	 */
	private $standings;

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Constructor.
	 *
	 * @param StandingsEngine $standings Standings engine.
	 * @param MatchRepository $matches   Match repository.
	 */
	public function __construct( StandingsEngine $standings, MatchRepository $matches ) {
		$this->standings = $standings;
		$this->matches   = $matches;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/mobile/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'summary' ),
				'permission_callback' => array( $this, 'public_read' ),
				'args'                => array(
					'league' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Compact league summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function summary( WP_REST_Request $request ) {
		$league = (int) $request['league'];

		$table = array_slice( $this->standings->table( $league, 0 ), 0, 5 );
		$top   = array();
		foreach ( $table as $i => $row ) {
			$top[] = array(
				'rank'   => $i + 1,
				'team'   => get_the_title( (int) $row['team_id'] ),
				'points' => (int) $row['points'],
			);
		}

		$upcoming = array();
		$posts    = $this->matches->all(
			array(
				'posts_per_page' => 5,
				'orderby'        => 'meta_value',
				'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => Keys::MATCH_LEAGUE,
						'value' => $league,
					),
					array(
						'key'   => Keys::MATCH_STATUS,
						'value' => Keys::STATUS_SCHEDULED,
					),
				),
			)
		);

		foreach ( $posts as $post ) {
			$d          = $this->matches->details( $post->ID );
			$upcoming[] = array(
				'date' => $d['date'],
				'home' => get_the_title( $d['home'] ),
				'away' => get_the_title( $d['away'] ),
			);
		}

		return new WP_REST_Response(
			array(
				'league'    => get_the_title( $league ),
				'standings' => $top,
				'upcoming'  => $upcoming,
			),
			200
		);
	}
}
