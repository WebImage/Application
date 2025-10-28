<?php

namespace WebImage\Console;

abstract class Option implements OptionInterface
{
	protected string  $name;
	protected string  $description;
	protected ?string $short;

	public function __construct(string $name, string $description, ?string $short = null)
	{
		$this->name        = $name;
		$this->description = $description;
		$this->short       = $short;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getShort(): ?string
	{
		return $this->short;
	}

	public function hasShort(): bool
	{
		return $this->short !== null;
	}

	abstract public function requiresValue(): bool;

	abstract public function acceptsValue(): bool;
}
