<?php declare(strict_types=1);

use GuzzleHttp\Psr7\BufferStream;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Application\HttpApplication;
use WebImage\Config\Config;
use WebImage\Http\Response;
use WebImage\Http\ServerRequest;

class HttpApplicationTest extends TestCase
{
//	public function testMismatchedRouteHasException()
//	{
//		$this->expectException(NotFoundException::class);
//		$app = HttpApplication::create(new Config([]));
//		$app->run();
//	}
//
//	public function testValidRouteResponseStatusCode()
//	{
//		$app = HttpApplication::create();
//		$routes = $app->routes();
//		$routes->get('/test', function(ServerRequestInterface $request, ResponseInterface $response) {
//			return $response;
//		});
//
//		$request = new ServerRequest('GET', '/test');
//		$response = $routes->dispatch($request);
//
//		$this->assertEquals(200, $response->getStatusCode(), 'Expecting 200 response.');
//	}

	public function testValidRouteResponseBody()
	{
		$app = HttpApplication::create(new Config(['debug' => true]));
		$routes = $app->routes();
		$routes->get('/test', function(ServerRequestInterface $request) {
			$factory = new HttpFactory();
			$response = $factory->createResponse();
			$body = new BufferStream();
			$body->write('Hello, World!');

			return $response->withBody($body);
		});

		$request = new ServerRequest('GET', '/test');
		$response = $routes->dispatch($request);

		$this->assertEquals('Hello, World!', $response->getBody()->getContents(), 'Expecting body to have value.');
	}
}