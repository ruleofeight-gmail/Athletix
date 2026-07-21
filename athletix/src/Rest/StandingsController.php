<?php
/**
 * Standings REST controller.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Engine\StandingsEngine;
use WP_REST_Request;
use WP_REST_Response;

/**
 * GET /athletix/v1/standings?league=&season=
 */
class StandingsController extends AbstractController {

	/**
	 * Standings engine.
	 *
	 * @var StandingsEngine
	 */
	private $standings;

	/**
	 * Constructor.
	 *
	 * @param StandingsEngine $standings Standings engine.
	 */
	public function __construct( StandingsEngine $standings ) {
		$this->standings = $standings;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/standings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_standings' ),
				'permission_callback' => array( $this, 'public_read' ),
				'args'                => array(
					'league' => array(
						'required'          => true,
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
	 * Return the ordered standings table with team titles.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_standings( WP_REST_Request $request ) {
		$league = (int) $request['league'];
		$season = (int) $request['season'];

		$rows = $this->standings->table( $league, $season );

		$data = array();
		$rank = 0;
		foreach ( $rows as $row ) {
			++$rank;
			$data[] = array(
				'rank'            => $rank,
				'team_id'         => (int) $row['team_id'],
				'team'            => get_the_title( (int) $row['team_id'] ),
				'played'          => (int) $row['played'],
				'won'             => (int) $row['won'],
				'drawn'           => (int) $row['drawn'],
				'lost'            => (int) $row['lost'],
				'goals_for'       => (int) $row['goals_for'],
				'goals_against'   => (int) $row['goals_against'],
				'goal_difference' => (int) $row['goal_difference'],
				'points'          => (int) $row['points'],
			);
		}

		return new WP_REST_Response( $data, 200 );
	}
}
