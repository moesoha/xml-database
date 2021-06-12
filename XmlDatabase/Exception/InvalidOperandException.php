<?php

namespace SohaJin\Toys\XmlDatabase\Exception;

class InvalidOperandException extends \Exception {
	public function __construct($value) {
		parent::__construct($value::class." cannot be convert to an Operand.", 0, null);
	}
}
