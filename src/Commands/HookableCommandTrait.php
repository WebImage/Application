<?php

namespace WebImage\Commands;


use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;

/**
 * Trait providing default implementations for hooks
 */
trait HookableCommandTrait
{
	public function beforeExecute(ConsoleInput $input, ConsoleOutput $output): void
	{
		// Default implementation - do nothing
	}

	public function afterExecute(ConsoleInput $input, ConsoleOutput $output, int $exitCode): void
	{
		// Default implementation - do nothing
	}

	public function onExecuteError(ConsoleInput $input, ConsoleOutput $output, \Throwable $exception): void
	{
		// Default implementation - do nothing
	}
}