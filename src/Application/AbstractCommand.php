<?php

namespace WebImage\Application;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command implements ContainerAwareInterface
{
	use ContainerAwareTrait;
}