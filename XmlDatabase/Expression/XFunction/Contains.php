<?php

namespace SohaJin\Toys\XmlDatabase\Expression\XFunction;

use SohaJin\Toys\XmlDatabase\Expression\Operand;
use function SohaJin\Toys\XmlDatabase\Expression\forceOperand;

class Contains implements Operand {
	private Operand $haystack;
	private Operand $needle;

	public function __construct($haystack, $needle) {
		$this->haystack = forceOperand($haystack);
		$this->needle = forceOperand($needle);
	}

	public function __toString() : string {
		return "contains({$this->haystack}, {$this->needle})";
	}
}
