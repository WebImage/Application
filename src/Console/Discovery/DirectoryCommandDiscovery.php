<?php

namespace WebImage\Console\Discovery;
/**
 * Discovers commands from a directory
 */
class DirectoryCommandDiscovery implements CommandDiscoveryInterface
{
	private string $directory;
	private string $namespace;
	private ?string $suffix;

	public function __construct(string $directory, string $namespace, string $suffix = 'Command')
	{
		$this->directory = rtrim($directory, '/\\');
		$this->namespace = rtrim($namespace, '\\');
		$this->suffix = $suffix;
	}

	public function discover(): array
	{
		$commands = [];

		if (!is_dir($this->directory)) {
			return $commands;
		}

		$files = glob($this->directory . '/*' . ($this->suffix ? $this->suffix : '') . '.php');

		foreach ($files as $file) {
			$className = $this->getClassNameFromFile($file);
			$fullClassName = $this->namespace . '\\' . $className;

			if (class_exists($fullClassName)) {
				$reflection = new \ReflectionClass($fullClassName);

				if (!$reflection->isAbstract() &&
					$reflection->implementsInterface(\WebImage\Commands\CommandInterface::class)) {
					$commands[] = new $fullClassName();
				}
			}
		}

		return $commands;
	}

	private function getClassNameFromFile(string $file): string
	{
		return basename($file, '.php');
	}
}