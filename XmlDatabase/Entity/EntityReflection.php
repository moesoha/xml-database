<?php

namespace SohaJin\Toys\XmlDatabase\Entity;

use \ReflectionClass, \ReflectionProperty, \ReflectionException;
use SohaJin\Toys\XmlDatabase\Attribute\AutoIncrement;
use SohaJin\Toys\XmlDatabase\Attribute\Entity;
use SohaJin\Toys\XmlDatabase\Attribute\NotMapped;
use SohaJin\Toys\XmlDatabase\Attribute\PrimaryKey;

class EntityReflection {
	private ReflectionClass $class;

	public function __construct(string $classPath) {
		$this->class = new ReflectionClass($classPath);
		if (count($this->class->getAttributes(Entity::class)) < 1) {
			throw new \RuntimeException($classPath.' is not an Entity');
		}
	}

	/**
	 * @return ReflectionProperty[]
	 */
	public function getFields(): array {
		return array_filter(
			$this->class->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC),
			fn (ReflectionProperty $property) => count($property->getAttributes(NotMapped::class)) < 1
		);
	}

	public function hasField(string $name): bool {
		return $this->getField($name) !== null;
	}

	public function getField(string $name): ?ReflectionProperty {
		try {
			$property = $this->class->getProperty($name);
			if (
				($property->isPrivate() || $property->isPublic()) &&
				count($property->getAttributes(NotMapped::class)) < 1
			) return $property;
		} catch (ReflectionException) {}
		return null;
	}

	/**
	 * @return ReflectionProperty[]
	 */
	public function getPrimaryKeyFields(): array {
		$fields = $this->getFields();
		$pkField = array_filter($fields, fn($f) => count($f->getAttributes(PrimaryKey::class)) > 0);
		if (count($pkField) < 1) {
			$pkField = array_filter($fields, fn($f) => strtolower($f->getName()) === 'id');
		}
		if (count($pkField) < 1) {
			throw new \RuntimeException('Entity '.$this->getClass()->getName().' doesn\'t have any primary key field.');
		}
		array_map(fn($f) => $f->setAccessible(true), $pkField);
		return $pkField;
	}

	/**
	 * @return ReflectionProperty[]
	 */
	public function getAutoIncrementFields(): array {
		$fields = $this->getFields();
		$pkField = array_filter($fields, fn($f) => count($f->getAttributes(AutoIncrement::class)) > 0);
		array_map(fn($f) => $f->setAccessible(true), $pkField);
		return $pkField;
	}

	public function getPropertyValue(object $object, string $field): mixed {
		$p = $this->class->getProperty($field);
		$p->setAccessible(true);
		return $p->getValue($object);
	}

	public function isPropertyValueEqual(object $a, object $b, string $field): mixed {
		if ($a::class !== $this->class->getName() || $b::class !== $this->class->getName()) {
			throw new \InvalidArgumentException("Object should be instance of ".$this->class->getName());
		}
		$property = $this->class->getProperty($field);
		$property->setAccessible(true);
		if (!$property->isInitialized($a) || !$property->isInitialized($b)) {
			return false;
		}
		return $property->getValue($a) === $property->getValue($b);
	}

	public function getClass(): ReflectionClass {
		return $this->class;
	}
}
