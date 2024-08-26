<?php

namespace WebImage\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Controllers\ExceptionsController;
use WebImage\Core\ArrayHelper;
use WebImage\Http\Response;
use WebImage\Http\ServerRequest;
use WebImage\Route\Router;
use WebImage\Route\RouterServiceProvider;
use WebImage\ServiceManager\ServiceManagerConfig;
use WebImage\ServiceManager\ServiceManagerConfigInterface;
use WebImage\Session\SessionServiceProvider;
use WebImage\View\ViewFactory;
use WebImage\View\FactoryServiceProvider as ViewFactoryServiceProvider;

class HttpApplication extends AbstractApplication {
	/**
	 * @inheritdoc
	 */
	public function run()
	{
		parent::run();
		$this->sendResponse();
	}

	private function sendResponse(): void
	{
		$router = $this->routes();
		$response = $router->dispatch($this->getRequest());

		if (!headers_sent()) {
			// Status response
			header(sprintf('HTTP/%s %s %s',
						   $response->getProtocolVersion(),
						   $response->getStatusCode(),
						   $response->getReasonPhrase())
			);
			// Headers
			foreach($response->getHeaders() as $header => $values) {
				foreach($values as $value) {
					header(sprintf('%s: %s', $header, $value));
				}
			}
		}

		echo $response->getBody();
	}

	/**
	 * Get Request
	 *
	 * @return ServerRequestInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getRequest(): ServerRequestInterface
	{
		return $this->getServiceManager()->get(ServerRequestInterface::class);
	}

	/**
	 * Get route collector
	 *
	 * @return Router
	 */
	public function routes(): Router
	{
		try {
			$router = $this->getServiceManager()->get(Router::class);
		} catch (NotFoundExceptionInterface $e) {
			die('Router failed');
		} catch (ContainerExceptionInterface $e) {
			die('Container failed');
		}

		return $router;
	}

	/**
	 * @inheritdoc
	 */
	protected static function getDefaultServiceManagerConfig(): array
	{
		return ArrayHelper::merge(parent::getDefaultServiceManagerConfig(), [
			ServiceManagerConfigInterface::SHARED => [
				ServerRequestInterface::class => [ServerRequest::class, 'fromGlobals'],
//				ResponseInterface::class => Response::class,
//				Router::class => Router::class
			],
			ServiceManagerConfigInterface::PROVIDERS => [
				ViewFactoryServiceProvider::class,
				RouterServiceProvider::class,
				SessionServiceProvider::class
			],
			ServiceManagerConfigInterface::INVOKABLES => [
				'ExceptionsController' => ExceptionsController::class
			]
//			ServiceManagerConfig::INFLECTORS => [
//				'LoggerAwareInterface' => [
//					'setLogger' => 'Some\Logger'
//				]
//				$container->inflector('LoggerAwareInterface')
//					->invokeMethod('setLogger', ['Some\Logger']); // Some\Logger will be resolved via the container

//			]
		]);
	}

	protected static function getDefaultConfig(): array
	{
		return array_merge_recursive(parent::getDefaultConfig(), [
			'app' => ['controllers' => ['namespace' => 'App\\Controllers']],
			'views' => ['helpers' => ['namedRoute' => \WebImage\View\Helpers\NamedRouteHelper::class]],
		]);
	}
}
