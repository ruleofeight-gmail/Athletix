<?php
/**
 * Team form analytics.
 *
 * @package Athletix
 */

namespace Athletix\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders a team's recent form (last N results) via [athletix_team_form].
 */
class TeamAnalytics {

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
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_team_form', array( $this, 'render' ) );
	}

	/**
	 * Compute a team's recent form.
	 *
	 * @param int $team_id Team id.
	 * @param int $limit   Number of matches.
	 * @return array[] List of ['result'=>W|D|L, 'for'=>int, 'against'=>int, 'match'=>id].
	 */
	public function form( $team_id, $limit = 5 ) {
		$team_id = absint( $team_id );
		$repo    = $this->plugin->make( 'repo.match' );

		$posts = $repo->all(
			array(
				'posts_per_page' => absint( $limit ),
				'orderby'        => 'meta_value',
				'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => Keys::MATCH_STATUS,
						'value' => Keys::STATUS_COMPLETED,
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => Keys::MATCH_HOME_TEAM,
							'value' => $team_id,
						),
						array(
							'key'   => Keys::MATCH_AWAY_TEAM,
							'value' => $team_id,
						),
					),
				),
			)
		);

		$form = array();
		foreach ( $posts as $post ) {
			$d       = $repo->details( $post->ID );
			$is_home = ( $d['home'] === $team_id );
			$for     = $is_home ? $d['home_score'] : $d['away_score'];
			$against = $is_home ? $d['away_score'] : $d['home_score'];

			if ( $for > $against ) {
				$result = 'W';
			} elseif ( $for === $against ) {
				$result = 'D';
			} else {
				$result = 'L';
			}

			$form[] = array(
				'result'  => $result,
				'for'     => $for,
				'against' => $against,
				'match'   => $post->ID,
			);
		}//end foreach

		return $form;
	}

	/**
	 * [athletix_team_form id="5" limit="5"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'limit' => 5,
			),
			$atts,
			'athletix_team_form'
		);

		$team_id = absint( $atts['id'] );
		if ( ! $team_id ) {
			$team_id = (int) get_the_ID();
		}

		$form = $this->form( $team_id, absint( $atts['limit'] ) );
		if ( empty( $form ) ) {
			return '<p class="athletix-empty">' . esc_html__( 'No recent matches.', 'athletix' ) . '</p>';
		}

		wp_enqueue_style( 'athletix' );

		ob_start();
		echo '<div class="athletix-form">';
		foreach ( array_reverse( $form ) as $item ) {
			printf(
				'<span class="athletix-form__badge athletix-form__badge--%s" title="%s">%s</span>',
				esc_attr( strtolower( $item['result'] ) ),
				esc_attr( $item['for'] . '–' . $item['against'] ),
				esc_html( $item['result'] )
			);
		}
		echo '</div>';

		return (string) ob_get_clean();
	}
}
