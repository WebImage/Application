<?php

namespace WebImage\View;

interface ViewFinderInterface {
	/**
	 * Find a view by name (or array of names)
	 *
	 * @param string|array $view
	 * @return FoundView|null
	 */
	public function find($view): ?FoundView;

	/**
	 * Add source path for views
	 *
	 * @param string $path
	 *
	 * @return void
	 */
	public function addPath(string $path): void;

	/**
	 * Add a variation
	 *
	 * @param string $variation
	 * @return void
	 */
	public function addVariation(string $variation): void;

	/**
	 * Add a file extension
	 * @param $extension
	 */
	public function addExtension($extension): void;
}