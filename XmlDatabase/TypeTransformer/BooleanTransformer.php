<?php


namespace SohaJin\Toys\XmlDatabase\TypeTransformer;


class BooleanTransformer implements TransformerInterface {
	public function toPhpValue(string $value): bool {
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	public function toXmlValue($value) : string {
		return $value ? 'true' : 'false';
	}
}
