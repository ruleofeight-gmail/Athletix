<?php
/**
 * Plugin orchestrator.
 *
 * @package Athletix
 */

namespace Athletix;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Contracts\Module;
use Athletix\Core\Cache;
use Athletix\Core\Config;
use Athletix\Core\Container;
use Athletix\Core\Events;
use Athletix\Core\Logger;
use Athletix\Core\Validator;

/**
 * Central runtime: owns the container and core services, collects feature
 * modules and boots them. Modules self-register via the `athletix/modules`
 * filter — no edits to this class or the autoloader are required to add one.
 */
final class Plugin {

	/**
	 * Shared instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Registered modules keyed by id.
	 *
	 * @var array<string,Module>
	 */
	private $modules = array();

	/**
	 * Whether boot() has run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Retrieve and boot the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Constructor: build the container and register core services.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->register_core_services();
	}

	/**
	 * Register the always-on core services as container singletons.
	 *
	 * @return void
	 */
	private function register_core_services() {
		$this->container->bind(
			'config',
			static function () {
				return new Config(
					array(
						'active_sport'  => 'soccer',
						'points_win'    => 3,
						'points_draw'   => 1,
						'points_loss'   => 0,
						'delete_data'   => false,
					)
				);
			}
		);

		$this->container->bind(
			'events',
			static function () {
				return new Events();
			}
		);

		$this->container->bind(
			'cache',
			static function () {
				return new Cache();
			}
		);

		$this->container->bind(
			'logger',
			static function () {
				return new Logger();
			}
		);

		$this->container->bind(
			'validator',
			static function () {
				return new Validator();
			}
		);
	}

	/**
	 * Collect and register modules.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->load_textdomain();

		/**
		 * Filter the list of Athletix modules to register.
		 *
		 * @param Module[] $modules Module instances.
		 * @param Plugin   $plugin  Plugin instance.
		 */
		$modules = apply_filters( 'athletix/modules', $this->default_modules(), $this );

		foreach ( $modules as $module ) {
			if ( $module instanceof Module ) {
				$this->modules[ $module->id() ] = $module;
				$module->register( $this );
			}
		}

		/**
		 * Fires once all modules are registered.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'athletix/booted', $this );
	}

	/**
	 * The built-in modules shipped with the plugin.
	 *
	 * Grows one entry per phase; each is a self-contained class implementing
	 * the Module contract.
	 *
	 * @return Module[]
	 */
	private function default_modules() {
		$modules = array();

		foreach ( $this->module_classes() as $class ) {
			if ( class_exists( $class ) ) {
				$modules[] = new $class();
			}
		}

		return $modules;
	}

	/**
	 * Fully-qualified module class names, in registration order.
	 *
	 * @return string[]
	 */
	private function module_classes() {
		return array(
			\Athletix\Security\SecurityModule::class,
			\Athletix\Data\DataModule::class,
			\Athletix\PostTypes\PostTypesModule::class,
			\Athletix\Engine\EngineModule::class,
			\Athletix\Competition\CompetitionModule::class,
			\Athletix\Rest\RestModule::class,
			\Athletix\Frontend\FrontendModule::class,
			\Athletix\Elementor\ElementorModule::class,
			\Athletix\Dashboard\DashboardModule::class,
			\Athletix\Search\SearchModule::class,
			\Athletix\ImportExport\ImportExportModule::class,
			\Athletix\Calendar\CalendarModule::class,
			\Athletix\Notifications\NotificationsModule::class,
			\Athletix\Analytics\AnalyticsModule::class,
			\Athletix\Media\MediaModule::class,
			\Athletix\Reports\ReportsModule::class,
		);
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'athletix', false, dirname( ATHLETIX_BASENAME ) . '/languages' );
	}

	/**
	 * The service container.
	 *
	 * @return Container
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 */
	public function make( $id ) {
		return $this->container->get( $id );
	}

	/**
	 * Config service.
	 *
	 * @return Config
	 */
	public function config() {
		return $this->container->get( 'config' );
	}

	/**
	 * Events service.
	 *
	 * @return Events
	 */
	public function events() {
		return $this->container->get( 'events' );
	}

	/**
	 * Cache service.
	 *
	 * @return Cache
	 */
	public function cache() {
		return $this->container->get( 'cache' );
	}

	/**
	 * Logger service.
	 *
	 * @return Logger
	 */
	public function logger() {
		return $this->container->get( 'logger' );
	}

	/**
	 * Validator service.
	 *
	 * @return Validator
	 */
	public function validator() {
		return $this->container->get( 'validator' );
	}

	/**
	 * Get a registered module by id.
	 *
	 * @param string $id Module id.
	 * @return Module|null
	 */
	public function module( $id ) {
		return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
	}
}
