<?php
/**
 * WP-CLI commands.
 *
 * @package Athletix
 */

namespace Athletix\Cli;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;

/**
 * `wp athletix` commands for maintenance from the command line.
 */
class Commands {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Rebuild league standings from completed matches.
	 *
	 * ## OPTIONS
	 *
	 * [--league=<id>]
	 * : Limit to a single league term id. Omit to recompute every league.
	 *
	 * [--season=<id>]
	 * : Restrict to a season term id. Defaults to all seasons (0).
	 *
	 * ## EXAMPLES
	 *
	 *     wp athletix recompute
	 *     wp athletix recompute --league=12 --season=3
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function recompute( $args, $assoc_args ) {
		$season  = isset( $assoc_args['season'] ) ? absint( $assoc_args['season'] ) : 0;
		$engine  = $this->plugin->make( 'engine.standings' );
		$leagues = isset( $assoc_args['league'] )
			? array( absint( $assoc_args['league'] ) )
			: wp_list_pluck( $this->plugin->make( 'repo.league' )->all(), 'term_id' );

		$count = 0;
		foreach ( $leagues as $league_id ) {
			$league_id = absint( $league_id );
			if ( $league_id ) {
				$engine->recompute( $league_id, $season );
				++$count;
			}
		}

		\WP_CLI::success(
			sprintf(
				/* translators: %d: number of leagues recomputed. */
				_n( 'Recomputed %d league.', 'Recomputed %d leagues.', $count, 'athletix' ),
				$count
			)
		);
	}
}
