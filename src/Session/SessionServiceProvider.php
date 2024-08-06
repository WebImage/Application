<?php

namespace WebImage\Session;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;

class SessionServiceProvider extends AbstractServiceProvider
{
	protected array $provides = [SessionInterface::class];

	public function register(): void
	{
		$this->getContainer()->addShared(SessionInterface::class, function() {
			return new PhpSessionWrapper();
		});
	}
}