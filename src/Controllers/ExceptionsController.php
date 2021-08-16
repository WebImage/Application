<?php

namespace WebImage\Controllers;

use League\Route\ContainerAwareInterface;
use League\Route\Http\Exception\HttpExceptionInterface;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Application\ApplicationInterface;
use WebImage\View\ViewFinderInterface;

class ExceptionsController extends AbstractController implements ContainerAwareInterface
{
	CONST ATTR_EXCEPTION = 'exception';

	private $exception;

	public function setException(\Throwable $e): void
	{
		$this->exception = $e;
	}

	public function exception()
	{
		return $this->view($this->getExceptionViewVars(), null, $this->getMasterViewToUse());
	}

	/**
	 * Check if master view can be found and return.  Otherwise return false to indicate that the master view should not be used
	 * @return false|mixed|string
	 */
	private function getMasterViewToUse()
	{
		/** @var ViewFinderInterface $viewFinder */
		$viewFinder = $this->getContainer()->get(ViewFinderInterface::class);
		$masterView = $viewFinder->find($this->getMasterViewName());

		return null === $masterView ? false : $this->getMasterViewName();
	}

	/**
	 * @return array
	 */
	protected function getExceptionViewVars(): array
	{
		return [
			'title' => $this->getTitle(),
			'message' => $this->getMessage(),
			'exception' => $this->getException(),
			'debug' => $this->isDebugging()
		];
	}

	protected function getTitle(): string
	{
		$exception = $this->getException();
		if ($exception instanceof HttpExceptionInterface) {
			return $exception->getStatusCode() . ' ' . $exception->getMessage();
		}

		return 'Oops!  There was an issue with your request.';
	}

	protected function getMessage(): string
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
	protected function getException(): \Throwable
	{
		return $this->exception;
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