<?php


namespace SohaJin\Toys\XmlDatabase\TypeTransformer;


interface TransformerInterface {
	public function toPhpValue(string $value): mixed;
	public function toXmlValue($value): string;
}
