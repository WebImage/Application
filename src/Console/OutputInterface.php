<?php

namespace WebImage\Console;

interface OutputInterface
{
	public function write(string $message): void;

	public function writeln(string $message = ''): void;

	public function info(string $message): void;

	public function success(string $message): void;

	public function warning(string $message): void;

	public function error(string $message): void;

	public function progressBar(int $total, ?callable $callback = null): ProgressBar;
}