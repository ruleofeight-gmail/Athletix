<?php
/**
 * Built-in soccer preset for the Customize variables.
 *
 * @package Athletix
 */

namespace Athletix\Customize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The default standings columns and outcomes — Athletix's historical soccer
 * behaviour, expressed as admin-style equations. Used two ways: the Seeder
 * plants editable copies of these on activation, and the VariableRepository
 * falls back to them when a site has no configured variables yet, so standings
 * always work and never regress.
 */
class Defaults {

	/**
	 * Reserved base variables always available to column equations, with a short
	 * description for the "Available variables" helper.
	 *
	 * @return array<string,string>
	 */
	public static function base_variables() {
		return array(
			'played'        => __( 'Matches played', 'athletix' ),
			'w'             => __( 'Wins', 'athletix' ),
			'won'           => __( 'Wins (alias)', 'athletix' ),
			'd'             => __( 'Draws', 'athletix' ),
			'drawn'         => __( 'Draws (alias)', 'athletix' ),
			'l'             => __( 'Losses', 'athletix' ),
			'lost'          => __( 'Losses (alias)', 'athletix' ),
			'gf'            => __( 'Goals for', 'athletix' ),
			'goals_for'     => __( 'Goals for (alias)', 'athletix' ),
			'ga'            => __( 'Goals against', 'athletix' ),
			'goals_against' => __( 'Goals against (alias)', 'athletix' ),
		);
	}

	/**
	 * Variables available to Outcome equations (evaluated per match, per team).
	 *
	 * @return array<string,string>
	 */
	public static function outcome_variables() {
		return array(
			'gf' => __( 'Goals for (this match)', 'athletix' ),
			'ga' => __( 'Goals against (this match)', 'athletix' ),
		);
	}

	/**
	 * Default standings columns (label, key, equation, precision, sort priority,
	 * order). A sort priority of 0 means "display only, not a default-sort key";
	 * positive priorities form the ordered default-sort chain (1 first).
	 *
	 * @return array[]
	 */
	public static function columns() {
		return array(
			array(
				'label'     => __( 'Played', 'athletix' ),
				'key'       => 'played',
				'equation'  => '$played',
				'precision' => 0,
				'sort'      => 0,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Won', 'athletix' ),
				'key'       => 'won',
				'equation'  => '$w',
				'precision' => 0,
				'sort'      => 0,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Drawn', 'athletix' ),
				'key'       => 'drawn',
				'equation'  => '$d',
				'precision' => 0,
				'sort'      => 0,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Lost', 'athletix' ),
				'key'       => 'lost',
				'equation'  => '$l',
				'precision' => 0,
				'sort'      => 0,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Goals For', 'athletix' ),
				'key'       => 'goals_for',
				'equation'  => '$gf',
				'precision' => 0,
				'sort'      => 4,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Goals Against', 'athletix' ),
				'key'       => 'goals_against',
				'equation'  => '$ga',
				'precision' => 0,
				'sort'      => 0,
				'order'     => 'asc',
			),
			array(
				'label'     => __( 'Goal Difference', 'athletix' ),
				'key'       => 'goal_difference',
				'equation'  => '$gf - $ga',
				'precision' => 0,
				'sort'      => 2,
				'order'     => 'desc',
			),
			array(
				'label'     => __( 'Points', 'athletix' ),
				'key'       => 'points',
				'equation'  => '( $w * 3 ) + $d',
				'precision' => 0,
				'sort'      => 1,
				'order'     => 'desc',
			),
		);
	}

	/**
	 * Default outcomes (label, key, equation). The key maps to a standings bucket
	 * (w = won, d = drawn, l = lost); the equation classifies a single match.
	 *
	 * @return array[]
	 */
	public static function outcomes() {
		return array(
			array(
				'label'    => __( 'Win', 'athletix' ),
				'key'      => 'w',
				'equation' => '$gf > $ga',
			),
			array(
				'label'    => __( 'Draw', 'athletix' ),
				'key'      => 'd',
				'equation' => '$gf == $ga',
			),
			array(
				'label'    => __( 'Loss', 'athletix' ),
				'key'      => 'l',
				'equation' => '$gf < $ga',
			),
		);
	}
}
