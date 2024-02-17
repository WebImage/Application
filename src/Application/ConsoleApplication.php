<?php

namespace WebImage\Application;

use League\Container\ContainerAwareInterface;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Symfony\Component\Console\Command\Command;
use WebImage\Config\Config;

class ConsoleApplication extends AbstractApplication
{
	const CONFIG_CONSOLE = 'console';
	const CONFIG_COMMANDS = 'commands';
	const CONFIG_DEFAULT_COMMAND = 'defaultCommand';
	const CONFIG_IS_SINGLE_COMMAND = 'isSingleCommand';

	public function run()
	{
		parent::run();

		$app = $this->createSymfonyConsoleApplication();
		$this->loadCommands($app);
		$this->setupDefaultCommand($app);
		$app->run();
	}

	protected function createSymfonyConsoleApplication(): SymfonyConsoleApplication
	{
		return new SymfonyConsoleApplication($this->getSymfonyApplicationName(), $this->getSymfonyApplicationVersion());
	}

	protected function getSymfonyApplicationName(): string
	{
		return 'UNDEFINED';
	}

	protected function getSymfonyApplicationVersion(): string
	{
		return 'UNDEFINED';
	}

	protected function loadCommands(SymfonyConsoleApplication $app)
	{
		$config   = $this->getConfig()->has(self::CONFIG_CONSOLE) ? $this->getConfig()->get(self::CONFIG_CONSOLE) : new Config();
		$commands = $config->get(self::CONFIG_COMMANDS, []);
		$sm       = $this->getServiceManager();

		foreach($commands as $commandName => $class) {

			/** @var Command $command */
			$command = $sm->has($class) ? $sm->get($class) : new $class();
			if (!is_numeric($commandName)) $command->setName($commandName);

			// Assign ContainerManager if the command supports it
			if ($command instanceof ContainerAwareInterface) $command->setContainer($this->getServiceManager());

			$app->add($command);
		}
	}

	/**
	 * Sets the default command
	 *
	 * @param SymfonyConsoleApplication $app
	 */
	protected function setupDefaultCommand(SymfonyConsoleApplication $app)
	{
		if (!$this->getConfig()->has(self::CONFIG_CONSOLE)) return;

		$config          = $this->getConfig()->get(self::CONFIG_CONSOLE);
		$defaultCommand  = $config->get(self::CONFIG_DEFAULT_COMMAND);
		$isSingleCommand = $config->get(self::CONFIG_IS_SINGLE_COMMAND, false);

		if ($defaultCommand === null) return;

		$command = $app->find($defaultCommand);
		$app->setDefaultCommand($command->getName(), $isSingleCommand);
	}
}
