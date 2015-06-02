<?php
namespace Etg24\EventSourcing\Auditing;

use Etg24\EventSourcing\Command\Command;
use Etg24\EventSourcing\Command\Handler\CommandHandler;
use TYPO3\Flow\Annotations as Flow;

/**
 * Command Auditing
 */
class CommandLogger {

	/**
	 * @Flow\Inject
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @Flow\Inject
	 * @var CommandSerializerInterface
	 */
	protected $commandSerializer;

	/**
	 * @param Command $command
	 * @param CommandHandler $commandHandler
	 * @return void
	 */
	public function onCommandHandlingSuccess(Command $command, CommandHandler $commandHandler) {
		$additionalData = array(
			'status' => 'success',
			'handler' => get_class($commandHandler),
			'command' => $this->getCommandData($command)
		);

		$this->logger->log(sprintf('%s success', $command), LOG_INFO, $additionalData, 'EventSourcing');
	}

	/**
	 * @param Command $command
	 * @param CommandHandler $commandHandler
	 * @param \Exception $exception
	 * @return void
	 */
	public function onCommandHandlingFailure(Command $command, CommandHandler $commandHandler, \Exception $exception) {
		$additionalData = array(
			'status' => 'failure',
			'handler' => get_class($commandHandler),
			'command' => $this->getCommandData($command),
			'exception' => array(
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'class' => get_class($exception),
				'line' => $exception->getLine(),
				'code' => $exception->getCode()
			)
		);

		$this->logger->log(sprintf('%s failure', $command), LOG_ERR, $additionalData, 'EventSourcing');
	}

	/**
	 * @param Command $command
	 * @return array
	 */
	protected function getCommandData(Command $command) {
		return array(
			'class' => get_class($command),
			'data' => $this->commandSerializer->serialize($command),
		);
	}

}