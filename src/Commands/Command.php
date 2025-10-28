<?php

namespace WebImage\Commands;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\DefinitionContainerInterface;
use Psr\Container\ContainerInterface;
use WebImage\Console\Argument;
use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;
use WebImage\Console\Option;

abstract class Command implements CommandInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	private string  $name = '';
	private string  $description = '';
	private ?string $group       = null;
	/** @var Argument[] */
	private array $arguments = [];
	/** @var Option[] */
	private array $options    = [];
	private bool    $configured = false;

	public function __construct(string $name = null)
	{
		if ($name) {
			$this->name = $name;
		}
		$this->configure();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function withName(string $name): CommandInterface
	{
		$instance = clone $this;
		$instance->name = $name;
		return $instance;
	}

	protected function setName(string $name): self
	{
		$this->name = $name;
		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function withDescription(string $description): CommandInterface
	{
		$instance = clone $this;
		$instance->description = $description;
		return $instance;
	}

	protected function setDescription(string $description): self
	{
		$this->description = $description;
		return $this;
	}


	public function getGroup(): ?string
	{
		return $this->group;
	}

	public function withGroup(string $group): CommandInterface
	{
		$instance = clone $this;
		$instance->group = $group;
		return $instance;
	}

	protected function setGroup(string $group): self
	{
		$this->group = $group;
		return $this;
	}

	/**
	 * @return Argument[]
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	/**
	 * @return Option[]
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	protected function addOption(Option $option): self
	{
		$this->options[] = $option;

		return $this;
	}

	public function withOptions(array $options): CommandInterface
	{
		$instance          = clone $this;
		$instance->options = [];

		foreach($options as $option) {
			$instance->addOption($option);
		}

		return $instance;
	}

	public function setContainer(ContainerInterface $container): ContainerAwareInterface
	{
		$this->container = $container;

		// Configure command after container is set
		if ($this instanceof ConfigurableCommandInterface && !$this->configured) {
			$this->configureCommand();
			$this->configured = true;
		}

		return $this;
	}


	protected function addArgument(Argument $argument): self
	{
		$this->arguments[] = $argument;

		return $this;
	}

	public function withArguments(array $arguments): CommandInterface
	{
		$instance = clone $this;
		$instance->arguments = [];

		foreach ($arguments as $argument) {
			$instance->addArgument($argument);
		}

		return $instance;
	}

	/**
	 * Basic configuration - called in constructor
	 */
	abstract protected function configure(): void;

	/**
	 * Execute the command
	 */
	abstract public function execute(ConsoleInput $input, ConsoleOutput $output): int;

	/**
	 * Validate input before execution (if command implements ValidatableCommandInterface)
	 */
	protected function validateInput(ConsoleInput $input): array
	{
		$errors = [];

		// Basic validation - can be extended by implementing ValidatableCommandInterface
		foreach ($this->arguments as $argument) {
			if ($argument->isRequired() && !$input->hasArgument($argument->getName())) {
				$errors[] = "Required argument '{$argument->getName()}' is missing";
			}
		}

		return $errors;
	}
}
