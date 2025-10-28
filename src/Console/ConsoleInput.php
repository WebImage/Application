<?php

namespace WebImage\Console;

class ConsoleInput implements InputInterface
{
	private array  $arguments      = [];
	private array  $namedArguments = [];
	private array  $options        = [];
	private string $command        = '';
	/** @var Argument[] */
	private array $argumentDefinitions = [];
	/** @var Option[] */
	private array $optionDefinitions = [];

	/**
	 * Parse command line arguments
	 *
	 * @param array $argv Command line arguments
	 * @param Argument[] $argumentDefinitions Array of Argument objects
	 * @param OptionInterface[] $optionDefinitions Array of Option objects
	 */
	public function parse(array $argv, array $argumentDefinitions = [], array $optionDefinitions = []): void
	{
		$this->argumentDefinitions = $argumentDefinitions;
		$this->optionDefinitions   = $optionDefinitions;
		array_shift($argv); // Remove script name

		if (empty($argv)) {
			return;
		}

		$this->command = array_shift($argv);

		$i = 0;
		while ($i < count($argv)) {
			$arg = $argv[$i];

			if (strpos($arg, '--') === 0) {
				$i += $this->parseOption($argv, $i, true);
			} elseif (strpos($arg, '-') === 0) {
				$i += $this->parseOption($argv, $i, false);
			} else {
				$this->arguments[] = $arg;
				$i++;
			}
		}

		$this->mapNamedArguments();
		$this->validateRequiredArguments();
		$this->validateRequiredOptions();
	}

	private function parseOption(array $argv, int $index, bool $isLong): int
	{
		$arg      = $argv[$index];
		$consumed = 1;

		if ($isLong) {
			$option = substr($arg, 2);

			// Handle --option=value format
			if (strpos($option, '=') !== false) {
				[$name, $value] = explode('=', $option, 2);
				$this->options[$name] = $value;
				return $consumed;
			}

			$name = $option;
		} else {
			$name = substr($arg, 1);
		}

		// Find option definition
		$optionDef = $this->findOptionDefinition($name, $isLong);

		if ($optionDef) {
			if ($optionDef->requiresValue()) {
				// This option requires a value
				if (isset($argv[$index + 1]) && !$this->isOption($argv[$index + 1])) {
					$this->options[$optionDef->getName()] = $argv[$index + 1];
					$consumed                             = 2;
				} else {
					throw new \InvalidArgumentException("Option '--{$optionDef->getName()}' requires a value");
				}
			} elseif ($optionDef->acceptsValue()) {
				// This option can optionally accept a value
				if (isset($argv[$index + 1]) && !$this->isOption($argv[$index + 1])) {
					$this->options[$optionDef->getName()] = $argv[$index + 1];
					$consumed                             = 2;
				} else {
					$this->options[$optionDef->getName()] = true;
				}
			} else {
				// This option is a simple flag
				$this->options[$optionDef->getName()] = true;
			}
		} else {
			// No definition found, try to be smart about it
			if (isset($argv[$index + 1]) && !$this->isOption($argv[$index + 1])) {
				$this->options[$name] = $argv[$index + 1];
				$consumed             = 2;
			} else {
				$this->options[$name] = true;
			}
		}

		return $consumed;
	}

	private function findOptionDefinition(string $name, bool $isLong): ?Option
	{
		foreach ($this->optionDefinitions as $option) {
			if ($isLong && $option->getName() === $name) {
				return $option;
			} elseif (!$isLong && $option->getShort() === $name) {
				return $option;
			}
		}
		return null;
	}

	private function isOption(string $arg): bool
	{
		return strpos($arg, '-') === 0;
	}

	private function mapNamedArguments(): void
	{
		$this->namedArguments = [];

		foreach ($this->argumentDefinitions as $index => $definition) {
			$name  = $definition->getName();
			$value = $this->arguments[$index] ?? null;

			if ($value !== null) {
				$this->namedArguments[$name] = $value;
			} elseif ($definition->hasDefault()) {
				$this->namedArguments[$name] = $definition->getDefault();
			}
		}
	}

	private function validateRequiredArguments(): void
	{
		foreach ($this->argumentDefinitions as $index => $definition) {
			if ($definition->isRequired() && !isset($this->arguments[$index])) {
				throw new \InvalidArgumentException("Required argument '{$definition->getName()}' is missing");
			}
		}
	}

	private function validateRequiredOptions(): void
	{
		foreach ($this->optionDefinitions as $option) {
			if ($option instanceof ValueOption && $option->isRequired() && !$this->hasOption($option->getName())) {
				throw new \InvalidArgumentException("Required option '--{$option->getName()}' is missing");
			}
		}
	}

	public function getCommand(): string
	{
		return $this->command;
	}

