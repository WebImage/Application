<?php

declare(strict_types=1);

use WebImage\Application\ConsoleApplication;
use WebImage\Config\Config;
use WebImage\Route\ArrayRouteLoader;

class ArrayRouteLoaderTest extends \PHPUnit\Framework\TestCase
{
	public function testRouteInfoCollection()
	{
		$aRoutes = [];

		$loader = new ArrayRouteLoader();
		$routes = $loader->load($aRoutes);

		$this->assertInstanceOf(\WebImage\Route\RouteInfoCollection::class, $routes);
	}

	public function testSimplePathHandler()
	{
		$routes = [
			'/' => 'HomeController@index'
		];

		$loader = new ArrayRouteLoader();
		$routes = $loader->load($routes);

		$this->assertCount(1, $routes);
		$this->assertInstanceOf(\WebImage\Route\RouteInfo::class, $routes[0]);
	}

	public function testSimplePathMethodHandlers()
	{
		$aRoutes = [
			'/' => [
				'GET' => 'HomeController@index',
				'POST' => 'HomeController@create',
				'PUT' => 'HomeController@update',
				'DELETE' => 'HomeController@delete',
				'OPTIONS' => 'HomeController@options'
			]
		];

		$loader = new ArrayRouteLoader();
		$routes = $loader->load($aRoutes);

		$this->assertCount(5, $routes);
		$this->assertEquals('GET', $routes[0]->getMethod());
		$this->assertEquals('POST', $routes[1]->getMethod());
		$this->assertEquals('PUT', $routes[2]->getMethod());
		$this->assertEquals('DELETE', $routes[3]->getMethod());
		$this->assertEquals('OPTIONS', $routes[4]->getMethod());
	}

	public function testGroupedRoutesWithMiddleware()
	{
		$aRoutes = [
			'middleware' => [
				'apiAuthMiddleware',
				'authorizedUserMiddleware'
			],
			[
				'/' => 'HomeController@index'
			]
		];

		$loader = new ArrayRouteLoader();
		$routes = $loader->load($aRoutes);
		$this->assertCount(1, $routes);
		$this->assertCount(2, $routes->get(0)->getMiddlewares());
		$this->assertEquals('apiAuthMiddleware', $routes->get(0)->getMiddlewares()[0]);
		$this->assertEquals('authorizedUserMiddleware', $routes->get(0)->getMiddlewares()[1]);
	}
}