<?php

namespace WebImage\Console;
/**
 * Interface for commands that provide their own help output
 */
interface HelpProviderInterface
{
	/**
	 * Generate custom help content
	 *
	 * @param ConsoleOutput $output
	 * @return bool Return true if help was handled, false to use default help
	 */
	public function showHelp(ConsoleOutput $output): bool;

	/**
	 * Optionally add additional help content to default help
	 *
	 * @param ConsoleOutput $output
	 */
	public function addHelpContent(ConsoleOutput $output): void;
}