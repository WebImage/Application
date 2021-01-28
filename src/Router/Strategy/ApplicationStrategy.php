<?php

namespace WebImage\Router\Strategy;

use Exception;
use Illuminate\Console\Application;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route As LeagueRoute;
use League\Route\Strategy\ApplicationStrategy as BaseApplicationStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Application\ApplicationInterface;
use WebImage\Controllers\ControllerInterface;
use WebImage\Controllers\ErrorsController;
use WebImage\Controllers\ExceptionsController;
use WebImage\Router\Route;
use WebImage\Router\RouteHelper;
use WebImage\String\Url;

class ApplicationStrategy extends BaseApplicationStrategy implements ContainerAwareInterface {
	use ContainerAwareTrait;

	/**
	 * @inheritdoc
	 */
	public function getCallable(LeagueRoute $route, array $vars)
	{
		return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($route, $vars)
		{
			$request = $this->injectVarsAsAttributes($request, $vars);

			$request = $this->preProcessCallable($request, $route);

			$response = call_user_func($route->getCallable(), $request, $response);

			if ($response instanceof ResponseInterface) {
				return $next($request, $response);
			}

			return $response;
		};
	}

	/**
	 * @inheritdoc
	 */
	public function getNotFoundDecorator(NotFoundException $exception)
	{
		return $this->exceptionResponse($exception);
	}

	/**
	 * @inheritdoc
	 */
	public function getExceptionDecorator(Exception $exception)
	{
		return $this->exceptionResponse($exception);
	}

	/**
	 * @inheritdoc
	 */
	public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception)
	{
		return $this->exceptionResponse($exception);
	}

	/**
	 * Injects vars into the request as attributes
	 *
	 * @param ServerRequestInterface $request
	 * @param array $vars
	 * @return ServerRequestInterface|static
	 */
	protected function injectVarsAsAttributes(ServerRequestInterface $request, array $vars)
	{
		foreach($vars as $key => $val) {
			$request = $request->withAttribute($key, $val);
		}

		return $request;
	}

	/**
	 * Changes the route callable to call handleRequest() and passes the "action" as an attribute
	 * @param ServerRequestInterface $request
	 * @param Route $route
	 *
	 * @return ServerRequestInterface
	 */
	private function preProcessCallable(ServerRequestInterface $request, Route $route)
	{
		$callable = $route->getCallable();

		$app = $this->getApplication($route);
		$config = $app->getConfig();

		if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
			if ($callable[0] instanceof ControllerInterface) {
				$request = $request->withAttribute(ControllerInterface::DISPATCH_ACTION_ATTRIBUTE, $callable[1]);
				$callable[1] = ControllerInterface::DISPATCH_METHOD;
			}
			if ($callable[0] instanceof ContainerAwareInterface) {
				$callable[0]->setContainer($this->getContainer());
			}
		}

		$route->setCallable($callable);

		return $request;
	}

	/**
	 * @param Route $route
	 *
	 * @return ApplicationInterface
	 */
	private function getApplication(Route $route)
	{
		return $route->getContainer()->get(ApplicationInterface::class);
	}
	/**
	 * Modify the response if the exception is an Http exception
	 *
	 * @param Exception $exception
	 * @return \Closure
	 */
	private function exceptionResponse(Exception $exception)
	{
		return function(ServerRequestInterface $request, ResponseInterface $response, \Closure $next) use ($exception) {

			$request = $request->withAttribute(ExceptionsController::ATTR_EXCEPTION, $exception);
			$response = $this->populateExceptionResponse($response, $exception);
			$route = $this->createExceptionRoute($request, $exception);
			/** @var ApplicationInterface $app */
			$app = $this->getContainer()->get(ApplicationInterface::class);
			$isDebugMode = $app->getConfig()->get('debug', false);

			$request = $this->preProcessCallable($request, $route);
			try {
				$response = call_user_func($route->getCallable(), $request, $response);
			} catch (Exception $e) {
				/**
				 * We have already tried to handle this request with
				 * the default handler.  Now just return a response
				 **/
				$msg = 'An unhandled error occurred.';
				if ($isDebugMode) $msg .= '  ' . $e->getMessage();
				$response->getBody()->write($msg);

				return $response;
			}

			if ($response instanceof ResponseInterface) {
				return $next($request, $response);
			}

			return $response;
		};
	}

	/**
	 * Apply the error's status code and any related headers to the response object
	 *
	 * @param ResponseInterface $response
	 * @param Exception $exception
	 *
	 * @return ResponseInterface|static
	 */
	private function populateExceptionResponse(ResponseInterface $response, Exception $exception)
	{
		/**
		 * Add error status code and headers to response
		 */
		if ($exception instanceof \League\Route\Http\Exception\HttpExceptionInterface) {
			$response = $response->withStatus($exception->getStatusCode());

			foreach ($exception->getHeaders() as $name => $value) {
				$response = $response->withHeader($name, $value);
			}
		}

		return $response;
	}

	private function createExceptionRoute(ServerRequestInterface $request, Exception $exception)
	{
		$url = new Url($request->getUri());

		$route = new Route();
		$route->setScheme($url->getScheme());
		$route->setHost($url->getHost());
		$route->setMethods([$request->getMethod()]);
		$route->setPath($url->getPath());
		$route->setContainer($this->getContainer());

		/** @var \WebImage\Application\ApplicationInterface $app */
		$app = $this->getContainer()->get(\WebImage\Application\ApplicationInterface::class);
		$config = $app->getConfig();

		$handler = null;
		foreach($this->getHandlerKeys($exception) as $key) {
			$key = sprintf('router.handlers.%s', $key);
			$handler = $config->get($key);
			if (null !== $handler) $handler = RouteHelper::normalizeHandler($handler);
		}

		if (null === $handler) $handler = $this->getDefaultExceptionHandler();

		$route->setCallable($handler);

		return $route;
	}

	private function getHandlerKeys(Exception $exception)
	{
		$handlerKeys = ['exception']; // List of keys to look for in config to handle this exception

		if ($exception instanceof \League\Route\Http\Exception\HttpExceptionInterface) {
			// Use the status code as a possible handler key value
			array_unshift($handlerKeys, $exception->getStatusCode());
		}

		return $handlerKeys;
	}

	private function getDefaultExceptionHandler()
	{
		// ExceptionsController will be pulled from the ServiceManager
		return RouteHelper::normalizeHandler('ExceptionsController@exception');
	}
}