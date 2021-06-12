<?php

namespace SohaJin\Toys\XmlDatabase\Store;

interface StoreInterface {
	public function getContent(string $name): string;
	public function setContent(string $name, string $data);
}
