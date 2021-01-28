<?php

namespace WebImage\View;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

class EngineResolver implements ContainerAwareInterface {
	use ContainerAwareTrait;

	protected $resolvers = [];
	protected $resolved = [];

	public function register($engine, $resolver)
	{
		unset($this->resolved[$engine]);

		$this->resolvers[$engine] = $resolver;
	}

	public function resolve($engine)
	{
		if (isset($this->resolved[$engine])) {
			return $this->resolved[$engine];
		}

		if (isset($this->resolvers[$engine])) {

			$resolved = $this->resolvers[$engine];

			$resolved = is_string($resolved) ? $this->getContainer()->get($resolved) : $resolved;

			return $this->resolved[$engine] = $resolved;
		}

		throw new \InvalidArgumentException('Engine not found: ' . $engine);
	}
}