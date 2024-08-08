<?php

namespace WebImage\Application;

use WebImage\Config\Config;
use WebImage\Core\Version;
use WebImage\ServiceManager\ServiceManagerConfig;

abstract class AbstractPlugin implements PluginInterface
{
	/**
	 * Whether ::install() has already been run
	 * @var bool
	 */
	private bool $installRan = false;
	/**
	 * @property string
	 */
	private ?string $pluginPath = null;
	/**
	 * @property PluginManifest
	 */
	private ?PluginManifest $manifest = null;

	/**
	 * Get the root plugin path
	 * Typically this will be the directory where the plugin.json manifest file exists
	 *
	 * @return string
	 */
	public function getPluginPath(): string
	{
		if (null === $this->pluginPath) {
			$r = new \ReflectionObject($this);
			$dir = $rootDir = dirname($r->getFileName());

			while (!file_exists($dir.'/plugin.json')) {
				if ($dir === dirname($dir)) {
					throw new \RuntimeException(sprintf('%s is missing the required plugin.json file', $r->getName()));
				}
				$dir = dirname($dir);
			}
			$this->pluginPath = $dir;
		}

		return $this->pluginPath;
	}

	/**
	 * @inheritdoc
	 */
	public function load(ApplicationInterface $app): void
	{
		$config = $this->getConfig();

		if (null !== $config && !($config instanceof Config)) {
			throw new \RuntimeException(sprintf('%s was expecting an instance of %s', __METHOD__, Config::class));
		}

		if (null !== $config) {
			$app->getConfig()->merge($config);

			// If serviceManager config key is defined then load those configurations
			if ($config->has(ApplicationInterface::CONFIG_SERVICE_MANAGER)) {
				$serviceConfig = new ServiceManagerConfig($config->get(ApplicationInterface::CONFIG_SERVICE_MANAGER));
				$serviceConfig->configureServiceManager($app->getServiceManager());
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function install(ApplicationInterface $app): void
	{
		if ($this->installRan) throw new \RuntimeException('Install has already been run for ' . $this->getManifest()->getId());
		// Mark that install has been run to help prevent it from being run multiple times on the same pass
		$this->installRan = true;
	}

	/**
	 * @inheritdoc
	 */
	public function uninstall(ApplicationInterface $app): void
	{
	}

	/**
	 * @return Config|null
	 */
	protected function getConfig(): ?Config
	{
		$configPath = $this->getManifest()->getRoot() . '/config/config.php';
		if (!file_exists($configPath)) return null;

		$config = require($configPath);
		if (!is_array($config)) throw new \RuntimeException($configPath . ' should return an array');

		return new Config($config);
	}

	public function getManifest(): PluginManifest
	{
		if (null === $this->manifest) $this->initManifest();

		return $this->manifest;
	}

	protected function initManifest()
	{
		$path = $this->getPluginPath();
		$manifestPath = $path . '/plugin.json';
		$this->manifest = new PluginManifest($manifestPath);
	}
}