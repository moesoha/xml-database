<?php

namespace SohaJin\Course202001\XmlDatabaseProgram\Entity;

use SohaJin\Toys\XmlDatabase\Attribute\AutoIncrement;
use SohaJin\Toys\XmlDatabase\Attribute\Entity;
use SohaJin\Toys\XmlDatabase\Attribute\NotMapped;

#[Entity] class Task {
	#[AutoIncrement] private int $id;
	private string $name;
	private int $createTime;
	private bool $solved = false;
	private int $groupKey;
	#[NotMapped] private string $unmapped = 'yay!';

	public function __construct(?int $id = null) {
		if (is_numeric($id)) {
			$this->id = $id;
		}
		$this->createTime = time();
	}

	public function getId(): int {
		return $this->id;
	}

	public function getName(): string {
		return $this->name;
	}
	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getCreateTime() : ?int {
		return $this->createTime;
	}
	public function setCreateTime(int $time): self {
		$this->createTime = $time;
		return $this;
	}

	public function getUnmapped(): string {
		return $this->unmapped;
	}
	public function setUnmapped(string $unmapped): self {
		$this->unmapped = $unmapped;
		return $this;
	}

	public function getGroupKey(): int {
		return $this->groupKey;
	}
	public function setGroupKey(int $group): self {
		$this->groupKey = $group;
		return $this;
	}

	public function isSolved(): bool {
		return $this->solved;
	}
	public function setSolved(bool $solved): self {
		$this->solved = $solved;
		return $this;
	}
}
