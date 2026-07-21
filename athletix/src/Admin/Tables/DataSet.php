<?php
/**
 * In-memory table data processing.
 *
 * @package Athletix
 */

namespace Athletix\Admin\Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The shared "abilities" behind every Athletix table: given a list of row
 * arrays and a query spec, it applies exact-match filters, a keyword search
 * across chosen columns, single-column sorting and pagination — and returns the
 * page of items plus the pre-pagination total.
 *
 * Deliberately free of WordPress so the same filtering/sorting rules power both
 * the WP_List_Table wrapper and the custom table, and can be unit-tested.
 */
class DataSet {

	/**
	 * Process rows against a query spec.
	 *
	 * @param array[] $rows Row arrays keyed by column slug.
	 * @param array   $spec {
	 *     Query spec.
	 *
	 *     @type array  $filters    Column => exact value (blank values ignored).
	 *     @type string $search     Keyword matched (case-insensitively) as a substring.
	 *     @type array  $searchable Columns the keyword is matched against.
	 *     @type string $orderby    Column to sort by ('' = keep input order).
	 *     @type string $order      'ASC' or 'DESC'.
	 *     @type int    $page       1-based page number.
	 *     @type int    $per_page   Rows per page.
	 * }
	 * @return array{items:array[],total:int}
	 */
	public static function process( array $rows, array $spec ) {
		$filters    = isset( $spec['filters'] ) && is_array( $spec['filters'] ) ? $spec['filters'] : array();
		$search     = isset( $spec['search'] ) ? trim( (string) $spec['search'] ) : '';
		$searchable = isset( $spec['searchable'] ) && is_array( $spec['searchable'] ) ? $spec['searchable'] : array();
		$orderby    = isset( $spec['orderby'] ) ? (string) $spec['orderby'] : '';
		$order      = isset( $spec['order'] ) && 'DESC' === strtoupper( (string) $spec['order'] ) ? 'DESC' : 'ASC';
		$page       = isset( $spec['page'] ) ? max( 1, (int) $spec['page'] ) : 1;
		$per_page   = isset( $spec['per_page'] ) ? max( 1, (int) $spec['per_page'] ) : 20;

		$rows = self::filter( $rows, $filters );
		$rows = self::search( $rows, $search, $searchable );

		$total = count( $rows );

		$rows = self::sort( $rows, $orderby, $order );

		$offset = ( $page - 1 ) * $per_page;
		$items  = array_slice( $rows, $offset, $per_page );

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Keep rows matching every non-empty exact filter.
	 *
	 * @param array[] $rows    Rows.
	 * @param array   $filters Column => value.
	 * @return array[]
	 */
	private static function filter( array $rows, array $filters ) {
		$active = array();
		foreach ( $filters as $column => $value ) {
			if ( '' !== $value && null !== $value ) {
				$active[ $column ] = (string) $value;
			}
		}

		if ( ! $active ) {
			return array_values( $rows );
		}

		return array_values(
			array_filter(
				$rows,
				static function ( $row ) use ( $active ) {
					foreach ( $active as $column => $value ) {
						if ( (string) ( isset( $row[ $column ] ) ? $row[ $column ] : '' ) !== $value ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	}

	/**
	 * Keep rows whose searchable columns contain the keyword.
	 *
	 * @param array[]  $rows       Rows.
	 * @param string   $search     Keyword.
	 * @param string[] $searchable Columns to search.
	 * @return array[]
	 */
	private static function search( array $rows, $search, array $searchable ) {
		if ( '' === $search || ! $searchable ) {
			return array_values( $rows );
		}

		$needle = self::lower( $search );

		return array_values(
			array_filter(
				$rows,
				static function ( $row ) use ( $searchable, $needle ) {
					foreach ( $searchable as $column ) {
						$haystack = self::lower( (string) ( isset( $row[ $column ] ) ? $row[ $column ] : '' ) );
						if ( '' !== $needle && false !== strpos( $haystack, $needle ) ) {
							return true;
						}
					}
					return false;
				}
			)
		);
	}

	/**
	 * Sort rows by a column, numerically when both values are numeric.
	 *
	 * @param array[] $rows    Rows.
	 * @param string  $orderby Column.
	 * @param string  $order   ASC|DESC.
	 * @return array[]
	 */
	private static function sort( array $rows, $orderby, $order ) {
		if ( '' === $orderby ) {
			return $rows;
		}

		$sign = 'DESC' === $order ? -1 : 1;

		usort(
			$rows,
			static function ( $a, $b ) use ( $orderby, $sign ) {
				$av = isset( $a[ $orderby ] ) ? $a[ $orderby ] : null;
				$bv = isset( $b[ $orderby ] ) ? $b[ $orderby ] : null;

				if ( is_numeric( $av ) && is_numeric( $bv ) ) {
					$cmp = ( $av < $bv ) ? -1 : ( ( $av > $bv ) ? 1 : 0 );
				} else {
					$cmp = strcasecmp( (string) $av, (string) $bv );
				}

				return $sign * $cmp;
			}
		);

		return $rows;
	}

	/**
	 * Lower-case helper (multibyte-aware when available).
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private static function lower( $value ) {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
	}
}
