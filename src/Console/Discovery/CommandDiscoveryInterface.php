<?php

namespace WebImage\Console\Discovery;

use WebImage\Commands\CommandInterface;

/**
 * Make it possible to auto-discover available commands
 */
interface CommandDiscoveryInterface
{
	/**
	 * Discover and return command instances
	 *
	 * @return CommandInterface[]
	 */
	public function discover(): array;
}