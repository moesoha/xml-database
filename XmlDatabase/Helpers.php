<?php

namespace SohaJin\Toys\XmlDatabase;

class Helpers {
	public static function convertPhpQualifiedNameToXmlElementName(string $name) {
		return str_replace('\\', '___', $name);
	}
}
