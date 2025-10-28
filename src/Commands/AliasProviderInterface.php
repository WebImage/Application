<?php

namespace WebImage\Commands;
/**
 * Interface for commands that support aliases
 */
interface AliasProviderInterface
{
	/**
	 * Get command aliases
	 *
	 * @return string[]
	 */
	public function getAliases(): array;
}