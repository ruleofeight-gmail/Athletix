<?php
/**
 * Data layer module.
 *
 * @package Athletix
 */

namespace Athletix\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Data\Repositories\LeagueRepository;
use Athletix\Data\Repositories\MatchRepository;
use Athletix\Data\Repositories\PlayerRepository;
use Athletix\Data\Repositories\PlayerStatsRepository;
use Athletix\Data\Repositories\RelationshipRepository;
use Athletix\Data\Repositories\SeasonRepository;
use Athletix\Data\Repositories\StandingsRepository;
use Athletix\Data\Repositories\TeamRepository;
use Athletix\Plugin;

/**
 * Installs custom tables and exposes repositories through the container under
 * the `repo.*` ids so engines and controllers resolve them by contract.
 */
class DataModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'data';
	}

	/**
	 * Register schema install + repository bindings.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$schema = new Schema();

		// Create tables on activation, and lazily upgrade on load if behind.
		add_action( 'athletix/activate', array( $schema, 'install' ) );
		add_action( 'admin_init', array( $schema, 'install' ) );

		$container = $plugin->container();

		$container->bind( 'repo.team', static function () {
			return new TeamRepository();
		} );
		$container->bind( 'repo.player', static function () {
			return new PlayerRepository();
		} );
		$container->bind( 'repo.match', static function () {
			return new MatchRepository();
		} );
		$container->bind( 'repo.league', static function () {
			return new LeagueRepository();
		} );
		$container->bind( 'repo.season', static function () {
			return new SeasonRepository();
		} );
		$container->bind( 'repo.standings', static function () {
			return new StandingsRepository();
		} );
		$container->bind( 'repo.player_stats', static function () {
			return new PlayerStatsRepository();
		} );
		$container->bind( 'repo.relationship', static function () {
			return new RelationshipRepository();
		} );
	}
}
