<?php
/**
 * Central registry of post-type slugs, taxonomy slugs and meta keys.
 *
 * One source of truth prevents the key drift that creeps in when strings are
 * repeated across dozens of files.
 *
 * @package Athletix
 */

namespace Athletix\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants only — never instantiated.
 */
final class Keys {

	/* Post types. */
	const LEAGUE   = 'ax_league';
	const SEASON   = 'ax_season';
	const TEAM     = 'ax_team';
	const PLAYER   = 'ax_player';
	const MATCH    = 'ax_match';
	const DIVISION = 'ax_division';

	/* Taxonomies. */
	const TAX_SPORT = 'ax_sport';

	/* Team meta. */
	const TEAM_LEAGUE  = '_ax_team_league';
	const TEAM_VENUE   = '_ax_team_venue';
	const TEAM_FOUNDED = '_ax_team_founded';
	const TEAM_COLOR   = '_ax_team_color';

	/* Player meta. */
	const PLAYER_TEAM     = '_ax_player_team';
	const PLAYER_POSITION = '_ax_player_position';
	const PLAYER_NUMBER   = '_ax_player_number';
	const PLAYER_HEIGHT   = '_ax_player_height';
	const PLAYER_WEIGHT   = '_ax_player_weight';
	const PLAYER_COUNTRY  = '_ax_player_country';
	const PLAYER_DOB      = '_ax_player_dob';

	/* Match meta. */
	const MATCH_LEAGUE     = '_ax_match_league';
	const MATCH_SEASON     = '_ax_match_season';
	const MATCH_HOME_TEAM  = '_ax_match_home_team';
	const MATCH_AWAY_TEAM  = '_ax_match_away_team';
	const MATCH_HOME_SCORE = '_ax_match_home_score';
	const MATCH_AWAY_SCORE = '_ax_match_away_score';
	const MATCH_DATE       = '_ax_match_date';
	const MATCH_STATUS     = '_ax_match_status';
	const MATCH_ROUND      = '_ax_match_round';

	/* Season meta. */
	const SEASON_LEAGUE = '_ax_season_league';
	const SEASON_START  = '_ax_season_start';
	const SEASON_END    = '_ax_season_end';

	/* Division meta. */
	const DIVISION_LEAGUE = '_ax_division_league';

	/* Match status values. */
	const STATUS_SCHEDULED = 'scheduled';
	const STATUS_COMPLETED = 'completed';

	/**
	 * All plugin post-type slugs.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return array( self::LEAGUE, self::SEASON, self::TEAM, self::PLAYER, self::MATCH, self::DIVISION );
	}

	/**
	 * Capability required to manage Athletix data.
	 *
	 * Granted to administrators (and the League Manager role) by the Security
	 * module; falls back gracefully because administrators also hold it.
	 *
	 * @return string
	 */
	public static function capability() {
		return 'manage_athletix';
	}
}
