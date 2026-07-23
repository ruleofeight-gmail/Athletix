<?php
/**
 * Catalogue of list-column source fields.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * The fields an admin can choose from when building the Teams / Players /
 * Matches list columns, plus the taxonomy filters each list offers. This is the
 * fixed menu of *available* sources (a column must read some real meta); which
 * of them appear, in what order and under what label is what an admin controls
 * via the List Columns editor.
 */
class ColumnCatalog {

	/**
	 * Selectable source fields, keyed by list post type then source key.
	 *
	 * @return array<string,array<string,array>>
	 */
	public static function sources() {
		return array(
			Keys::TEAM   => array(
				'venue'   => array(
					'label' => __( 'Venue', 'athletix' ),
					'meta'  => Keys::TEAM_VENUE,
					'type'  => 'text',
				),
				'founded' => array(
					'label'   => __( 'Founded', 'athletix' ),
					'meta'    => Keys::TEAM_FOUNDED,
					'type'    => 'number',
					'numeric' => true,
				),
				'color'   => array(
					'label' => __( 'Color', 'athletix' ),
					'meta'  => Keys::TEAM_COLOR,
					'type'  => 'color',
				),
			),
			Keys::PLAYER => array(
				'team'     => array(
					'label'   => __( 'Team', 'athletix' ),
					'meta'    => Keys::PLAYER_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'position' => array(
					'label' => __( 'Position', 'athletix' ),
					'meta'  => Keys::PLAYER_POSITION,
					'type'  => 'text',
				),
				'number'   => array(
					'label'   => __( 'Number', 'athletix' ),
					'meta'    => Keys::PLAYER_NUMBER,
					'type'    => 'number',
					'numeric' => true,
				),
			),
			Keys::MATCH  => array(
				'date'   => array(
					'label' => __( 'Match Date', 'athletix' ),
					'meta'  => Keys::MATCH_DATE,
					'type'  => 'date',
				),
				'home'   => array(
					'label'   => __( 'Home', 'athletix' ),
					'meta'    => Keys::MATCH_HOME_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'away'   => array(
					'label'   => __( 'Away', 'athletix' ),
					'meta'    => Keys::MATCH_AWAY_TEAM,
					'type'    => 'post',
					'numeric' => true,
				),
				'round'  => array(
					'label'   => __( 'Round', 'athletix' ),
					'meta'    => Keys::MATCH_ROUND,
					'type'    => 'number',
					'numeric' => true,
				),
				'status' => array(
					'label' => __( 'Status', 'athletix' ),
					'meta'  => Keys::MATCH_STATUS,
					'type'  => 'text',
				),
			),
		);
	}

	/**
	 * Taxonomy filter dropdowns offered per list.
	 *
	 * @return array<string,string[]>
	 */
	public static function filters() {
		return array(
			Keys::TEAM   => array( Keys::TAX_SPORT, Keys::LEAGUE, Keys::DIVISION ),
			Keys::PLAYER => array( Keys::TAX_SPORT, Keys::LEAGUE ),
			Keys::MATCH  => array( Keys::LEAGUE, Keys::SEASON, Keys::DIVISION, Keys::TAX_SPORT ),
		);
	}

	/**
	 * The list post types that support configurable columns.
	 *
	 * @return string[]
	 */
	public static function lists() {
		return array_keys( self::sources() );
	}

	/**
	 * Human label for a list post type.
	 *
	 * @param string $post_type List post type.
	 * @return string
	 */
	public static function list_label( $post_type ) {
		$labels = array(
			Keys::TEAM   => __( 'Teams', 'athletix' ),
			Keys::PLAYER => __( 'Players', 'athletix' ),
			Keys::MATCH  => __( 'Matches', 'athletix' ),
		);

		return isset( $labels[ $post_type ] ) ? $labels[ $post_type ] : $post_type;
	}
}
