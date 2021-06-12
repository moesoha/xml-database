<?php

namespace SohaJin\Toys\XmlDatabase;

use \DOMDocument;
use SohaJin\Toys\XmlDatabase\Entity\EntityDocument;
use SohaJin\Toys\XmlDatabase\Entity\EntityManager;
use SohaJin\Toys\XmlDatabase\Store\{StoreInterface, FileStore, StoreType};

class XmlDatabase {
	private string $databaseName;
	private StoreInterface $store;
	private EntityManager $entityManager;

	/**
	 * @var array<string, EntityDocument>
	 */
	private array $documents = [];

	public function __construct(string $dbName, ?StoreInterface $store) {
        $this->databaseName = $dbName;
        $this->store = $store ?: new FileStore();
        $this->entityManager = new EntityManager($this);
	}

	public function getEntityManager(): EntityManager {
		return $this->entityManager;
	}

	public function addEntityClass(string $className): self {
		$document = new EntityDocument($className);
		$this->documents[$document->getClassName()] = $document;
		return $this;
	}

	public function getEntityDocument(string $className): ?EntityDocument {
		return $this->documents[$className] ?? null;
	}

	private function storeGetContent(string $name, string $type) {
		$filename = "{$this->databaseName}+$name";
		$this->store->acquireReadLock($filename, $type);
		$content = $this->store->getContent($filename, $type);
		$this->store->releaseReadLock($filename, $type);
		return $content;
	}

	private function storeSetContent(string $name, string $type, string $content) {
		$filename = "{$this->databaseName}+$name";
		$this->store->acquireWriteLock($filename, $type);
		$this->store->setContent($filename, $type, $content);
		$this->store->releaseWriteLock($filename, $type);
	}

	public function loadXmlDocument(string $name): DOMDocument {
		if (!($document = $this->getEntityDocument($name))) {
			throw new \InvalidArgumentException("$name is not registered as an entity.");
		}
		if (!empty($s = $this->storeGetContent(Helpers::convertPhpQualifiedNameToXmlElementName($name), StoreType::XML))) {
			$dom = new DOMDocument();
			$dom->loadXML($s);
		} else {
			$dom = $document->generateXmlDocument([]);
		}
		$document->validateXmlDocument($dom);
		return $dom;
	}

	public function saveXmlDocument(string $name, DOMDocument $dom) {
		if (!($document = $this->getEntityDocument($name))) {
			throw new \InvalidArgumentException("$name is not registered as an entity.");
		}
		$document->validateXmlDocument($dom);
		$this->storeSetContent(Helpers::convertPhpQualifiedNameToXmlElementName($name), StoreType::XML, $dom->saveXML());
	}
}
