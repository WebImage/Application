<?php

namespace WebImage\Console;

use League\Container\ContainerAwareInterface;
use Psr\Container\ContainerInterface;
use WebImage\Commands\AliasProviderInterface;
use WebImage\Commands\CommandInterface;
use WebImage\Commands\ValidatableCommandInterface;

class Console
{
	/** @var CommandInterface[] */
	private array  $commands = [];
	private array  $aliases = [];
	private string $name;
	private string $version;
	private ConsoleInput  $input;
	private ConsoleOutput $output;
	private ?string $defaultCommand = null;
	private ?ContainerInterface $container = null;

	public function __construct(string $name = 'Console App', string $version = '1.0.0')
	{
		$this->name    = $name;
		$this->version = $version;
		$this->input   = new ConsoleInput();
		$this->output  = new ConsoleOutput();
	}

	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
	}

	public function addCommand(CommandInterface $command): void
	{
		$commandName = $command->getName();
		$this->commands[$commandName] = $command;

		// Handle aliases if supported
		if ($command instanceof AliasProviderInterface) {
			foreach ($command->getAliases() as $alias) {
				$this->aliases[$alias] = $commandName;
			}
		}

		// Inject container if command supports it
		if ($command instanceof ContainerAwareInterface && $this->container) {
			$command->setContainer($this->container);
		}
	}

	public function setDefaultCommand(string $commandName): void
	{
		$this->defaultCommand = $commandName;
	}

	public function run(array $argv = null): int
	{
		$this->prepareArguments($argv);

		$commandName = $this->input->getCommand();

		if (!$commandName) {
			if ($this->defaultCommand) {
				$commandName = $this->defaultCommand;
			} else {
				$this->showCommandList();
				return 0;
			}
		}

		if ($commandName == 'list') {
			$this->showCommandList();
			return 0;
		}

		if (!isset($this->commands[$commandName])) {
			$this->output->error("Command '{$commandName}' not found.");
			return 1;
		}

		$command = $this->commands[$commandName];

		// Handle help
		if ($this->input->hasOption('help') || $this->input->hasOption('h')) {
			return $this->showCommandHelp($command);
		}

		if ($command instanceof ValidatableCommandInterface) {
			$errors = $command->validate($this->input);
			if (!empty($errors)) {
				foreach($errors as $error) {
					$this->output->error($error);
				}
				return 1;
			}
		}

		try {
			return $command->execute($this->input, $this->output);
		} catch (\Exception $e) {
			$this->output->error('Error: ' . $e->getMessage());
			return 1;
		}
	}

	private function prepareArguments(array $args = null): void
	{
        global $argv;
        $argv = $args ?? ($_SERVER['argv'] ?? ($argv ?? []));

		if ($this->isSingleCommandMode()) {
			array_unshift($argv, $this->defaultCommand);
			die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
		}

		$this->input->parse($argv);
	}

	private function isSingleCommandMode(): bool
	{
		if (count($this->commands) === 1 && $this->defaultCommand !== null) {
			if (!isset($this->commands[$this->defaultCommand])) {
				throw new \InvalidArgumentException(
					"Default command '{$this->defaultCommand}' not found. Available commands: "
					. implode(', ', array_keys($this->commands))
				);
			}
			return true;
		}

		return false;
	}

//	private function runSingleCommand(array $argv): int
//	{
//		$command = $this->commands[$this->defaultCommand];
//
//		// Parse input directly for the single command (no command name parsing)
//		$this->parseCommandInputForSingleMode($command, $argv);
//
//		// Handle help
//		if ($this->input->hasOption('help') || $this->input->hasOption('h')) {
//			return $this->showCommandHelp($command);
//		}
//
//		// Validate input if command supports validation
//		if ($command instanceof ValidatableCommandInterface) {
//			$errors = $command->validate($this->input);
//			if (!empty($errors)) {
//				foreach ($errors as $error) {
//					$this->output->error($error);
//				}
//				return 1;
//			}
//		}
//
//		try {
//			return $command->execute($this->input, $this->output);
//		} catch (\Exception $e) {
//			$this->output->error('Error: ' . $e->getMessage());
//			return 1;
//		}
//	}

