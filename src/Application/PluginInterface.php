<?php

namespace WebImage\Application;

interface PluginInterface
{
	/**
	 * @return PluginManifest
	 */
	public function getManifest(): PluginManifest;

	/**
	 * Do all work required to make this plugin usable
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return void
	 */
	public function init(ApplicationInterface $app): void;

	/**
	 * Perform any required steps to install a plugin
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return void
	 */
	public function install(ApplicationInterface $app): void;

	/**
	 * Perform any required steps to reverse the installation process
	 *
	 * @param ApplicationInterface $app
	 *
	 * @return void
	 */
	public function uninstall(ApplicationInterface $app): void;
}