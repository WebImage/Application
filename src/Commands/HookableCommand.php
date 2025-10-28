<?php

namespace WebImage\Commands;

use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;

/**
 * Example of how to use hooks in the enhanced Command class
 */
abstract class HookableCommand extends Command implements HookableCommandInterface
{
	use HookableCommandTrait;

	/**
	 * Template method that handles the execution lifecycle
	 */
	final public function execute(ConsoleInput $input, ConsoleOutput $output): int
	{
		try {
			$this->beforeExecute($input, $output);
			$exitCode = $this->doExecute($input, $output);
			$this->afterExecute($input, $output, $exitCode);
			return $exitCode;
		} catch (\Throwable $e) {
			$this->onExecuteError($input, $output, $e);
			throw $e;
		}
	}

	/**
	 * The actual command logic - implement this instead of execute()
	 */
	abstract protected function doExecute(ConsoleInput $input, ConsoleOutput $output): int;
}