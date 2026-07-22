<?php
/**
 * Tournament builder.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Repositories\LeagueRepository;
use Athletix\Data\Repositories\RelationshipRepository;

/**
 * Creates a knockout tournament as a league flagged as such, with its teams
 * linked via the relationships table.
 *
 * The draft inserted an ax_league but then referenced teams three different
 * ways; here a single relationship ("league_team") is the one source of truth
 * for membership, and a meta flag distinguishes a tournament from a round-robin
 * league.
 */
class TournamentBuilder {

	const REL_LEAGUE_TEAM = 'league_team';
	const META_TOURNAMENT = '_ax_is_tournament';

	/**
	 * League repository.
	 *
	 * @var LeagueRepository
	 */
	private $leagues;

	/**
	 * Relationship repository.
	 *
	 * @var RelationshipRepository
	 */
	private $relationships;

	/**
	 * Constructor.
	 *
	 * @param LeagueRepository       $leagues       League storage.
	 * @param RelationshipRepository $relationships Relationship storage.
	 */
	public function __construct( LeagueRepository $leagues, RelationshipRepository $relationships ) {
		$this->leagues       = $leagues;
		$this->relationships = $relationships;
	}

	/**
	 * Create a tournament and link its teams.
	 *
	 * @param string $name     Tournament name.
	 * @param int[]  $team_ids Participating team ids.
	 * @return int|\WP_Error Tournament (league) id or error.
	 */
	public function create( $name, array $team_ids ) {
		$name = trim( wp_strip_all_tags( $name ) );

		if ( '' === $name ) {
			return new \WP_Error( 'athletix_no_name', __( 'A tournament name is required.', 'athletix' ) );
		}

		$league_id = $this->leagues->create( array( 'name' => $name ) );

		if ( is_wp_error( $league_id ) ) {
			return $league_id;
		}

		update_term_meta( $league_id, self::META_TOURNAMENT, 1 );

		foreach ( array_filter( array_map( 'absint', $team_ids ) ) as $team_id ) {
			$this->relationships->add( $league_id, $team_id, self::REL_LEAGUE_TEAM );
		}

		return (int) $league_id;
	}

	/**
	 * Team ids linked to a tournament/league.
	 *
	 * @param int $league_id League id.
	 * @return int[]
	 */
	public function team_ids( $league_id ) {
		return $this->relationships->targets( $league_id, self::REL_LEAGUE_TEAM );
	}
}
