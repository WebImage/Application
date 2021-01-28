<?php
/**
 * Keeps track of and loads plugins.
 *
 * Lifecycle:
 * 1. $loader->register($directoryForPlugin)
 * 2. $loader->load($pluginId)
 */
namespace WebImage\Application;

use WebImage\Core\Dictionary;

class PluginLoader
{
	/**
	 * The base path from which relative paths will be appended
	 * @var string
	 */
	private $basePluginDir;
	/**
	 * Plugins that have been registered
	 *
	 * @var Dictionary
	 */
	private $registered;
	/**
	 * The plugins that have been loaded
	 * @var string[] Plugin IDs that have already been loaded
	 */
	private $loaded = [];

	/**
	 * PluginLoader constructor.
	 */
	public function __construct($basePluginDir)
	{
		$this->basePluginDir = rtrim($basePluginDir, '/');
		$this->registered = new Dictionary();
	}

	public function register(PluginInterface $plugin)
	{
		$id = $plugin->getManifest()->getId();

		if ($this->registered->has($id)) {
			throw new PluginRegisteredException(sprintf('The plugin %s has already been registered', $id));
		}

		$this->registered->set($id, $plugin);
	}

	public function load(ApplicationInterface $app)
	{
		/**
		 * Continue running until all registered plugins have been loaded
		 * Plugins themselves can call $app->registerPlugin(...) to load more plugins
		 */
		while (count($plugins = $this->getUnloadedPlugins()) > 0) {
			/**
			 * @var string $pluginId
			 * @var PluginInterface $plugin
			 */
			foreach ($plugins as $pluginId => $plugin) {
				$this->verifyRequirements($plugin);
				$plugin->load($app);
				$this->loaded[] = $pluginId;
				$app->getServiceManager()->share($pluginId, $plugin);
			}
		}
	}

	/**
	 * Returns all plugins that have not been loaded
	 *
	 * @return array [pluginId] => plugin
	 */
	private function getUnloadedPlugins()
	{
		$plugins = [];

		foreach ($this->registered as $pluginId => $plugin) {
			if (in_array($pluginId, $this->loaded)) continue;

			$plugins[$pluginId] = $plugin;
		}

		return $plugins;
	}

	/**
	 * Check that a plugin to be loaded has all of its requirements met
	 * @param PluginInterface $plugin
	 */
	private function verifyRequirements(PluginInterface $plugin)
	{
		foreach ($plugin->getManifest()->getRequiredPlugins() as $requiredPlugin) {
			/**
			 * Check that a required plugin is registered
			 */
			if ($this->registered->has($requiredPlugin->getId())) {
				/** @var PluginInterface $registeredPlugin */
				$registeredPlugin = $this->registered->get($requiredPlugin->getId());
				/**
				 * Verify that the required version is installed
				 */
				if ($requiredPlugin->getVersion()->compare($registeredPlugin->getManifest()->getVersion()) > 0) {
					throw new PluginRegisteredException(sprintf(
						'Plugin %s requires plugin %s version %s or higher (%s installed)',
						$plugin->getManifest()->getId(),
						$requiredPlugin->getId(),
						$requiredPlugin->getVersion(),
						$registeredPlugin->getManifest()->getVersion()
					));
				}
			} else {
				throw new PluginNotFoundException(sprintf('Plugin %s is missing required plugin %s', $plugin->getManifest()->getId(), $requiredPlugin->getId()));
			}
		}
	}
}