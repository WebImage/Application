<?php

namespace WebImage\View\Helpers;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use WebImage\Application\ApplicationInterface;
use WebImage\Application\HttpApplication;
use WebImage\View\AbstractHelper;
use WebImage\View\ViewManagerAwareInterface;
use WebImage\View\ViewManagerAwareTrait;

class NamedRouteHelper extends AbstractHelper implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	public function __invoke(string $routeName, ?array $params = null, array $queryParams = [])
	{
		$app = $this->getContainer()->get(ApplicationInterface::class);
		if (!($app instanceof HttpApplication)) {
			throw new \RuntimeException('RouteHelper requires an instance of HttpApplication');
		}

		$router = $app->routes();
		$route = $router->getNamedRoute($routeName);
		$url = $route->getPath($params);

		if (count($queryParams) > 0) {
			$query = http_build_query($queryParams);
			$url .= '?' . $query;
		}

		return $url;
	}
}