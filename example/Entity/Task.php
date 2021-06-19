<?php

namespace SohaJin\Course202001\XmlDatabaseProgram\Entity;

use SohaJin\Toys\XmlDatabase\Attribute\AutoIncrement;
use SohaJin\Toys\XmlDatabase\Attribute\Entity;
use SohaJin\Toys\XmlDatabase\Attribute\NotMapped;
use SohaJin\Toys\XmlDatabase\Attribute\PrimaryKey;

#[Entity] class Task {
	#[PrimaryKey, AutoIncrement] private int $id;
	private string $name;
	private ?int $time;
	#[NotMapped] private string $unmapped = 'yay!';

	public function __construct(?int $id = null) {
		if (is_numeric($id)) {
			$this->id = $id;
		}
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

	public function getTime() : ?int {
		return $this->time;
	}
	public function setTime(?int $time): self {
		$this->time = $time;
		return $this;
	}

	public function getUnmapped(): string {
		return $this->unmapped;
	}
	public function setUnmapped(string $unmapped): self {
		$this->unmapped = $unmapped;
		return $this;
	}
}
