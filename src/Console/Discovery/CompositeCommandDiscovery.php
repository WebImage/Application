<?php

namespace WebImage\Console\Discovery;

/**
 * Composite discovery that combines multiple discovery strategies
 */
class CompositeCommandDiscovery implements CommandDiscoveryInterface
{
	/** @var CommandDiscoveryInterface[] */
	private array $discoveries;

	public function __construct(CommandDiscoveryInterface ...$discoveries)
	{
		$this->discoveries = $discoveries;
	}

	public function addDiscovery(CommandDiscoveryInterface $discovery): void
	{
		$this->discoveries[] = $discovery;
	}

	public function discover(): array
	{
		$commands = [];

		foreach ($this->discoveries as $discovery) {
			$commands = array_merge($commands, $discovery->discover());
		}

		return $commands;
	}
}