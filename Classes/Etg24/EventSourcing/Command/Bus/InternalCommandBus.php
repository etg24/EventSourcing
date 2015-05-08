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
				$commandHandler->handle($command);
				$commandHandled = TRUE;
				break;
			}
		}

		if ($commandHandled === FALSE) {
			throw new UnableToHandleCommandException('The command "' . get_class($command) . '" could not be handled by the command bus.', 1428327683);
		}
	}

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