<?php
/**
 * PHPUnit bootstrap for pure (WordPress-free) unit tests.
 *
 * @package Athletix
 */

// The domain classes guard on ABSPATH; define it so they can be loaded
// outside of WordPress for isolated unit testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/../src/Support/Autoloader.php';
\Athletix\Support\Autoloader::register( 'Athletix\\', __DIR__ . '/../src/' );
