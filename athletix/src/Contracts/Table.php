<?php
/**
 * Table contract.
 *
 * @package Athletix
 */

namespace Athletix\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A displayable admin table.
 *
 * Two implementations satisfy this contract: one wraps WordPress's own
 * WP_List_Table (so we inherit its sorting, search, pagination and bulk-action
 * machinery), the other is a bespoke filterable renderer. Callers depend only
 * on this interface, so a screen can switch between the standard and custom
 * table without changing its code.
 */
interface Table {

	/**
	 * Output the complete table UI (including any filter/search controls).
	 *
	 * @return void
	 */
	public function render();
}
