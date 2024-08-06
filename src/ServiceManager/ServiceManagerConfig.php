<?php

namespace WebImage\ServiceManager;

use League\Container\ServiceProvider\ServiceProviderInterface;
use WebImage\Config\Config;

class ServiceManagerConfig extends Config implements ServiceManagerConfigInterface
{
	public function __construct(Config $config = null)
	{
		parent::__construct([]);
		if ($config instanceof Config) {
			$this->merge($config);
		}
	}

	public function getInvokables()
	{
		return (isset($this[static::INVOKABLES])) ? $this->normalizeConfig($this[static::INVOKABLES]) : [];
	}

	public function getShared()
	{
		return (isset($this[static::SHARED])) ? $this->normalizeConfig($this[static::SHARED]) : [];
	}

	public function getProviders()
	{
		return (isset($this[static::PROVIDERS])) ? $this[static::PROVIDERS] : [];
	}

	public function configureServiceManager(ServiceManagerInterface $serviceManager)
	{
		$serviceManager->add('copyright', 'Copyright (c) ' . date('Y') . ' Corporate Web Image');

		foreach($this->getShared() as $alias => $concrete) {
			$serviceManager->addShared($alias, $concrete);
		}

		foreach($this->getInvokables() as $alias => $concrete) {
			$serviceManager->add($alias, $concrete);
		}

		foreach($this->getProviders() as $provider) {
			$serviceManager->addServiceProvider($this->getInstantiatedServiceProvider($serviceManager, $provider));
		}
	}

	/**
	 * Convenience method for instantiating class from ServiceManager or raw class name
	 * @param ServiceManagerInterface $serviceManager
	 * @param string $provider
	 * @return ServiceProviderInterface
	 */
	private function getInstantiatedServiceProvider(ServiceManagerInterface $serviceManager, string $provider): ServiceProviderInterface
	{
		return $serviceManager->has($provider) ? $serviceManager->get($provider) : new $provider;
	}

	/**
	 * Normalize config format to allow classes to be added to service stack
	 *
	 * @param iterable $config
	 *
	 * @return Config
	 */
	protected function normalizeConfig(iterable $config)
	{
		if (is_array($config)) $config = new Config($config);

		foreach($config as $alias => $concrete) {
			$concrete = $this->normalizeConcrete($concrete);
			if (is_numeric($alias)) {
				$config->del($alias);
				$alias = $concrete;
				$concrete = null;
			}
			$config->set($alias, $concrete);
		}

		return $config;
	}
	/**
	 * Put $concrete in a normalized usable format
	 *
	 * @param $concrete
	 * @return mixed
	 */
	protected function normalizeConcrete($concrete)
	{
		if ($concrete instanceof Config) {
			$concrete = $concrete->toArray();
		}

		return $concrete;
	}
}