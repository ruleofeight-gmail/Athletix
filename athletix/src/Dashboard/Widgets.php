<?php
/**
 * WordPress dashboard widgets.
 *
 * @package Athletix
 */

namespace Athletix\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Adds Athletix tiles to the WordPress admin dashboard.
 */
class Widgets {

	/**
	 * Plugin.
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
	 * Register on dashboard setup.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'add' ) );
	}

	/**
	 * Add the widgets (for users who can manage Athletix).
	 *
	 * @return void
	 */
	public function add() {
		if ( ! Keys::can_manage() ) {
			return;
		}

		wp_add_dashboard_widget( 'athletix_overview', __( 'Athletix Overview', 'athletix' ), array( $this, 'overview' ) );
		wp_add_dashboard_widget( 'athletix_recent', __( 'Athletix — Recent Matches', 'athletix' ), array( $this, 'recent' ) );
		wp_add_dashboard_widget( 'athletix_scorers', __( 'Athletix — Top Scorers', 'athletix' ), array( $this, 'scorers' ) );
	}

	/**
	 * Overview counts.
	 *
	 * @return void
	 */
	public function overview() {
		$counts = array(
			__( 'Leagues', 'athletix' ) => (int) wp_count_terms(
				array(
					'taxonomy'   => Keys::LEAGUE,
					'hide_empty' => false,
				)
			),
			__( 'Teams', 'athletix' )   => (int) wp_count_posts( Keys::TEAM )->publish,
			__( 'Players', 'athletix' ) => (int) wp_count_posts( Keys::PLAYER )->publish,
			__( 'Matches', 'athletix' ) => (int) wp_count_posts( Keys::MATCH )->publish,
		);

		echo '<ul>';
		foreach ( $counts as $label => $count ) {
			printf( '<li><strong>%d</strong> %s</li>', (int) $count, esc_html( $label ) );
		}
		echo '</ul>';
	}

	/**
	 * Recent matches list.
	 *
	 * @return void
	 */
	public function recent() {
		$matches = $this->plugin->make( 'repo.match' )->all(
			array(
				'posts_per_page' => 5,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $matches ) ) {
			echo '<p>' . esc_html__( 'No matches yet.', 'athletix' ) . '</p>';
			return;
		}

		echo '<ul>';
		foreach ( $matches as $match ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( (string) get_edit_post_link( $match->ID ) ),
				esc_html( get_the_title( $match ) )
			);
		}
		echo '</ul>';
	}

	/**
	 * Top scorers leaderboard.
	 *
	 * @return void
	 */
	public function scorers() {
		$rows = $this->plugin->make( 'engine.statistics' )->leaderboard( 'goals', 0, 5 );

		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No statistics recorded.', 'athletix' ) . '</p>';
			return;
		}

		echo '<ol>';
		foreach ( $rows as $row ) {
			printf(
				'<li>%s — <strong>%s</strong></li>',
				esc_html( get_the_title( (int) $row['player_id'] ) ),
				esc_html( (string) ( 0 + $row['total'] ) )
			);
		}
		echo '</ol>';
	}
}