//	private function runMultiCommand(array $argv): int
//	{
//		$this->input->parse($argv);
//
//		$commandName = $this->input->getCommand();
//
//		// Use default command if no command provided
//		if (!$commandName) {
//			if ($this->defaultCommand) {
//				$commandName = $this->defaultCommand;
//			} else {
//				$this->showCommandList();
//				return 0;
//			}
//		}
//
//		// Handle 'list' command
//		if ($commandName === 'list') {
//			$this->showCommandList();
//			return 0;
//		}
//
//		// Resolve aliases
//		$commandName = $this->aliases[$commandName] ?? $commandName;
//
//		if (!isset($this->commands[$commandName])) {
//			$this->output->error("Command '{$commandName}' not found.");
//			return 1;
//		}
//
//		$command = $this->commands[$commandName];
//
//		// Handle help
//		if ($this->input->hasOption('help') || $this->input->hasOption('h')) {
//			return $this->showCommandHelp($command);
//		}
//
//		// Re-parse input with command-specific definitions
//		$this->parseCommandInput($command, $argv);
//
//		// Validate input if command supports validation
//		if ($command instanceof ValidatableCommandInterface) {
//			$errors = $command->validate($this->input);
//			if (!empty($errors)) {
//				foreach ($errors as $error) {
//					$this->output->error($error);
//				}
//				return 1;
//			}
//		}
//
//		try {
//			return $command->execute($this->input, $this->output);
//		} catch (\Exception $e) {
//			$this->output->error('Error: ' . $e->getMessage());
//			return 1;
//		}
//	}

	private function parseCommandInputForSingleMode(CommandInterface $command, array $argv): void
	{
		$argumentDefinitions = $command->getArguments();
		$optionDefinitions = $command->getOptions();

		// Create a modified ConsoleInput for single command mode
		$this->input = new ConsoleInput();
		// Parse without expecting a command name - treat first argument as actual argument
		$this->input->parse($argv, $argumentDefinitions, $optionDefinitions);
	}

	private function parseCommandInput(CommandInterface $command, array $argv): void
	{
		$argumentDefinitions = $command->getArguments();
		$optionDefinitions = $command->getOptions();

		$this->input->parse($argv, $argumentDefinitions, $optionDefinitions);
	}

	private function showCommandList(): bool
	{
		// Check if any command wants to handle the list display
		foreach ($this->commands as $command) {
			if ($command instanceof ListProviderInterface) {
				if ($command->showInList($this->output)) {
					return true;
				}
			}
		}

		// Default list display
		$this->output->writeln("<yellow>{$this->name}</yellow> <green>version {$this->version}</green>");
		$this->output->writeln('');
		$this->output->writeln('<yellow>Usage:</yellow>');
		$this->output->writeln('  command [options] [arguments]');
		$this->output->writeln('');
		$this->output->writeln('<yellow>Available commands:</yellow>');

		$grouped = $this->groupCommands();

		foreach ($grouped as $group => $commands) {
			if ($group !== '_default') {
				$this->output->writeln("<green> {$group}</green>");
			}

			foreach ($commands as $command) {
				$name        = $command->getName();
				$description = $command->getDescription();

//				if (($command instanceof OptionalCommandInterface) && !$command->shouldDisplay($this)) continue;
				$padding     = str_repeat(' ', max(2, 25 - strlen($name)));

				if ($group !== '_default') {
					$this->output->writeln("  <cyan>{$name}</cyan>{$padding}{$description}");
				} else {
					$this->output->writeln(" <cyan>{$name}</cyan>{$padding}{$description}");
				}
			}

			if ($group !== '_default') {
				$this->output->writeln('');
			}
		}

		return true;
	}

	private function showCommandHelp(CommandInterface $command): int
	{
		// Check if command wants to handle its own help
		if ($command instanceof HelpProviderInterface) {
			if ($command->showHelp($this->output)) {
				return 0;
			}
		}

		// Default help display
		$this->output->writeln("<yellow>Description:</yellow>");
		$this->output->writeln("  " . $command->getDescription());
		$this->output->writeln('');

		$this->output->writeln("<yellow>Usage:</yellow>");
		$this->output->writeln("  " . $command->getName() . " [options] [arguments]");
		$this->output->writeln('');

		$arguments = $command->getArguments();
		if (!empty($arguments)) {
			$this->output->writeln("<yellow>Arguments:</yellow>");
			foreach ($arguments as $arg) {
				$required = $arg['required'] ? '' : ' (optional)';
				$this->output->writeln("  <green>{$arg['name']}</green>    {$arg['description']}{$required}");
			}
			$this->output->writeln('');
		}

		$options = $command->getOptions();

		if (!empty($options)) {
			$this->output->writeln("<yellow>Options:</yellow>");
			foreach ($options as $option) {
				$short = $option->hasShort() ? "-{$option->getShort()}, " : '    ';
				$this->output->writeln("  {$short}--{$option->getName()}    {$option->getDescription()}");
			}
			$this->output->writeln('');
		}

		// Allow command to add additional help content
		if ($command instanceof HelpProviderInterface) {
			$command->addHelpContent($this->output);
		}

		return 0;
	}

	private function groupCommands(): array
	{
		$grouped = ['_default' => []];

		foreach ($this->commands as $command) {
			// Do not show hidden commands in groups
			if ($command instanceof CommandVisibilityInterface && !$command->isVisible()) continue;

			$name  = $command->getName();
			$group = $command->getGroup();

			if (!$group && strpos($name, ':') !== false) {
				$group = explode(':', $name)[0];
			}

			$groupKey             = $group ?: '_default';
			$grouped[$groupKey][] = $command;
		}

		return $grouped;
	}

	/**
	 * Get all registered commands
	 *
	 * @return CommandInterface[]
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	/**
	 * Get command by name or alias
	 */
	public function getCommand(string $name): ?CommandInterface
	{
		// Check direct command name
		if (isset($this->commands[$name])) {
			return $this->commands[$name];
		}

		// Check aliases
		if (isset($this->aliases[$name])) {
			return $this->commands[$this->aliases[$name]];
		}

		return null;
	}

	/**
	 * Check if command exists
	 */
	public function hasCommand(string $name): bool
	{
		return isset($this->commands[$name]) || isset($this->aliases[$name]);
	}
}