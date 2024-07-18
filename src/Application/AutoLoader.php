<?php

namespace WebImage\Application;

class AutoLoader
{
	private $map = [];
	public function map(string $namespace, string $path)
	{
		$this->map[$namespace] = $path;
	}

	public function register()
	{
		spl_autoload_register([$this, 'load']);
	}

	public function load(string $class)
	{
		$matchedFile = null;
		$matchLen = 0;

		// Search through mapped namespace for the class file
		foreach($this->map as $namespace => $path) {
			$len = strlen($namespace);
			if (substr($class, 0, $len) == $namespace) {
				$fullPath = rtrim($path, '/\\') . '/' . str_replace('\\', '/', substr($class, $len+1)) . '.php';
				if (file_exists($fullPath) && $len > $matchLen) {
					$matchedFile = $fullPath;
					$matchLen = $len;
				}
			}
		}

		if ($matchedFile !== null) {
			require $matchedFile;
		}
	}
}