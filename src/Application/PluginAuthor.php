<?php

namespace WebImage\Application;

class PluginAuthor {
	/** @var string The name of the plugin author */
	private $name;
	/** @var string The author's email address */
	private $email;
	/** @var string The author's email */
	private $website;

	/**
	 * PluginAuthor constructor.
	 *
	 * @param string $name
	 * @param string $email
	 * @param string $website
	 */
	public function __construct($name, $email, $website)
	{
		$this->name = $name;
		$this->email = $email;
		$this->website = $website;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getEmail(): string
	{
		return $this->email;
	}

	/**
	 * @return string
	 */
	public function getWebsite(): string
	{
		return $this->website;
	}
}