<?php

namespace WebImage\ServiceManager;

interface ServiceManagerAwareInterface {
	/**
	 * Get the service manager
	 *
	 * @return ServiceManagerInterface
	 */
	public function getServiceManager();

	/**
	 * Set the service manager
	 *
	 * @param ServiceManagerInterface $sm
	 */
	public function setServiceManager(ServiceManagerInterface $sm);
}