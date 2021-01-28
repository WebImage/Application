<?php

namespace WebImage\View;

interface ViewInterface {
	/**
	 * Set the vars
	 *
	 * @param array $data
	 * @return ViewInterface
	 */
	public function setData(array $data);

	/**
	 * Get the value for a specific var
	 *
	 * @param $name
	 * @param mixed $default null
	 * @return mixed
	 */
	public function get($name, $default=null);

	/**
	 * Set the value for a variable
	 *
	 * @param $name
	 * @param $value
	 * @return ViewInterface
	 */
	public function set($name, $value);
	/**
	 * Renders a view using vars
	 *
	 * @param array $data
	 * @return string
	 */
	public function render(array $data);
}