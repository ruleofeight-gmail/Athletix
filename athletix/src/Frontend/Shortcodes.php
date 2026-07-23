<?php
/**
 * Front-end shortcodes.
 *
 * @package Athletix
 */

namespace Athletix\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Renders standings, rosters and schedules via shortcodes, delegating data to
 * the engines/repositories and markup to template partials.
 */
class Shortcodes {

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
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'athletix_standings', array( $this, 'standings' ) );
		add_shortcode( 'athletix_roster', array( $this, 'roster' ) );
		add_shortcode( 'athletix_schedule', array( $this, 'schedule' ) );
		add_shortcode( 'athletix_match', array( $this, 'match_card' ) );
		add_shortcode( 'athletix_player', array( $this, 'player' ) );
		add_shortcode( 'athletix_bracket', array( $this, 'bracket' ) );
		add_shortcode( 'athletix_staff', array( $this, 'staff' ) );
		add_shortcode( 'athletix_team_form', array( $this, 'team_form' ) );
		add_shortcode( 'athletix_team', array( $this, 'team' ) );
	}

	/**
	 * [athletix_match id="42"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function match_card( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'athletix_match' );

		$match_id = absint( $atts['id'] );
		if ( ! $match_id ) {
			$match_id = (int) get_the_ID();
		}

		$repo = $this->plugin->make( 'repo.match' );
		$post = $match_id ? get_post( $match_id ) : null;

		if ( ! $post || Keys::MATCH !== $post->post_type ) {
			return '';
		}

		return $this->render(
			'match-card',
			array(
				'match'   => $post,
				'details' => $repo->details( $match_id ),
			)
		);
	}

	/**
	 * [athletix_player id="7"] (defaults to the current player in the loop)
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function player( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'stats' => 'yes',
			),
			$atts,
			'athletix_player'
		);

		$player_id = absint( $atts['id'] );
		if ( ! $player_id ) {
			$player_id = (int) get_the_ID();
		}

		$post = $player_id ? get_post( $player_id ) : null;
		if ( ! $post || Keys::PLAYER !== $post->post_type ) {
			return '';
		}

		return $this->render(
			'player',
			array(
				'player'     => $post,
				'show_stats' => ( 'yes' === $atts['stats'] ),
			)
		);
	}

	/**
	 * [athletix_standings league="12" season="0"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function standings( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
			),
			$atts,
			'athletix_standings'
		);

		$league = $this->term_id( $atts['league'], Keys::LEAGUE );
		$season = $this->term_id( $atts['season'], Keys::SEASON );

		$rows = $this->plugin->make( 'engine.standings' )->table( $league, $season );

		return $this->render( 'standings', array( 'rows' => $rows ) );
	}

	/**
	 * Resolve a shortcode league/season value to a term id. Accepts a numeric
	 * id, a term slug, or a term name — so `league="premier"` works as well as
	 * `league="12"`.
	 *
	 * @param mixed  $value    Attribute value.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	private function term_id( $value, $taxonomy ) {
		if ( is_numeric( $value ) ) {
			return absint( $value );
		}

		$value = trim( (string) $value );
		if ( '' === $value ) {
			return 0;
		}

		$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
		if ( ! $term ) {
			$term = get_term_by( 'name', $value, $taxonomy );
		}

		return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
	}

	/**
	 * [athletix_roster team="5"] or [athletix_roster league="12"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function roster( $atts ) {
		$atts = shortcode_atts(
			array(
				'team'    => 0,
				'league'  => 0,
				'columns' => 3,
				'group'   => '',
			),
			$atts,
			'athletix_roster'
		);

		$players = $this->plugin->make( 'repo.player' );

		if ( $atts['team'] ) {
			$posts = $players->for_team( absint( $atts['team'] ) );
		} elseif ( $atts['league'] ) {
			$teams = wp_list_pluck( $this->plugin->make( 'repo.team' )->for_league( $this->term_id( $atts['league'], Keys::LEAGUE ) ), 'ID' );
			$posts = empty( $teams ) ? array() : $players->all(
				array(
					'posts_per_page' => -1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => Keys::PLAYER_TEAM,
							'value'   => $teams,
							'compare' => 'IN',
						),
					),
				)
			);
		} else {
			$posts = $players->all();
		}

		return $this->render(
			'roster',
			array(
				'players' => $posts,
				'columns' => max( 1, absint( $atts['columns'] ) ),
				'group'   => ( 'position' === $atts['group'] ) ? 'position' : '',
			)
		);
	}

	/**
	 * [athletix_schedule league="12" scope="upcoming"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function schedule( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
				'team'   => 0,
				'limit'  => 20,
			),
			$atts,
			'athletix_schedule'
		);

		$args = array(
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'meta_value',
			'meta_key'       => Keys::MATCH_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
		);

		// Scope to a single team: matches where it plays home or away.
		$team = absint( $atts['team'] );
		if ( $team ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'   => Keys::MATCH_HOME_TEAM,
					'value' => $team,
				),
				array(
					'key'   => Keys::MATCH_AWAY_TEAM,
					'value' => $team,
				),
			);
		}

		$league = $this->term_id( $atts['league'], Keys::LEAGUE );
		$season = $this->term_id( $atts['season'], Keys::SEASON );

		$tax = array();
		if ( $league ) {
			$tax[] = array(
				'taxonomy' => Keys::LEAGUE,
				'field'    => 'term_id',
				'terms'    => $league,
			);
		}
		if ( $season ) {
			$tax[] = array(
				'taxonomy' => Keys::SEASON,
				'field'    => 'term_id',
				'terms'    => $season,
			);
		}
		if ( $tax ) {
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$matches = $this->plugin->make( 'repo.match' );

		return $this->render(
			'schedule',
			array(
				'matches' => $matches->all( $args ),
				'repo'    => $matches,
			)
		);
	}

	/**
	 * [athletix_bracket league="12" season="3"]
	 *
	 * Renders the playoff bracket for a league/season: the seeded knockout
	 * matches (flagged by the PlayoffSeeder) laid out round by round, with the
	 * winner of each decided match highlighted.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function bracket( $atts ) {
		$atts = shortcode_atts(
			array(
				'league' => 0,
				'season' => 0,
			),
			$atts,
			'athletix_bracket'
		);

		$league = $this->term_id( $atts['league'], Keys::LEAGUE );
		$season = $this->term_id( $atts['season'], Keys::SEASON );

		if ( ! $league ) {
			return '';
		}

		$tax = array(
			array(
				'taxonomy' => Keys::LEAGUE,
				'field'    => 'term_id',
				'terms'    => $league,
			),
		);
		if ( $season ) {
			$tax[] = array(
				'taxonomy' => Keys::SEASON,
				'field'    => 'term_id',
				'terms'    => $season,
			);
		}

		$repo    = $this->plugin->make( 'repo.match' );
		$matches = $repo->all(
			array(
				'posts_per_page' => -1,
				'orderby'        => 'meta_value_num',
				'meta_key'       => Keys::MATCH_ROUND, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::MATCH_PLAYOFF,
						'value' => 1,
					),
				),
				'tax_query'      => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			)
		);

		$rows = array();
		foreach ( $matches as $match ) {
			$details          = $repo->details( $match->ID );
			$details['round'] = (int) get_post_meta( $match->ID, Keys::MATCH_ROUND, true );
			$rows[]           = $details;
		}

		$rounds = ( new \Athletix\Competition\BracketBuilder() )->build( $rows );

		return $this->render( 'bracket', array( 'rounds' => $rounds ) );
	}

	/**
	 * [athletix_staff team="5"] or [athletix_staff league="12"]
	 *
	 * Lists the coaching/management staff for a team (or every team in a
	 * league), the way a SportsPress team page shows its staff.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function staff( $atts ) {
		$atts = shortcode_atts(
			array(
				'team'   => 0,
				'league' => 0,
			),
			$atts,
			'athletix_staff'
		);

		$repo = $this->plugin->make( 'repo.staff' );

		if ( $atts['team'] ) {
			$posts = $repo->for_team( absint( $atts['team'] ) );
		} elseif ( $atts['league'] ) {
			$posts = $repo->all(
				array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => Keys::LEAGUE,
							'field'    => 'term_id',
							'terms'    => $this->term_id( $atts['league'], Keys::LEAGUE ),
						),
					),
				)
			);
		} else {
			$posts = $repo->all();
		}

		return $this->render( 'staff', array( 'staff' => $posts ) );
	}

	/**
	 * [athletix_team_form team="5" limit="5"]
	 *
	 * Renders a team's recent form — the last completed results as W/D/L
	 * badges, most recent last, like the form guide on a SportsPress team page.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function team_form( $atts ) {
		// Accept id="" as an alias of team="" (the single-team templates pass id).
		$atts = shortcode_atts(
			array(
				'team'  => 0,
				'id'    => 0,
				'limit' => 5,
			),
			$atts,
			'athletix_team_form'
		);

		$team_id = absint( $atts['team'] ) ? absint( $atts['team'] ) : absint( $atts['id'] );
		if ( ! $team_id ) {
			return '';
		}

		$repo    = $this->plugin->make( 'repo.match' );
		$matches = $repo->all(
			array(
				'posts_per_page' => max( 1, absint( $atts['limit'] ) ),
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

		// Oldest → newest so the badges read left to right chronologically.
		$results = array();
		foreach ( array_reverse( $matches ) as $match ) {
			$details = $repo->details( $match->ID );
			$is_home = ( (int) $details['home'] === $team_id );
			$for     = $is_home ? $details['home_score'] : $details['away_score'];
			$against = $is_home ? $details['away_score'] : $details['home_score'];

			if ( $for > $against ) {
				$outcome = 'w';
			} elseif ( $for < $against ) {
				$outcome = 'l';
			} else {
				$outcome = 'd';
			}

			$results[] = array(
				'outcome' => $outcome,
				'match'   => $match,
				'label'   => strtoupper( $outcome ) . ' ' . $for . '–' . $against,
			);
		}

		return $this->render( 'team-form', array( 'results' => $results ) );
	}

	/**
	 * [athletix_team id="5"] (defaults to the team in the loop)
	 *
	 * The full SportsPress-style team page: a badge/logo header, a team-details
	 * panel, the league table, fixtures & results for the team, the squad
	 * grouped by position and the staff list — composed from the individual
	 * views so a single tag drops a complete team profile onto a page.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function team( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'athletix_team' );

		$team_id = absint( $atts['id'] );
		if ( ! $team_id ) {
			$team_id = (int) get_the_ID();
		}

		$post = $team_id ? get_post( $team_id ) : null;
		if ( ! $post || Keys::TEAM !== $post->post_type ) {
			return '';
		}

		$league = $this->plugin->make( 'repo.team' )->league_of( $team_id );

		return $this->render(
			'team-page',
			array(
				'team'   => $post,
				'league' => $league,
			)
		);
	}

	/**
	 * Render a template partial with data, returning its output.
	 *
	 * @param string $template Template slug (templates/{slug}.php).
	 * @param array  $data     Data extracted into scope.
	 * @return string
	 */
	private function render( $template, array $data ) {
		wp_enqueue_style( 'athletix' );

		$file = ATHLETIX_PATH . 'templates/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );
		include $file;

		return (string) ob_get_clean();
	}
}
