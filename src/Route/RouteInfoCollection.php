<?php

namespace WebImage\Route;

use WebImage\Core\Collection;

/**
 * @implements Collection<RouteInfo>
 */
class RouteInfoCollection extends Collection
{
	protected function assertValidItem($item): void
	{
		if (!$item instanceof RouteInfo) {
			throw new \InvalidArgumentException('Item must be an instance of ' . RouteInfo::class);
		}
	}

	public function injectRoutes(Router $router): void
	{
		foreach($this as $routeInfo) {
			$router->map($routeInfo->getMethod(), $routeInfo->getPath(), $routeInfo->getHandler())->lazyMiddlewares($routeInfo->getMiddlewares());
		}
	}
}