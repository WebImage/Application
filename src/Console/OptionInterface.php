<?php

namespace WebImage\Console;

interface OptionInterface
{
	public function getName(): string;

	public function getDescription(): string;

	public function getShort(): ?string;

	public function hasShort(): bool;

	public function requiresValue(): bool;

	public function acceptsValue(): bool;
}