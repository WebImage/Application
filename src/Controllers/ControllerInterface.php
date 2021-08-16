<?php

namespace WebImage\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface {
	/**
	 * Gets the "action" called by the dispatcher
	 * @return string
	 */
	public function getDispatchedActionName(): string;

	/**
	 * Sets the name used by the dispatcher to call call the appropriate method
	 * @param string $action
	 * @return string
	 */
	public function setDispatchedActionName(string $action): void;

	public function getRequest(): ServerRequestInterface;
	public function setRequest(ServerRequestInterface $request);

	public function getResponse(): ResponseInterface;
	public function setResponse(ResponseInterface $response);
}