<?php

namespace WebImage\Commands;
/**
 * Interface for commands that can be configured after container injection
 */
interface ConfigurableCommandInterface
{
	/**
	 * Configure the command after dependencies have been injected
	 * This is called after the container is set (if applicable)
	 */
	public function configureCommand(): void;
}