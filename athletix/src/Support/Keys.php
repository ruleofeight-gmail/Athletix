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

	/* Top-level admin menu slug (parent of every Athletix screen). */
	const MENU = 'athletix';

	/* Post types. */
	const TEAM   = 'ax_team';
	const PLAYER = 'ax_player';
	const MATCH  = 'ax_match';

	/* Customize variable post types (admin-defined standings columns + outcomes). */
	const STANDING    = 'ax_standing';
	const OUTCOME     = 'ax_outcome';
	const LIST_COLUMN = 'ax_column';

	/* Customize variable meta. */
	const VAR_KEY       = '_ax_var_key';
	const VAR_EQUATION  = '_ax_var_equation';
	const VAR_PRECISION = '_ax_var_precision';
	const VAR_SORT      = '_ax_var_sort';
	const VAR_ORDER     = '_ax_var_order';
	const VAR_SPORT     = '_ax_var_sport';

	/* List Column meta (which admin list + which source field). */
	const COL_LIST   = '_ax_col_list';
	const COL_SOURCE = '_ax_col_source';

	/* Taxonomies. League, Season and Division are taxonomies (SportsPress model). */
	const TAX_SPORT = 'ax_sport';
	const LEAGUE    = 'ax_league';
	const SEASON    = 'ax_season';
	const DIVISION  = 'ax_division';

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
	const MATCH_PLAYOFF    = '_ax_playoff';

	/* Season meta. */
	const SEASON_LEAGUE = '_ax_season_league';
	const SEASON_START  = '_ax_season_start';
	const SEASON_END    = '_ax_season_end';

	/* Division meta. */
	const DIVISION_LEAGUE = '_ax_division_league';

	/* League term meta. */
	const LEAGUE_SPORT = '_ax_league_sport';
	const LEAGUE_COLOR = '_ax_league_color';

	/* Match status values. */
	const STATUS_SCHEDULED = 'scheduled';
	const STATUS_COMPLETED = 'completed';

	/**
	 * All plugin post-type slugs.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return array( self::TEAM, self::PLAYER, self::MATCH );
	}

	/**
	 * All plugin taxonomy slugs (Sport, League, Season, Division).
	 *
	 * @return string[]
	 */
	public static function taxonomies() {
		return array( self::TAX_SPORT, self::LEAGUE, self::SEASON, self::DIVISION );
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
