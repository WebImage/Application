<?php

namespace WebImage\Route;

use WebImage\Core\ArrayHelper;

class RouteInfo
{
	private string  $method;
	private string  $path;
	private string  $handler;
	private array   $middlewares = [];
	private ?string $name;

	/**
	 * @param string $method
	 * @param string $path
	 * @param string $handler
	 * @param array $middlewares
	 */
	public function __construct(string $method, string $path, string $handler, array $middlewares, string $name = null)
	{
		ArrayHelper::assertItemTypes($middlewares, 'string');
		$this->method      = $method;
		$this->path        = $path;
		$this->handler     = $handler;
		$this->middlewares = $middlewares;
		$this->name        = $name;
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

	public function getName(): ?string
	{
		return $this->name;
	}
}