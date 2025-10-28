<?php

namespace WebImage\Console;

class Argument
{
	private string  $name;
	private string  $description;
	private bool    $required;
	private ?string $default;

	public function __construct(string $name, string $description, bool $required = true, ?string $default = null)
	{
		$this->name        = $name;
		$this->description = $description;
		$this->required    = $required;
		$this->default     = $default;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function getDefault(): ?string
	{
		return $this->default;
	}

	public function hasDefault(): bool
	{
		return $this->default !== null;
	}

	/**
	 * Create a required argument
	 */
	public static function required(string $name, string $description): self
	{
		return new self($name, $description, true);
	}

	/**
	 * Create an optional argument
	 */
	public static function optional(string $name, string $description, ?string $default = null): self
	{
		return new self($name, $description, false, $default);
	}
}