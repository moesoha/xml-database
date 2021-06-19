<?php

namespace SohaJin\Toys\XmlDatabase\Entity;

use \DOMXPath;
use SohaJin\Toys\XmlDatabase\QueryBuilder;
use SohaJin\Toys\XmlDatabase\XmlDatabase;

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

	public function __construct(XmlDatabase $database) {
		$this->database = $database;
	}

	private function assertClassName(string $className): EntityDocument {
		if (!($d = $this->database->getEntityDocument($className))) {
			throw new \InvalidArgumentException("$className is not registered as an entity.");
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
			foreach($list['update'] as $entity) $document->replaceOrInsertDataRow($entity, $dom);
			foreach($list['delete'] as $entity) $document->deleteDataRow($entity, $dom);
			$this->database->saveXmlDocument($className, $dom);
		}
		$this->updateBuffer = [];
		$this->deleteBuffer = [];
	}

	public function query(string $className, string $xpath) {
		$document = $this->assertClassName($className);
		$dom = $this->database->loadXmlDocument($className);
		$nodes = (new DOMXPath($dom))->query($xpath);
		if (!$nodes || $nodes->count() < 1) {
			return [];
		}
		return $document->parseXmlNodeList($nodes);
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
