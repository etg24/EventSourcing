<?php
namespace Etg24\EventSourcing\Command\Handler;

use Etg24\EventSourcing\Command\Command;

interface CommandHandlerInterface {

	/**
	 * @param Command $command
	 * @return boolean TRUE if the command handler can handle the command
	 */
	public function canHandleCommand(Command $command);

	/**
	 * @param Command $command
	 * @return void
	 */
	public function handle(Command $command);

}