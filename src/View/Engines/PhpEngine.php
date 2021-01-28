<?php

namespace WebImage\View\Engines;

use Exception;
use WebImage\View\ViewInterface;

class PhpEngine implements EngineInterface {
	/**
	 * @inheritdoc
	 */
	public function get($path, array $data, ViewInterface $view=null)
	{
		/**
		 * Remove $this from template file scope
		 * Adds $view to template
		 */
		$render = (function($__path, $__data) use ($view) {
			$vars = '[' . implode(', ', array_keys($__data)) . ']';
			ob_start();
			extract($__data, EXTR_SKIP);
			include($__path);

			return ltrim(ob_get_clean());
		})->bindTo(null); // Could potentially bind $this to $view...

		return $render($path, $data);
	}

	protected function handleException(Exception $e, $ob_level)
	{
		while (ob_get_level() > $ob_level) {
			ob_end_clean();
		}

		throw $e;
	}
}