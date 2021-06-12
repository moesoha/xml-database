<?php

namespace SohaJin\Toys\XmlDatabase\Expression;

require_once 'misc.php';

class Operator implements Operand {
	private Operand $left;
	private Operand $right;
	private string $operator;

	private function __construct(mixed $left, string $operator, mixed $right) {
		$this->left = forceOperand($left);
		$this->right = forceOperand($right);
		$this->operator = $operator;
	}

	private static function bracket(Operand $op) {
		if ($op instanceof Operator) {
			return "($op)";
		}
		return (string)$op;
	}

	public function __toString() : string {
		return self::bracket($this->left)." {$this->operator} ".self::bracket($this->right);
	}

	public static function eq($left, $right): Operator { return new self($left, '=', $right); }
	public static function ne($left, $right): Operator { return new self($left, '!=', $right); }
	public static function lt($left, $right): Operator { return new self($left, '<', $right); }
	public static function le($left, $right): Operator { return new self($left, '<=', $right); }
	public static function gt($left, $right): Operator { return new self($left, '>', $right); }
	public static function ge($left, $right): Operator { return new self($left, '>=', $right); }

	public static function add($left, $right): Operator { return new self($left, '+', $right); }
	public static function sub($left, $right): Operator { return new self($left, '-', $right); }
	public static function mul($left, $right): Operator { return new self($left, '*', $right); }
	public static function mod($left, $right): Operator { return new self($left, 'mod', $right); }
	public static function div($left, $right): Operator { return new self($left, 'div', $right); }

	public static function and($left, $right): Operator { return new self($left, 'and', $right); }
	public static function or($left, $right): Operator { return new self($left, 'or', $right); }
}
