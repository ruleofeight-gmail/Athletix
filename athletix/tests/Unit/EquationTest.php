<?php
/**
 * Tests for the Equation evaluator.
 *
 * @package Athletix
 */

namespace Athletix\Tests\Unit;

use Athletix\Engine\Equation;
use PHPUnit\Framework\TestCase;

/**
 * Verifies evaluation (arithmetic, comparisons, functions, guards) and the
 * save-time validator.
 */
class EquationTest extends TestCase {

	/**
	 * A soccer-style context row.
	 *
	 * @return array<string,float>
	 */
	private function context() {
		return array(
			'w'  => 5,
			'd'  => 2,
			'l'  => 1,
			'gf' => 12,
			'ga' => 7,
		);
	}

	/**
	 * Arithmetic with precedence and parentheses.
	 *
	 * @return void
	 */
	public function test_arithmetic_and_precedence() {
		$eq = new Equation();

		$this->assertSame( 17.0, $eq->evaluate( '($w * 3) + $d', $this->context() ) );
		$this->assertSame( 5.0, $eq->evaluate( '$gf - $ga', $this->context() ) );
		$this->assertSame( 17.0, $eq->evaluate( '$w * 3 + $d', $this->context() ), 'Multiplication binds before addition.' );
	}

	/**
	 * Unary minus is distinguished from subtraction.
	 *
	 * @return void
	 */
	public function test_unary_minus() {
		$eq = new Equation();

		$this->assertSame( 3.0, $eq->evaluate( '-$ga + 10', $this->context() ) );
		$this->assertSame( -5.0, $eq->evaluate( '$ga - $gf', $this->context() ) );
	}

	/**
	 * Comparisons yield 1 or 0 — the basis for Outcomes.
	 *
	 * @return void
	 */
	public function test_comparisons_yield_boolean() {
		$eq = new Equation();

		$this->assertSame( 1.0, $eq->evaluate( '$gf > $ga', $this->context() ) );
		$this->assertSame( 0.0, $eq->evaluate( '$gf < $ga', $this->context() ) );
		$this->assertSame( 1.0, $eq->evaluate( '$gf >= 12', $this->context() ) );
		$this->assertSame( 0.0, $eq->evaluate( '$gf == $ga', $this->context() ) );
		$this->assertSame( 1.0, $eq->evaluate( '$gf != $ga', $this->context() ) );
	}

	/**
	 * Functions and precision rounding.
	 *
	 * @return void
	 */
	public function test_functions_and_precision() {
		$eq = new Equation();

		$this->assertSame( 1.5, $eq->evaluate( 'round($gf / 8, 2)', $this->context(), 2 ) );
		$this->assertSame( 7.0, $eq->evaluate( 'abs($ga - $gf) + 2', $this->context() ) );
		$this->assertSame( 12.0, $eq->evaluate( 'max($gf, $ga)', $this->context() ) );
		$this->assertSame( 2.0, $eq->evaluate( 'min($d, $gf)', $this->context() ) );
		$this->assertSame( 2.0, $eq->evaluate( '$gf / 5', $this->context(), 0 ), 'Result is rounded to the given precision.' );
	}

	/**
	 * Division by zero is guarded to 0 rather than fataling.
	 *
	 * @return void
	 */
	public function test_division_by_zero_is_guarded() {
		$eq = new Equation();

		$this->assertSame( 0.0, $eq->evaluate( '$gf / $missing', $this->context() ) );
		$this->assertSame( 0.0, $eq->evaluate( '$gf / 0', $this->context() ) );
	}

	/**
	 * An unknown variable defaults to zero at evaluation time.
	 *
	 * @return void
	 */
	public function test_unknown_variable_is_zero_at_runtime() {
		$eq = new Equation();

		$this->assertSame( 12.0, $eq->evaluate( '$gf + $nope', $this->context() ) );
	}

	/**
	 * The validator accepts a well-formed formula and reports its variables.
	 *
	 * @return void
	 */
	public function test_validator_accepts_and_reports_variables() {
		$result = ( new Equation() )->validate( '($w * 3) + $d', array( 'w', 'd', 'l' ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( '', $result['error'] );
		$this->assertEqualsCanonicalizing( array( 'w', 'd' ), $result['variables'] );
	}

	/**
	 * The validator rejects syntax errors, unknown variables and functions.
	 *
	 * @return void
	 */
	public function test_validator_rejects_bad_input() {
		$eq = new Equation();

		$this->assertFalse( $eq->validate( '', array( 'w' ) )['ok'], 'Empty is invalid.' );
		$this->assertFalse( $eq->validate( '$w * ', array( 'w' ) )['ok'], 'Dangling operator is invalid.' );
		$this->assertFalse( $eq->validate( '($w + $d', array( 'w', 'd' ) )['ok'], 'Unbalanced parens are invalid.' );

		$unknown = $eq->validate( '$w + $xyz', array( 'w', 'd' ) );
		$this->assertFalse( $unknown['ok'] );
		$this->assertSame( 'unknown_variable:xyz', $unknown['error'] );

		$fn = $eq->validate( 'foo($w)', array( 'w' ) );
		$this->assertFalse( $fn['ok'] );
		$this->assertSame( 'unknown_function:foo', $fn['error'] );
	}
}
