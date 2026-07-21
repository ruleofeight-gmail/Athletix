<?php
/**
 * Uninstall routine.
 *
 * Runs when the plugin is deleted from the WordPress admin. Removes the
 * custom posts and their meta. Guarded so it never runs outside of the
 * uninstall context.
 *
 * @package Athletix
 */

// Exit if not called by WordPress during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all Athletix content. Kept intentionally simple; on very large sites
 * this should be batched, but it is safe for typical installs.
 */
$athletix_post_types = array( 'athletix_athlete', 'athletix_team', 'athletix_event' );

foreach ( $athletix_post_types as $athletix_type ) {
	$athletix_posts = get_posts(
		array(
			'post_type'      => $athletix_type,
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);

	foreach ( $athletix_posts as $athletix_post_id ) {
		wp_delete_post( $athletix_post_id, true );
	}
}

// Clear any lingering rewrite rules.
flush_rewrite_rules();
