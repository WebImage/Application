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

	/**
	 * @param Router $router
	 * @param string $pathPrefix The point at which to mount the routes, e.g. /somepath
	 * @return void
	 */
	public function injectRoutes(Router $router, string $pathPrefix = ''): void
	{
		$pathPrefix = $this->normalizePathPrefix($pathPrefix);

		foreach($this as $routeInfo) {
			$route = $router->map(
				$routeInfo->getMethod(),
				rtrim($pathPrefix . $routeInfo->getPath(), '/'),
				$routeInfo->getHandler()
			);
			$route->lazyMiddlewares($routeInfo->getMiddlewares());
			if ($routeInfo->getName() !== null) {
				$route->setName($routeInfo->getName());
			}
		}
	}

	private function normalizePathPrefix(string $pathPrefix): string
	{
		if (strlen($pathPrefix) > 0 && substr($pathPrefix, 0, 1) != '/') {
			throw new \InvalidArgumentException('Path prefix must start with a forward slash');
		}

		return rtrim($pathPrefix, '/');
	}
}