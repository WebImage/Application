<?php

namespace WebImage\Console;

class ConsoleOutput implements OutputInterface
{
	private array $colors = [
		'reset'      => "\033[0m",
		'black'      => "\033[30m",
		'red'        => "\033[31m",
		'green'      => "\033[32m",
		'yellow'     => "\033[33m",
		'blue'       => "\033[34m",
		'magenta'    => "\033[35m",
		'cyan'       => "\033[36m",
		'white'      => "\033[37m",
		'bg_black'   => "\033[40m",
		'bg_red'     => "\033[41m",
		'bg_green'   => "\033[42m",
		'bg_yellow'  => "\033[43m",
		'bg_blue'    => "\033[44m",
		'bg_magenta' => "\033[45m",
		'bg_cyan'    => "\033[46m",
		'bg_white'   => "\033[47m",
		'bold'       => "\033[1m",
		'dim'        => "\033[2m",
		'underline'  => "\033[4m",
	];

	public function write(string $message): void
	{
		echo $this->formatMessage($message);
	}

	public function writeln(string $message = ''): void
	{
		echo $this->formatMessage($message) . "\n";
	}

	public function info(string $message): void
	{
		$this->writeln("<cyan>{$message}</cyan>");
	}

	public function success(string $message): void
	{
		$this->writeln("<green>{$message}</green>");
	}

	public function warning(string $message): void
	{
		$this->writeln("<yellow>{$message}</yellow>");
	}

	public function error(string $message): void
	{
		$this->writeln("<red>{$message}</red>");
	}

	private function formatMessage(string $message): string
	{
		// Simple tag-based formatting: <color>text</color>
		return preg_replace_callback('/<(\w+)>(.*?)<\/\1>/', function ($matches) {
			$tag  = $matches[1];
			$text = $matches[2];

			if (isset($this->colors[$tag])) {
				return $this->colors[$tag] . $text . $this->colors['reset'];
			}

			return $text;
		},                           $message);
	}

	public function progressBar(int $total, callable $callback = null): ProgressBar
	{
		return new ProgressBar($this, $total, $callback);
	}
}
