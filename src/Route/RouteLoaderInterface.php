<?php

namespace WebImage\Route;

use WebImage\Core\Collection;

/**
 * Defines a convenient way to load routes
 */
interface RouteLoaderInterface {
	/**
	 * Load routes
	 * $aRoutes is an array or associative array that defines a series of paths, methods, and handlers.
	 * Example:
	 * [
	 *   '/' => 'Home@index',
	 *
	 * ]
	 * @param array $aRoutes
	 * @return Collection
	 */
	public function load(array $aRoutes): RouteInfoCollection;
}