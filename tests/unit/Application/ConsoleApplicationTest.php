<?php declare(strict_types=1);

use WebImage\Application\ConsoleApplication;
use WebImage\Config\Config;

class ConsoleApplicationTest extends \PHPUnit\Framework\TestCase
{
	public function testApplicationConfigAppName()
	{
		$appName = 'Test Application';
		$appVersion = '1.2.3';

		$app = ConsoleApplication::create(new Config(), $appName, $appVersion);
		$this->assertEquals($app->getConfig()->get(ConsoleApplication::CONFIG_APP_NAME), $appName, 'Ensure that app name is set in config.');
		$this->assertEquals($app->getConfig()->get(ConsoleApplication::CONFIG_APP_VERSION), $appVersion, 'Ensure that app version is set in config.');
	}

	public function testConfigValuePersists()
	{
		$name = 'myTest';
		$value = 'myValue';

		$app = ConsoleApplication::create(new Config([$name => $value]));

		$this->assertEquals($app->getConfig()->get($name), $value, 'Ensure that config value persists.');
	}
}