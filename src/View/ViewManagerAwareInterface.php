<?php

namespace WebImage\View;

interface ViewManagerAwareInterface
{
	/**
	 * Gets the view manager
	 * @return ViewManager
	 */
	public function getViewManager(): ViewManager;

	/**
	 * Sets the view manager
	 * @param ViewManager $viewManager
	 * @return mixed
	 */
	public function setViewManager(ViewManager $viewManager);
}