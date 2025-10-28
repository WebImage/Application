<?php

namespace WebImage\Commands;

use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;

/**
 * Interface for commands that support lifecycle hooks
 */
interface HookableCommandInterface
{
	/**
	 * Called before command execution
	 */
	public function beforeExecute(ConsoleInput $input, ConsoleOutput $output): void;

	/**
	 * Called after successful command execution
	 */
	public function afterExecute(ConsoleInput $input, ConsoleOutput $output, int $exitCode): void;

	/**
	 * Called when command execution fails with an exception
	 */
	public function onExecuteError(ConsoleInput $input, ConsoleOutput $output, \Throwable $exception): void;
}