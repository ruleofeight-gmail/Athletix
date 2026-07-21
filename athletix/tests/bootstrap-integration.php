<?php
/**
 * PHPUnit bootstrap for WordPress integration tests.
 *
 * Requires the WordPress test suite (install via bin/install-wp-tests.sh).
 *
 * @package Athletix
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tmp       = getenv( 'TMPDIR' ) ? rtrim( getenv( 'TMPDIR' ), '/\\' ) : '/tmp';
	$_tests_dir = $_tmp . '/wordpress-tests-lib';
}

$_functions = $_tests_dir . '/includes/functions.php';

if ( ! is_readable( $_functions ) ) {
	echo "Could not find the WordPress test suite at {$_tests_dir}.\n";
	echo "Run: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]\n";
	exit( 1 );
}

require_once $_functions;

/**
 * Manually load the plugin under test.
 *
 * @return void
 */
function _athletix_load_plugin() {
	require dirname( __DIR__ ) . '/athletix.php';
}
tests_add_filter( 'muplugins_loaded', '_athletix_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
