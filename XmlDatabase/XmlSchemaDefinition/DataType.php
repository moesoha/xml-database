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
		$typeName = ltrim((string)$type, '?');
		if (!isset(self::$PhpTypeMap[$typeName])) {
			throw new \RuntimeException('Unknown '.$typeName.', cannot to be convert to XSD type.');
		}
		return self::$PhpTypeMap[$typeName];
	}
}
