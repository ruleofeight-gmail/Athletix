<?php
/**
 * Calendar module.
 *
 * @package Athletix
 */

namespace Athletix\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Plugin;

/**
 * Registers the iCalendar schedule feed.
 */
class CalendarModule implements Module {

	/**
	 * Module id.
	 *
	 * @return string
	 */
	public function id() {
		return 'calendar';
	}

	/**
	 * Register.
	 *
	 * @param Plugin $plugin Plugin instance.
	 * @return void
	 */
	public function register( Plugin $plugin ) {
		( new IcsFeed( $plugin ) )->register();
		( new Venues() )->register();
		( new CalendarView( $plugin ) )->register();

		// Recurring scheduler exposed as a service for programmatic use.
		$plugin->container()->bind(
			'calendar.recurring',
			static function () use ( $plugin ) {
				return new RecurringScheduler( $plugin->make( 'competition.scheduler' ) );
			}
		);

		if ( is_admin() ) {
			( new ConflictDetector( $plugin->make( 'repo.match' ) ) )->register();
		}
	}
}
