<?php


namespace SohaJin\Toys\XmlDatabase\Expression;


use SohaJin\Toys\XmlDatabase\Expression\XFunction\Contains;

class Expr {
	public static function contains($haystack, $needle): Operand { return new Contains($haystack, $needle); }

	/* from Operator */
	public static function eq($left, $right): Operand { return Operator::eq($left, $right); }
	public static function ne($left, $right): Operand { return Operator::ne($left, $right); }
	public static function lt($left, $right): Operand { return Operator::lt($left, $right); }
	public static function le($left, $right): Operand { return Operator::le($left, $right); }
	public static function gt($left, $right): Operand { return Operator::gt($left, $right); }
	public static function ge($left, $right): Operand { return Operator::ge($left, $right); }
	public static function add($left, $right): Operand { return Operator::add($left, $right); }
	public static function sub($left, $right): Operand { return Operator::sub($left, $right); }
	public static function mul($left, $right): Operand { return Operator::mul($left, $right); }
	public static function mod($left, $right): Operand { return Operator::mod($left, $right); }
	public static function div($left, $right): Operand { return Operator::div($left, $right); }
	public static function and($left, $right): Operand { return Operator::and($left, $right); }
	public static function or($left, $right): Operand { return Operator::or($left, $right); }
}
