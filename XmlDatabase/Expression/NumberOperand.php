<?php


namespace SohaJin\Toys\XmlDatabase\Expression;


class NumberOperand implements Operand {
	/**
	 * @var numeric
	 */
	private $value;

	public function __construct($value) {
		if (!is_numeric($value)) {
			throw new \InvalidArgumentException("$value is not numeric.");
		}
		$this->value = $value;
	}

	public function __toString() : string {
		return (string)$this->value;
	}
}
