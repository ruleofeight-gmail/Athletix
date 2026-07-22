<?php
/**
 * Sport rules provider.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Config;
use Athletix\Sports\SportRegistry;
use Athletix\Support\Keys;

/**
 * Supplies sport-specific scoring rules. Defaults now come from the resolved
 * sport profile (SportRegistry); the settings page still overrides them for the
 * site's primary sport, and the `athletix/sport_*` filters still apply. A league
 * can declare its own sport (League term meta), so multi-sport installs get
 * per-league scoring without touching the standings math.
 */
class SportEngine {

	/**
	 * Config service.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Sport profile registry.
	 *
	 * @var SportRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param Config        $config   Configuration.
	 * @param SportRegistry $registry Sport profile registry.
	 */
	public function __construct( Config $config, SportRegistry $registry ) {
		$this->config   = $config;
		$this->registry = $registry;
	}

	/**
	 * The site's primary (active) sport slug.
	 *
	 * @return string
	 */
	public function active() {
		return (string) $this->config->get( 'active_sport', 'soccer' );
	}

	/**
	 * The sport slug a league runs (from the League term's sport meta), falling
	 * back to the active sport.
	 *
	 * @param int $league_id League term id.
	 * @return string
	 */
	public function for_league( $league_id ) {
		$league_id = absint( $league_id );

		if ( $league_id ) {
			$sport_term = (int) get_term_meta( $league_id, Keys::LEAGUE_SPORT, true );
			if ( $sport_term ) {
				$term = get_term( $sport_term, Keys::TAX_SPORT );
				if ( $term && ! is_wp_error( $term ) ) {
					return $term->slug;
				}
			}
		}

		return $this->active();
	}

	/**
	 * The sport slug a team plays: its own Sport term if set, otherwise the sport
	 * of the league it belongs to, otherwise the active sport.
	 *
	 * @param int $team_id Team post id.
	 * @return string
	 */
	public function for_team( $team_id ) {
		$team_id = absint( $team_id );

		if ( $team_id ) {
			$sports = get_the_terms( $team_id, Keys::TAX_SPORT );
			if ( is_array( $sports ) && $sports ) {
				return $sports[0]->slug;
			}

			$leagues = get_the_terms( $team_id, Keys::LEAGUE );
			if ( is_array( $leagues ) && $leagues ) {
				return $this->for_league( $leagues[0]->term_id );
			}
		}

		return $this->active();
	}

	/**
	 * The profile for a sport slug (empty = active sport).
	 *
	 * @param string $sport Sport slug.
	 * @return \Athletix\Contracts\SportProfile
	 */
	public function profile( $sport = '' ) {
		return $this->registry->get( $sport ? $sport : $this->active() );
	}

	/**
	 * Points awarded for win/draw/loss for a sport.
	 *
	 * @param string $sport Sport slug; empty uses the active sport.
	 * @return array{win:int,draw:int,loss:int}
	 */
	public function points( $sport = '' ) {
		$slug    = $sport ? $sport : $this->active();
		$scoring = $this->registry->get( $slug )->scoring();

		// The primary sport still honours the settings-page point overrides.
		if ( $slug === $this->active() ) {
			$scoring = array(
				'win'  => (int) $this->config->get( 'points_win', isset( $scoring['win'] ) ? $scoring['win'] : 3 ),
				'draw' => (int) $this->config->get( 'points_draw', isset( $scoring['draw'] ) ? $scoring['draw'] : 1 ),
				'loss' => (int) $this->config->get( 'points_loss', isset( $scoring['loss'] ) ? $scoring['loss'] : 0 ),
			);
		}

		$points = array(
			'win'  => (int) ( isset( $scoring['win'] ) ? $scoring['win'] : 3 ),
			'draw' => (int) ( isset( $scoring['draw'] ) ? $scoring['draw'] : 1 ),
			'loss' => (int) ( isset( $scoring['loss'] ) ? $scoring['loss'] : 0 ),
		);

		/**
		 * Filter the points table for a sport.
		 *
		 * @param array  $points Win/draw/loss points.
		 * @param string $sport  Sport slug.
		 */
		return apply_filters( 'athletix/sport_points', $points, $slug );
	}

	/**
	 * Ordered tie-break chain for a sport's standings.
	 *
	 * @param string $sport Sport slug; empty uses the active sport.
	 * @return string[] Field names in priority order.
	 */
	public function tiebreakers( $sport = '' ) {
		$slug  = $sport ? $sport : $this->active();
		$chain = $this->registry->get( $slug )->tiebreakers();

		// The primary sport still honours the settings-page tie-break chain.
		if ( $slug === $this->active() ) {
			$configured = $this->config->get( 'tiebreakers', $chain );
			if ( is_array( $configured ) && $configured ) {
				$chain = $configured;
			}
		}

		if ( ! is_array( $chain ) || ! $chain ) {
			$chain = StandingsSorter::DEFAULT_CHAIN;
		}

		/**
		 * Filter the standings tie-break chain for a sport.
		 *
		 * @param string[] $chain Ordered field names.
		 * @param string   $sport Sport slug.
		 */
		return (array) apply_filters( 'athletix/sport_tiebreakers', $chain, $slug );
	}
}
