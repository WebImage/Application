<?php

namespace WebImage\ServiceManager;

use WebImage\Container\Container;

class ServiceManager extends Container implements ServiceManagerInterface {
	/**
	 * ServiceManager constructor.
	 * @param ServiceManagerConfigInterface|null $config
	 */
	public function __construct(ServiceManagerConfigInterface $config=null)
	{
		parent::__construct();
		if ($config instanceof ServiceManagerConfigInterface) {
			$config->configureServiceManager($this);
		}
	}

	protected function getFromThisContainer($alias, array $args = [])
	{
		$val = parent::getFromThisContainer($alias, $args);

		if ($val instanceof ServiceManagerAwareInterface) {
			$val->setServiceManager($this);
		}

		return $val;
	}

}