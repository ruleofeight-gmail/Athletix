<?php
/**
 * Admin list-column configuration for the Athletix content types.
 *
 * @package Athletix
 */

namespace Athletix\Admin\Lists;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * The default sortable/filterable meta columns for the Teams, Players and
 * Matches list screens. Centralized so the columns are described in one place
 * and fed to the generic ListScreen engine — the same shape any future list (or
 * an admin-defined column set) can supply.
 */
class ListConfig {

	/**
	 * All list-screen configs, keyed by post type.
	 *
	 * @return array[]
	 */
	public static function all() {
		return array(
			self::teams(),
			self::players(),
			self::matches(),
		);
	}

	/**
	 * Teams list: venue, founded, colour; filter by sport/league/division.
	 *
	 * @return array
	 */
	private static function teams() {
		return array(
			'post_type' => Keys::TEAM,
			'columns'   => array(
				'axl_venue'   => array(
					'label' => __( 'Venue', 'athletix' ),
					'meta'  => Keys::TEAM_VENUE,
					'type'  => 'text',
				),
				'axl_founded' => array(
					'label'   => __( 'Founded', 'athletix' ),
					'meta'    => Keys::TEAM_FOUNDED,
					'type'    => 'number',
					'numeric' => true,
				),
				'axl_color'   => array(
					'label' => __( 'Color', 'athletix' ),
					'meta'  => Keys::TEAM_COLOR,
					'type'  => 'color',
				),
			),
			'filters'   => array( Keys::TAX_SPORT, Keys::LEAGUE, Keys::DIVISION ),
		);
	}

	/**
	 * Players list: team, position, number; filter by sport/league.
	 *
	 * @return array
	 */
	private static function players() {
		return array(
			'post_type' => Keys::PLAYER,
			'columns'   => array(
				'axl_team'     => array(
					'label'   => __( 'Team', 'athletix' ),
					'meta'    => Keys::PLAYER_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'axl_position' => array(
					'label' => __( 'Position', 'athletix' ),
					'meta'  => Keys::PLAYER_POSITION,
					'type'  => 'text',
				),
				'axl_number'   => array(
					'label'   => __( 'Number', 'athletix' ),
					'meta'    => Keys::PLAYER_NUMBER,
					'type'    => 'number',
					'numeric' => true,
				),
			),
			'filters'   => array( Keys::TAX_SPORT, Keys::LEAGUE ),
		);
	}

	/**
	 * Matches list: date, home, away, round, status; filter by league/season/division.
	 *
	 * @return array
	 */
	private static function matches() {
		return array(
			'post_type' => Keys::MATCH,
			'columns'   => array(
				'axl_date'   => array(
					'label' => __( 'Match Date', 'athletix' ),
					'meta'  => Keys::MATCH_DATE,
					'type'  => 'date',
				),
				'axl_home'   => array(
					'label'   => __( 'Home', 'athletix' ),
					'meta'    => Keys::MATCH_HOME_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'axl_away'   => array(
					'label'   => __( 'Away', 'athletix' ),
					'meta'    => Keys::MATCH_AWAY_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'axl_round'  => array(
					'label'   => __( 'Round', 'athletix' ),
					'meta'    => Keys::MATCH_ROUND,
					'type'    => 'number',
					'numeric' => true,
				),
				'axl_status' => array(
					'label' => __( 'Status', 'athletix' ),
					'meta'  => Keys::MATCH_STATUS,
					'type'  => 'text',
				),
			),
			'filters'   => array( Keys::LEAGUE, Keys::SEASON, Keys::DIVISION, Keys::TAX_SPORT ),
		);
	}
}
