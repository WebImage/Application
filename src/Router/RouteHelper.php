<?php

namespace WebImage\Router;

class RouteHelper {
	/**
	 * Normalize string name to conform with underlying League version of route, which is in the format 'Controller::action'
	 *
	 * @param mixed $handler
	 * @return mixed
	 */
	public static function normalizeHandler($handler)
	{
		$leagueActionSeparator = '::';
		if (is_string($handler)) {
			$handler = str_replace('@', $leagueActionSeparator, $handler);
//			$handler = self::expandShorthand($handler, $leagueActionSeparator);
		}

		return $handler;
	}

//	private static function expandShorthand(string $handler, string $leagueActionSeparator)
//	{
//		list($controller, $action) = array_pad(explode($leagueActionSeparator, $handler, 2), 2, null);
//		if (null === $action) return $controller;
//
//		if (substr($controller, -10) != 'Controller' && false === strpos($controller, '/')) {
//			$controller = 'App\\Controllers\\' . $controller . 'Controller';
//		}
//
//		return $controller . $leagueActionSeparator . $action;
//	}
}