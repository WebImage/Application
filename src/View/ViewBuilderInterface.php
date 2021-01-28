<?php

namespace WebImage\View;

interface ViewBuilderInterface
{
	/**
	 * Allows a view to be modified
	 * @param View $view
	 * @return void
	 */
	public function buildView(View $view);
}