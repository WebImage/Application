<?php

namespace WebImage\Console;

interface CommandVisibilityInterface
{
	/**
	 * Whether the command should be listed in "command list" interfaces
	 * @return bool
	 */
	public function isVisible(): bool;

	/**
	 * Whether the command should be executed.  A command may be made invisible but left executable to allow for hidden commands
	 * @return bool
	 */
	public function isExecutable(): bool;
}