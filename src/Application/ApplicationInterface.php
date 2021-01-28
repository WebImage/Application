<?php

namespace WebImage\Application;

use WebImage\Config\Config;
use WebImage\ServiceManager\ServiceManagerAwareInterface;
use WebImage\ServiceManager\ServiceManagerConfigInterface;

interface ApplicationInterface extends ServiceManagerAwareInterface {
	const CONFIG_SERVICE_MANAGER = 'serviceManager';

	/**
	 * Get the application configuration
	 *
	 * @return Config
	 */
	public function getConfig();

	/**
	 * Executes an application to completion
	 *
	 * @return null
	 */
	public function run();

	/**
	 * Register an application plugin
	 *
	 * @param PluginInterface $plugin
	 * @return ApplicationInterface
	 */
	public function registerPlugin(PluginInterface $plugin);
	/**
	 * The path to a specific plugin's directory (where plugin.json lies)
	 *
	 * @param string $pluginDir A relative path from app/plugins (preferred), or an absolute path
	 *
	 * @return mixed
	 */
//	public function registerPlugin($pluginDir);

	/**
	 * Get the path to the project root files
	 *
	 * @return mixed
	 */
	public function getProjectPath();

	/**
	 * Get the root path for the core (this) library
	 * @return mixed
	 */
	public function getCorePath();
}