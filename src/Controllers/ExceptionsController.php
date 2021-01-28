<?php

namespace WebImage\Controllers;

use League\Route\Http\Exception\HttpExceptionInterface;
use League\Route\Http\Exception\NotFoundException;
use WebImage\Application\ApplicationInterface;

class ExceptionsController extends AbstractController
{
	CONST ATTR_EXCEPTION = 'exception';

	private $exception;

	public function setError(\Exception $e)
	{
		$this->exception = $e;
	}

	public function exception()
	{
		return $this->view($this->getExceptionViewVars());
	}

	/**
	 * @return array
	 */
	protected function getExceptionViewVars()
	{
		return [
			'title' => $this->getTitle(),
			'message' => $this->getMessage(),
			'exception' => $this->getException(),
			'debug' => $this->isDebugging()
		];
	}

	protected function getTitle()
	{
		$exception = $this->getException();
		if ($exception instanceof HttpExceptionInterface) {
			return $exception->getStatusCode() . ' ' . $exception->getMessage();
		}

		return 'Oops!  There was an issue with your request.';
	}

	protected function getMessage()
	{
		$exception = $this->getException();
		if ($exception instanceof NotFoundException) {
			return 'The page you requested could not be found';
		}

		return 'An internal error occurred.  We have been notified and will fix this issue as soon as possible.  Please check back soon.';
	}

	/**
	 * @return HttpExceptionInterface|\Exception|null
	 */
	protected function getException()
	{
		return $this->getRequest()->getAttribute(self::ATTR_EXCEPTION);
	}

	/**
	 * Ensures that views always come from the path /resources/views/controllers/exceptions
	 * @return string
	 */
	protected function getControllerNameForView()
	{
		return 'exceptions';
	}

	protected function isDebugging()
	{
		/** @var ApplicationInterface $app */
		$app = $this->getContainer()->get(ApplicationInterface::class);

		return $app->getConfig()->get('debug', false);
	}
}