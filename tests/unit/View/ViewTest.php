<?php

use PHPUnit\Framework\TestCase;
use WebImage\Application\HttpApplication;
use WebImage\Container\Container;
use WebImage\Paths\PathManager;
use WebImage\View\EngineResolver;
use WebImage\View\Engines\PhpEngine;
use WebImage\View\FileViewFinder;
use WebImage\View\ViewFactory;
use WebImage\View\View;
use WebImage\View\ViewFinderInterface;
use WebImage\View\ViewManager;
use WebImage\View\ViewNotFoundException;

class ViewTest extends TestCase
{
	public function testViewConstructor()
	{
		$view = new View(__DIR__ . '/../../resources/views/hello-name.php', ['name' => 'Name'], new PhpEngine());
		$this->assertEquals('Hello, Name!', $view->render(), 'View should render.');
	}

	public function testFoundView()
	{
		$vm = $this->createViewManager();

		$view = $vm->view('hello-name', ['name' => 'Name']);

		$this->assertEquals('Hello, Name!', $view->render(), 'View should render');
	}

	public function testViewVarsExist()
	{
		$view = new View(__DIR__ . '/../../resources/views/vars-list.php', ['var1' => 'First', 'var2' => 'Second', 'var3' => 'Third']);

		$this->assertEquals('[var1, var2, var3, helpers]', $view->render(), '$vars in template should return defined variables.');
	}

	public function testNonExistentView()
	{
		$this->expectException(ViewNotFoundException::class);
		$vm = $this->createViewManager();
		$view = $vm->view('non-existent-view', ['name' => 'Name']);

		$this->assertEquals('Hello, Name!', $view->render(), 'View should render');
	}

	private function createViewManager()
	{
		return new ViewManager($this->createViewFactory());
	}

	private function createViewFactory()
	{
		$finder = $this->createFileViewFinder();

		$engines = new EngineResolver();
		$engines->register('php', new PhpEngine());
		$factory = new ViewFactory($finder, $engines);
		$factory->addExtension('php', 'php');

		return $factory;
	}

	private function createFileViewFinder(): ViewFinderInterface
	{
		$finder = new FileViewFinder($this->createPathManager());
		$finder->addExtension('php');

		return $finder;
	}

	private function createPathManager()
	{
		$paths = new PathManager();
		$paths->add(__DIR__ . '/../../resources/views');

		return $paths;
	}
}