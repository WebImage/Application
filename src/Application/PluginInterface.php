<?php

namespace WebImage\Application;

interface PluginInterface
{
	/**
	 * @return PluginManifest
	 */
	public function getManifest();

	/**
	 * Do all work required to make this plugin usable
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return null
	 */
	public function load(ApplicationInterface $app);

	/**
	 * Perform any required steps to install a plugin
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return null
	 */
	public function install(ApplicationInterface $app);

	/**
	 * Perform any required steps to reverse the installation process
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return mixed
	 */
	public function uninstall(ApplicationInterface $app);
}