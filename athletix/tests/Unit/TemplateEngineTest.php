<?php
/**
 * Tests for TemplateEngine.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Automation\TemplateEngine;
use PHPUnit\Framework\TestCase;

/**
 * Verifies placeholder substitution.
 */
class TemplateEngineTest extends TestCase {

	/**
	 * Known placeholders are replaced; unknown ones become empty.
	 *
	 * @return void
	 */
	public function test_renders_placeholders() {
		$engine = new TemplateEngine();

		$out = $engine->render(
			'{home} {home_score}-{away_score} {away} [{missing}]',
			array(
				'home'       => 'Rovers',
				'away'       => 'City',
				'home_score' => 2,
				'away_score' => 1,
			)
		);

		$this->assertSame( 'Rovers 2-1 City []', $out );
	}

	/**
	 * Non-scalar context values render as empty strings.
	 *
	 * @return void
	 */
	public function test_non_scalar_is_empty() {
		$engine = new TemplateEngine();
		$out    = $engine->render( 'x{arr}y', array( 'arr' => array( 1, 2 ) ) );
		$this->assertSame( 'xy', $out );
	}
}
