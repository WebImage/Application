<?php

namespace WebImage\View;

use League\Container\Definition\ClassDefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Paths\PathManager;
use WebImage\Paths\PathManagerInterface;
use WebImage\View\Engines\PhpEngine;

class FactoryServiceProvider extends AbstractServiceProvider {
	const CONFIG_VIEWS = 'views';
	const CONFIG_PATHS = 'paths';
	const CONFIG_EXTENSIONS = 'extensions';
	const CONFIG_ENGINES = 'engines';
	const CONFIG_VARIATIONS = 'variations';

	protected $provides = [
		Factory::class,
		ViewFinderInterface::class,
		EngineResolver::class,
		PhpEngine::class
	];

	/**
	 * Register provided classes with container
	 *
	 * @return void
	 */
	public function register()
	{
		$config = $this->getViewConfig();

		$this->registerViewFactory($config);
		$this->registerViewFinder($config);
		$this->registerEngineResolver($config);
		$this->registerPhpEngine($config);
	}

	/**
	 * Register the View/Factory class with the container
	 *
	 * @return void
	 */
	protected function registerViewFactory(Config $config)
	{
		/** @var ClassDefinitionInterface $def */
		$def = $this->getContainer()
			->share(Factory::class, Factory::class)
			->withArguments([
				ViewFinderInterface::class,
				EngineResolver::class
			]);

//		https://olegkrivtsov.github.io/using-zend-framework-3-book/html/en/Role_Based_Access_Control/Adding_Access_Controller_Plugin_and_View_Helper.html
		// Laravel
//			Factory::composer($view, $callback, $priority
//				$composers = [];
//				foreach($views as $view) {
//					$composers[] = $this->addViewEvent($view, $callback, 'composing: ', $priority);
//				}
//		protected function addViewEvent($view, $callback, $prefix = 'composing: ', $priority = null)
//	{
//		$view = $this->normalizeName($view);
//
//		if ($callback instanceof Closure) {
//			$this->addEventListener($prefix.$view, $callback, $priority);
//
//			return $callback;
//		} elseif (is_string($callback)) {
//			return $this->addClassEvent($view, $callback, $prefix, $priority);
//		}
//	}
//
//		/**
//		 * Register a class based view composer.
//		 *
//		 * @param  string    $view
//		 * @param  string    $class
//		 * @param  string    $prefix
//		 * @param  int|null  $priority
//		 * @return \Closure
//		 */
//		protected function addClassEvent($view, $class, $prefix, $priority = null)
//	{
//		$name = $prefix.$view;
//
//		// When registering a class based view "composer", we will simply resolve the
//		// classes from the application IoC container then call the compose method
//		// on the instance. This allows for convenient, testable view composers.
//		$callback = $this->buildClassEventCallback($class, $prefix);
//
//		$this->addEventListener($name, $callback, $priority);
//
//		return $callback;
//	}
//		$view['form']->render($form);
//		$view->helpers('form')->render($form);
//		$view->helpers('form')

		// Add supported extensions
		$extensions = isset($config[self::CONFIG_EXTENSIONS]) ? $config[self::CONFIG_EXTENSIONS] : new Config();

		foreach($extensions as $extension => $engineKey) {
			$def->withMethodCall('addExtension', [$extension, $engineKey]);
		}
	}

	/**
	 * Register template engine resolver
	 *
	 * @return void
	 */
	protected function registerEngineResolver(Config $config)
	{
		$this->getContainer()->share(EngineResolver::class, function() use ($config)
		{
			$resolver = new EngineResolver();

			$engines = isset($config[self::CONFIG_ENGINES]) ? $config[self::CONFIG_ENGINES] : [];

			foreach($engines as $engine => $engineResolver) {
				$resolver->register($engine, $engineResolver);
			}

			return $resolver;
		});
	}

	/**
	 * Register the ViewFinderInterface with the container
	 *
	 * @return void
	 */
	protected function registerViewFinder(Config $config)
	{
		$this->getContainer()->share(ViewFinderInterface::class, function() use ($config)
		{
			$pathManager = $this->getViewPathManager($config);

			$finder = new FileViewFinder($pathManager);

			$extensions = isset($config[self::CONFIG_EXTENSIONS]) ? $config[self::CONFIG_EXTENSIONS] : new Config();
			foreach($extensions as $extension => $engineKey) {
				$finder->addExtension($extension);
			}

			// Add supported variations
			$variations = isset($config[self::CONFIG_VARIATIONS]) ? $config[self::CONFIG_VARIATIONS] : new Config();
			foreach($variations as $variation) {
				$finder->addVariation($variation);
			}

			return $finder;
		});
	}

	/**
	 * Build up a functioning view path manager
	 *
	 * @return PathManager
	 */
	protected function getViewPathManager(Config $config)
	{
		/** @var PathManager $pathManager */
		$pathManager = $config['usePathManager'] === false ? new PathManager() : $this->getContainer()->get(PathManagerInterface::class);

		$resourcePath = isset($config['appendResourceDirectory']) ? $config['appendResourceDirectory'] : 'resources';
		$viewPath = isset($config['appendViewDirectory']) ? $config['appendResourceDirectory'] : 'views';

		$resourcePath = trim($resourcePath, '/');
		$viewPath = trim($viewPath, '/');

		// Setup view path manager with default paths
		$viewPathManager = $pathManager->withAppendedPath($resourcePath . '/' . $viewPath);
		// Get any additional hard-coded view paths
		$paths = $this->getViewPaths($config);

		foreach($paths as $path) {
			if (!$viewPathManager->has($path)) {
				$viewPathManager->add($path);
			}
		}

		return $viewPathManager;
	}

	/**
	 * Get the view configuration from the main app configuration
	 *
	 * @return Config
	 */
	protected function getViewConfig()
	{
		/** @var ApplicationInterface $app */
		$app = $this->getContainer()->get(ApplicationInterface::class);
		$appConfig = $app->getConfig();
		$viewConfig = isset($appConfig[self::CONFIG_VIEWS]) ? $appConfig[self::CONFIG_VIEWS] : new Config();

		$config = $this->getDefaultViewConfig();
		$config->merge($viewConfig);

		return $config;
	}

	protected function getDefaultViewConfig()
	{
		return new Config([
			'extensions' => ['php' => 'php'], // extension => engineKey
			'engines' => ['php' => 'WebImage\View\Engines\PhpEngine'] // engineKey => engine class
		]);
	}

	/**
	 * Get the list of paths where views reside
	 *
	 * @param Config $config
	 * @return Config|array
	 */
	protected function getViewPaths(Config $config)
	{
		return $config[self::CONFIG_PATHS] ? $config[self::CONFIG_PATHS] : [];
	}

	protected function registerPhpEngine()
	{
		$this->getContainer()->share(PhpEngine::class, PhpEngine::class);
	}
}