<?php
/**
 * Safe formula evaluator for admin-defined variables.
 *
 * @package Athletix
 */

namespace Athletix\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exception messages here are internal parser codes (never rendered to a page),
// and this class is deliberately WordPress-free, so escaping helpers do not
// apply.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Evaluates the free-form equations an admin writes for calculated variables
 * (standings columns, statistics, outcomes) against a numeric context row.
 *
 * It is a purpose-built tokenizer → shunting-yard → RPN evaluator: it only ever
 * does arithmetic, comparisons and a tiny whitelist of functions over `$token`
 * look-ups — it never touches PHP eval(), so a formula can do nothing beyond
 * maths on the known variables. WordPress-free, so it can be unit-tested and
 * shared by every calculated variable type.
 *
 * Grammar:
 *   - numbers (12, 3.5), variables ($goals), parentheses
 *   - operators: + - * /  and comparisons  > < >= <= == !=  (yield 1 or 0)
 *   - functions: round(x,p), min(a,b), max(a,b), abs(x)
 */
class Equation {

	/**
	 * Operator precedence (higher binds tighter).
	 *
	 * @var array<string,int>
	 */
	private static $precedence = array(
		'==' => 1,
		'!=' => 1,
		'>'  => 1,
		'<'  => 1,
		'>=' => 1,
		'<=' => 1,
		'+'  => 2,
		'-'  => 2,
		'*'  => 3,
		'/'  => 3,
		'u-' => 4,
	);

	/**
	 * Supported functions and their fixed arity.
	 *
	 * @var array<string,int>
	 */
	private static $functions = array(
		'round' => 2,
		'min'   => 2,
		'max'   => 2,
		'abs'   => 1,
	);

	/**
	 * Evaluate a formula against a context row, rounded to a precision.
	 *
	 * Never throws: a malformed formula (which the validator rejects at save
	 * time) or a divide-by-zero yields 0.0 so a display path can't fatal.
	 *
	 * @param string              $formula   The equation.
	 * @param array<string,float> $context  Map of variable key => numeric value.
	 * @param int                 $precision Decimal places to round to.
	 * @return float
	 */
	public function evaluate( $formula, array $context, $precision = 0 ) {
		try {
			$value = $this->run( $this->to_rpn( $this->tokenize( (string) $formula ) ), $context );
		} catch ( \Exception $e ) {
			return 0.0;
		}

		return round( $value, max( 0, (int) $precision ) );
	}

	/**
	 * Validate a formula: parseable, known functions, and every variable is in
	 * the allowed set.
	 *
	 * @param string   $formula The equation.
	 * @param string[] $allowed Allowed variable keys (without the leading $).
	 * @return array{ok:bool,error:string,variables:string[]}
	 */
	public function validate( $formula, array $allowed = array() ) {
		$formula = (string) $formula;

		if ( '' === trim( $formula ) ) {
			return array(
				'ok'        => false,
				'error'     => 'empty',
				'variables' => array(),
			);
		}

		try {
			$tokens = $this->tokenize( $formula );
			$rpn    = $this->to_rpn( $tokens );
		} catch ( \Exception $e ) {
			return array(
				'ok'        => false,
				'error'     => $e->getMessage(),
				'variables' => array(),
			);
		}

		$variables = array();
		foreach ( $tokens as $token ) {
			if ( 'var' === $token['type'] ) {
				$variables[ $token['value'] ] = true;
			}
		}
		$variables = array_keys( $variables );

		if ( $allowed ) {
			foreach ( $variables as $var ) {
				if ( ! in_array( $var, $allowed, true ) ) {
					return array(
						'ok'        => false,
						'error'     => 'unknown_variable:' . $var,
						'variables' => $variables,
					);
				}
			}
		}

		// Dry-run against a zeroed context to catch arity/structure errors.
		try {
			$zero = array();
			foreach ( $variables as $var ) {
				$zero[ $var ] = 0.0;
			}
			$this->run( $rpn, $zero );
		} catch ( \Exception $e ) {
			return array(
				'ok'        => false,
				'error'     => $e->getMessage(),
				'variables' => $variables,
			);
		}

		return array(
			'ok'        => true,
			'error'     => '',
			'variables' => $variables,
		);
	}

