<?php
/**
 * Cron-driven automation.
 *
 * @package Athletix
 */

namespace Athletix\Automation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Plugin;
use Athletix\Support\Keys;

/**
 * Registers a daily WP-Cron event that fires the `athletix/daily` domain event
 * and emails reminders for matches happening the next day (when enabled).
 */
class Scheduler {

	const CRON_HOOK = 'athletix_daily_event';

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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'athletix/activate', array( $this, 'schedule' ) );
		add_action( 'athletix/deactivate', array( $this, 'unschedule' ) );
		add_action( self::CRON_HOOK, array( $this, 'run' ) );

		// Self-heal if the event was lost.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) && did_action( 'init' ) ) {
			$this->schedule();
		}
	}

	/**
	 * Schedule the daily event.
	 *
	 * @return void
	 */
	public function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear the scheduled event.
	 *
	 * @return void
	 */
	public function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Daily run.
	 *
	 * @return void
	 */
	public function run() {
		$this->plugin->events()->fire( 'daily', array( 'date' => gmdate( 'Y-m-d' ) ) );
		$this->plugin->logger()->info( 'Daily automation run' );

		if ( $this->plugin->config()->get( 'notify_reminders', false ) ) {
			$this->send_reminders();
		}
	}

	/**
	 * Email a reminder listing tomorrow's matches.
	 *
	 * @return void
	 */
	private function send_reminders() {
		$tomorrow = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS );

		$matches = $this->plugin->make( 'repo.match' )->all(
			array(
				'posts_per_page' => 100,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Keys::MATCH_DATE,
						'value' => $tomorrow,
					),
				),
			)
		);

		if ( empty( $matches ) ) {
			return;
		}

		$recipient = $this->plugin->config()->get( 'notify_email', get_option( 'admin_email' ) );
		if ( ! is_email( $recipient ) ) {
			return;
		}

		$lines = array();
		foreach ( $matches as $match ) {
			$lines[] = get_the_title( $match );
		}

		wp_mail(
			$recipient,
			__( '[Athletix] Matches tomorrow', 'athletix' ),
			implode( "\n", $lines )
		);
	}
}
