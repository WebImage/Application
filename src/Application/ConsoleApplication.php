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
	
	public function run()
	{
		parent::run();

		$app = $this->createSymfonyConsoleApplication();
		$this->loadCommands($app);
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
		$config = $this->getConfig()->has(self::CONFIG_CONSOLE) ? $this->getConfig()->get(self::CONFIG_CONSOLE) : new Config();
		$commands =$config->get(self::CONFIG_COMMANDS, []);
		$sm = $this->getServiceManager();

		foreach($commands as $commandName => $class) {

			/** @var Command $command */
			$command = $sm->has($class) ? $sm->get($class) : new $class();
			$command->setName($commandName);

			// Assign ContainerManager if the command supports it
			if ($command instanceof ContainerAwareInterface) $command->setContainer($this->getServiceManager());

			$app->add($command);
		}
	}	
}