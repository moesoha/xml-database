<?php

namespace SohaJin\Toys\XmlDatabase\Entity;

use \DOMXPath, \DOMNodeList;
use SohaJin\Toys\XmlDatabase\QueryBuilder;
use SohaJin\Toys\XmlDatabase\XmlDatabase;
use function SohaJin\Toys\XmlDatabase\libxmlCallWrapper;

class EntityManager {
	private XmlDatabase $database;

	/**
	 * @var array<int, object>
	 */
	private array $updateBuffer = [];

	/**
	 * @var array<int, object>
	 */
	private array $deleteBuffer = [];

	/**
	 * @var array<string, array<string, int>>
	 */
	private array $autoIncrementOverride = [];

	public function __construct(XmlDatabase $database) {
		$this->database = $database;
	}

	private function assertClassName(string $className): EntityDocument {
		if (!($d = $this->database->getEntityDocument($className))) {
			throw new \InvalidArgumentException("$className is not registered as an entity.");
		}
		if (!isset($this->autoIncrementOverride[$className])) {
			$this->autoIncrementOverride[$className] = [];
		}
		return $d;
	}

	public function delete(object $entity) {
		$this->assertClassName($entity::class);
		if (!$this->inBuffer($entity, $this->deleteBuffer)) {
			$this->deleteBuffer[] = $entity;
		}
		$this->removeFromBuffer($entity, $this->updateBuffer);
	}

	public function update(object $entity) {
		$this->assertClassName($entity::class);
		$this->removeFromBuffer($entity, $this->updateBuffer);
		$this->updateBuffer[] = $entity;
		$this->removeFromBuffer($entity, $this->deleteBuffer);
	}

	public function setAutoIncrementValue(string $class, string $field, int $value) {
		$document = $this->assertClassName($class);
		$this->autoIncrementOverride[$class][$field] = $value;
	}

	public function persist() {
		$pendingEntitiesByClass = [];
		$makeSureKey = function ($key) use (&$pendingEntitiesByClass) {
			if (!isset($pendingEntitiesByClass[$key])) {
				$pendingEntitiesByClass[$key] = [
					'update' => [],
					'delete' => []
				];
			}
		};
		foreach($this->updateBuffer as $entity) {
			$makeSureKey($entity::class);
			$pendingEntitiesByClass[$entity::class]['update'][] = $entity;
		}
		foreach($this->deleteBuffer as $entity) {
			$makeSureKey($entity::class);
			$pendingEntitiesByClass[$entity::class]['delete'][] = $entity;
		}
		foreach($pendingEntitiesByClass as $className => $list) {
			$dom = $this->database->loadXmlDocument($className);
			$document = $this->database->getEntityDocument($className);
			foreach(($this->autoIncrementOverride[$className] ?? []) as $field => $value) $document->nextAutoIncrementValue($field, $dom, $value);
			foreach($list['update'] as $entity) $document->replaceOrInsertDataRow($entity, $dom);
			foreach($list['delete'] as $entity) $document->deleteDataRow($entity, $dom);
			$this->database->saveXmlDocument($className, $dom);
		}
		$this->updateBuffer = [];
		$this->deleteBuffer = [];
		$this->autoIncrementOverride = [];
	}

	public function query(string $className, string $xpath): DOMNodeList {
		$this->assertClassName($className);
		$dom = $this->database->loadXmlDocument($className);
		return libxmlCallWrapper(fn() => (new DOMXPath($dom))->query($xpath));
	}

	public function queryThenCollect(string $className, string $xpath) {
		return $this->assertClassName($className)->parseXmlNodeList($this->query($className, $xpath));
	}

	public function createQueryBuilder(string $className) {
		$this->assertClassName($className);
		return (new QueryBuilder($className))->setEntityManager($this);
	}

	private function isEntityEqual(object $a, object $b): bool {
		return $a::class === $b::class && $this->database->getEntityDocument($a::class)?->isEntityEqual($a, $b);
	}

	private function inBuffer(object $needle, array $haystack): bool {
		return count(array_filter($haystack, fn($o) => $this->isEntityEqual($o, $needle))) > 0;
	}

	private function removeFromBuffer(object $needle, array $haystack) {
		foreach($haystack as $key => $current) {
			if ($this->isEntityEqual($current, $needle)) {
				unset($haystack[$key]);
				return;
			}
		}
	}
}
