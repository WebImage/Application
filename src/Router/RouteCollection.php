<?php

namespace WebImage\Router;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Router\Strategy\ApplicationStrategy;

class RouteCollection extends \League\Route\RouteCollection implements RouteCollectionInterface, ContainerAwareInterface {
	use ContainerAwareTrait;
	use RouteCollectionMapTrait;

	public function getDispatcher(ServerRequestInterface $request)
	{
		$this->prepRoutes($request);

		return (new Dispatcher($this->getData()))->setStrategy($this->getStrategy());
	}


	/**
	 * Use our own strategy, unless one was explicitly set
	 *
	 * @return \League\Route\Strategy\StrategyInterface|ApplicationStrategy
	 */
	public function getStrategy()
	{
		$strategy = parent::getStrategy();

		if (null === $strategy) {

			$strategy = new ApplicationStrategy();

			if ($strategy instanceof ContainerAwareInterface) {
				$strategy->setContainer($this->getContainer());
			}

			$this->setStrategy($strategy);
		}

		return $strategy;
	}

	public function fallback($handler)
	{
		return $this->map('*', '*', $handler);
	}
}