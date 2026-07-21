<?php
/**
 * Uninstall routine.
 *
 * Only removes data when the administrator explicitly opted in via the
 * "delete data on uninstall" setting. Guarded to the uninstall context.
 *
 * @package Athletix
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$athletix_settings = get_option( 'athletix_settings', array() );

if ( empty( $athletix_settings['delete_data'] ) ) {
	// Preserve content; just drop bookkeeping options.
	delete_option( 'athletix_version' );
	delete_option( 'athletix_installed_at' );
	return;
}

global $wpdb;

// Delete plugin post types and their meta.
$athletix_post_types = array( 'ax_league', 'ax_season', 'ax_team', 'ax_player', 'ax_match', 'ax_division' );

foreach ( $athletix_post_types as $athletix_type ) {
	$athletix_ids = get_posts(
		array(
			'post_type'        => $athletix_type,
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	foreach ( $athletix_ids as $athletix_id ) {
		wp_delete_post( $athletix_id, true );
	}
}

// Drop custom tables created by the data layer.
$athletix_tables = array(
	$wpdb->prefix . 'athletix_standings',
	$wpdb->prefix . 'athletix_player_stats',
	$wpdb->prefix . 'athletix_relationships',
);

foreach ( $athletix_tables as $athletix_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DROP TABLE IF EXISTS {$athletix_table}" );
}

// Remove options.
delete_option( 'athletix_settings' );
delete_option( 'athletix_version' );
delete_option( 'athletix_installed_at' );
delete_option( 'athletix_db_version' );
