<?php

namespace WebImage\Commands;

use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;
use WebImage\Console\OptionInterface;

/**
 * Command Interface - Defines the contract for console commands
 *
 * Commands are immutable objects that can be cloned with modifications using the "with" methods.
 * This allows command discovery systems to customize command properties (name, description, etc.)
 * without affecting the original command instance or requiring mutable setters.
 *
 * The interface supports two main use cases:
 * 1. Basic command execution with getName(), getDescription(), execute(), etc.
 * 2. Command customization through immutable "with" methods that return new instances
 *
 * Example usage:
 *   $command = new SomeCommand();
 *   $customized = $command->withName('db:migrate')
 *                        ->withDescription('Run database migrations')
 *                        ->withGroup('database');
 *
 * The original $command remains unchanged, while $customized is a new instance
 * with the modified properties.
 */
interface CommandInterface
{
	/**
	 * Get the command name used for CLI invocation
	 *
	 * @return string The command name (e.g., "migrate", "db:seed", "cache:clear")
	 */
	public function getName(): string;

	/**
	 * Get the human-readable description of what this command does
	 *
	 * @return string A brief description shown in help text and command listings
	 */
	public function getDescription(): string;

	/**
	 * Get the optional group/namespace this command belongs to
	 *
	 * Commands can be grouped for better organization in help listings.
	 * If null, the command appears in the default group.
	 *
	 * @return string|null The group name (e.g., "database", "cache") or null
	 */
	public function getGroup(): ?string;

	/**
	 * Get the array of argument definitions this command accepts
	 *
	 * @return array Array of Argument objects defining positional parameters
	 */
	public function getArguments(): array;

	/**
	 * Get the array of option definitions this command accepts
	 *
	 * @return OptionInterface[] Array of Option objects defining --flags and --options
	 */
	public function getOptions(): array;

	/**
	 * Execute the command with the given input and output
	 *
	 * This is the main entry point where the command performs its work.
	 *
	 * @param ConsoleInput $input Parsed command line input (arguments, options)
	 * @param ConsoleOutput $output Output interface for writing to console
	 * @return int Exit code (0 for success, non-zero for failure)
	 */
	public function execute(ConsoleInput $input, ConsoleOutput $output): int;

	/**
	 * Create a new command instance with a different name
	 *
	 * Returns a clone of this command with the name property changed.
	 * The original command instance is not modified.
	 *
	 * This is typically used by command discovery systems to customize
	 * how commands appear in the CLI without affecting the base command.
	 *
	 * @param string $name The new command name
	 * @return CommandInterface A new command instance with the specified name
	 */
	public function withName(string $name): CommandInterface;

	/**
	 * Create a new command instance with a different description
	 *
	 * Returns a clone of this command with the description property changed.
	 * The original command instance is not modified.
	 *
	 * @param string $description The new command description
	 * @return CommandInterface A new command instance with the specified description
	 */
	public function withDescription(string $description): CommandInterface;

	/**
	 * Create a new command instance with a different group
	 *
	 * Returns a clone of this command with the group property changed.
	 * The original command instance is not modified.
	 *
	 * @param string $group The new group name
	 * @return CommandInterface A new command instance with the specified group
	 */
	public function withGroup(string $group): CommandInterface;

	/**
	 * Create a new command instance with different arguments
	 *
	 * Returns a clone of this command with the arguments array replaced.
	 * The original command instance is not modified.
	 *
	 * This allows discovery systems to modify or extend the argument
	 * definitions of a command.
	 *
	 * @param array $arguments Array of Argument objects
	 * @return CommandInterface A new command instance with the specified arguments
	 */
	public function withArguments(array $arguments): CommandInterface;

	/**
	 * Create a new command instance with different options
	 *
	 * Returns a clone of this command with the options array replaced.
	 * The original command instance is not modified.
	 *
	 * This allows discovery systems to modify or extend the option
	 * definitions of a command.
	 *
	 * @param array $options Array of Option objects
	 * @return CommandInterface A new command instance with the specified options
	 */
	public function withOptions(array $options): CommandInterface;
}