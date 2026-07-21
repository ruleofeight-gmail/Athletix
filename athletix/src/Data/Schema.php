<?php
/**
 * Custom table schema and migrations.
 *
 * @package Athletix
 */

namespace Athletix\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and upgrades the plugin's custom tables. Aggregates (standings,
 * player stats) and relationships live in real, indexed tables rather than
 * serialized post meta so they are queryable and scale.
 */
class Schema {

	/**
	 * Bump when a table definition changes.
	 */
	const DB_VERSION = '1';

	/**
	 * Option storing the installed schema version.
	 */
	const VERSION_OPTION = 'athletix_db_version';

	/**
	 * Standings table (unprefixed).
	 */
	const STANDINGS = 'athletix_standings';

	/**
	 * Player statistics table (unprefixed).
	 */
	const PLAYER_STATS = 'athletix_player_stats';

	/**
	 * Relationships table (unprefixed).
	 */
	const RELATIONSHIPS = 'athletix_relationships';

	/**
	 * Fully-qualified (prefixed) table name.
	 *
	 * @param string $table One of the class constants.
	 * @return string
	 */
	public static function table( $table ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Create or upgrade all tables when the stored version is behind.
	 *
	 * @return void
	 */
	public function install() {
		if ( get_option( self::VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		$this->create_tables();
		update_option( self::VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Run dbDelta for every table.
	 *
	 * @return void
	 */
	private function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$standings       = self::table( self::STANDINGS );
		$player_stats    = self::table( self::PLAYER_STATS );
		$relationships   = self::table( self::RELATIONSHIPS );

		$sql = array();

		$sql[] = "CREATE TABLE {$standings} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			league_id bigint(20) unsigned NOT NULL DEFAULT 0,
			season_id bigint(20) unsigned NOT NULL DEFAULT 0,
			division_id bigint(20) unsigned NOT NULL DEFAULT 0,
			team_id bigint(20) unsigned NOT NULL DEFAULT 0,
			played int(11) NOT NULL DEFAULT 0,
			won int(11) NOT NULL DEFAULT 0,
			drawn int(11) NOT NULL DEFAULT 0,
			lost int(11) NOT NULL DEFAULT 0,
			goals_for int(11) NOT NULL DEFAULT 0,
			goals_against int(11) NOT NULL DEFAULT 0,
			points int(11) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY entity (league_id,season_id,team_id),
			KEY team (team_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$player_stats} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			player_id bigint(20) unsigned NOT NULL DEFAULT 0,
			season_id bigint(20) unsigned NOT NULL DEFAULT 0,
			match_id bigint(20) unsigned NOT NULL DEFAULT 0,
			metric varchar(50) NOT NULL DEFAULT '',
			value double NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY player_season (player_id,season_id),
			KEY metric (metric)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$relationships} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			from_id bigint(20) unsigned NOT NULL DEFAULT 0,
			to_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rel_type varchar(50) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY rel (from_id,to_id,rel_type),
			KEY from_type (from_id,rel_type),
			KEY to_type (to_id,rel_type)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}
