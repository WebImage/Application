<?php

namespace WebImage\Application;

use Exception;
use League\Container\ContainerAwareInterface;
use WebImage\Config\Config;
use WebImage\Console\Console;
use WebImage\Console\Discovery\ConfigCommandDiscovery;
use WebImage\Console\Discovery\DirectoryCommandDiscovery;
use WebImage\Console\Discovery\CompositeCommandDiscovery;

class ConsoleApplication extends AbstractConsoleApplication
{
	const CONFIG_CONSOLE = 'console';
	const CONFIG_COMMANDS = 'commands';
	const CONFIG_DEFAULT_COMMAND = 'defaultCommand';
	const CONFIG_COMMAND_DIRECTORIES = 'commandDirectories';
	const CONFIG_COMMAND_NAMESPACE = 'commandNamespace';

	protected function setupCommandDiscovery(Console $console): void
	{
		$discoveries = new CompositeCommandDiscovery();

		// Add config-based discovery
		$this->addConfigDiscovery($discoveries);

		// Add directory-based discovery
		$this->addDirectoryDiscovery($discoveries);

		// Add all discovered
		foreach($discoveries->discover() as $command) {
			$console->addCommand($command);
		}
	}

	protected function addConfigDiscovery(CompositeCommandDiscovery $discoveries): void
	{
		$config = $this->getConfig()->has(self::CONFIG_CONSOLE)
			? $this->getConfig()->get(self::CONFIG_CONSOLE)
			: new Config();

		$commands = $config->get(self::CONFIG_COMMANDS, []);

		if (!empty($commands)) {
			if ($commands instanceof Config) $commands = $commands->toArray();

			$discoveries->addDiscovery(
				new ConfigCommandDiscovery($commands, $this->getServiceManager())
			);
		}
	}

	protected function addDirectoryDiscovery(CompositeCommandDiscovery $discoveries): void
	{
		$config = $this->getConfig()->has(self::CONFIG_CONSOLE)
			? $this->getConfig()->get(self::CONFIG_CONSOLE)
			: new Config();

		$directories = $config->get(self::CONFIG_COMMAND_DIRECTORIES, []);
		$namespace = $config->get(self::CONFIG_COMMAND_NAMESPACE, '');

		foreach ($directories as $directory) {
			if (is_dir($directory)) {
				$discoveries->addDiscovery(
					new DirectoryCommandDiscovery($directory, $namespace)
				);
			}
		}
	}

	protected function setupDefaultCommand(Console $console): void
	{
		if (!$this->getConfig()->has(self::CONFIG_CONSOLE)) {
			return;
		}

		$config = $this->getConfig()->get(self::CONFIG_CONSOLE);
		$defaultCommand = $config->get(self::CONFIG_DEFAULT_COMMAND);

		if ($defaultCommand === null) {
			return;
		}

		// Verify the default command exists
		if ($console->hasCommand($defaultCommand)) {
			$console->setDefaultCommand($defaultCommand);
		}
	}
}