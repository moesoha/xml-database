<?php


namespace SohaJin\Toys\XmlDatabase\XmlSchemaDefinition;


class DataType {
	public const Integer = "xs:integer";
	public const String = "xs:string";

	public static array $PhpTypeMap = [
		'int' => self::Integer,
		'string' => self::String
	];
	public static function convertFromPhpType(\ReflectionType $type) {
		if (!isset(self::$PhpTypeMap[(string)$type])) {
			throw new \RuntimeException('Unknown '.$type.', cannot to be convert to XSD type.');
		}
		return self::$PhpTypeMap[(string)$type];
	}
}
