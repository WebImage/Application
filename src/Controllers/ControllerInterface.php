<?php

namespace WebImage\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface {
	const DISPATCH_ACTION_ATTRIBUTE = '_dispatchActionAttribute';
	const DISPATCH_METHOD = 'handleRequest';
	public function handleRequest(ServerRequestInterface $req, ResponseInterface $res);
}