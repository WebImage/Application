<?php

namespace WebImage\Controllers;

use GuzzleHttp\Psr7\HttpFactory;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\String\Helper;
use WebImage\View\Factory;
use WebImage\View\ViewInterface;

class AbstractController implements ControllerInterface, ContainerAwareInterface {
	use ContainerAwareTrait;
	/**
	 * @var ServerRequestInterface
	 */
	private $request;
	/**
	 * @var ResponseInterface
	 */
	private $response;
	/** @var string The name used by the dispatcher / application strategy */
	private $dispatchedActionName;
	/**
	 * @var Dictionary
	 */
	private $queryVars;
	/**
	 * @var Dictionary
	 */
	private $postVars;

	/**
	 * @inheritDoc
	 */
	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

	/**
	 * @inheritDoc
	 */
	public function setRequest(ServerRequestInterface $request)
	{
		$this->request = $request;
	}

	/**
	 * @inheritDoc
	 */
	public function getResponse(): ResponseInterface
	{
		if ($this->response === null) {
			$httpFactory = new HttpFactory();
			$this->response = $httpFactory->createResponse();
		}

		return $this->response;
	}

	/**
	 * @inheritDoc
	 */
	public function setResponse(ResponseInterface $response)
	{
		$this->response = $response;
	}

	/**
	 * @return ApplicationInterface
	 */
	public function getApplication()
	{
		return $this->getContainer()->get(ApplicationInterface::class);
	}

	protected function queryParams()
	{
		if (null === $this->queryVars) {
			$this->queryVars = new Dictionary($this->getRequest()->getQueryParams());
		}

		return $this->queryVars;
	}

	/**
	 * Returns a view object
	 * @param array $vars
	 * @param null $viewKey
	 * @param null|string|bool $masterViewName null to use default; string for path to template; false
	 * @return ViewInterface
	 */
	protected function view(array $vars=array(), $viewKey=null, $masterViewName=null): ?ViewInterface
	{
		if (null !== $viewKey && !is_string($viewKey)) {
			throw new \InvalidArgumentException('Expecting string for viewKey');
		}

		$viewKey = (null === $viewKey) ? $this->getDefaultViewName() : $viewKey;
		/** @var Factory $factory */
		$factory = $this->getContainer()->get(Factory::class);
		$view = $factory->create($viewKey, $vars);

		if (null === $masterViewName) {
			$masterViewName = $this->getMasterViewName();
		}

		if (false !== $masterViewName) {
			$view->extend($masterViewName);
		}

		return $view;
	}

	public function getDispatchedActionName(): string
	{
		return $this->dispatchedActionName;
	}

	public function setDispatchedActionName(string $action): void
	{
		$this->dispatchedActionName = $action;
	}

	/**
	 * Generate a view name based on the controller and action
	 *
	 * @return string
	 */
	protected function getDefaultViewName()
	{
		$name   = $this->getControllerNameForView();
		$action = $this->getDispatchedActionName();
		$action = strtolower(Helper::pascalToHyphenated($action));

		return sprintf('%s/%s/%s', 'controllers', $name, $action);
	}

	/**
	 * Generate a view name for the current controller
	 * @return string
	 */
	protected function getControllerNameForView()
	{
		$class = get_class($this);
		$parts = explode('\\', $class);

		$name = array_pop($parts);
		$name = strtolower($name);

		if (substr($name, -10) == 'controller') {
			$name = substr($name, 0, -10);
		}

		return $name;
	}

	/**
	 * Get the default master view name
	 */
	protected function getMasterViewName()
	{
		/** @var ApplicationInterface $app */
		$app = $this->getContainer()->get(ApplicationInterface::class);
		$config = $app->getConfig();
		$viewConfig = isset($config['views']) ? $config['views'] : new Config();

		return isset($viewConfig['defaultMasterView']) ? $viewConfig['defaultMasterView'] : 'layouts/default';
	}

	public function redirect($url, $responseCode=301)
	{
		$factory = new HttpFactory();

		return $factory->createResponse($responseCode)
			->withHeader('Location', $url);
	}
}