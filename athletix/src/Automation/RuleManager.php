<?php
/**
 * Event-driven automation rules.
 *
 * @package Athletix
 */

namespace Athletix\Automation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Core\Events;
use Athletix\Core\Logger;

/**
 * Stores automation rules and runs their actions when the matching domain
 * event fires. A rule is: { event, action, recipient, subject, body }.
 */
class RuleManager {

	const OPTION = 'athletix_rules';

	/**
	 * Events a rule may listen to.
	 *
	 * @return array<string,string>
	 */
	public static function events() {
		return array(
			'match_completed'   => __( 'Match completed', 'athletix' ),
			'standings_updated' => __( 'Standings updated', 'athletix' ),
			'team_registered'   => __( 'Team registered', 'athletix' ),
			'payment_recorded'  => __( 'Payment recorded', 'athletix' ),
		);
	}

	/**
	 * Actions a rule may run.
	 *
	 * @return array<string,string>
	 */
	public static function actions() {
		return array(
			'email' => __( 'Send email', 'athletix' ),
			'log'   => __( 'Write to activity log', 'athletix' ),
		);
	}

	/**
	 * Events service.
	 *
	 * @var Events
	 */
	private $events;

	/**
	 * Template engine.
	 *
	 * @var TemplateEngine
	 */
	private $templates;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Events         $events    Events.
	 * @param TemplateEngine $templates Template engine.
	 * @param Logger         $logger    Logger.
	 */
	public function __construct( Events $events, TemplateEngine $templates, Logger $logger ) {
		$this->events    = $events;
		$this->templates = $templates;
		$this->logger    = $logger;
	}

	/**
	 * Subscribe to every event that has at least one active rule.
	 *
	 * @return void
	 */
	public function register() {
		$used = array();
		foreach ( $this->rules() as $rule ) {
			if ( ! empty( $rule['active'] ) ) {
				$used[ $rule['event'] ] = true;
			}
		}

		foreach ( array_keys( $used ) as $event ) {
			$this->events->listen(
				$event,
				function ( $payload ) use ( $event ) {
					$this->on_event( $event, (array) $payload );
				}
			);
		}
	}

	/**
	 * Run every active rule bound to an event.
	 *
	 * @param string $event   Event name.
	 * @param array  $payload Event payload.
	 * @return void
	 */
	public function on_event( $event, array $payload ) {
		foreach ( $this->rules() as $rule ) {
			if ( empty( $rule['active'] ) || $rule['event'] !== $event ) {
				continue;
			}
			$this->run( $rule, $payload );
		}
	}

	/**
	 * Execute a single rule's action.
	 *
	 * @param array $rule    Rule.
	 * @param array $payload Event payload.
	 * @return void
	 */
	private function run( array $rule, array $payload ) {
		$body = $this->templates->render( isset( $rule['body'] ) ? $rule['body'] : '', $payload );

		if ( 'email' === $rule['action'] ) {
			$recipient = ! empty( $rule['recipient'] ) ? $rule['recipient'] : get_option( 'admin_email' );
			if ( is_email( $recipient ) ) {
				$subject = $this->templates->render( isset( $rule['subject'] ) ? $rule['subject'] : '', $payload );
				wp_mail( $recipient, '' !== $subject ? $subject : __( 'Athletix notification', 'athletix' ), $body );
			}
		} elseif ( 'log' === $rule['action'] ) {
			$this->logger->info( 'Automation: ' . $body, array( 'event' => $rule['event'] ) );
		}
	}

	/**
	 * All stored rules.
	 *
	 * @return array[]
	 */
	public function rules() {
		$rules = get_option( self::OPTION, array() );
		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Add a rule.
	 *
	 * @param array $rule Rule data.
	 * @return void
	 */
	public function add( array $rule ) {
		$rules   = $this->rules();
		$rules[] = array(
			'event'     => sanitize_key( $rule['event'] ),
			'action'    => sanitize_key( $rule['action'] ),
			'recipient' => isset( $rule['recipient'] ) ? sanitize_email( $rule['recipient'] ) : '',
			'subject'   => isset( $rule['subject'] ) ? sanitize_text_field( $rule['subject'] ) : '',
			'body'      => isset( $rule['body'] ) ? sanitize_textarea_field( $rule['body'] ) : '',
			'active'    => true,
		);

		update_option( self::OPTION, array_values( $rules ) );
	}

	/**
	 * Delete a rule by index.
	 *
	 * @param int $index Rule index.
	 * @return void
	 */
	public function delete( $index ) {
		$rules = $this->rules();
		unset( $rules[ absint( $index ) ] );
		update_option( self::OPTION, array_values( $rules ) );
	}
}
