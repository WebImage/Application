<?php

namespace WebImage\Http\Exception;

use Exception;
use League\Route\Http\Exception as HttpException;

class InternalServerException extends HttpException
{
	/**
	 * Constructor
	 *
	 * @param string     $message
	 * @param \Exception $previous
	 * @param integer    $code
	 */
	public function __construct($message = 'Internal Server Error', Exception $previous = null, $code = 0)
	{
		parent::__construct(500, $message, $previous, [], $code);
	}
}
