<?php
/**
 * Relationship repository (custom table).
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Data\Schema;

/**
 * Generic typed relationships between entities (e.g. league⇄division,
 * division⇄team), replacing scattered post-meta conventions with one indexed
 * table.
 */
class RelationshipRepository {

	/**
	 * WordPress database.
	 *
	 * @var \wpdb
	 */
	private $db;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = Schema::table( Schema::RELATIONSHIPS );
	}

	/**
	 * Add a relationship (idempotent via the unique key).
	 *
	 * @param int    $from_id  Source entity id.
	 * @param int    $to_id    Target entity id.
	 * @param string $rel_type Relationship type slug.
	 * @return void
	 */
	public function add( $from_id, $to_id, $rel_type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->query(
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"INSERT IGNORE INTO {$this->table} (from_id, to_id, rel_type) VALUES (%d, %d, %s)",
				absint( $from_id ),
				absint( $to_id ),
				sanitize_key( $rel_type )
			)
		);
	}

	/**
	 * Remove a relationship.
	 *
	 * @param int    $from_id  Source id.
	 * @param int    $to_id    Target id.
	 * @param string $rel_type Type slug.
	 * @return void
	 */
	public function remove( $from_id, $to_id, $rel_type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->db->delete(
			$this->table,
			array(
				'from_id'  => absint( $from_id ),
				'to_id'    => absint( $to_id ),
				'rel_type' => sanitize_key( $rel_type ),
			)
		);
	}

	/**
	 * Target ids related from a source by type.
	 *
	 * @param int    $from_id  Source id.
	 * @param string $rel_type Type slug.
	 * @return int[]
	 */
	public function targets( $from_id, $rel_type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $this->db->get_col(
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT to_id FROM {$this->table} WHERE from_id = %d AND rel_type = %s",
				absint( $from_id ),
				sanitize_key( $rel_type )
			)
		);

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Source ids related to a target by type.
	 *
	 * @param int    $to_id    Target id.
	 * @param string $rel_type Type slug.
	 * @return int[]
	 */
	public function sources( $to_id, $rel_type ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $this->db->get_col(
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT from_id FROM {$this->table} WHERE to_id = %d AND rel_type = %s",
				absint( $to_id ),
				sanitize_key( $rel_type )
			)
		);

		return array_map( 'intval', (array) $ids );
	}
}
