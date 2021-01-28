<?php

namespace WebImage\Controllers;

use League\Route\Http\Exception\NotFoundException;

class StaticFileController {
	private $root, $reqAttrName;
	public function __construct($pathRoot, $reqAttrName='path')
	{
		$this->root = rtrim($pathRoot, '/');
		$this->reqAttrName = $reqAttrName;
	}

	public function handlePage(\Psr\Http\Message\ServerRequestInterface $req, \Psr\Http\Message\ResponseInterface $res)
	{
		$path = $this->systemPath($req);

		if (!file_exists($path)) throw new NotFoundException();

		ob_start();
		include($path);
		$res->getBody()->write(ob_get_clean());

		return $res;
	}

	private function systemPath($req)
	{
		$path = '/' . ltrim($req->getAttribute($this->reqAttrName), '/');

		if ($path == '/') $path .= 'index.php';
		else $path .= '.php';

		$root = realpath($this->root);
		$full_path = realpath($root . $path);

		// Make sure that we are actually in the directory that we are supposed to be in...
		if (substr($full_path, 0, strlen($root)) != $root) throw new \RuntimeException('Unable to load path');

		return $this->root . $path;
	}

	public static function create($pathRoot, $reqAttrName='path')
	{
		$s = new self($pathRoot, $reqAttrName);

		return [$s, 'handlePage'];
	}
}