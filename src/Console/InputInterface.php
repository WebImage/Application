<?php

namespace WebImage\Console;

interface InputInterface
{
	/**
	 * Parse command line arguments
	 *
	 * @param array $argv Command line arguments
	 * @param Argument[] $argumentDefinitions Array of Argument objects
	 * @param OptionInterface[] $optionDefinitions Array of Option objects
	 */
	public function parse(array $argv, array $argumentDefinitions = [], array $optionDefinitions = []): void;

	public function getCommand(): string;

	/**
	 * Get argument by position index or name
	 *
	 * @param int|string $identifier Position index (0-based) or argument name
	 * @param string $default Default value if argument not found
	 * @return string
	 */
	public function getArgument($identifier, string $default = ''): string;

	/**
	 * Check if argument exists by position or name
	 *
	 * @param int|string $identifier Position index or argument name
	 * @return bool
	 */
	public function hasArgument($identifier): bool;

	public function getArguments(): array;

	public function getNamedArguments(): array;

	public function getArgumentCount(): int;

	/**
	 * Get argument definitions
	 *
	 * @return Argument[]
	 */
	public function getArgumentDefinitions(): array;

	/**
	 * Get option definitions
	 *
	 * @return Option[]
	 */
	public function getOptionDefinitions(): array;

	/**
	 * Get option value
	 * Returns true for flags without values, actual value if provided
	 *
	 * @param string $name Option name
	 * @param mixed $default Default value if option not set
	 * @return mixed
	 */
	public function getOption(string $name, $default = null);

	public function hasOption(string $name): bool;

	/**
	 * Check if option was provided as a flag (true) vs with a value
	 *
	 * @param string $name Option name
	 * @return bool
	 */
	public function isOptionFlag(string $name): bool;

	/**
	 * Get option value only if it's not a flag
	 *
	 * @param string $name Option name
	 * @param mixed $default Default value
	 * @return mixed Returns null if option is a flag, value otherwise
	 */
	public function getOptionValue(string $name, $default = null);

	public function ask(string $question, string $default = ''): string;

	public function askHidden(string $question): string;

	public function choice(string $question, array $choices, string $default = null): string;

	public function confirm(string $question, bool $default = false): bool;
}