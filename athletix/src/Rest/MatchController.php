<?php
/**
 * Match write REST controller.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;
use Athletix\Data\Repositories\MatchRepository;
use Athletix\Support\Keys;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Authenticated write endpoint for recording a match result.
 *
 * POST /athletix/v1/matches/{id}/result updates the score/status meta and fires
 * the same `match_saved` event the editor does, so standings recompute exactly
 * as they would from wp-admin — no separate write path to drift out of sync.
 */
class MatchController extends AbstractController {

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Event dispatcher.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Permission policy.
	 *
	 * @var Permissions
	 */
	private $permissions;

	/**
	 * Constructor.
	 *
	 * @param MatchRepository $matches     Match repository.
	 * @param Events          $events      Event dispatcher.
	 * @param Permissions     $permissions Permission policy.
	 */
	public function __construct( MatchRepository $matches, Events $events, Permissions $permissions ) {
		$this->matches     = $matches;
		$this->events      = $events;
		$this->permissions = $permissions;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/matches/(?P<id>\d+)/result',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_result' ),
				'permission_callback' => array( $this->permissions, 'can_manage' ),
				'args'                => array(
					'id'         => array(
						'sanitize_callback' => 'absint',
					),
					'home_score' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'away_score' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'status'     => array(
						'default'           => Keys::STATUS_COMPLETED,
						'sanitize_callback' => 'sanitize_key',
						'enum'              => array( Keys::STATUS_SCHEDULED, Keys::STATUS_COMPLETED ),
					),
				),
			)
		);
	}

	/**
	 * Record a match result and trigger a standings recompute.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_result( WP_REST_Request $request ) {
		$match_id = (int) $request['id'];

		if ( get_post_type( $match_id ) !== Keys::MATCH ) {
			return new WP_Error(
				'athletix_match_not_found',
				__( 'No match found for that id.', 'athletix' ),
				array( 'status' => 404 )
			);
		}

		$details = $this->matches->details( $match_id );

		if ( ! $details['home'] || ! $details['away'] ) {
			return new WP_Error(
				'athletix_match_incomplete',
				__( 'This match has no teams assigned yet.', 'athletix' ),
				array( 'status' => 409 )
			);
		}

		$status = (string) $request['status'];

		$this->matches->set_meta( $match_id, Keys::MATCH_HOME_SCORE, (int) $request['home_score'] );
		$this->matches->set_meta( $match_id, Keys::MATCH_AWAY_SCORE, (int) $request['away_score'] );
		$this->matches->set_meta( $match_id, Keys::MATCH_STATUS, $status );

		$payload = array_merge(
			array( 'match_id' => $match_id ),
			$this->matches->details( $match_id )
		);

		if ( Keys::STATUS_COMPLETED === $status ) {
			$this->events->fire( 'match_completed', $payload );
		}

		$this->events->fire( 'match_saved', $payload );

		return new WP_REST_Response(
			array(
				'id'         => $match_id,
				'home_score' => (int) $request['home_score'],
				'away_score' => (int) $request['away_score'],
				'status'     => $status,
				'saved'      => true,
			),
			200
		);
	}
}
