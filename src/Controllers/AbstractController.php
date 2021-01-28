<?php

namespace WebImage\Controllers;

use League\Container\ContainerAwareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\Container\ContainerAwareTrait;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Http\Response;
use WebImage\String\Helper;
use WebImage\View\Factory;
use WebImage\View\View;
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
	/**
	 * @var Dictionary
	 */
	private $queryVars;
	/**
	 * @var Dictionary
	 */
	private $postVars;

	/**
	 * Take a routed requested and send it to the appropriate action
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 *
	 * @return mixed
	 */
	public function handleRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		$this->request = $request;
		$this->response = $response;

		$action = $request->getAttribute(ControllerInterface::DISPATCH_ACTION_ATTRIBUTE);

		/** @var ResponseInterface|string|array $result */
		$result = call_user_func([$this, $action]);

		if (is_array($result)) {
			$response = $response->withAddedHeader('Content-type: ', 'application/json');
			$response->getBody()->write(json_encode($result));
		} else if (is_string($result)) {
			$response->getBody()->write($result);
		} else if ($result instanceof ViewInterface) {
			$response->getBody()->write($result->render());
		} else if ($result instanceof ResponseInterface) {
			$response = $result;
		}

		return $response;
	}

	/**
	 * @return ServerRequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
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
	protected function view(array $vars=array(), $viewKey=null, $masterViewName=null)
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

	/**
	 * Generate a view name based on the controller and action
	 *
	 * @return string
	 */
	protected function getDefaultViewName()
	{
		$name = $this->getControllerNameForView();
		$action = $this->getRequest()->getAttribute(ControllerInterface::DISPATCH_ACTION_ATTRIBUTE);
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
		$response = $this->getResponse()
			->withStatus($responseCode)
			->withHeader('Location', $url);

		return $response;
	}
}