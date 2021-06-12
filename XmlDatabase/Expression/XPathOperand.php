<?php

namespace SohaJin\Toys\XmlDatabase\Expression;

class XPathOperand implements Operand {
	private string $value;

	public function __construct(string $value) {
		$this->value = $value;
	}

	public function __toString() : string {
		return $this->value;
	}
}
