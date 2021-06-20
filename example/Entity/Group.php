<?php

namespace SohaJin\Course202001\XmlDatabaseProgram\Entity;

use SohaJin\Toys\XmlDatabase\Attribute\AutoIncrement;
use SohaJin\Toys\XmlDatabase\Attribute\Entity;
use SohaJin\Toys\XmlDatabase\Attribute\PrimaryKey;

#[Entity] class Group {
	#[PrimaryKey, AutoIncrement] private int $key;
	private string $name;
	private ?int $defaultTime;

	public function getKey() : int {
		return $this->key;
	}

	public function getName(): string {
		return $this->name;
	}
	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getDefaultTime(): ?int {
		return $this->defaultTime;
	}
	public function setDefaultTime(int $defaultTime): self {
		$this->defaultTime = $defaultTime;
		return $this;
	}
}
