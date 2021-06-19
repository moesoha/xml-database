<?php


namespace SohaJin\Toys\XmlDatabase\XmlSchemaDefinition\DataTransformer;


interface TransformerInterface {
	public function toPhpValue(string $value): mixed;
	public function toXmlValue($value): string;
}
