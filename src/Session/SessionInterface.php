<?php

namespace WebImage\Session;

interface SessionInterface
{
	/**
	 * Get a stored value as a string or NULL if not set
	 * @param string $id
	 * @return string|null
	 */
	public function get(string $id): ?string;
	/**
	 * Store a string value
	 * @param string $id
	 * @param string $value
	 * @return void
	 */
	public function set(string $id, string $value): void;
	/**
	 * Check if a value has been set for the specified $id
	 * @param string $id
	 * @return bool
	 */
	public function has(string $id): bool;
	/**
	 * @param string $id
	 * @return bool TRUE if the id was found and deleted, otherwise false
	 */
	public function del(string $id): bool;
	/**
	 * Completely get rid of the session and all associated keys
	 * @return bool TRUE if the session was previously initialized and destroyed or FALSE if not
	 */
	public function destroy(): bool;
}