	/**
	 * Get argument by position index or name
	 *
	 * @param int|string $identifier Position index (0-based) or argument name
	 * @param string $default Default value if argument not found
	 * @return string
	 */
	public function getArgument($identifier, string $default = ''): string
	{
		if (is_int($identifier)) {
			return $this->arguments[$identifier] ?? $default;
		} elseif (is_string($identifier)) {
			return $this->namedArguments[$identifier] ?? $default;
		}

		return $default;
	}

	/**
	 * Check if argument exists by position or name
	 *
	 * @param int|string $identifier Position index or argument name
	 * @return bool
	 */
	public function hasArgument($identifier): bool
	{
		if (is_int($identifier)) {
			return isset($this->arguments[$identifier]);
		} elseif (is_string($identifier)) {
			return isset($this->namedArguments[$identifier]);
		}

		return false;
	}

	public function getArguments(): array
	{
		return $this->arguments;
	}

	public function getNamedArguments(): array
	{
		return $this->namedArguments;
	}

	public function getArgumentCount(): int
	{
		return count($this->arguments);
	}

	/**
	 * Get argument definitions
	 *
	 * @return Argument[]
	 */
	public function getArgumentDefinitions(): array
	{
		return $this->argumentDefinitions;
	}

	/**
	 * Get option definitions
	 *
	 * @return Option[]
	 */
	public function getOptionDefinitions(): array
	{
		return $this->optionDefinitions;
	}

	/**
	 * Get option value
	 * Returns true for flags without values, actual value if provided
	 *
	 * @param string $name Option name
	 * @param mixed $default Default value if option not set
	 * @return mixed
	 */
	public function getOption(string $name, $default = null)
	{
		if (!$this->hasOption($name)) {
			// Check if there's a default value from the option definition
			$optionDef = $this->findOptionByName($name);
			if ($optionDef instanceof ValueOption && $optionDef->hasDefault()) {
				return $optionDef->getDefault();
			} elseif ($optionDef instanceof FlexibleOption && $optionDef->hasDefaultValue()) {
				return $optionDef->getDefaultValue();
			}
		}

		return $this->options[$name] ?? $default;
	}

	private function findOptionByName(string $name): ?Option
	{
		foreach ($this->optionDefinitions as $option) {
			if ($option->getName() === $name) {
				return $option;
			}
		}
		return null;
	}

	public function hasOption(string $name): bool
	{
		return isset($this->options[$name]);
	}

	/**
	 * Check if option was provided as a flag (true) vs with a value
	 *
	 * @param string $name Option name
	 * @return bool
	 */
	public function isOptionFlag(string $name): bool
	{
		return $this->hasOption($name) && $this->options[$name] === true;
	}

	/**
	 * Get option value only if it's not a flag
	 *
	 * @param string $name Option name
	 * @param mixed $default Default value
	 * @return mixed Returns null if option is a flag, value otherwise
	 */
	public function getOptionValue(string $name, $default = null)
	{
		if ($this->isOptionFlag($name)) {
			return null;
		}
		return $this->getOption($name, $default);
	}

	public function ask(string $question, string $default = ''): string
	{
		echo $question;
		if ($default) {
			echo " [{$default}]";
		}
		echo ': ';

		$handle = fopen('php://stdin', 'r');
		$input  = trim(fgets($handle));
		fclose($handle);

		return $input ?: $default;
	}

	public function askHidden(string $question): string
	{
		echo $question . ': ';

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$password = shell_exec('powershell -Command "Read-Host -AsSecureString | ConvertFrom-SecureString"');
			return trim($password ?? '');
		} else {
			system('stty -echo');
			$password = trim(fgets(STDIN));
			system('stty echo');
			echo "\n";
			return $password;
		}
	}

	public function choice(string $question, array $choices, string $default = null): string
	{
		echo $question . "\n";

		foreach ($choices as $index => $choice) {
			$marker = $choice === $default ? ' (default)' : '';
			echo "  [" . ($index + 1) . "] {$choice}{$marker}\n";
		}

		echo "Choose [1-" . count($choices) . "]";
		if ($default) {
			$defaultIndex = array_search($default, $choices);
			echo " [" . ($defaultIndex + 1) . "]";
		}
		echo ': ';

		$handle = fopen('php://stdin', 'r');
		$input  = trim(fgets($handle));
		fclose($handle);

		if (empty($input) && $default) {
			return $default;
		}

		$index = (int)$input - 1;
		if (isset($choices[$index])) {
			return $choices[$index];
		}

		echo "Invalid choice. Please try again.\n";
		return $this->choice($question, $choices, $default);
	}

	public function confirm(string $question, bool $default = false): bool
	{
		$suffix = $default ? ' [Y/n]' : ' [y/N]';
		echo $question . $suffix . ': ';

		$handle = fopen('php://stdin', 'r');
		$input  = trim(strtolower(fgets($handle)));
		fclose($handle);

		if (empty($input)) {
			return $default;
		}

		return in_array($input, ['y', 'yes', '1', 'true']);
	}
}