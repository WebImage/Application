<?php

namespace WebImage\Application;

use WebImage\Core\Version;

class RequiredPlugin
{
	/** @var string */
	private $id;
	/** @var Version */
	private $version;

	/**
	 * PluginRequiredPlugin constructor.
	 *
	 * @param string $id
	 * @param Version $version
	 */
	public function __construct($id, Version $version)
	{
		$this->id = $id;
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return Version
	 */
	public function getVersion(): Version
	{
		return $this->version;
	}
}