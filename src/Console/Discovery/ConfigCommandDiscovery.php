<?php

namespace WebImage\Console\Discovery;
use WebImage\Commands\CommandDecorator;

/**
 * Discovers commands from a configuration array
 */
class ConfigCommandDiscovery implements CommandDiscoveryInterface
{
	private array $config;
	private \WebImage\ServiceManager\ServiceManagerInterface $serviceManager;

	public function __construct(array $config, ?\WebImage\ServiceManager\ServiceManagerInterface $serviceManager)
	{
		$this->config = $config;
		$this->serviceManager = $serviceManager;
	}

	public function discover(): array
	{
		$commands = [];

		foreach ($this->config as $commandName => $class) {
			if (!$class) throw new \InvalidArgumentException(sprintf('Command at %s should have a string value to indicate a corresponding class name or service key', $commandName));

				$command = $this->createCommand($class);

				// Allows name to be overwritten
				if (!is_numeric($commandName)) {
					$command = $command->withName($commandName);
				}

				$commands[] = $command;
		}

		return $commands;
	}

	private function createCommand(string $class): \WebImage\Commands\CommandInterface
	{
		if ($this->serviceManager->has($class)) {
			return $this->serviceManager->get($class);
		}

		if (!class_exists($class)) {
			throw new \InvalidArgumentException(sprintf('Class %s does not exist', $class));
		}

		return new $class();
	}
}