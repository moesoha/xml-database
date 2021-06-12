<?php

namespace SohaJin\Toys\XmlDatabase\Store;

interface StoreInterface {
	public function getContent(string $name, string $type): string;
	public function setContent(string $name, string $type, string $data);

	public function acquireReadLock(string $name, string $type): bool;
	public function releaseReadLock(string $name, string $type): bool;
	public function acquireWriteLock(string $name, string $type): bool;
	public function releaseWriteLock(string $name, string $type): bool;
}
