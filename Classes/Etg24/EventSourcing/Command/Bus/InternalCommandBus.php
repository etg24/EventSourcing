<?php
namespace Etg24\EventSourcing\Command\Bus;

use Etg24\EventSourcing\Command\Command;
use Etg24\EventSourcing\Command\Bus\Exception\UnableToHandleCommandException;
use Etg24\EventSourcing\Command\Handler\CommandHandlerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 */
class InternalCommandBus {

	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var CommandHandlerInterface[]
	 */
	protected $commandHandlers = [];

	public function initializeObject() {
		$eventHandlerClassNames = self::getCommandHandlerImplementationClassNames($this->objectManager);

		foreach ($eventHandlerClassNames as $eventHandlerClassName) {
			$this->commandHandlers[$eventHandlerClassName] = $this->objectManager->get($eventHandlerClassName);
		}
	}

	/**
	 * @todo Use CommandInterface
	 * @param Command $command
	 * @throws UnableToHandleCommandException
	 */
	public function handle(Command $command) {
		$commandHandled = FALSE;

		foreach ($this->commandHandlers as $commandHandler) {
			if ($commandHandler->canHandleCommand($command)) {
				try {
					$commandHandler->handle($command);
					$commandHandled = TRUE;
					$this->emitCommandHandlingSuccess($command, $commandHandler);
					break;
				} catch (\Exception $exception) {
					$this->emitCommandHandlingFailure($command, $commandHandler, $exception);
					throw $exception;
				}
			}
		}

		if ($commandHandled === FALSE) {
			throw new UnableToHandleCommandException('The command "' . get_class($command) . '" could not be handled by the command bus.', 1428327683);
		}
	}

	/**
	 * @param Command $command
	 * @param CommandHandlerInterface $commandHandler
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitCommandHandlingSuccess(Command $command, CommandHandlerInterface $commandHandler) {}

	/**
	 * @param Command $command
	 * @param CommandHandlerInterface $commandHandler
	 * @param \Exception $exception
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitCommandHandlingFailure(Command $command, CommandHandlerInterface $commandHandler, \Exception $exception) {}

	/**
	 * @param ObjectManagerInterface $objectManager
	 * @return array Class names of all command handler class names
	 * @Flow\CompileStatic
	 */
	static public function getCommandHandlerImplementationClassNames(ObjectManagerInterface $objectManager) {
		$reflectionService = $objectManager->get(ReflectionService::class);
		return $reflectionService->getAllImplementationClassNamesForInterface(CommandHandlerInterface::class);
	}

}