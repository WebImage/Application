<?php

namespace WebImage\Route\Strategy;

use GuzzleHttp\Psr7\HttpFactory;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\Route as LeagueRoute;
use League\Route\Strategy\ApplicationStrategy as BaseApplicationStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebImage\Controllers\AbstractController;
use WebImage\Controllers\ControllerInterface;
use WebImage\Controllers\ExceptionsController;
use WebImage\Route\Route;
use WebImage\View\ViewInterface;

class ApplicationStrategy extends BaseApplicationStrategy implements ContainerAwareInterface {
	use ContainerAwareTrait;

	public function invokeRouteCallable(LeagueRoute $route, ServerRequestInterface $request): ResponseInterface
	{
		$handler = $route->getCallable($this->getContainer());

		if (is_array($handler) && is_object($handler[0])) {
			if ($handler[0] instanceof ControllerInterface) {
				try {
					$handler[0]->setDispatchedActionName($handler[1]);
				} catch (\Throwable $e) {
					echo $e->getMessage() . '<br>';
					die(__FILE__ . ':' . __LINE__ . PHP_EOL);
				}

				$handler[0]->setRequest($request);
			}

			if ($handler[0] instanceof ContainerAwareInterface) {
				$handler[0]->setContainer($this->getContainer());
			}
		}

		return $this->normalizeHandlerResponse($handler($request, $route->getVars()), $handler);
	}

	/**
	 * @inheritdoc
	 */
//	public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
//	{
//		return $this->exceptionResponse($exception);
//	}

	/**
	 * Normalized a mixed
	 * @param mixed $result
	 * @return ResponseInterface
	 */
	public function normalizeHandlerResponse($result, $handler): ResponseInterface
	{
		/**
		 * Prepare response object
		 */
		if ($result instanceof ResponseInterface) {
			$response = $result;
		} else {
			if (is_array($handler) && count($handler) > 0 && is_object($handler[0]) && $handler[0] instanceof ControllerInterface) {
				$response = $handler[0]->getResponse();
			} else {
				$httpFactory = new HttpFactory();
				$response = $httpFactory->createResponse();
			}
		}

		/**
		 * Set body of response
		 */
		if (is_array($result)) {
			$response = $response->withAddedHeader('Content-type', 'application/json');
			$response->getBody()->write(json_encode($result));
		} else if (is_string($result)) {
			$response->getBody()->write($result);
		} else if ($result instanceof ViewInterface) {
			$response->getBody()->write($result->render());
		} else if (!($result instanceof ResponseInterface)) {
			throw new \Exception('Invalid result');
		}

		return $response;
	}

	/**
	 * @inheritdoc
	 */
	public function getThrowableHandler(): MiddlewareInterface
	{
		return new class($this) implements MiddlewareInterface
		{
			private $strategy;

			/**
			 *  constructor.
			 * @param ApplicationStrategy $strategy
			 */
			public function __construct(ApplicationStrategy $strategy)
			{
				$this->strategy = $strategy;
			}

			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
			{
				try {
					$response = $handler->handle($request);
				} catch (\Throwable $exception) {

					$route = new Route($request->getMethod(), $request->getUri()->getPath(), ExceptionsController::class . '::exception');
					$isDebugMode = true;

					try {
						$handler = $route->getCallable($this->strategy->getContainer());

						if (is_array($handler) && is_object($handler[0])) {
							if ($handler[0] instanceof ControllerInterface || $handler[0] instanceof AbstractController) { // @TODO for some reason instanceof ControllerInterface returns false, even though AbstractController implements it

								$request = $request->withAttribute(ExceptionsController::ATTR_EXCEPTION, $exception);

								$handler[0]->setRequest($request);
								$handler[0]->setDispatchedActionName($handler[1]);

								if ($handler[0] instanceof ExceptionsController) {
									$handler[0]->setException($exception);
								}
							}
							if ($handler[0] instanceof ContainerAwareInterface) {
								$handler[0]->setContainer($this->strategy->getContainer());
							}
						}

						$response = $this->strategy->normalizeHandlerResponse($handler($request), $handler);
					} catch (\Throwable $e) {
						/**
						 * We have already tried to handle this request with
						 * the default handler.  Now just return a response
						 **/
						$msg = 'An unhandled error occurred.';
						if ($isDebugMode) $msg .= '  ' . $e->getMessage();
						$httpFactory = new HttpFactory();
						$response = $httpFactory->createResponse(500);
						$response->getBody()->write($msg);
					}
				}

				return $response;
			}
		};
	}
}
