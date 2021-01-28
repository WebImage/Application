<?php

namespace WebImage\ServiceManager;

trait ServiceManagerAwareTrait {
	protected $serviceManager;

	/**
	 * Get the service manager
	 *
	 * @return ServiceManagerInterface
	 */
	public function getServiceManager()
	{
		return $this->serviceManager;
	}

	/**
	 * Set the service manager
	 *
	 * @param ServiceManagerInterface $sm
	 */
	public function setServiceManager(ServiceManagerInterface $serviceManager)
	{
		$this->serviceManager = $serviceManager;
	}
}