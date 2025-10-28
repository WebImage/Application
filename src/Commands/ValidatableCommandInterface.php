<?php

namespace WebImage\Commands;
use WebImage\Console\ConsoleInput;

/**
 * Interface for commands that can validate their configuration
 */
interface ValidatableCommandInterface
{
	/**
	 * Validate the command's arguments and options before execution
	 *
	 * @param ConsoleInput $input
	 * @return array Array of validation errors (empty if valid)
	 */
	public function validate(ConsoleInput $input): array;
}