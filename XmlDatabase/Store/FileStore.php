<?php

namespace SohaJin\Toys\XmlDatabase\Store;

use SohaJin\Toys\XmlDatabase\Exception\AcquiringLockException;

class FileStore implements StoreInterface {
	private string $path;
	private string $filenamePrefix;

	public function __construct(string $path = __DIR__, string $filenamePrefix = '') {
		$this->path = $path;
		$this->filenamePrefix = $filenamePrefix;
	}

	private function getFullPath(string $name): string {
		return rtrim($this->path, '\\/').DIRECTORY_SEPARATOR.$this->filenamePrefix.$name;
	}

	public function getContent(string $name): string {
		if(!file_exists($filename = $this->getFullPath($name))) {
			return '';
		}
		$fp = fopen($filename, 'r');
		if(!flock($fp, LOCK_SH)) {
			fclose($fp);
			throw new AcquiringLockException();
		}
		clearstatcache(true, $filename);
		$data = fread($fp, filesize($filename));
		flock($fp, LOCK_UN);
		fclose($fp);
		if($data === false) {
			return '';
		}
		return $data;
	}

	public function setContent(string $name, string $data) {
		$fp = fopen($this->getFullPath($name), 'w');
		if(!flock($fp, LOCK_EX)) {
			fclose($fp);
			throw new AcquiringLockException();
		}
		$result = fwrite($fp, $data);
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		if($result === false) {
			throw new \RuntimeException('File IO error while writing data!');
		}
	}
}
