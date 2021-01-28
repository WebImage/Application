<?php

namespace WebImage\View;

interface ViewFinderInterface {
	/**
	 * Find a view by name (or array of names)
	 *
	 * @param string|array $view
	 * @return FoundView|null
	 */
	public function find($view);

	/**
	 * Add source path for views
	 *
	 * @param string $path
	 *
	 * @return void
	 */
	public function addPath($path);

	/**
	 * Add a variation
	 *
	 * @param string $variation
	 * @return void
	 */
	public function addVariation($variation);

	/**
	 * Add a file extension
	 * @param $extension
	 * @return mixed
	 */
	public function addExtension($extension);
}