<?php

namespace WebImage\Router;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;

class RouterServiceProvider extends AbstractServiceProvider
{
	public function provides(string $id): bool
	{
		return $id == Router::class;
	}

	public function register(): void
	{
		$this->getContainer()->addShared(Router::class)->addMethodCall('setContainer', [$this->getContainer()]);
	}
}