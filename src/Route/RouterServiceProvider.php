<?php

namespace WebImage\Route;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;

class RouterServiceProvider extends AbstractServiceProvider
{
	protected array $provides = [Router::class];

	public function register(): void
	{
		$this->getContainer()->addShared(Router::class)->addMethodCall('setContainer', [$this->getContainer()]);
	}
}
