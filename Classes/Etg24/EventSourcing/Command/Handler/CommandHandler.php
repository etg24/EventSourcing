<?php
namespace Etg24\EventSourcing\Command\Handler;

use Etg24\EventSourcing\Command\Command;
use Etg24\EventSourcing\Command\Handler\Exception\UnableToHandleCommandException;

abstract class CommandHandler implements CommandHandlerInterface {

	/**
	 * By convention, a command handler must be in a namespace part "CommandHandler", and its
	 * commands must be on the same level with a namespace part "Command".
	 *
	 * E. g. 	\Some\Package\CommandHandler\...
	 * 			\Some\Package\Command\...
	 *
	 * @var string
	 */
	protected $commandNameSpacePrefix;

	protected function getCommandNameSpacePrefix() {
		if ($this->commandNameSpacePrefix === NULL) {
			$commandHandlerClassName = get_class($this);
			$this->commandNameSpacePrefix = substr($commandHandlerClassName, 0, strpos($commandHandlerClassName, 'CommandHandler')) . 'Command';
		}

		return $this->commandNameSpacePrefix;
	}

	/**
	 * @param Command $command
	 * @return boolean
	 */
	public function canHandleCommand(Command $command) {
		if ($this->commandIsWithinHandlerNameSpace($command) === FALSE) {
			return FALSE;
		}

		$commandName = $this->getCommandName($command);
		$commandHandleMethod = $this->getCommandHandleMethod($commandName);

		return method_exists($this, $commandHandleMethod);
	}

	/**
	 * @param Command $command
	 * @return boolean
	 */
	protected function commandIsWithinHandlerNameSpace(Command $command) {
		$commandClassName = get_class($command);
		return (strpos($commandClassName, $this->getCommandNameSpacePrefix()) === 0);
	}

	/**
	 * @param Command $command
	 * @throws UnableToHandleCommandException
	 */
	public function handle(Command $command) {
		if ($this->canHandleCommand($command) === FALSE) {
			throw new UnableToHandleCommandException('The command "' . get_class($command) . '" cannot be handled by this handler.', 1428327525);
		}

		$commandName = $this->getCommandName($command);
		$commandHandleMethod = $this->getCommandHandleMethod($commandName);

		$this->$commandHandleMethod($command);
	}

	/**
	 * @param Command $command
	 * @return string
	 */
	protected function getCommandName(Command $command) {
		$nameParts = explode('\\', get_class($command));
		return (string) array_pop($nameParts);
	}

	/**
	 * @param string $commandName
	 * @return string
	 */
	protected function getCommandHandleMethod($commandName) {
		return 'handle' . $commandName . 'Command';
	}

}