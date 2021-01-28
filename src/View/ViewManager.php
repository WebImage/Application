<?php

namespace WebImage\View;

use WebImage\Core\Dictionary;

class ViewManager {
	/**
	 * @var Factory
	 */
	private $factory;
	/** @var string The name of the region currently being worked on */
	private $currentRegion;
	/** @var string[] */
	private $regions = [];
	private $helpers;

	/**
	 * ViewManager constructor.
	 * @param Factory $factory
	 */
	public function __construct(Factory $factory)
	{
		$this->factory = $factory;
		$this->helpers = new Dictionary();
	}

	/**
	 * Create a new view
	 *
	 * @param string $view
	 * @param array $data
	 * @return View
	 */
	public function view(string $view, array $data=[])
	{
		return $this->factory->create($view, $data, $this);
	}

	/**
	 * Start capturing content to be used in another region
	 *
	 * @param $name
	 * @throws \RuntimeException
	 */
	public function startRegion($name)
	{
		if (null !== $this->currentRegion) {
			throw new \RuntimeException(sprintf('Cannot nest region %s inside of region %s', $name, $this->currentRegion));
		}

		$this->currentRegion = $name;
		$this->regions[$name] = '';
		ob_start();
	}

	/**
	 * End the current capture region
	 *
	 * @return void
	 */
	public function endRegion()
	{
		if (null === $this->currentRegion) throw new RuntimeException('No regions have been started');
		$this->regions[$this->currentRegion] = trim(ob_get_clean());
		$this->currentRegion = null;
	}

	/**
	 * Display content captured via $this->startRegion() / $this->endRegion
	 *
	 * @todo consider resetting regions
	 * @param $name
	 * @return string
	 */
	public function region($name)
	{
		if ($this->hasRegion($name)) {
			return $this->regions[$name];
		}
	}

	public function hasRegion($name)
	{
		return isset($this->regions[$name]);
	}

	/**
	 * Get helpers
	 *
	 * @return Dictionary
	 */
	public function helpers()
	{
		return $this->helpers;
	}

	/**
	 * Get a specific helper
	 *
	 * @param string $name
	 *
	 * @throws HelperNotFoundException When helper cannot be found
	 *
	 * @return mixed
	 */
	public function helper($name)
	{
		if (!$this->helpers()->has($name)) {
			throw new HelperNotFoundException('Missing view helper: ' . $name);
		}

		$service = $this->helpers()->get($name);
		$helper =  $this->factory->getContainer()->get($service);

		// Add ViewManager instance
		if ($helper instanceof ViewManagerAwareInterface) {
			$helper->setViewManager($this);
		}

		return $helper;
	}

	public function getFactory(): Factory
	{
		return $this->factory;
	}
}