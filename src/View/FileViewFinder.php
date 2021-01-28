<?php

namespace WebImage\View;

use RuntimeException;
use WebImage\Paths\PathManager;

class FileViewFinder implements ViewFinderInterface {
	private static $EXCLUDE_PROFILES_CHAR = '!';
	/**
	 * @var string[]
	 */
	protected $extensions = [];
	/**
	 * @var string[]
	 */
	protected $profiles = [];
	/**
	 * @var PathManager
	 */
	protected $paths;
	/**
	 * @var string[] Cache of previously found views
	 */
	protected $views;

	public function __construct(PathManager $paths)
	{
		$this->paths = $paths;
	}

	/**
	 * @inheritdoc
	 */
	public function find($view)
	{
		$views = is_array($view) ? $view : [$view];
		$primaryView = $views[0];

		if (isset($this->views[$primaryView])) {
			return $this->views[$primaryView];
		}

		$filesViews = $this->getPossibleFilePaths($views);
		list($viewFile, $viewName) = $this->firstExisting($filesViews);
		$viewName = $viewName ?: $primaryView; // Default back to primary view key if nothing is found

		return $this->views[$viewName] = null === $viewFile ? null : new FoundView($viewName, $viewFile);
	}

	private function firstExisting($filesViews)
	{
		foreach ($filesViews as $file => $viewName) {
			if (file_exists($file)) {
				return [$file, $viewName];
			}
		}
		return [null, null];
	}

	/**
	 * @inheritdoc
	 */
	public function addPath($location)
	{
		$this->paths->add($location);
	}

	/**
	 * @inheritdoc
	 */
	public function addVariation($variation)
	{
		$this->profiles[] = $variation;
	}

	/**
	 * @inheritdoc
	 */
	public function addExtension($extension)
	{
		if (($index = array_search($extension, $this->extensions)) !== false) {
			unset($this->extensions[$index]);
		}

		array_unshift($this->extensions, $extension);
	}

	/**
	 * @param string|string[]|array $view
	 * @return array [file] => viewName
	 */
	protected function getScoredPossiblePaths($view)
	{
		$views = is_array($view) ? $view : [$view];

		$viewPaths = array();

		foreach($views as $view) {
			$excludeProfiles = substr($view, -1) == self::$EXCLUDE_PROFILES_CHAR;
			if ($excludeProfiles) {
				$view = substr($view, 0, -1);
			}

			foreach ($this->paths->all() as $path) {
				$base = $path . '/' . $view;

				if (!$excludeProfiles) {
					foreach ($this->profiles as $profile) {
						foreach ($this->extensions as $extension) {
							$viewPath = sprintf('%s~%s.%s', $base, $profile, $extension);
							$viewPaths[$viewPath] = $view;
						}
					}
				}

				foreach ($this->extensions as $extension) {
					$viewPath = sprintf('%s.%s', $base, $extension);
					$viewPaths[$viewPath] = $view;
				}
			}
		}

		return $viewPaths;
	}

	/**
	 * @param string|string[]|array $view
	 * @return array [file] => viewName
	 */
	protected function getPossibleFilePaths($view)
	{
		return $this->getScoredPossiblePaths($view);
	}
}