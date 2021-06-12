<?php

namespace SohaJin\Toys\XmlDatabase\Expression;

class StringOperand implements Operand {
	private string $value;

	public function __construct(string $value) {
		$this->value = $value;
	}

	public function __toString() : string {
		return '"'.htmlentities($this->value, ENT_COMPAT).'"';
	}
}
