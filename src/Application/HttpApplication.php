<?php

namespace WebImage\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Controllers\ExceptionsController;
use WebImage\Core\ArrayHelper;
use WebImage\Http\ServerRequest;
use WebImage\Route\ArrayRouteLoader;
use WebImage\Route\Router;
use WebImage\Route\RouterServiceProvider;
use WebImage\ServiceManager\ServiceManagerConfigInterface;
use WebImage\Session\SessionServiceProvider;
use WebImage\View\ViewFactoryServiceProvider;

class HttpApplication extends AbstractApplication {
	/**
	 * @inheritdoc
	 */
	public function run(): int
	{
        return $this->sendResponse();
	}

    protected function initialize()
    {
        parent::initialize();
        $this->autoLoadRoutes();
    }

    /**
     * Automatically load compiled routes if configured
     */
    protected function autoLoadRoutes(): void
    {
        $config = $this->getConfig();

        // Check if auto-loading is disabled
        if (isset($config['router']['autoLoad']) && !$config['router']['autoLoad']) {
            return;
        }

        // Get compiled file path
        $compiledFile = $config['router']['compiledFile'] ?? $this->getProjectPath() . '/config/routes.yaml.php';

        // Load if exists
        if (file_exists($compiledFile)) {
            $this->loadRoutesFromFile($compiledFile);
        } elseif (isset($config['router']['required']) && $config['router']['required']) {
            throw new \RuntimeException("Required routes file not found: {$compiledFile}");
        }
    }

    /**
     * Load routes from compiled PHP file
     *
     * @param string $file Path to compiled routes file
     */
    public function loadRoutesFromFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("Routes file not found: {$file}");
        }

        $routeArray = require $file;
        $loader = new ArrayRouteLoader();
        $routes = $loader->load($routeArray);
        $routes->injectRoutes($this->routes());
    }

	private function sendResponse(): int
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

        return 0;
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
            'router' => [
                'autoLoad' => true,  // Autoload compiled routes
                'required' => false, // Don't throw error if missing
                'routeFiles' => null, // Will default to /app/config/routes.yaml in compiler
                'compiledFile' => null // Will default to /app/config/routes.yaml.php in compiler
            ],
		]);
	}
}
