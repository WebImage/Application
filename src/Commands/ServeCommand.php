<?php

/**
 * Thanks, CodeIgniter 4
 */
namespace WebImage\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebImage\Application\AbstractCommand;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;

class ServeCommand extends AbstractCommand
{
	private int $portOffset = 0; // Start from PORT and increment in case of failure
	private int $tries = 10; // Max number of tries to start server using incremental $portOffset

	protected function configure()
	{
		$this->setName('serve');
		$this->setDescription('Run a PHP server');
		$this->addOption('php', 'The PHP Binary [default: "PHP_BINARY"]');
		$this->addOption('host', 'The HTTP Host [default: "localhost"]');
		$this->addOption('port', 'The HTTP Host Port [default: "8080"]');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
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

			$this->run($input, $output);
		}
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