<?php
namespace Etg24\EventSourcing\Auditing;

use Etg24\EventSourcing\Command\Command;
use Etg24\EventSourcing\Command\Handler\CommandHandlerInterface;
use Etg24\EventSourcing\Domain\Model\ObjectName;
use Etg24\EventSourcing\Serializer\ArraySerializer;
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
	 * @var ArraySerializer
	 */
	protected $commandSerializer;

	/**
	 * @param Command $command
	 * @param CommandHandlerInterface $commandHandler
	 * @return void
	 */
	public function onCommandHandlingSuccess(Command $command, CommandHandlerInterface $commandHandler) {
		$commandHandlerName = new ObjectName($commandHandler);
		$additionalData = [
			'status' => 'success',
			'handler' => $commandHandlerName->getName(),
			'command' => $this->getCommandData($command)
		];

		$this->logger->log(sprintf('%s success', $command), LOG_INFO, $additionalData, 'EventSourcing');
	}

	/**
	 * @param Command $command
	 * @param CommandHandlerInterface $commandHandler
	 * @param \Exception $exception
	 * @return void
	 */
	public function onCommandHandlingFailure(Command $command, CommandHandlerInterface $commandHandler, \Exception $exception) {
		$additionalData = [
			'status' => 'failure',
			'handler' => get_class($commandHandler),
			'command' => $this->getCommandData($command),
			'exception' => [
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'class' => get_class($exception),
				'line' => $exception->getLine(),
				'code' => $exception->getCode()
			]
		];

		$this->logger->log(sprintf('%s failure', $command), LOG_ERR, $additionalData, 'EventSourcing');
	}

	/**
	 * @param Command $command
	 * @return array
	 */
	protected function getCommandData(Command $command) {
		return $this->commandSerializer->serialize($command);
	}

}