<?php
/**
 * Competition module.
 *
 * @package Athletix
 */

namespace Athletix\Competition;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Engine\ScheduleCalculator;
use Athletix\Plugin;

/**
 * Wires divisions, scheduling, brackets, playoffs, tournaments and the
 * Competition admin screen through the container.
 */
class CompetitionModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'competition';
	}

	/**
	 * Register competition services and admin.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$c = $plugin->container();

		$c->bind(
			'competition.divisions',
			static function () use ( $plugin ) {
				return new Divisions( $plugin->make( 'repo.relationship' ) );
			}
		);

		$c->bind(
			'competition.bracket_seeder',
			static function () {
				return new BracketSeeder();
			}
		);

		$c->bind(
			'competition.scheduler',
			static function () use ( $plugin ) {
				return new CompetitionScheduler( $plugin->make( 'repo.match' ), new ScheduleCalculator() );
			}
		);

		$c->bind(
			'competition.playoffs',
			static function () use ( $plugin ) {
				return new PlayoffSeeder(
					$plugin->make( 'engine.ranking' ),
					$plugin->make( 'competition.bracket_seeder' ),
					$plugin->make( 'competition.scheduler' )
				);
			}
		);

		$c->bind(
			'competition.tournaments',
			static function () use ( $plugin ) {
				return new TournamentBuilder( $plugin->make( 'repo.league' ), $plugin->make( 'repo.relationship' ) );
			}
		);

		if ( is_admin() ) {
			$admin = new CompetitionAdmin(
				$plugin->make( 'competition.scheduler' ),
				$plugin->make( 'competition.playoffs' ),
				$plugin->make( 'repo.team' ),
				$plugin->make( 'repo.league' ),
				$plugin->make( 'repo.season' )
			);
			$admin->register();
		}
	}
}
