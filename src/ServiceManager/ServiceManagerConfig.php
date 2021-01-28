<?php

namespace WebImage\ServiceManager;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebImage\Config\Config;
use WebImage\Http\Response;
use WebImage\Http\ServerRequest;
use WebImage\Router\RouteCollection;
use WebImage\Router\RouteCollectionInterface;

class ServiceManagerConfig extends Config implements ServiceManagerConfigInterface
{
	public function __construct(Config $config = null)
	{
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
			$serviceManager->share($alias, $concrete);
		}

		foreach($this->getInvokables() as $alias => $concrete) {
			$serviceManager->add($alias, $concrete);
		}

		foreach($this->getProviders() as $provider) {
			$serviceManager->addServiceProvider($provider);
		}
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