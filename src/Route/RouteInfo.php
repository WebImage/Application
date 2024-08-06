<?php

namespace WebImage\Route;

use WebImage\Core\ArrayHelper;

class RouteInfo
{
	private string $method;
	private string $path;
	private string $handler;
	private array  $middlewares = [];

	/**
	 * @param string $method
	 * @param string $path
	 * @param string $handler
	 * @param array $middlewares
	 */
	public function __construct(string $method, string $path, string $handler, array $middlewares)
	{
		ArrayHelper::assertItemTypes($middlewares, 'string');
		$this->method      = $method;
		$this->path        = $path;
		$this->handler     = $handler;
		$this->middlewares = $middlewares;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getHandler(): string
	{
		return $this->handler;
	}

	public function getMiddlewares(): array
	{
		return $this->middlewares;
	}
}