<?php

namespace WebImage\Router;

use WebImage\Application\ApplicationInterface;

class Route extends \League\Route\Route
{
	/**
	 * @inheritdoc
	 */
	public function getCallable()
	{
		if (is_string($this->callable)) {
			list($controller, $action) = array_pad(explode('::', $this->callable, 2), 2, null);

			if (null === $action) return parent::getCallable();

			$controller = $this->getExpandedControllerName($controller);

			$this->callable = sprintf('%s::%s', $controller, $action);
		}

		return parent::getCallable();
	}

	/**
	 * Expands a controller name in a short form, i.e. an alias, into a
	 * fully-qualified class name, e.g. Home would be expanded into
	 * App\Controllers\HomeController
	 *
	 * @param string $controller
	 * @return string
	 */
	protected function getExpandedControllerName(string $controller)
	{
		$app = $this->getApplication();
		if (null === $app) return $controller;

		if (substr($controller, -10) != 'Controller' && false === strpos($controller, '\\')) {
			$controllerNamespace = $app->getConfig()->get('app.controllers.namespace');
			$expandedName = sprintf('%s\\%sController', $controllerNamespace, $controller);

			/**
			 * Make sure the callable does not exist, either directly as a class, or in the container.
			 * Also check to make sure that the expandedName exists, either directly as a class, or in
			 * a container, before modifying the controller name.
			 */
			if (!class_exists($controller) && !$this->getContainer()->has($controller) && (class_exists($expandedName) || $this->getContainer()->has($controller))) {
				$controller = $expandedName;
			}
		}

		return $controller;
	}

	/**
	 * @return ApplicationInterface|null
	 */
	protected function getApplication()
	{
		return $this->getContainer()->get(ApplicationInterface::class);
	}
}