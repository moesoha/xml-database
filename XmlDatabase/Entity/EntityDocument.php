<?php


namespace SohaJin\Toys\XmlDatabase\Entity;

use \DOMDocument, \DOMElement, \DOMNodeList, \DOMXPath;
use SohaJin\Toys\XmlDatabase\Helpers;
use SohaJin\Toys\XmlDatabase\QueryBuilder;
use SohaJin\Toys\XmlDatabase\XmlSchemaDefinition\DataType;

class EntityDocument {
	private EntityReflection $reflection;

	/**
	 * @param string|EntityReflection $entity
	 */
	public function __construct(mixed $entity) {
		if (is_string($entity)) {
			$this->reflection = new EntityReflection($entity);
		} else if ($entity instanceof EntityReflection) {
			$this->reflection = $entity;
		} else {
			throw new \InvalidArgumentException("should be class qualified name or EntityReflection instance.");
		}
	}

	public function getClassName(): string {
		return $this->reflection->getClass()->getName();
	}

	public function getPrimaryKeyFields(): array {
		return array_map(fn($f) => $f->getName(), $this->reflection->getPrimaryKeyFields());
	}

	public function getPrimaryKeyValues(object $o): array {
		return array_map(fn($f) => $f->getValue($o), $this->reflection->getPrimaryKeyFields());
	}

	public function generateXmlSchema(): string {
		$entityXmlTag = Helpers::convertPhpQualifiedNameToXmlElementName($this->getClassName());
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->appendChild($root = $dom->createElementNS('http://www.w3.org/2001/XMLSchema', 'xs:schema')); // xsd
		// root element
		$root->appendChild($docRoot = $root = $dom->createElement('xs:element'));
		$root->setAttribute('name', Helpers::convertPhpQualifiedNameToXmlElementName(self::class));
		$root->appendChild($root = $dom->createElement('xs:complexType'));
		$root->appendChild($root = $dom->createElement('xs:sequence'));
		// entity row
		$root->appendChild($root = $dom->createElement('xs:element'));
		$root->setAttribute('name', $entityXmlTag);
		$root->setAttribute('minOccurs', 0);
		$root->setAttribute('maxOccurs', 'unbounded');
		$root->appendChild($root = $dom->createElement('xs:complexType'));
		// fields
		$root->appendChild($root = $dom->createElement('xs:all'));
		foreach($this->reflection->getFields() as $property) {
			$root->appendChild($o = $dom->createElement('xs:element'));
			$o->setAttribute('name', $property->getName());
			$o->setAttribute('type', DataType::convertFromPhpType($property->getType()));
			if (substr((string)$property->getType(), 0, 1) === '?') {
				$o->setAttribute('minOccurs', 0);
			}
		}
		// primary key
		$docRoot->appendChild($root = $dom->createElement('xs:unique'));
		$root->setAttribute('name', 'PrimaryKey');
		$root->appendChild($o = $dom->createElement('xs:selector'));
		$o->setAttribute('xpath', $entityXmlTag);
		foreach($this->getPrimaryKeyFields() as $field) {
			$root->appendChild($o = $dom->createElement('xs:field'));
			$o->setAttribute('xpath', $field);
		}

		return $dom->saveXML();
	}

	public function generateXmlElement(object $object, DOMDocument $dom): DOMElement {
		$el = $dom->createElement(Helpers::convertPhpQualifiedNameToXmlElementName($this->getClassName()));
		foreach($this->reflection->getFields() as $property) {
			$property->setAccessible(true);
			if (!$property->isInitialized($object)) {
				continue;
			}
			$el->appendChild($dom->createElement($property->getName(), $property->getValue($object)));
		}
		return $el;
	}

	public function replaceOrInsertDataRow(object $object, DOMDocument $dom) {
		$xpath = (new QueryBuilder($object::class))->findByPrimaryKey(...$this->getPrimaryKeyValues($object))->getXPath();
		$nodeList = (new DOMXPath($dom))->query($xpath);
		if ($nodeList && $nodeList->count() > 0) {
			$nodes = iterator_to_array($nodeList);
			foreach($nodes as $node) $node->parentNode->removeChild($node);
		}
		$dom->getElementsByTagName(Helpers::convertPhpQualifiedNameToXmlElementName(self::class))
			->item(0)
			->appendChild($this->generateXmlElement($object, $dom));
	}

	public function deleteDataRow(object $object, DOMDocument $dom) {
		$xpath = (new QueryBuilder($object::class))->findByPrimaryKey(...$this->getPrimaryKeyValues($object))->getXPath();
		$nodeList = (new DOMXPath($dom))->query($xpath);
		if ($nodeList && $nodeList->count() > 0) {
			foreach($nodeList as $node) $dom->removeChild($node);
		}
	}

	public function parseXmlElement(DOMElement $el): object {
		$object = $this->reflection->getClass()->newInstanceWithoutConstructor();
		if ($el->nodeName !== Helpers::convertPhpQualifiedNameToXmlElementName($this->getClassName())) {
			throw new \InvalidArgumentException("Unexpected node name {$el->nodeName}");
		}
		foreach($this->reflection->getFields() as $property) {
			$e = $el->getElementsByTagName($property->getName());
			$elCount = $e->count();
			if ($elCount > 1) {
				throw new \RuntimeException('Multiple '.$property->getName().' found');
			} else {
				$type = (string)$property->getType();
				if ($elCount === 0 && (substr($type, 0, 1) !== '?')) {
					continue;
				}
				$property->setAccessible(true);
				$value = null;
				if ($elCount === 1) {
					$value = $e->item(0)->nodeValue;
					settype($value, ltrim($type, '?'));
				}
				$property->setValue($object, $value);
			}
		}
		return $object;
	}

	public function generateXmlDocument(array $objects): DOMDocument {
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->appendChild($root = $dom->createElement(Helpers::convertPhpQualifiedNameToXmlElementName(self::class)));
		foreach($objects as $object) {
			$root->appendChild($this->generateXmlElement($object, $dom));
		}
		return $dom;
	}

	public function parseXmlDocument(DOMDocument $dom): array {
		$root = $dom->firstChild;
		if ($root?->nodeName !== Helpers::convertPhpQualifiedNameToXmlElementName(self::class)) {
			throw new \InvalidArgumentException("Unexpected tag name {$root->nodeName}");
		}
		return $this->parseXmlNodeList($root->childNodes);
	}

	public function parseXmlNodeList(DOMNodeList $nodes): array {
		$objects = [];
		foreach($nodes as $node) {
			$objects[] = $this->parseXmlElement($node);
		}
		return $objects;
	}

	public function validateXmlDocument(DOMDocument $dom): bool {
		libxml_use_internal_errors(true);
		libxml_clear_errors();
		$result = $dom->schemaValidateSource($this->generateXmlSchema());
		$errors = libxml_get_errors();
		libxml_clear_errors();
		if(count($errors) > 0) {
			throw new \RuntimeException(implode("\n", array_map(fn($e) => $e->message, $errors)));
		}
		return $result;
	}

	public function isEntityEqual(object $a, object $b) {
		if (
			$a::class !== $this->getClassName() ||
			$b::class !== $this->getClassName()
		) {
			throw new \InvalidArgumentException('Incorrect entity class to compare.');
		}
		$differentFields = array_filter($this->getPrimaryKeyFields(), fn($f) => !$this->reflection->isPropertyValueEqual($a, $b, $f));
		return count($differentFields) === 0;
	}
}
