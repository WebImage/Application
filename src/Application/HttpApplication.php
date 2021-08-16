<?php

namespace WebImage\Application;

use League\Route\Middleware\StackAwareInterface;
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
use WebImage\View\Factory as ViewFactory;
use WebImage\View\FactoryServiceProvider as ViewFactoryServiceProvider;

class HttpApplication extends AbstractApplication {
	/**
	 * @inheritdoc
	 */
	public function run()
	{
		parent::run();

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
	 */
	public function getRequest()
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
		return $this->getServiceManager()->get(Router::class);
	}

	/**
	 * @inheritdoc
	 */
	protected static function getDefaultServiceManagerConfig()
	{
		return ArrayHelper::merge(parent::getDefaultServiceManagerConfig(), [
			ServiceManagerConfig::SHARED => [
				ServerRequestInterface::class => [ServerRequest::class, 'fromGlobals'],
//				ResponseInterface::class => Response::class,
//				Router::class => Router::class
			],
			ServiceManagerConfig::PROVIDERS => [
				ViewFactoryServiceProvider::class,
				RouterServiceProvider::class
			],
			ServiceManagerConfig::INVOKABLES => [
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

	protected static function getDefaultConfig()
	{
		return array_merge_recursive(parent::getDefaultConfig(), [
			'app' => ['controllers' => ['namespace' => 'App\\Controllers']],
		]);
	}
}
