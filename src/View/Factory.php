<?php
/**
 * View factory, heavily influenced by Laravel View Factory
 *
 * @author Robert Jones II <rjones@corporatewebimage.com>
 * @copyright Corporate Web Image, Inc. 2018
 **/
namespace WebImage\View;

use InvalidArgumentException;
use RuntimeException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use WebImage\Application\ApplicationInterface;
use WebImage\Core\Dictionary;
use WebImage\Event\ManagerInterface As EventManager;

class Factory implements ContainerAwareInterface {
	use ContainerAwareTrait;

	/**
	 * @var ViewFinderInterface
	 */
	private $finder;
	/**
	 * @var EngineResolver
	 */
	private $engines;
	/**
	 * @var string[]
	 */
	private $extensions = [];
	/**
	 * @var Dictionary ['viewName' => ViewBuilderInterface]
	 */
	private $builders;

	/**
	 * Factory constructor.
	 *
	 * @param ViewFinderInterface $finder
	 * @param EngineResolver $engines
	 */
	public function __construct(ViewFinderInterface $finder, EngineResolver $engines)
	{
		$this->finder = $finder;
		$this->engines = $engines;
		$this->builders = new Dictionary();
	}

	/**
	 * Create a full renderable View
	 *
	 * @param string|array $view A string view, or array of possible views
	 * @param array $data
	 *
	 * @return View
	 */
	public function create($view, array $data = [], ViewManager $manager=null)
	{
		if (null === $manager) $manager = $this->createViewManager();

//		$this->callCreator($view = new View($this, $this->getEngineFromPath($path), $view, $path, $data));
		$foundView = $this->findView($view);

		$view = new View(
			$path = $foundView->getView(),
			$data,
			$this->getEngineFromPath($path),
			$manager,
			$foundView->getViewName()
		);
//		if (null === $foundView) {
//			throw new ViewNotFoundException(sprintf('Unable to find view: %s', implode(', ', $views)));
//		}
		$this->buildView($view);

		$this->getEventManager()->trigger('view.created', $view, $this);

		return $view;
	}

	private function getEventManager(): EventManager
	{
		return $this->getContainer()->get(EventManager::class);
	}

	/**
	 * @param string|array $view
	 * @throws ViewNotFoundException
	 * @return null|FoundView
	 */
	public function findView($view): ?FoundView
	{
		$views = is_array($view) ? $view : [$view];

		$foundView = null;

		/**
		 * Use events to find view (short-circuits view finder)
		 */
		foreach($views as $checkView) {
			$responses = $this->getEventManager()->trigger('view.find', $view, $this);
			foreach($responses as $response) {
				if (null === $response) continue;
				if (!($response instanceof FoundView)) throw new \RuntimeException('Event handlers for view.find must return an instance of ' . FoundView::class);

				// Event handler returned a valid view
				return $response;
			}
		}

		if (null === $foundView) $foundView = $this->finder->find($view);

		return $foundView;
	}

	private function getEngineFromPath(string $path)
	{
		if (! $extension = $this->getExtension($path)) {
			throw new InvalidArgumentException('Unrecognized extension in file: ' . $path);
		}

		$engine = $this->extensions[$extension];

		return $this->engines->resolve($engine);
	}

	private function getExtension(string $path)
	{
		$extensions = array_keys($this->extensions);

		$best_match = null;
		$best_match_len = 0;

		foreach($extensions as $extension) {

			$extension_len = strlen($extension);
			$path_extension = substr($path, -$extension_len-1);

			if ($path_extension == '.'.$extension) {
				if (null === $best_match || $extension_len > $best_match_len) {
					$best_match = $extension;
					$best_match_len = $extension_len;
				}
			}
		}

		return $best_match;
	}

	public function addExtension(string $extension, string $engine, $resolver=null)
	{
		$this->finder->addExtension($extension);

		if (null !== $resolver) {
			$this->engines->register($engine, $resolver);
		}

		unset($this->extensions[$extension]);

		$this->extensions[$extension] = $engine;
	}

	private function buildView(View $view)
	{
		if (!$this->builders->has($view->getViewName())) return;
//
//		$builderClass = $this->getContainer()->get($this->builders->get($view->getViewName()));
//		/** @var ViewBuilderInterface $builder */
//		$builder = $this->getContainer()->get($builderClass);
//		$builder->buildView($view);
	}

	public function addBuilder(string $view, /*string | ViewBuilderInterface */$viewBuilder)
	{
		$this->builders->set($view, $viewBuilder);
	}

	/**
	 * Get registered extension-to-engine mapping
	 *
	 * @return string[]
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	private function createViewManager()
	{
		/** @var ApplicationInterface $app */
		$app = $this->getContainer()->get(ApplicationInterface::class);
		$helpers = $app->getConfig()->get('views.helpers', []);
		$manager = new ViewManager($this);

		foreach($helpers as $name => $helper) {
			$manager->helpers()->set($name, $helper);
		}

		return $manager;
	}
}