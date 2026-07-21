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
$athletix_post_types = array( 'ax_league', 'ax_season', 'ax_team', 'ax_player', 'ax_match', 'ax_division', 'ax_announcement' );

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

// Delete the plugin's taxonomy terms (sport, venue).
foreach ( array( 'ax_sport', 'ax_venue' ) as $athletix_tax ) {
	$athletix_terms = get_terms(
		array(
			'taxonomy'   => $athletix_tax,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( is_array( $athletix_terms ) ) {
		foreach ( $athletix_terms as $athletix_term_id ) {
			wp_delete_term( $athletix_term_id, $athletix_tax );
		}
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

// Remove every option the plugin creates.
$athletix_options = array(
	'athletix_settings',
	'athletix_version',
	'athletix_installed_at',
	'athletix_db_version',
	'athletix_rules',
	'athletix_subscriptions',
	'athletix_audit_log',
);

foreach ( $athletix_options as $athletix_option ) {
	delete_option( $athletix_option );
}

// Remove per-user notification preferences.
delete_metadata( 'user', 0, 'athletix_notify_results', '', true );

// Remove the custom capabilities and role added by the Security module.
$athletix_caps = array(
	'manage_athletix',
	'athletix_manage_competitions',
	'athletix_manage_payments',
	'athletix_manage_members',
	'athletix_edit_matches',
	'athletix_view_reports',
);

foreach ( wp_roles()->role_objects as $athletix_role ) {
	foreach ( $athletix_caps as $athletix_cap ) {
		if ( $athletix_role->has_cap( $athletix_cap ) ) {
			$athletix_role->remove_cap( $athletix_cap );
		}
	}
}

remove_role( 'athletix_manager' );

// Clear the scheduled daily automation event.
$athletix_cron = wp_next_scheduled( 'athletix_daily_event' );
if ( $athletix_cron ) {
	wp_unschedule_event( $athletix_cron, 'athletix_daily_event' );
}
