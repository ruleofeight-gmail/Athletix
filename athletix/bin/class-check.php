<?php
/**
 * Static unresolved-class-reference checker.
 *
 * Catches class references (Name::CONST, new Name(), extends/implements Name)
 * that resolve to a non-existent class because they are neither imported with a
 * `use` statement nor defined in the same namespace. This is the bug class that
 * `php -l` and boot smoke-tests miss, because such code only fatals when the
 * offending line actually executes (e.g. on a specific hook).
 *
 * Usage: php bin/class-check.php [src-dir]
 * Exit code 1 if any unresolved reference is found.
 *
 * @package Athletix
 */

$root = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : __DIR__ . '/../src';

if ( ! is_dir( $root ) ) {
	fwrite( STDERR, "Not a directory: {$root}\n" );
	exit( 2 );
}

$files = new RegexIterator(
	new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) ),
	'/\.php$/'
);

$defined = array();
$parsed  = array();

foreach ( $files as $file ) {
	$src = file_get_contents( $file );
	$ns  = '';
	if ( preg_match( '/^namespace\s+([^;]+);/m', $src, $m ) ) {
		$ns = trim( $m[1] );
	}
	if ( preg_match_all( '/^(?:final\s+|abstract\s+)?(?:class|interface|trait)\s+(\w+)/m', $src, $mm ) ) {
		foreach ( $mm[1] as $cn ) {
			$defined[ $ns . '\\' . $cn ] = true;
		}
	}
	$uses = array();
	if ( preg_match_all( '/^use\s+([^;]+);/m', $src, $um ) ) {
		foreach ( $um[1] as $u ) {
			$u = trim( $u );
			if ( stripos( $u, ' as ' ) !== false ) {
				list( $full, $alias )   = preg_split( '/\s+as\s+/i', $u );
				$uses[ trim( $alias ) ] = ltrim( trim( $full ), '\\' );
			} else {
				$parts                       = explode( '\\', $u );
				$uses[ trim( end( $parts ) ) ] = ltrim( $u, '\\' );
			}
		}
	}
	$parsed[ (string) $file ] = array( $ns, $uses, $src );
}

$globals = array(
	'WP_Post', 'WP_Query', 'WP_Error', 'WP_REST_Request', 'WP_REST_Response', 'WP_REST_Server',
	'WP_User', 'WP_Roles', 'WP_Term', 'wpdb', 'WP_UnitTestCase',
	'Exception', 'RuntimeException', 'InvalidArgumentException', 'LogicException', 'Throwable',
	'stdClass', 'ArrayIterator', 'Closure', 'Generator', 'DateTime', 'DateTimeImmutable',
);

$issues = array();

foreach ( $parsed as $file => $data ) {
	list( $ns, $uses, $src ) = $data;
	$body = preg_replace( '/^\s*(namespace|use)\s+[^;]+;/m', '', $src );

	$refs = array();
	if ( preg_match_all( '/(?<![\w\\\\>$])([A-Z]\w+)::/', $body, $a ) ) {
		$refs = array_merge( $refs, $a[1] );
	}
	if ( preg_match_all( '/\bnew\s+([A-Z]\w+)\s*\(/', $body, $b ) ) {
		$refs = array_merge( $refs, $b[1] );
	}
	if ( preg_match_all( '/\b(?:extends|implements)\s+([A-Z]\w+)/', $body, $c ) ) {
		$refs = array_merge( $refs, $c[1] );
	}

	foreach ( array_unique( $refs ) as $ref ) {
		if ( in_array( $ref, array( 'self', 'static', 'parent' ), true ) ) {
			continue;
		}
		if ( in_array( $ref, $globals, true ) ) {
			continue;
		}
		if ( isset( $uses[ $ref ] ) ) {
			continue;
		}
		if ( isset( $defined[ $ns . '\\' . $ref ] ) ) {
			continue;
		}
		// Referenced fully-qualified (with a leading backslash) somewhere? Then it is explicit.
		if ( preg_match( '/\\\\' . preg_quote( $ref, '/' ) . '\b/', $body ) ) {
			continue;
		}
		$issues[] = sprintf( '%s  ->  %s  (resolves to %s\\%s, not defined or imported)', str_replace( $root . '/', '', $file ), $ref, $ns, $ref );
	}
}

if ( $issues ) {
	fwrite( STDERR, "Unresolved class references:\n" . implode( "\n", array_unique( $issues ) ) . "\n" );
	exit( 1 );
}

echo "class-check: no unresolved class references.\n";
