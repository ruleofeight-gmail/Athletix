<?php
/**
 * Match processing engine.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;
use Athletix\Core\Logger;
use Athletix\Data\Repositories\MatchRepository;
use Athletix\Support\Keys;

/**
 * Watches match saves/deletes and fires domain events. Unlike the draft, it
 * guards against autosave, revisions and non-published posts, validates the
 * teams and scores, and dispatches a scope (league/season) that downstream
 * engines recompute idempotently.
 */
class MatchEngine {

	/**
	 * Events service.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Match repository.
	 *
	 * @var MatchRepository
	 */
	private $matches;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Scope (league/season) captured before a hard delete, keyed by post id.
	 *
	 * @var array<int,array>
	 */
	private $pending_delete = array();

	/**
	 * Constructor.
	 *
	 * @param Events          $events  Event dispatcher.
	 * @param MatchRepository $matches Match repository.
	 * @param Logger          $logger  Logger.
	 */
	public function __construct( Events $events, MatchRepository $matches, Logger $logger ) {
		$this->events  = $events;
		$this->matches = $matches;
		$this->logger  = $logger;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'save_post_' . Keys::MATCH, array( $this, 'on_save' ), 20, 2 );

		// Hard delete: capture scope before removal, recompute after — otherwise
		// the match is still in the DB when standings recompute and its result
		// is not actually removed.
		add_action( 'before_delete_post', array( $this, 'capture_delete_scope' ) );
		add_action( 'deleted_post', array( $this, 'on_deleted' ) );

		// Trash/untrash keep the post + meta, so recompute immediately; the
		// completed() query excludes non-published matches.
		add_action( 'trashed_post', array( $this, 'on_delete' ) );
		add_action( 'untrashed_post', array( $this, 'on_delete' ) );
	}

	/**
	 * Remember a match's scope just before it is hard-deleted.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function capture_delete_scope( $post_id ) {
		if ( get_post_type( $post_id ) !== Keys::MATCH ) {
			return;
		}

		$this->pending_delete[ (int) $post_id ] = $this->matches->details( $post_id );
	}

	/**
	 * After a hard delete, recompute the captured scope (the match is now gone).
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function on_deleted( $post_id ) {
		$post_id = (int) $post_id;

		if ( ! isset( $this->pending_delete[ $post_id ] ) ) {
			return;
		}

		$details = $this->pending_delete[ $post_id ];
		unset( $this->pending_delete[ $post_id ] );

		$this->events->fire( 'match_saved', array_merge( array( 'match_id' => $post_id ), $details ) );
	}

	/**
	 * Handle a match save.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function on_save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$details = $this->matches->details( $post_id );

		if ( ! $details['home'] || ! $details['away'] || $details['home'] === $details['away'] ) {
			$this->logger->debug( 'Match skipped: invalid teams', array( 'match' => $post_id ) );
			return;
		}

		$payload = array_merge( array( 'match_id' => $post_id ), $details );

		if ( Keys::STATUS_COMPLETED === $details['status'] ) {
			$this->events->fire( 'match_completed', $payload );
		}

		// Always fire a generic scope event so schedules/standings can react.
		$this->events->fire( 'match_saved', $payload );
	}

	/**
	 * Handle a match deletion/trash by dispatching its scope for recompute.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function on_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== Keys::MATCH ) {
			return;
		}

		$details = $this->matches->details( $post_id );

		$this->events->fire(
			'match_saved',
			array_merge( array( 'match_id' => $post_id ), $details )
		);
	}
}
