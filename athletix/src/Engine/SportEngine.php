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

/**
 * Supplies sport-specific scoring rules. Points come from configuration and
 * can be overridden per sport via the `athletix/sport_points` filter, so a new
 * sport never requires touching the standings math.
 */
class SportEngine {

	/**
	 * Config service.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param Config $config Configuration.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * The currently active sport slug.
	 *
	 * @return string
	 */
	public function active() {
		return (string) $this->config->get( 'active_sport', 'soccer' );
	}

	/**
	 * Points awarded for win/draw/loss for a sport.
	 *
	 * @param string $sport Sport slug; empty uses the active sport.
	 * @return array{win:int,draw:int,loss:int}
	 */
	public function points( $sport = '' ) {
		$sport = $sport ? $sport : $this->active();

		$points = array(
			'win'  => (int) $this->config->get( 'points_win', 3 ),
			'draw' => (int) $this->config->get( 'points_draw', 1 ),
			'loss' => (int) $this->config->get( 'points_loss', 0 ),
		);

		/**
		 * Filter the points table for a sport.
		 *
		 * @param array  $points Win/draw/loss points.
		 * @param string $sport  Sport slug.
		 */
		return apply_filters( 'athletix/sport_points', $points, $sport );
	}

	/**
	 * Ordered tie-break chain for a sport's standings.
	 *
	 * @param string $sport Sport slug; empty uses the active sport.
	 * @return string[] Field names in priority order.
	 */
	public function tiebreakers( $sport = '' ) {
		$sport = $sport ? $sport : $this->active();

		$chain = $this->config->get( 'tiebreakers', StandingsSorter::DEFAULT_CHAIN );

		if ( ! is_array( $chain ) || ! $chain ) {
			$chain = StandingsSorter::DEFAULT_CHAIN;
		}

		/**
		 * Filter the standings tie-break chain for a sport.
		 *
		 * @param string[] $chain Ordered field names.
		 * @param string   $sport Sport slug.
		 */
		return (array) apply_filters( 'athletix/sport_tiebreakers', $chain, $sport );
	}
}
