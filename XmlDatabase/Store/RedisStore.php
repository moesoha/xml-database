<?php

namespace SohaJin\Toys\XmlDatabase\Store;

use Predis\Client as PredisClient;

class RedisStore implements StoreInterface {
	private PredisClient $client;
	private string $keyPrefix;

	public function __construct(mixed $url = null, string $keyPrefix = '', mixed $redisOptions = null) {
		$this->client = new PredisClient($url, $redisOptions);
		$this->keyPrefix = $keyPrefix;
	}

	private function getRedisKey(string $name): string {
		return $this->keyPrefix.$name;
	}

	public function getContent(string $name) : string {
		return $this->client->get($this->getRedisKey($name)) ?? '';
	}

	public function setContent(string $name, string $data) {
		return $this->client->set($this->getRedisKey($name), $data);
	}
}
