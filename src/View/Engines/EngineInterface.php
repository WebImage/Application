<?php

namespace WebImage\View\Engines;

use WebImage\View\ViewInterface;

interface EngineInterface /* extends Laravel/View/Engines/Engine Interface */
{
	/**
	 * Get the evaluated contents of the view
	 *
	 * @param string $path
	 * @param array $data
	 * @return string
	 */
	public function get($path, array $data, ViewInterface $view=null);
}