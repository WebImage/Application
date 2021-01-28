<?php

namespace WebImage\Router;

trait RouteCollectionMapTrait {
	use \League\Route\RouteCollectionMapTrait;

	protected $routes = [];

	/**
	 * @inheritdoc
	 */
	public function map($method, $path, $handler)
	{
		$path = sprintf('/%s', ltrim($path, '/'));
		$route = new Route;
		$route->setMethods((array)$method);
		$route->setPath($path);
		$route->setCallable(RouteHelper::normalizeHandler($handler));
		$this->routes[] = $route;

		return $route;
	}
}