<?php

namespace SohaJin\Toys\XmlDatabase\XmlSchemaDefinition;

use SohaJin\Toys\XmlDatabase\XmlSchemaDefinition\DataTransformer\BooleanTransformer;
use SohaJin\Toys\XmlDatabase\XmlSchemaDefinition\DataTransformer\TransformerInterface;

// FIXME: many shits here, it just works now
class DataType {
	public const Integer = 'xs:integer';
	public const String = 'xs:string';
	public const Boolean = 'xs:boolean';

	public static array $PhpTypeMap = [
		'int' => self::Integer,
		'string' => self::String,
		'bool' => self::Boolean
	];
	public static array $transformers = [
		self::Boolean => BooleanTransformer::class
	];

	public static function getXmlType(\ReflectionType $type) {
		$typeName = ltrim((string)$type, '?');
		if (!isset(self::$PhpTypeMap[$typeName])) {
			throw new \RuntimeException("Unknown $typeName, cannot to be convert to XSD type.");
		}
		return self::$PhpTypeMap[$typeName];
	}

	public static function toXmlValue($value): string {
		$type = gettype($value);
		if ($type === 'integer') $type = 'int';
		if ($type === 'boolean') $type = 'bool';
		if ($type === 'object') $type = $value::class;
		if (!isset(self::$PhpTypeMap[$type])) {
			throw new \RuntimeException("Unknown $type, cannot to be convert to XSD type.");
		}
		$xsType = self::$PhpTypeMap[$type];
		if (isset(self::$transformers[$xsType])) {
			$reflector = new \ReflectionClass(self::$transformers[$xsType]);
			/** @var TransformerInterface $transformer */
			$transformer = $reflector->newInstance();
			return $transformer->toXmlValue($value);
		}
		return (string)$value;
	}

	public static function toPhpValue(string $xsType, string $value) {
		if (isset(self::$transformers[$xsType])) {
			$reflector = new \ReflectionClass(self::$transformers[$xsType]);
			/** @var TransformerInterface $transformer */
			$transformer = $reflector->newInstance();
			return $transformer->toPhpValue($value);
		}
		$map = array_flip(self::$PhpTypeMap);
		if (isset($map[$xsType])) {
			$type = $map[$xsType] === 'int' ? 'integer' : $map[$xsType];
			settype($value, $type);
			return $value;
		}
		throw new \RuntimeException("Unknown $xsType, cannot to be convert to PHP value.");
	}
}