	/**
	 * Break a formula into typed tokens.
	 *
	 * @param string $formula Formula.
	 * @return array[] List of ['type'=>..,'value'=>..].
	 * @throws \RuntimeException On an unexpected character.
	 */
	private function tokenize( $formula ) {
		$tokens = array();
		$len    = strlen( $formula );
		$i      = 0;
		$prev   = null;
		// Previous token type, for unary-minus detection.

		while ( $i < $len ) {
			$ch = $formula[ $i ];

			if ( ctype_space( $ch ) ) {
				++$i;
				continue;
			}

			// Number.
			if ( ctype_digit( $ch ) || ( '.' === $ch && $i + 1 < $len && ctype_digit( $formula[ $i + 1 ] ) ) ) {
				$num = '';
				while ( $i < $len && ( ctype_digit( $formula[ $i ] ) || '.' === $formula[ $i ] ) ) {
					$num .= $formula[ $i ];
					++$i;
				}
				$tokens[] = array(
					'type'  => 'num',
					'value' => (float) $num,
				);
				$prev     = 'num';
				continue;
			}

			// Variable ($key).
			if ( '$' === $ch ) {
				$name = '';
				++$i;
				while ( $i < $len && ( ctype_alnum( $formula[ $i ] ) || '_' === $formula[ $i ] ) ) {
					$name .= $formula[ $i ];
					++$i;
				}
				if ( '' === $name ) {
					throw new \RuntimeException( 'bad_variable' );
				}
				$tokens[] = array(
					'type'  => 'var',
					'value' => strtolower( $name ),
				);
				$prev     = 'var';
				continue;
			}

			// Identifier → function name.
			if ( ctype_alpha( $ch ) || '_' === $ch ) {
				$name = '';
				while ( $i < $len && ( ctype_alnum( $formula[ $i ] ) || '_' === $formula[ $i ] ) ) {
					$name .= $formula[ $i ];
					++$i;
				}
				$name = strtolower( $name );
				if ( ! isset( self::$functions[ $name ] ) ) {
					throw new \RuntimeException( 'unknown_function:' . $name );
				}
				$tokens[] = array(
					'type'  => 'func',
					'value' => $name,
				);
				$prev     = 'func';
				continue;
			}

			// Two-character comparison operators.
			$two = substr( $formula, $i, 2 );
			if ( in_array( $two, array( '>=', '<=', '==', '!=' ), true ) ) {
				$tokens[] = array(
					'type'  => 'op',
					'value' => $two,
				);
				$prev     = 'op';
				$i       += 2;
				continue;
			}

			// Single-character operators / parens / comma.
			if ( in_array( $ch, array( '+', '-', '*', '/', '>', '<' ), true ) ) {
				$op = $ch;
				if ( '-' === $ch && ( null === $prev || in_array( $prev, array( 'op', 'lparen', 'comma' ), true ) ) ) {
					$op = 'u-';
				}
				$tokens[] = array(
					'type'  => 'op',
					'value' => $op,
				);
				$prev     = 'op';
				++$i;
				continue;
			}

			if ( '(' === $ch ) {
				$tokens[] = array(
					'type'  => 'lparen',
					'value' => '(',
				);
				$prev     = 'lparen';
				++$i;
				continue;
			}

			if ( ')' === $ch ) {
				$tokens[] = array(
					'type'  => 'rparen',
					'value' => ')',
				);
				$prev     = 'rparen';
				++$i;
				continue;
			}

			if ( ',' === $ch ) {
				$tokens[] = array(
					'type'  => 'comma',
					'value' => ',',
				);
				$prev     = 'comma';
				++$i;
				continue;
			}

			throw new \RuntimeException( 'unexpected_char:' . $ch );
		}//end while

		return $tokens;
	}

	/**
	 * Shunting-yard: convert the token stream to Reverse Polish Notation.
	 *
	 * @param array[] $tokens Tokens.
	 * @return array[] RPN output queue.
	 * @throws \RuntimeException On mismatched parentheses.
	 */
	private function to_rpn( array $tokens ) {
		$output = array();
		$stack  = array();

		foreach ( $tokens as $token ) {
			switch ( $token['type'] ) {
				case 'num':
				case 'var':
					$output[] = $token;
					break;

				case 'func':
					$stack[] = $token;
					break;

				case 'comma':
					while ( $stack && 'lparen' !== end( $stack )['type'] ) {
						$output[] = array_pop( $stack );
					}
					if ( ! $stack ) {
						throw new \RuntimeException( 'misplaced_comma' );
					}
					break;

				case 'op':
					while (
						$stack &&
						'op' === end( $stack )['type'] &&
						self::$precedence[ end( $stack )['value'] ] >= self::$precedence[ $token['value'] ] &&
						'u-' !== $token['value']
					) {
						$output[] = array_pop( $stack );
					}
					$stack[] = $token;
					break;

				case 'lparen':
					$stack[] = $token;
					break;

				case 'rparen':
					while ( $stack && 'lparen' !== end( $stack )['type'] ) {
						$output[] = array_pop( $stack );
					}
					if ( ! $stack ) {
						throw new \RuntimeException( 'mismatched_parens' );
					}
					array_pop( $stack );
					// Drop the '('.
					if ( $stack && 'func' === end( $stack )['type'] ) {
						$output[] = array_pop( $stack );
					}
					break;
			}//end switch
		}//end foreach

		while ( $stack ) {
			$top = array_pop( $stack );
			if ( 'lparen' === $top['type'] || 'rparen' === $top['type'] ) {
				throw new \RuntimeException( 'mismatched_parens' );
			}
			$output[] = $top;
		}

		return $output;
	}

