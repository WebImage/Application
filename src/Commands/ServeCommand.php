<?php

/**
 * Thanks, CodeIgniter 4
 */
namespace WebImage\Commands;

use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Console\FlexibleOption;
use WebImage\Console\InputInterface;
use WebImage\Console\OutputInterface;
use WebImage\Console\ValueOption;

class ServeCommand extends Command
{
	private int $portOffset = 0; // Start from PORT and increment in case of failure
	private int $tries = 10; // Max number of tries to start server using incremental $portOffset

	protected function configure(): void
	{
		$this->setName('serve');
		$this->setDescription('Run a PHP server');
		$this->addOption(ValueOption::optional('php', 'The PHP Binary', null, PHP_BINARY));
		$this->addOption(ValueOption::optional('host', 'The HTTP Host', null, 'localhost'));
		$this->addOption(ValueOption::optional('port', 'The HTTP Host Port', null, 8080));
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		// Collect any user-supplied options and apply them.
		$php  = escapeshellarg($input->getOption('php') == '' ? PHP_BINARY : $input->getOption('php'));
		$host = $input->getOption('host') == '' ? 'localhost' : $input->getOption('host');
		$port = (int) ($input->getOption('port') == '' ? 8080 : $input->getOption('port')) + $this->portOffset;

		// Get the party started.
		$output->writeln('WebImage development server started on http://' . $host . ':' . $port);
		$output->writeln('Press Control-C to stop.');

		// Set the Front Controller path as Document Root.
		$docRoot = escapeshellarg($this->getWebRoot());

		// Mimic Apache's mod_rewrite functionality with user settings.
		$rewrite = escapeshellarg(__DIR__ . '/rewrite.php');

		// Call PHP's built-in webserver, making sure to set our
		// base path to the public folder, and to use the rewrite file
		// to ensure our environment is set and it simulates basic mod_rewrite.
		passthru($php . ' -S ' . $host . ':' . $port . ' -t ' . $docRoot . ' ' . $rewrite, $status);

		if ($status && $this->portOffset < $this->tries) {
			$this->portOffset++;

			return $this->execute($input, $output);
		}

		return 0;
	}

	private function getWebRoot(): string
	{
		$config = $this->getAppConfig();
		if (!$config->has('app') || !$config->get('app')->has('webroot')) throw new \RuntimeException('Set app.webroot to the public absolute web path');
		else if (!file_exists($config->get('app.webroot'))) throw new \RuntimeException('app.webroot path does not exist: ' . $config->get('app.webroot'));

		return $config->get('app.webroot');
	}

	private function getAppConfig(): Config
	{
		return $this->getContainer()->get(ApplicationInterface::class)->getConfig();
	}
}