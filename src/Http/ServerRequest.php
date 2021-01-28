<?php

namespace WebImage\Http;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\ServerRequest as BaseServerRequest;

class ServerRequest extends BaseServerRequest {
	public function withAttributes(array $attributes)
	{
		$request = $this;
		foreach($attributes as $key => $val) {
			$request = $this->withAttribute($key, $val);
		}

		return $request;
	}

	/**
	 * Get the first value that we can find
	 *
	 * @param $name
	 * @param null $default
	 *
	 * @return mixed|null
	 */
//	public function get($name, $default = null)
//	{
//		$all = [
//			$this->post($name),
//			$this->query($name)
//		];
//
//		foreach($all as $val) {
//			if (null !== $val) {
//				return $val;
//			}
//		}
//
//		return $default;
//	}
//
//	public function post($name, $default = null)
//	{
//		$vars = $this->getParsedBody();
//
//		return isset($vars[$name]) ? $vars[$name] : $default;
//	}
//
//	public function query($name, $default = null)
//	{
//		$vars = $this->getQueryParams();
//
//		return isset($vars[$name]) ? $vars[$name] : $default;
//	}

	public static function fromGlobals()
	{
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		$headers = function_exists('getallheaders') ? getallheaders() : [];
		$uri = self::getUriFromGlobals();
		$body = new LazyOpenStream('php://input', 'r+');
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

		$serverRequest = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

		return $serverRequest
			->withCookieParams($_COOKIE)
			->withQueryParams($_GET)
			->withParsedBody($_POST)
			->withUploadedFiles(self::normalizeFiles($_FILES));
	}
}