<?php
namespace Etg24\EventSourcing\Command\Bus;

use Etg24\EventSourcing\Command\Command;

interface CommandBusInterface {

	/**
	 * @param Command $command
	 * @return void
	 */
	public function handle(Command $command);

}