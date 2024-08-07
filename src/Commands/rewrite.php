<?php

// Thanks CodeIgniter 4
// Avoid this file run when listing commands
if (PHP_SAPI === 'cli') {
	return;
}

$uri = urldecode(
	parse_url('https://codeigniter.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// All request handle by index.php file.
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Full path
$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($uri, '/');

// If $path is an existing file or folder within the public folder
// then let the request handle it like normal.
if ($uri !== '/' && (is_file($path) || is_dir($path))) {
	return false;
}

unset($uri, $path);

// Otherwise, we'll load the index file and let
// the framework handle the request from here.
require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
// @codeCoverageIgnoreEnd