<?php
/**
 * Sports engine module.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Binds the engines into the container and registers their hooks/listeners.
 */
class EngineModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'engine';
	}

	/**
	 * Wire the engine layer.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		$c = $plugin->container();

		$c->bind(
			'engine.sport',
			static function () use ( $plugin ) {
				return new SportEngine( $plugin->config() );
			}
		);

		$c->bind(
			'engine.match',
			static function () use ( $plugin ) {
				return new MatchEngine( $plugin->events(), $plugin->make( 'repo.match' ), $plugin->logger() );
			}
		);

		$c->bind(
			'engine.standings',
			static function () use ( $plugin ) {
				return new StandingsEngine(
					$plugin->events(),
					$plugin->make( 'repo.match' ),
					$plugin->make( 'repo.standings' ),
					$plugin->make( 'engine.sport' ),
					$plugin->cache()
				);
			}
		);

		$c->bind(
			'engine.ranking',
			static function () use ( $plugin ) {
				return new RankingEngine( $plugin->make( 'engine.standings' ) );
			}
		);

		$c->bind(
			'engine.statistics',
			static function () use ( $plugin ) {
				return new StatisticsEngine( $plugin->events(), $plugin->make( 'repo.player_stats' ) );
			}
		);

		// Instantiate the engines that subscribe to events/hooks.
		$plugin->make( 'engine.match' )->register();
		$plugin->make( 'engine.standings' )->register();
		$plugin->make( 'engine.statistics' )->register();
	}
}
