<?php

namespace WebImage\Console;

class ProgressBar
{
	private ConsoleOutput $output;
	private int           $total;
	private int       $current = 0;
	private int       $width   = 50;
	private /*?callable*/ $callback;

	public function __construct(ConsoleOutput $output, int $total, callable $callback = null)
	{
		$this->output   = $output;
		$this->total    = $total;
		$this->callback = $callback;
	}

	public function advance(int $step = 1): void
	{
		$this->current = min($this->current + $step, $this->total);
		$this->display();

		if ($this->callback) {
			call_user_func($this->callback, $this->current, $this->total);
		}
	}

	public function setProgress(int $current): void
	{
		$this->current = min($current, $this->total);
		$this->display();
	}

	public function finish(): void
	{
		$this->current = $this->total;
		$this->display();
		$this->output->writeln('');
	}

	private function display(): void
	{
		$percent = $this->total > 0 ? ($this->current / $this->total) * 100 : 0;
		$filled  = (int)($this->width * $percent / 100);
		$empty   = $this->width - $filled;

		$bar = str_repeat('=', $filled) . str_repeat(' ', $empty);
		$this->output->write("\r[{$bar}] {$this->current}/{$this->total} (" . number_format($percent, 1) . "%)");
	}
}
