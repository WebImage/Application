<?php

namespace WebImage\ServiceManager;

interface ServiceManagerConfigInterface {
	const INVOKABLES = 'invokables';
	const SHARED = 'shared';
	const INFLECTORS = 'inflectors';
	const PROVIDERS = 'providers';
	/**
	 * Configure a service manager
	 *
	 * @param ServiceManager $serviceManager
	 * @return mixed
	 */
	public function configureServiceManager(ServiceManagerInterface $serviceManager);
}