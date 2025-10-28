<?php

namespace WebImage\Console;

/**
 * Flexible Option - Options that work as both flags and value options
 *
 * Use when an option should work as a simple flag (with a default value)
 * OR accept a custom value to override that default. This provides maximum
 * flexibility for users - they can use the shorthand flag form or customize
 * the behavior with a specific value.
 *
 * Examples:
 *   --verbose (default: "info"), --verbose=debug    (logging with levels)
 *   --port (default: "8080"), --port=3000          (server port)
 *   --format (default: "table"), --format=json     (output format)
 *
 * CLI Usage:
 *   command --verbose           ✓ Uses default value ("info")
 *   command --verbose=debug     ✓ Uses custom value ("debug")
 *   command -v                  ✓ Uses default value (short form)
 *   command -v=warn             ✓ Uses custom value (short form)
 *   command                     ✓ Option not used at all
 *
 * Note: This is different from ValueOption with required=false because
 * FlexibleOption allows "--verbose" without "=" while ValueOption would
 * require "--verbose=something" if the option is used at all.
 */
class FlexibleOption extends Option
{
	private ?string $defaultValue;

	public function __construct(string $name, string $description, ?string $short = null, ?string $defaultValue = null)
	{
		parent::__construct($name, $description, $short);
		$this->defaultValue = $defaultValue;
	}

	public function requiresValue(): bool
	{
		return false;
	}

	public function acceptsValue(): bool
	{
		return true;
	}

	public function getDefaultValue(): ?string
	{
		return $this->defaultValue;
	}

	public function hasDefaultValue(): bool
	{
		return $this->defaultValue !== null;
	}

	/**
	 * Create a flexible option
	 */
	public static function create(string $name, string $description, ?string $short = null, ?string $defaultValue = null): self
	{
		return new self($name, $description, $short, $defaultValue);
	}
}