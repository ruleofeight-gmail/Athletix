<?php
/**
 * Scheduling conflict detection.
 *
 * @package Athletix
 */

namespace Athletix\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Repositories\MatchRepository;
use Athletix\Support\Keys;

/**
 * Detects scheduling conflicts: two matches on the same date sharing a venue,
 * or a team booked for two matches on the same date. Surfaces conflicts as a
 * notice on the match edit screen.
 */
class ConflictDetector {

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Constructor.
	 *
	 * @param MatchRepository $matches Match repository.
	 */
	public function __construct( MatchRepository $matches ) {
		$this->matches = $matches;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'edit_form_top', array( $this, 'maybe_notice' ) );
	}

	/**
	 * Return the ids of matches conflicting with the given one.
	 *
	 * @param int $match_id Match id.
	 * @return int[]
	 */
	public function conflicts_for( $match_id ) {
		$match_id = absint( $match_id );
		$details  = $this->matches->details( $match_id );

		if ( empty( $details['date'] ) ) {
			return array();
		}

		$same_day = $this->matches->all(
			array(
				'posts_per_page' => 100,
				'post__not_in'   => array( $match_id ),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::MATCH_DATE,
						'value' => $details['date'],
					),
				),
			)
		);

		$venue     = wp_get_object_terms( $match_id, Venues::TAXONOMY, array( 'fields' => 'ids' ) );
		$venue     = is_wp_error( $venue ) ? array() : $venue;
		$conflicts = array();

		foreach ( $same_day as $other ) {
			$other_details = $this->matches->details( $other->ID );

			// Shared team.
			$teams       = array( $details['home'], $details['away'] );
			$other_teams = array( $other_details['home'], $other_details['away'] );
			if ( array_intersect( $teams, $other_teams ) ) {
				$conflicts[ $other->ID ] = true;
				continue;
			}

			// Shared venue.
			if ( $venue ) {
				$other_venue = wp_get_object_terms( $other->ID, Venues::TAXONOMY, array( 'fields' => 'ids' ) );
				$other_venue = is_wp_error( $other_venue ) ? array() : $other_venue;
				if ( array_intersect( $venue, $other_venue ) ) {
					$conflicts[ $other->ID ] = true;
				}
			}
		}

		return array_map( 'intval', array_keys( $conflicts ) );
	}

	/**
	 * Show a conflict notice at the top of the match editor.
	 *
	 * @param \WP_Post $post Post being edited.
	 * @return void
	 */
	public function maybe_notice( $post ) {
		if ( ! $post instanceof \WP_Post || Keys::MATCH !== $post->post_type ) {
			return;
		}

		$conflicts = $this->conflicts_for( $post->ID );
		if ( empty( $conflicts ) ) {
			return;
		}

		$links = array();
		foreach ( $conflicts as $id ) {
			$links[] = '<a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
		}

		printf(
			'<div class="notice notice-warning"><p>%s %s</p></div>',
			esc_html__( 'Scheduling conflict on this date with:', 'athletix' ),
			wp_kses_post( implode( ', ', $links ) )
		);
	}
}