	/**
	 * Evaluate an RPN queue against the context.
	 *
	 * @param array[]             $rpn     RPN tokens.
	 * @param array<string,float> $context Variable values.
	 * @return float
	 * @throws \RuntimeException On a malformed expression.
	 */
	private function run( array $rpn, array $context ) {
		$stack = array();

		foreach ( $rpn as $token ) {
			if ( 'num' === $token['type'] ) {
				$stack[] = (float) $token['value'];
			} elseif ( 'var' === $token['type'] ) {
				$stack[] = isset( $context[ $token['value'] ] ) ? (float) $context[ $token['value'] ] : 0.0;
			} elseif ( 'op' === $token['type'] ) {
				$stack[] = $this->apply_op( $token['value'], $stack );
			} elseif ( 'func' === $token['type'] ) {
				$stack[] = $this->apply_func( $token['value'], $stack );
			}
		}

		if ( 1 !== count( $stack ) ) {
			throw new \RuntimeException( 'malformed_expression' );
		}

		return (float) $stack[0];
	}

	/**
	 * Apply an operator, consuming operands from the stack.
	 *
	 * @param string  $op    Operator.
	 * @param float[] $stack Value stack (by reference).
	 * @return float
	 * @throws \RuntimeException On a stack underflow.
	 */
	private function apply_op( $op, array &$stack ) {
		if ( 'u-' === $op ) {
			if ( ! $stack ) {
				throw new \RuntimeException( 'underflow' );
			}
			return -1.0 * (float) array_pop( $stack );
		}

		if ( count( $stack ) < 2 ) {
			throw new \RuntimeException( 'underflow' );
		}

		$b = (float) array_pop( $stack );
		$a = (float) array_pop( $stack );

		switch ( $op ) {
			case '+':
				return $a + $b;
			case '-':
				return $a - $b;
			case '*':
				return $a * $b;
			case '/':
				return 0.0 === $b ? 0.0 : $a / $b;
			case '>':
				return $a > $b ? 1.0 : 0.0;
			case '<':
				return $a < $b ? 1.0 : 0.0;
			case '>=':
				return $a >= $b ? 1.0 : 0.0;
			case '<=':
				return $a <= $b ? 1.0 : 0.0;
			case '==':
				return $a === $b ? 1.0 : 0.0;
			case '!=':
				return $a !== $b ? 1.0 : 0.0;
		}//end switch

		throw new \RuntimeException( 'bad_operator:' . $op );
	}

	/**
	 * Apply a function, consuming its arguments from the stack.
	 *
	 * @param string  $name  Function name.
	 * @param float[] $stack Value stack (by reference).
	 * @return float
	 * @throws \RuntimeException On a stack underflow.
	 */
	private function apply_func( $name, array &$stack ) {
		switch ( $name ) {
			case 'abs':
				if ( ! $stack ) {
					throw new \RuntimeException( 'underflow' );
				}
				return abs( (float) array_pop( $stack ) );

			case 'round':
				if ( count( $stack ) < 2 ) {
					throw new \RuntimeException( 'underflow' );
				}
				$p = (int) array_pop( $stack );
				$v = (float) array_pop( $stack );
				return round( $v, max( 0, $p ) );

			case 'min':
			case 'max':
				if ( count( $stack ) < 2 ) {
					throw new \RuntimeException( 'underflow' );
				}
				$b = (float) array_pop( $stack );
				$a = (float) array_pop( $stack );
				return 'min' === $name ? min( $a, $b ) : max( $a, $b );
		}//end switch

		throw new \RuntimeException( 'bad_function:' . $name );
	}
}
