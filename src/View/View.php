<?php

namespace WebImage\View;

use ArrayAccess;
use League\Plates\Engine;
use Symfony\Component\Console\Exception\RuntimeException;
use WebImage\View\Engines\EngineInterface;

class View implements ViewInterface, ArrayAccess
{
	/** @var string */
	private $file;
	/** @var string */
	private $viewName;
	/** @var array */
	private $data = array();
	/** @var EngineInterface */
	private $engine;
	/** @var ViewManager */
	private $manager;
	/** @var ViewInterface */
	private $parent;

	/**
	 * View constructor.
	 *
	 * @param string $file Path to file
	 * @param array $data Key value data for template
	 * @param ViewManager|null $manager
	 * @param EngineInterface|null $engine
	 * @param null $viewName
	 */
	public function __construct(string $file, array $data, EngineInterface $engine=null, ViewManager $manager=null, string $viewName=null)
	{
		$this->file = $file;
		$this->data = $data;
		$this->engine = $engine;
		$this->manager = $manager ?: new ViewManager; // @todo new ViewManager will fail without engine constructor
		$this->viewName = $viewName;
	}

	/**
	 * @inheritdoc
	 */
	public function render(array $data=null)
	{
		$data = $this->mergedDataWithHelpers($data);

		$view = $this->getEngine()->get($this->getFile(), $data, $this);

		if (null !== $this->parent) {
			$data['content'] = $view;
			$view = $this->parent->render($data);
		}

		return $view;
	}

	/**
	 * @param $view
	 * @param array $data
	 *
	 * @return View The parent view
	 */
	public function extend($view, array $data=[])
	{
		if ($view == $this->getViewName()) throw new \RuntimeException(sprintf('%s cannot be nested within itself', $view));

		$this->parent = $this->view($view, $data);

		return $this->parent;
	}

	private function mergedDataWithHelpers(array $data=null)
	{
		$data = (null === $data) ? $this->data : array_merge($this->data, $data);

		$data['helpers'] = $this->helpers();

		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function setData(array $data)
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function get($name, $default = null)
	{
		return in_array($name, array_keys($this->data)) ? $this->data[$name] : $default;
	}

	/**
	 * @inheritdoc
	 */
	public function set($name, $value)
	{
		$this->data[$name] = $value;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @return ViewManager
	 */
	public function getManager()
	{
		return $this->manager;
	}

	/**
	 * @return EngineInterface
	 */
	public function getEngine()
	{
		if (null === $this->engine) {
			$this->engine = new Engines\PhpEngine();
		}

		return $this->engine;
	}

	/**
	 * @return string
	 */
	public function getViewName(): string
	{
		return $this->viewName;
	}

	/**
	 * @inheritdoc
	 */
	public function view($view, array $data=[])
	{
		$data = array_merge($this->data, $data);

		return $this->getManager()->view($view, $data);
	}

	/**
	 * @inheritdoc
	 */
	public function startRegion($name)
	{
		$this->getManager()->startRegion($name);
	}

	/**
	 * @inheritdoc
	 */
	public function endRegion()
	{
		$this->getManager()->endRegion();
	}

	/**
	 * @inheritdoc
	 */
	public function region($name)
	{
		return $this->getManager()->region($name);
	}

	/**
	 * @inheritdoc
	 */
	public function hasRegion($name)
	{
		return $this->getManager()->region($name);
	}

	/**
	 * @inheritdoc
	 */
	public function helpers()
	{
		return $this->getManager()->helpers();
	}

	/**
	 * @inheritdoc
	 */
	public function helper($name)
	{
		return $this->getManager()->helper($name);
	}

	/**
	 * Render view
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Check if a helper exists
	 *
	 * @param string $helperName
	 *
	 * @throws HelperNotFoundException
	 *
	 * @return mixed
	 */
	public function offsetExists($helperName)
	{
		return $this->getManager()->helpers()->has($helperName);
	}

	/**
	 * Gets a helper
	 *
	 * @param string $helperName
	 *
	 * @return string
	 */
	public function offsetGet($helperName)
	{
		return $this->helper($helperName);
	}

	/**
	 * INTENTIONALLY NOT IMPLEMENTED
	 *
	 * @param string $offset
	 *
	 * @throws \RuntimeException
	 */
	public function offsetSet($offset, $value)
	{
		throw new \RuntimeException('Cannot set helper here');
	}

	/**
	 * INTENTIONALLY NOT IMPLEMENTED
	 *
	 * @param string $offset
	 *
	 * @throws \RuntimeException
	 */
	public function offsetUnset($offset)
	{
		throw new \RuntimeException('Cannot unset helper');
	}

	public function __invoke($view, array $data=[])
	{
		return $this->view($view, $data);
	}
}