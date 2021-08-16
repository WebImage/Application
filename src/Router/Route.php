<?php

namespace WebImage\Router;

use League\Route\Route as LeagueRoute;
use Psr\Container\ContainerInterface;
use WebImage\Application\ApplicationInterface;

class Route extends LeagueRoute
{
	/**
	 * @inheritdoc
	 */
	public function getCallable(?ContainerInterface $container = null): callable
	{
		$callable = $this->handler;

		if (is_string($callable)) {
			list($controller, $action) = array_pad(explode('::', $this->handler, 2), 2, null);

			if ($action === null) return parent::getCallable($container);

			$controller = $this->getExpandedControllerName($container, $controller);

			$this->handler = sprintf('%s::%s', $controller, $action);
		}

		return parent::getCallable($container);
	}

	/**
	 * Expands a controller name in a short form, i.e. an alias, into a
	 * fully-qualified class name, e.g. Home would be expanded into
	 * App\Controllers\HomeController
	 *
	 * @param string $controller
	 * @return string
	 */
	protected function getExpandedControllerName(ContainerInterface $container, string $controller)
	{
		$app = $this->getApplication($container);
		if (null === $app) return $controller;

		if (substr($controller, -10) != 'Controller' && false === strpos($controller, '\\')) {
			$controllerNamespace = $app->getConfig()->get('app.controllers.namespace');
			$expandedName = sprintf('%s\\%sController', $controllerNamespace, $controller);

			/**
			 * Make sure the callable does not exist, either directly as a class, or in the container.
			 * Also check to make sure that the expandedName exists, either directly as a class, or in
			 * a container, before modifying the controller name.
			 */
			if (!class_exists($controller) && !$container->has($controller) && (class_exists($expandedName) || $container->has($controller))) {
				$controller = $expandedName;
			}
		}

		return $controller;
	}

	/**
	 * @return ApplicationInterface|null
	 */
	protected function getApplication(ContainerInterface $container): ?ApplicationInterface
	{
		return $container->get(ApplicationInterface::class);
	}
}