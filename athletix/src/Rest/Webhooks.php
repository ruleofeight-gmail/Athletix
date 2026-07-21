<?php
/**
 * Outgoing webhooks.
 *
 * @package Athletix
 */

namespace Athletix\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;

/**
 * Posts a JSON payload to configured URLs when key events occur, so external
 * services can react to results and standings changes. URLs are supplied via
 * the `athletix/webhook_urls` filter.
 */
class Webhooks {

	/**
	 * Events service.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Constructor.
	 *
	 * @param Events $events Events.
	 */
	public function __construct( Events $events ) {
		$this->events = $events;
	}

	/**
	 * Subscribe to broadcastable events.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( array( 'match_completed', 'standings_updated' ) as $event ) {
			$this->events->listen(
				$event,
				function ( $payload ) use ( $event ) {
					$this->dispatch( $event, (array) $payload );
				}
			);
		}
	}

	/**
	 * Configured webhook URLs.
	 *
	 * @return string[]
	 */
	private function urls() {
		/**
		 * Filter the outgoing webhook URLs.
		 *
		 * @param string[] $urls Endpoint URLs.
		 */
		$urls = apply_filters( 'athletix/webhook_urls', array() );
		return array_filter( array_map( 'esc_url_raw', (array) $urls ) );
	}

	/**
	 * POST an event to every configured URL (non-blocking).
	 *
	 * @param string $event   Event name.
	 * @param array  $payload Payload.
	 * @return void
	 */
	public function dispatch( $event, array $payload ) {
		$urls = $this->urls();
		if ( empty( $urls ) ) {
			return;
		}

		$body = wp_json_encode(
			array(
				'event' => $event,
				'data'  => $payload,
				'site'  => home_url(),
			)
		);

		foreach ( $urls as $url ) {
			wp_remote_post(
				$url,
				array(
					'timeout'  => 5,
					'blocking' => false,
					'headers'  => array( 'Content-Type' => 'application/json' ),
					'body'     => $body,
				)
			);
		}
	}
}
