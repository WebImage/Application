<?php

namespace WebImage\Application;

use Exception;
use WebImage\Commands\RoutesCompileCommand;
use WebImage\Commands\ServeCommand;
use WebImage\Config\Config;
use WebImage\Console\Console;
use WebImage\Console\Discovery\CompositeCommandDiscovery;
use WebImage\Console\Discovery\ConfigCommandDiscovery;
use WebImage\Console\Discovery\DirectoryCommandDiscovery;
use WebImage\Core\ArrayHelper;
use WebImage\Core\Version;

abstract class AbstractConsoleApplication extends AbstractApplication
{
	const CONFIG_APP_NAME = 'applicationName';
	const CONFIG_APP_VERSION = 'applicationVersion';

	/**
	 * @throws Exception
	 */
	public function run(): int
	{
		$console = new Console($this->getName(), $this->getVersion());

		// Set container if available
		if ($this->getServiceManager()) {
			$console->setContainer($this->getServiceManager());
		}

		$this->setupConsole($console);

		return $console->run();
	}

	/**
	 * Name to assign to the Console on creation (@see self::run())
	 * @return string
	 */
	protected function getName(): string
	{
		return $this->getConfig()->get(self::CONFIG_APP_NAME) ?? 'Console';
	}

	/**
	 * Version to assign to the Console on creation (@see self::run())
	 * @return string
	 */
	protected function getVersion(): string
	{
		return Version::createFromString($this->getConfig()->get(self::CONFIG_APP_VERSION) ?? '1.0.0');
	}

	protected static function getDefaultConfig(): array
	{
		return ArrayHelper::merge(
			parent::getDefaultConfig(),
			[
				ConsoleApplication::CONFIG_CONSOLE => [
					ConsoleApplication::CONFIG_COMMANDS => [
						ServeCommand::class,
                        RoutesCompileCommand::class
					]
				]
			]
		);
	}


	protected function setupConsole(Console $console): void
	{
		$this->setupCommandDiscovery($console);
		$this->setupDefaultCommand($console);
	}
	protected function setupCommandDiscovery(Console $console): void {}

	protected function setupDefaultCommand(Console $console): void {}
}