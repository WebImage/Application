<?php

namespace WebImage\Router\Strategy;

//use Exception;
//use Illuminate\Console\Application;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Container\ContainerAwareInterface as ContainerContainerAwareInterface;
//use League\Route\Http\Exception\MethodNotAllowedException;
//use League\Route\Http\Exception\NotFoundException;
//use League\Route\Route As LeagueRoute;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route as LeagueRoute;
use League\Route\Strategy\ApplicationStrategy as BaseApplicationStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebImage\Controllers\ControllerInterface;
use WebImage\Controllers\ExceptionsController;
use WebImage\Router\Route;
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

		return $this->normalizeHandlerResponse($handler($request, $route->getVars()));
	}

	/**
	 * @inheritdoc
	 */
	public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
	{
		return $this->exceptionResponse($exception);
	}

	/**
	 * Normalized a mixed
	 * @param mixed $result
	 * @return ResponseInterface
	 */
	public function normalizeHandlerResponse($result)
	{
		$httpFactory = $result instanceof ResponseInterface ? null : new HttpFactory();

		if (is_array($result)) {
			$response = $httpFactory->createResponse();
			$response = $response->withAddedHeader('Content-type', 'application/json');
			$response->getBody()->write(json_encode($result));
		} else if (is_string($result)) {
			$response = $httpFactory->createResponse();
			$response->getBody()->write($result);
		} else if ($result instanceof ViewInterface) {
			$response = $httpFactory->createResponse();
			$response->getBody()->write($result->render());
		} else if ($result instanceof ResponseInterface) {
			$response = $result;
		} else {
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

						$response = $this->strategy->normalizeHandlerResponse($handler($request));
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