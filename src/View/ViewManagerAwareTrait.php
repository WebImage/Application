<?php

namespace WebImage\View;

trait ViewManagerAwareTrait
{
	private $_viewManager;
	/**
	 * @inheritdoc
	 */
	public function getViewManager(): ViewManager
	{
		return $this->_viewManager;
	}

	/**
	 * @inheritdoc
	 */
	public function setViewManager(ViewManager $viewManager)
	{
		$this->_viewManager = $viewManager;
	}
}