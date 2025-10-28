<?php

namespace WebImage\Console;
/**
 * Interface for commands that provide their own list output
 */
interface ListProviderInterface
{
	/**
	 * Generate custom list content for this command
	 *
	 * @param ConsoleOutput $output
	 * @return bool Return true if listing was handled, false to use default listing
	 */
	public function showInList(ConsoleOutput $output): bool;
}