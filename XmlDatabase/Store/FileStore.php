<?php


namespace SohaJin\Toys\XmlDatabase\Store;


class FileStore implements StoreInterface {
	private string $path;
	private string $filenamePrefix;

	public function __construct(string $path = __DIR__, string $filenamePrefix = '') {
		$this->path = $path;
		$this->filenamePrefix = $filenamePrefix;
	}

	private function getFullPath(string $path): string {
		return rtrim($this->path, '\\/') . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
	}

	private function getFilename(string $name, string $type): string {
		return $this->filenamePrefix.$name.'.'.$type;
	}

	public function getContent(string $name, string $type): string {
		if(file_exists($filename = $this->getFullPath($this->getFilename($name, $type)))) {
			$data = file_get_contents($filename);
			if(is_string($data)) {
				return $data;
			}
		}
		return '';
	}

	public function setContent(string $name, string $type, string $data) {
		$result = file_put_contents($this->getFullPath($this->getFilename($name, $type)), $data);
		if ($result === false) {
			throw new \RuntimeException('File IO error while writing data!');
		}
	}

	public function acquireReadLock(string $name, string $type): bool {
		// TODO: Implement acquireReadLock() method.
		return true;
	}

	public function releaseReadLock(string $name, string $type): bool {
		// TODO: Implement releaseReadLock() method.
		return true;
	}

	public function acquireWriteLock(string $name, string $type): bool {
		// TODO: Implement acquireWriteLock() method.
		return true;
	}

	public function releaseWriteLock(string $name, string $type): bool {
		// TODO: Implement releaseWriteLock() method.
		return true;
	}
}
