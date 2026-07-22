<?php
/**
 * League repository (taxonomy term).
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for leagues, stored as terms of the ax_league taxonomy.
 */
class LeagueRepository extends TermRepository {

	/**
	 * Taxonomy.
	 *
	 * @var string
	 */
	protected $taxonomy = Keys::LEAGUE;
}
