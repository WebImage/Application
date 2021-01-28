<?php

namespace WebImage\View;

class FoundView
{
	/** @var string */
	private $viewKey;
	/** @var string */
	private $view;

	/**
	 * FoundView constructor.
	 * @param string $viewKey
	 * @param string $view
	 */
	public function __construct($viewKey, $view)
	{
		$this->viewKey = $viewKey;
		$this->view = $view;
	}

	/**
	 * @return string
	 */
	public function getViewName(): string
	{
		return $this->viewKey;
	}

	/**
	 * @return string
	 */
	public function getView(): string
	{
		return $this->view;
	}
}