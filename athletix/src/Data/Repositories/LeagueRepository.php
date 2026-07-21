<?php
/**
 * League repository.
 *
 * @package Athletix
 */

namespace Athletix\Data\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Athletix\Support\Keys;

/**
 * Data access for leagues.
 */
class LeagueRepository extends BaseRepository {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = Keys::LEAGUE;
}
