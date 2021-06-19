<?php

namespace SohaJin\Toys\XmlDatabase {
	function phpClassNameToXmlElementName(string $name) {
		return str_replace('\\', '-', $name);
	}
}
