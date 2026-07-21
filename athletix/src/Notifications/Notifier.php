<?php
/**
 * Email notifications.
 *
 * @package Athletix
 */

namespace Athletix\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Config;
use Athletix\Core\Events;

/**
 * Sends opt-in email notifications in response to domain events. Recipients
 * and the on/off switch come from configuration; templates are filterable.
 */
class Notifier {

	/**
	 * Events.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Config.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param Events $events Events.
	 * @param Config $config Config.
	 */
	public function __construct( Events $events, Config $config ) {
		$this->events = $events;
		$this->config = $config;
	}

	/**
	 * Subscribe to events.
	 *
	 * @return void
	 */
	public function register() {
		$this->events->listen( 'match_completed', array( $this, 'on_match_completed' ) );
	}

	/**
	 * Email a result summary when a match completes, if enabled.
	 *
	 * @param array $payload Match payload.
	 * @return void
	 */
	public function on_match_completed( array $payload ) {
		if ( ! $this->config->get( 'notify_results', false ) ) {
			return;
		}

		$recipient = $this->config->get( 'notify_email', get_option( 'admin_email' ) );
		if ( ! is_email( $recipient ) ) {
			return;
		}

		$home    = get_the_title( (int) ( $payload['home'] ?? 0 ) );
		$away    = get_the_title( (int) ( $payload['away'] ?? 0 ) );
		$score   = (int) ( $payload['home_score'] ?? 0 ) . ' - ' . (int) ( $payload['away_score'] ?? 0 );
		$subject = sprintf(
			/* translators: 1: home team, 2: away team. */
			__( '[Athletix] Result: %1$s vs %2$s', 'athletix' ),
			$home,
			$away
		);
		$body = sprintf(
			/* translators: 1: home team, 2: away team, 3: score. */
			__( '%1$s vs %2$s finished %3$s.', 'athletix' ),
			$home,
			$away,
			$score
		);

		/**
		 * Filter the match-result notification email.
		 *
		 * @param array $email   Subject/body/recipient.
		 * @param array $payload Match payload.
		 */
		$email = apply_filters(
			'athletix/notification_email',
			array(
				'to'      => $recipient,
				'subject' => $subject,
				'body'    => $body,
			),
			$payload
		);

		wp_mail( $email['to'], $email['subject'], $email['body'] );
	}
}
