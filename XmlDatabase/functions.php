<?php

namespace SohaJin\Toys\XmlDatabase {
	use SohaJin\Toys\XmlDatabase\Exception\LibxmlException;

	function phpClassNameToXmlElementName(string $name): string {
		return str_replace('\\', '-', $name);
	}

	function libxmlCallWrapper(callable $fn) {
		libxml_use_internal_errors(true);
		libxml_clear_errors();
		$result = $fn();
		$errors = libxml_get_errors();
		libxml_clear_errors();
		if(count($errors) > 0 || !$result) {
			throw new LibxmlException(implode("\n", array_map(fn($e) => $e->message, $errors)) ?: 'libxml calling failed.');
		}
		return $result;
	}
}
