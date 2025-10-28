<?php

namespace WebImage\Console;

/**
 * Value Option - Options that require or optionally accept a specific value
 *
 * Use when you need to capture user input as a string value.
 * Can be configured as required (must provide value) or optional (can be omitted entirely).
 * When optional, the entire option can be left out, but if used, it MUST have a value.
 *
 * Examples:
 *   --config=file.json, -c file.json    (required: configuration file path)
 *   --output=report.pdf, -o report.pdf  (optional: output file, has default)
 *   --database=mysql                    (required: database type)
 *
 * CLI Usage:
 *   # Required value option:
 *   command --config=app.json    ✓ Provides required value
 *   command --config app.json    ✓ Provides required value (space syntax)
 *   command --config             ✗ Error: required value missing
 *   command                      ✓ Option not used (if optional)
 *
 *   # Optional value option:
 *   command --output=custom.txt  ✓ Provides custom value
 *   command --output             ✗ Error: if used, must have value
 *   command                      ✓ Option not used, uses default if set
 */
class ValueOption extends Option
{
	private ?string $default;
	private bool    $required;

	public function __construct(string $name, string $description, ?string $short = null, bool $required = true, ?string $default = null)
	{
		parent::__construct($name, $description, $short);
		$this->required = $required;
		$this->default  = $default;
	}

	public function requiresValue(): bool
	{
		return $this->required;
	}

	public function acceptsValue(): bool
	{
		return true;
	}

	public function getDefault(): ?string
	{
		return $this->default;
	}

	public function hasDefault(): bool
	{
		return $this->default !== null;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * Create a required value option
	 */
	public static function required(string $name, string $description, ?string $short = null): self
	{
		return new self($name, $description, $short, true);
	}

	/**
	 * Create an optional value option
	 */
	public static function optional(string $name, string $description, ?string $short = null, ?string $default = null): self
	{
		return new self($name, $description, $short, false, $default);
	}
}