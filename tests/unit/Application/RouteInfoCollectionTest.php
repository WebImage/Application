<?php declare(strict_types=1);

use WebImage\Application\ConsoleApplication;
use WebImage\Config\Config;

class RouteInfoCollectionTest extends \PHPUnit\Framework\TestCase
{
	public function testValidItemType()
	{
		$collection = new \WebImage\Route\RouteInfoCollection();
		$collection->add(new \WebImage\Route\RouteInfo('GET', '/', 'HomeController@home',  []));
		$this->assertInstanceOf(\WebImage\Route\RouteInfo::class, $collection[0]);
	}

	public function testInvalidItemType()
	{
		$this->expectException(\InvalidArgumentException::class);
		$collection = new \WebImage\Route\RouteInfoCollection();
		$collection->add('Invalid type');
	}
}