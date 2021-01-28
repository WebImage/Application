<?php

namespace WebImage\Application;

use Psr\Log\InvalidArgumentException;
use WebImage\Core\Dictionary;
use WebImage\Core\Version;

class PluginManifest
{
	private $fileKey = 'manifestFile';
	/** @var PluginAuthor[] */
	private $authors = [];
	/** @var string */
	private $id;
	/** @var string */
	private $name;
	/** @var Version */
	private $version;
	/** @var string */
	private $description = '';
	/** @var string $root Path to plugin root */
	private $root;
	private $requiredPlugins = [];

	public function __construct(string $manifestFile)
	{
		$manifest = $this->parseManifestFile($manifestFile);

		$this->root = dirname($manifestFile);

		$this->id = $manifest->get('id');
		$this->name = $manifest->get('name');
		$this->description = $manifest->get('description', '');
		$this->version = Version::createFromString($manifest->get('version'));
		$this->loadAuthors($manifest);
		$this->loadRequirements($manifest);
	}

	/**
	 * @param string $manifestFile
	 *
	 * @return Dictionary
	 * @throws InvalidManifestException
	 */
	private function parseManifestFile(string $manifestFile)
	{
		if (!file_exists($manifestFile)) {
			throw new InvalidManifestException('Missing required plugin.json');
		}

		$manifestStr = file_get_contents($manifestFile);
		$manifest = empty($manifestStr) ? [] : json_decode($manifestStr, true);

		if (null === $manifest) {
			throw new InvalidManifestException('Unable to parse manifest file ' . $manifestFile);
		}

		$dManifest = new Dictionary($manifest);
		$dManifest->set($this->fileKey, $manifestFile);

		$this->validateKeys($manifest, ['id', 'name', 'version'], ['description', 'author', 'authors', 'requirements'], $dManifest);

		return $dManifest;
	}

	private function loadAuthors(Dictionary $manifest)
	{
		if (!$manifest->has('authors') && !$manifest->has('author')) $this->missingFieldException('author or authors', $manifest);

		if ($manifest->has('authors') && $manifest->has('author')) {
			throw new InvalidManifestException('Only author or authors value can exist, but not both');
		}

		$authors = $manifest->has('author') ? [$manifest->get('author')] : $manifest->get('authors');

		for($i=0; $i < count($authors); $i++) {
			$this->loadAuthor($manifest, $authors[$i], $i+1);
		}
	}

	private function loadAuthor(Dictionary $manifest, Dictionary $author, $pos)
	{
		$this->validateKeys($author->toArray(), ['name', 'email'], ['website'], $manifest, 'authors['.$pos.']');

		$this->authors[] = new PluginAuthor(
			$author->get('name'),
			$author->get('email'),
			$author->get('website')
		);
	}

	private function loadRequirements(Dictionary $manifest)
	{
		if (!$manifest->has('requirements')) return;
		$this->loadRequiredPlugins($manifest->get('requirements'), $manifest);
	}

	private function loadRequiredPlugins(Dictionary $requirements, Dictionary $manifest)
	{
		if (!$requirements->has('plugins')) return;

		/** @var string|string[]Dictionary[] $plugins */
		$plugins = $requirements->get('plugins');

		foreach($plugins as $plugin => $sVersion) {
			if (is_numeric($plugin)) $this->formatException('requirements.plugins must be in the format {"pluginId":"version"} where version is a minimum requirement in the format #.#.#', $manifest);
			$version = Version::createFromString($sVersion);
			$this->requiredPlugins[] = new RequiredPlugin($plugin, $version);
		}
	}

	private function validateKeys(array $var, array $requiredVars, array $allowedKeys, Dictionary $manifest, string $locationDescription='root')
	{
		$this->requiredKeys($var, $requiredVars, $manifest, $locationDescription);
		$this->allowedKeys($var, array_merge($requiredVars, $allowedKeys), $manifest, $locationDescription);
	}

	private function requiredKeys(array $var, array $keys, Dictionary $manifest, string $locationDescription)
	{
		foreach($keys as $requiredVar) {
			if (!isset($var[$requiredVar])) $this->missingFieldException($requiredVar, $manifest, $locationDescription);
		}
	}

	private function allowedKeys(array $var, array $keys, Dictionary $manifest, $locationDescription='root')
	{
		$diff = array_diff(array_keys($var), $keys);

		if (count($diff) > 0) $this->unexpectedFieldsException($diff, $manifest, $locationDescription);
	}

	private function missingFieldException(string $requiredVar, Dictionary $manifest, string $locationDescription='root')
	{
		$this->formatException(
			sprintf('Missing required field(s) %s at %s', $requiredVar, $locationDescription),
			$manifest
		);
	}

	private function unexpectedFieldsException(array $fields, Dictionary $manifest, string $locationDescription='root')
	{
		$this->formatException(
			sprintf('Unexpected field(s) %s at %s.', implode(', ', $fields), $locationDescription),
			$manifest
		);
	}

	private function formatException($msg, Dictionary $manifest)
	{
		$manifestFile = $manifest->get($this->fileKey);
		throw new InvalidManifestException(sprintf('Invalid plugin.json.  %s in %s', $msg, $manifestFile));
	}

	/**
	 * @return PluginAuthor[]
	 */
	public function getAuthors(): array
	{
		return $this->authors;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return Version
	 */
	public function getVersion(): Version
	{
		return $this->version;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getRoot(): string
	{
		return $this->root;
	}

	/**
	 * @return RequiredPlugin[]
	 */
	public function getRequiredPlugins(): array
	{
		return $this->requiredPlugins;
	}
}