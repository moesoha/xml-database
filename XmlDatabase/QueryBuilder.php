<?php

namespace SohaJin\Toys\XmlDatabase;

use SohaJin\Toys\XmlDatabase\Entity\EntityDocument;
use SohaJin\Toys\XmlDatabase\Entity\EntityManager;
use SohaJin\Toys\XmlDatabase\Entity\EntityReflection;
use SohaJin\Toys\XmlDatabase\Exception\NonSingleResultException;
use SohaJin\Toys\XmlDatabase\Expression\XPathOperand;
use function SohaJin\Toys\XmlDatabase\Expression\forceOperand;

class QueryBuilder {
	private EntityReflection $reflection;
	private string $rootXPath;
	private ?EntityManager $entityManager;

	/**
	 * @var array<int, array<int, string>>
	 */
	private array $orSegments = [];

	public function __construct(string $className) {
		$this->reflection = new EntityReflection($className);
		$this->rootXPath =
			'/'.phpClassNameToXmlElementName(EntityDocument::class).
			'/'.phpClassNameToXmlElementName($className)
		;
	}

	/** @internal */
	public function setEntityManager(EntityManager $entityManager): self {
		$this->entityManager = $entityManager;
		return $this;
	}

	public function findByPrimaryKey(): self {
		$fieldCount = count($fields = $this->reflection->getPrimaryKeyFields());
		$argsCount = func_num_args();
		if ($fieldCount !== $argsCount) {
			throw new \InvalidArgumentException("Entity ".$this->reflection->getClass()->getName(). " has $fieldCount primary key field(s), you passed $argsCount.");
		}
		$this->orSegments = ['['.implode(' and ', array_map(fn($f, $v) => './'.$f->getName()."='".htmlentities($v, ENT_QUOTES)."'", $fields, func_get_args())).']'];
		return $this;
	}

	public function where($operand): self {
		$this->orSegments = [forceOperand($operand)];
		return $this;
	}

	public function andWhere($operand): self {
		$operand = forceOperand($operand);
		if (count($this->orSegments) < 1) $this->orSegments[] = '';
		$this->orSegments = array_map(fn($s) => $s."[$operand]", $this->orSegments);
		return $this;
	}

	public function orWhere($operand): self {
		$operand = forceOperand($operand);
		$this->orSegments[] = "[$operand]";
		return $this;
	}

	public function fieldOp(string $fieldName): XPathOperand {
		if ($this->reflection->hasField($fieldName)) {
			return new XPathOperand("./$fieldName");
		}
		throw new \InvalidArgumentException("$fieldName is not a field of {$this->reflection->getClass()->getName()}");
	}

	public function getXPath(): string {
		return implode(' | ', array_map(fn ($s) => $this->rootXPath.$s, $this->orSegments));
	}

	public function getResult(): array {
		if (!$this->entityManager) {
			throw new \RuntimeException('EntityManager is not ready on this QueryBuilder.');
		}
		return $this->entityManager->query($this->reflection->getClass()->getName(), $this->getXPath());
	}

	public function getSingleResult(): ?object {
		$results = $this->getResult();
		if (count($results) > 1) {
			throw new NonSingleResultException();
		}
		return $results[0] ?? null;
	}
}
