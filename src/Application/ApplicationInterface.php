<?php

namespace WebImage\Application;

use WebImage\Config\Config;
use WebImage\ServiceManager\ServiceManagerAwareInterface;
use WebImage\ServiceManager\ServiceManagerConfigInterface;

interface ApplicationInterface extends ServiceManagerAwareInterface {
    const RUN_STATUS_OKAY = 0;
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
	 * @return int
	 */
	public function run(): int;

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
	public function getProjectPath(): string;

	/**
	 * Set the project path to use, where all config, src, etc. directories reside
	 * @param string $path
	 * @return void
	 */
	public function setProjectPath(string $path): void;

	/**
	 * Get the root path for the core (this) library
	 * @return mixed
	 */
	public function getCorePath();
}