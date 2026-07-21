<?php
/**
 * Minimal translation-catalog (.pot) generator.
 *
 * Scans the plugin source for gettext calls in the `athletix` text domain and
 * writes languages/athletix.pot. Deliberately dependency-free (no WP-CLI) so it
 * runs anywhere PHP does; it understands the call shapes this plugin actually
 * uses: __(), _e(), esc_html__/e(), esc_attr__/e(), _n(), _x(), _nx().
 *
 * Usage: php bin/make-pot.php
 *
 * @package Athletix
 */

$root   = dirname( __DIR__ );
$domain = 'athletix';
$out    = $root . '/languages/athletix.pot';

$scan_dirs = array( '/src', '/templates', '/athletix.php', '/assets/js' );

$files = array();
foreach ( $scan_dirs as $rel ) {
	$path = $root . $rel;
	if ( is_file( $path ) ) {
		$files[] = $path;
		continue;
	}
	if ( ! is_dir( $path ) ) {
		continue;
	}
	$iter = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
	foreach ( new RegexIterator( $iter, '/\.(php|js)$/' ) as $file ) {
		$files[] = (string) $file;
	}
}
sort( $files );

/**
 * Collected strings keyed by "context\4singular\4plural" to dedupe, each value
 * carrying the msgid parts plus the source references.
 */
$entries = array();

// Single-form functions: __( 'x' ), _e( 'x' ), esc_*__( 'x' ), esc_*_e( 'x' ).
$single = 'esc_html_e|esc_attr_e|esc_html__|esc_attr__|__|_e';
// Context single: _x( 'x', 'ctx' ), esc_*_x( 'x', 'ctx' ), _ex( 'x', 'ctx' ).
$ctx = 'esc_html_x|esc_attr_x|_ex|_x';
// Plural: _n( 's', 'p', $n ). Plural+context: _nx( 's', 'p', $n, 'ctx' ).

$str = "'((?:\\\\.|[^'\\\\])*)'|\"((?:\\\\.|[^\"\\\\])*)\"";

foreach ( $files as $file ) {
	$src = file_get_contents( $file );
	$ref = ltrim( str_replace( $root, '', $file ), '/' );

	// _nx( singular, plural, n, context, domain )
	if ( preg_match_all( '/\b_nx\s*\(\s*(?:' . $str . ')\s*,\s*(?:' . $str . ')\s*,[^,]+,\s*(?:' . $str . ')\s*,\s*[\'"]' . $domain . '[\'"]/s', $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			add_entry( $entries, unquote( ( $x[1] ?? null ), ( $x[2] ?? null ) ), unquote( ( $x[3] ?? null ), ( $x[4] ?? null ) ), unquote( ( $x[5] ?? null ), ( $x[6] ?? null ) ), $ref );
		}
	}

	// _n( singular, plural, n, domain )
	if ( preg_match_all( '/\b_n\s*\(\s*(?:' . $str . ')\s*,\s*(?:' . $str . ')\s*,[^,]+,\s*[\'"]' . $domain . '[\'"]/s', $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			add_entry( $entries, unquote( ( $x[1] ?? null ), ( $x[2] ?? null ) ), '', unquote( ( $x[3] ?? null ), ( $x[4] ?? null ) ), $ref );
		}
	}

	// Context single: _x / _ex / esc_*_x ( text, context, domain )
	if ( preg_match_all( '/\b(?:' . $ctx . ')\s*\(\s*(?:' . $str . ')\s*,\s*(?:' . $str . ')\s*,\s*[\'"]' . $domain . '[\'"]/s', $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			add_entry( $entries, unquote( ( $x[3] ?? null ), ( $x[4] ?? null ) ), '', unquote( ( $x[1] ?? null ), ( $x[2] ?? null ) ), $ref );
		}
	}

	// Single-form: fn( text, domain )
	if ( preg_match_all( '/\b(?:' . $single . ')\s*\(\s*(?:' . $str . ')\s*,\s*[\'"]' . $domain . '[\'"]/s', $src, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			add_entry( $entries, unquote( ( $x[1] ?? null ), ( $x[2] ?? null ) ), '', '', $ref );
		}
	}
}//end foreach

/**
 * Record (or merge references into) a catalog entry.
 *
 * @param array  $entries  Catalog (by reference).
 * @param string $singular Singular msgid.
 * @param string $plural   Plural msgid or ''.
 * @param string $context  Context or ''.
 * @param string $ref      Source reference.
 * @return void
 */
function add_entry( array &$entries, $singular, $plural, $context, $ref ) {
	if ( '' === $singular ) {
		return;
	}
	$key = $context . "\4" . $singular . "\4" . $plural;
	if ( ! isset( $entries[ $key ] ) ) {
		$entries[ $key ] = array(
			'singular' => $singular,
			'plural'   => $plural,
			'context'  => $context,
			'refs'     => array(),
		);
	}
	$entries[ $key ]['refs'][ $ref ] = true;
}

/**
 * Pick the matched single- or double-quoted capture and unescape it.
 *
 * @param string|null $single Single-quoted capture.
 * @param string|null $double Double-quoted capture.
 * @return string
 */
function unquote( $single, $double ) {
	$value = ( isset( $single ) && '' !== $single ) ? $single : ( isset( $double ) ? $double : '' );
	return stripcslashes( $value );
}

/**
 * Escape a string for a PO msgid/msgstr literal.
 *
 * @param string $text Raw text.
 * @return string
 */
function po_escape( $text ) {
	return str_replace(
		array( '\\', '"', "\n", "\t" ),
		array( '\\\\', '\\"', '\\n', '\\t' ),
		$text
	);
}

ksort( $entries );

$now    = gmdate( 'Y-m-d H:iO' );
$header = <<<POT
# Copyright (C) Athletix
# This file is distributed under the GPL-2.0-or-later license.
msgid ""
msgstr ""
"Project-Id-Version: Athletix 2.0.0\\n"
"Report-Msgid-Bugs-To: https://example.com/support\\n"
"POT-Creation-Date: {$now}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"X-Domain: {$domain}\\n"


POT;

$body = '';
foreach ( $entries as $entry ) {
	$refs  = implode( ' ', array_keys( $entry['refs'] ) );
	$body .= '#: ' . $refs . "\n";
	if ( '' !== $entry['context'] ) {
		$body .= 'msgctxt "' . po_escape( $entry['context'] ) . "\"\n";
	}
	$body .= 'msgid "' . po_escape( $entry['singular'] ) . "\"\n";
	if ( '' !== $entry['plural'] ) {
		$body .= 'msgid_plural "' . po_escape( $entry['plural'] ) . "\"\n";
		$body .= "msgstr[0] \"\"\n";
		$body .= "msgstr[1] \"\"\n\n";
	} else {
		$body .= "msgstr \"\"\n\n";
	}
}

if ( ! is_dir( dirname( $out ) ) ) {
	mkdir( dirname( $out ), 0755, true );
}
file_put_contents( $out, $header . $body );

printf( "Wrote %d strings to %s\n", count( $entries ), str_replace( $root . '/', '', $out ) );
