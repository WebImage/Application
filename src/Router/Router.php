<?php

namespace WebImage\Router;

use GuzzleHttp\Psr7\HttpFactory;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\Route as LeagueRoute;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyInterface;
use WebImage\Router\Strategy\ApplicationStrategy;

class Router extends \League\Route\Router implements ContainerAwareInterface
{
	use ContainerAwareTrait;
	/**
	 * @inheritDoc
	 */
	public function getStrategy(): ?StrategyInterface
	{
		$strategy = parent::getStrategy();

		if (null === $strategy) {
			$strategy = new ApplicationStrategy(new HttpFactory());
			$strategy->setContainer($this->getContainer());
		}

		return $strategy;
	}

	public function map(string $method, string $path, $handler): LeagueRoute
	{
		$path  = sprintf('/%s', ltrim($path, '/'));

		$route = new Route($method, $path, RouteHelper::normalizeHandler($handler));
		$this->routes[] = $route;

		return $route;
	}
}