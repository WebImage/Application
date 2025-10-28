<?php

namespace WebImage\Console;

/**
 * Flag Option - Boolean toggle options that don't accept values
 *
 * Use for simple on/off switches that are either present or not.
 * When the flag is present, it's considered "true", when absent it's "false".
 *
 * Examples:
 *   --verbose, -v     (enable verbose output)
 *   --force, -f       (force operation without confirmation)
 *   --dry-run         (show what would happen without executing)
 *
 * CLI Usage:
 *   command --verbose           ✓ Flag is set
 *   command -v                  ✓ Flag is set (short form)
 *   command --verbose=anything  ✗ Error: flags don't accept values
 *   command                     ✓ Flag is not set
 */
class FlagOption extends Option
{
	public function requiresValue(): bool
	{
		return false;
	}

	public function acceptsValue(): bool
	{
		return false;
	}

	/**
	 * Create a flag option
	 */
	public static function create(string $name, string $description, ?string $short = null): self
	{
		return new self($name, $description, $short);
	}
}
