<?php

namespace WebImage\Console;

class TableRenderer
{
	private OutputInterface $output;
	private array $headers = [];
	private array $rows = [];
	private array $defaultStyles = [];
	private bool $hasHeaders = false;

	public function __construct(OutputInterface $output)
	{
		$this->output = $output;
	}

	/**
	 * Set table headers
	 */
	public function setHeaders(array $headers): self
	{
		$this->headers = $headers;
		$this->hasHeaders = true;
		return $this;
	}

	/**
	 * Set default styles for columns
	 */
	public function setDefaultStyles(array $styles): self
	{
		$this->defaultStyles = $styles;
		return $this;
	}

	/**
	 * Add a data row to the table
	 */
	public function addRow(array $row, array $styles = []): self
	{
		$this->rows[] = [
			'type' => 'row',
			'data' => $row,
			'styles' => array_merge($this->defaultStyles, $styles)
		];
		return $this;
	}

	/**
	 * Add a separator line between rows
	 */
	public function addSeparator(): self
	{
		$this->rows[] = [
			'type' => 'separator'
		];
		return $this;
	}

	/**
	 * Add multiple rows at once
	 */
	public function addRows(array $rows, array $styles = []): self
	{
		foreach ($rows as $row) {
			$this->addRow($row, $styles);
		}
		return $this;
	}

	/**
	 * Clear all queued rows and headers
	 */
	public function clear(): self
	{
		$this->rows = [];
		$this->headers = [];
		$this->hasHeaders = false;
		return $this;
	}

	/**
	 * Render the complete table
	 */
	public function render(): void
	{
		if (empty($this->rows) && !$this->hasHeaders) {
			return;
		}

		// Auto-detect headers from first row if not set
		if (!$this->hasHeaders && !empty($this->rows)) {
			$firstRow = $this->getFirstDataRow();
			if ($firstRow && is_array($firstRow['data']) && $this->isAssociativeArray($firstRow['data'])) {
				$this->headers = array_keys($firstRow['data']);
				$this->hasHeaders = true;
			}
		}

		$widths = $this->calculateColumnWidths();

		// Render table
		$this->drawTableBorder($widths);

		// Render headers if present
		if ($this->hasHeaders && !empty($this->headers)) {
			$this->drawTableRow($this->headers, $widths, []);
			$this->drawTableBorder($widths);
		}

		// Render rows
		foreach ($this->rows as $row) {
			if ($row['type'] === 'separator') {
				$this->drawTableBorder($widths);
			} elseif ($row['type'] === 'row') {
				$rowData = is_array($row['data']) ? array_values($row['data']) : [$row['data']];
				$this->drawTableRow($rowData, $widths, $row['styles']);
			}
		}

		$this->drawTableBorder($widths);
	}

	/**
	 * Render and clear the table
	 */
	public function renderAndClear(): void
	{
		$this->render();
		$this->clear();
	}

	/**
	 * Get the number of queued rows (excluding separators)
	 */
	public function getRowCount(): int
	{
		return count(array_filter($this->rows, fn($row) => $row['type'] === 'row'));
	}

	/**
	 * Check if table has any content to render
	 */
	public function hasContent(): bool
	{
		return !empty($this->rows) || $this->hasHeaders;
	}

	private function calculateColumnWidths(): array
	{
		$widths = [];

		// Include headers in width calculation
		if ($this->hasHeaders) {
			foreach ($this->headers as $i => $header) {
				$widths[$i] = mb_strlen((string)$header);
			}
		}

		// Include all row data in width calculation
		foreach ($this->rows as $row) {
			if ($row['type'] === 'row') {
				$rowData = is_array($row['data']) ? array_values($row['data']) : [$row['data']];
				foreach ($rowData as $i => $cell) {
					$cellLength = mb_strlen(strip_tags((string)$cell));
					$widths[$i] = max($widths[$i] ?? 0, $cellLength);
				}
			}
		}

		return $widths;
	}

	private function drawTableBorder(array $widths): void
	{
		$this->output->write('+');
		foreach ($widths as $width) {
			$this->output->write(str_repeat('-', $width + 2) . '+');
		}
		$this->output->writeln();
	}

	private function drawTableRow(array $row, array $widths, array $styles): void
	{
		$this->output->write('|');
		foreach ($row as $i => $cell) {
			$width = $widths[$i] ?? 0;
			$align = $styles[$i]['align'] ?? 'left';
			$color = $styles[$i]['color'] ?? null;
			$bg = $styles[$i]['bg'] ?? null;

			$cellStr = (string)$cell;

			// Apply formatting tags
			if ($color) {
				$cellStr = "<{$color}>{$cellStr}</{$color}>";
			}
			if ($bg) {
				$cellStr = "<{$bg}>{$cellStr}</{$bg}>";
			}

			// Calculate padding (accounting for formatting tags)
			$plainText = strip_tags(preg_replace('/<\w+>(.*?)<\/\w+>/', '$1', $cellStr));
			$padding = $width - mb_strlen($plainText);

			switch ($align) {
				case 'right':
					$this->output->write(' ' . str_repeat(' ', $padding) . $cellStr . ' |');
					break;
				case 'center':
					$leftPad = (int)floor($padding / 2);
					$rightPad = $padding - $leftPad;
					$this->output->write(' ' . str_repeat(' ', $leftPad) . $cellStr . str_repeat(' ', $rightPad) . ' |');
					break;
				default: // left
					$this->output->write(' ' . $cellStr . str_repeat(' ', $padding) . ' |');
					break;
			}
		}
		$this->output->writeln();
	}

	private function getFirstDataRow(): ?array
	{
		foreach ($this->rows as $row) {
			if ($row['type'] === 'row') {
				return $row;
			}
		}
		return null;
	}

	private function isAssociativeArray(array $array): bool
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}
}