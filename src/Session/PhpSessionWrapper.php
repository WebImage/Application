<?php

namespace WebImage\Session;

class PhpSessionWrapper implements SessionInterface
{
	private bool $_initialized = false;

	public function isInitialized(): bool
	{
		return $this->_initialized;
	}

	public function isAvailable(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}

	public function get(string $id): ?string
	{
		$this->init();
		if (!$this->has($id)) return null;

		return $_SESSION[$id];
	}

	public function set(string $id, string $value): void
	{
		$this->init();
		$_SESSION[$id] = $value;
	}

	public function has(string $id): bool
	{
		$this->init();
		return array_key_exists($id, $_SESSION);
	}

	public function del(string $id): bool
	{
		$this->init();
		if (!$this->has($id)) {
			return false;
		}
		unset($_SESSION[$id]);
	}


	public function destroy(): bool
	{
		$this->init();
		$this->_initialized = false;
		session_destroy();
		return true;
	}

	private function init(): void
	{
		if ($this->_initialized) return;
		else if (headers_sent()) throw new SessionException('Headers have already been sent');
		session_start();
		$this->_initialized = true;
	}
}
