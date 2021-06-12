<?php

namespace SohaJin\Toys\XmlDatabase\Expression {
	use SohaJin\Toys\XmlDatabase\Exception\InvalidOperandException;

	function forceOperand(mixed $value, array $blackList = []): Operand {
		if (is_object($value) && in_array($value::class, $blackList)) {
			throw new InvalidOperandException($value);
		}
		if ($value instanceof Operand) {
			return $value;
		}
		if (is_numeric($value)) {
			return new NumberOperand($value);
		}
		if (is_string($value)) {
			return new StringOperand($value);
		}
		throw new InvalidOperandException($value);
	}
}
