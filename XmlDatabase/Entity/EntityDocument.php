<?php


namespace SohaJin\Toys\XmlDatabase\Entity;

use \DOMDocument, \DOMElement, \DOMNodeList, \DOMXPath;
use SohaJin\Toys\XmlDatabase\Attribute\AutoIncrement;
use SohaJin\Toys\XmlDatabase\QueryBuilder;
use SohaJin\Toys\XmlDatabase\XmlSchemaDefinition\DataType;
use function SohaJin\Toys\XmlDatabase\phpClassNameToXmlElementName;

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
		return array_map(fn($f) => $f->isInitialized($o) ? $f->getValue($o) : null, $this->reflection->getPrimaryKeyFields());
	}

	public function generateXmlSchema(): string {
		$entityXmlTag = phpClassNameToXmlElementName($this->getClassName());
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->appendChild($root = $dom->createElementNS('http://www.w3.org/2001/XMLSchema', 'xs:schema')); // xsd
		// root element
		$root->appendChild($docRoot = $root = $dom->createElement('xs:element'));
		$root->setAttribute('name', phpClassNameToXmlElementName(self::class));
		$root->appendChild($root = $dom->createElement('xs:complexType'));
		$root->appendChild($docRootSeq = $dom->createElement('xs:sequence'));

		// auto increment unique constraint
		$autoIncrementTag = phpClassNameToXmlElementName(AutoIncrement::class);
		$docRoot->appendChild($root = $dom->createElement('xs:unique'));
		$root->setAttribute('name', 'AutoIncrement');
		$root->appendChild($o = $dom->createElement('xs:selector'));
		$o->setAttribute('xpath', $autoIncrementTag);
		$root->appendChild($o = $dom->createElement('xs:field'));
		$o->setAttribute('xpath', '@entity');
		$root->appendChild($o = $dom->createElement('xs:field'));
		$o->setAttribute('xpath', '@field');
		// auto increment element
		$docRootSeq->appendChild($root = $dom->createElement('xs:element'));
		$root->setAttribute('name', $autoIncrementTag);
		$root->setAttribute('minOccurs', 0);
		$root->setAttribute('maxOccurs', 'unbounded');
		$root->appendChild($root = $dom->createElement('xs:complexType'));
		$root->appendChild($o = $dom->createElement('xs:attribute'));
		$o->setAttribute('name', 'entity');
		$o->setAttribute('type', 'xs:NCName');
		$root->appendChild($o = $dom->createElement('xs:attribute'));
		$o->setAttribute('name', 'field');
		$o->setAttribute('type', 'xs:string');
		$root->appendChild($o = $dom->createElement('xs:attribute'));
		$o->setAttribute('name', 'value');
		$o->setAttribute('type', 'xs:integer');

		// entity row
		$docRootSeq->appendChild($root = $dom->createElement('xs:element'));
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
		$docRoot->appendChild($root = $dom->createElement('xs:key'));
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
		$el = $dom->createElement(phpClassNameToXmlElementName($this->getClassName()));
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
		$pkValues = $this->getPrimaryKeyValues($object);
		if (!in_array(null, $pkValues)) { // null primary key means not initialized, skip
			$xpath = (new QueryBuilder($object::class))->findByPrimaryKey(...$pkValues)->getXPath();
			$nodeList = (new DOMXPath($dom))->query($xpath);
			if ($nodeList && $nodeList->count() > 0) {
				$nodes = iterator_to_array($nodeList);
				foreach($nodes as $node) $node->parentNode->removeChild($node);
			}
		}
		$autoIncrementFields = $this->reflection->getAutoIncrementFields();
		foreach($autoIncrementFields as $field) {
			if ($field->isInitialized($object)) continue;
			$field->setValue($object, $this->nextAutoIncrementValue($field->getName(), $dom));
		}
		$dom->firstElementChild->appendChild($this->generateXmlElement($object, $dom));
	}

	public function nextAutoIncrementValue(string $fieldName, DOMDocument $dom, int $valueOverride = 0): int {
		if (count($this->reflection->getField($fieldName)?->getAttributes(AutoIncrement::class) ?? []) < 1) {
			return 0;
		}
		$xpath =
			'/'.phpClassNameToXmlElementName(self::class).
			'/'.phpClassNameToXmlElementName(AutoIncrement::class).
			'[@entity="'.phpClassNameToXmlElementName($this->getClassName()).'"]'.
			'[@field="'.$fieldName.'"]'.
			'/@value'
		;
		$aiNodes = (new DOMXPath($dom))->query($xpath);
		if ($aiNodes && $aiNodes->count() > 0) {
			$o = $aiNodes->item(0);
			$value = $valueOverride ?: (((int)$o->nodeValue) + 1);
			$o->nodeValue = (string)($value);
			return $value;
		}
		$root = $dom->firstElementChild;
		$root->insertBefore($el = $dom->createElement(phpClassNameToXmlElementName(AutoIncrement::class)), $root->firstChild);
		$el->setAttribute('entity', phpClassNameToXmlElementName($this->getClassName()));
		$el->setAttribute('field', $fieldName);
		$el->setAttribute('value', $valueOverride ?: 1);
		return $valueOverride ?: 1;
	}

	public function deleteDataRow(object $object, DOMDocument $dom) {
		$xpath = (new QueryBuilder($object::class))->findByPrimaryKey(...$this->getPrimaryKeyValues($object))->getXPath();
		$nodeList = (new DOMXPath($dom))->query($xpath);
		if ($nodeList && $nodeList->count() > 0) {
			foreach($nodeList as $node) $node->parentNode->removeChild($node);
		}
	}

	public function parseXmlElement(DOMElement $el): object {
		$object = $this->reflection->getClass()->newInstanceWithoutConstructor();
		if ($el->nodeName !== phpClassNameToXmlElementName($this->getClassName())) {
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
		$dom->appendChild($root = $dom->createElement(phpClassNameToXmlElementName(self::class)));
		foreach($objects as $object) {
			$root->appendChild($this->generateXmlElement($object, $dom));
		}
		return $dom;
	}

	public function parseXmlDocument(DOMDocument $dom): array {
		$root = $dom->firstChild;
		if ($root?->nodeName !== phpClassNameToXmlElementName(self::class)) {
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
