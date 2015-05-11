<?php
namespace Etg24\EventSourcing\Command\Controller;

use Etg24\EventSourcing\Command\Bus\InternalCommandBus;
use ReflectionClass;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\Argument;
use TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException;

/**
 * @Flow\Scope("singleton")
 */
class DomainCommandController extends CommandController {

	/**
	 * @var InternalCommandBus
	 * @Flow\Inject
	 */
	protected $commandBus;

	protected function resolveCommandMethodName() {
		return 'execute';
	}

	/**
	 * @return void
	 * @throws InvalidArgumentTypeException
	 */
	protected function initializeCommandMethodArguments() {
		$this->arguments->removeAll();

		/** @var DomainCommandArgumentDefinition[] $commandArguments */
		$commandArguments = $this->request->getCommand()->getArgumentDefinitions();

		foreach ($commandArguments as $commandArgument) {
			$this->arguments->addNewArgument(
				$commandArgument->getName(),
				$commandArgument->getArgumentType(),
				$commandArgument->isRequired()
			);
		}
	}

	protected function callCommandMethod() {
		$preparedArguments = array();
		/** @var Argument $argument */
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);

		if (is_string($commandResult) && strlen($commandResult) > 0) {
			$this->response->appendContent($commandResult);
		} elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
			$this->response->appendContent((string)$commandResult);
		}
	}

	public function execute() {
		$class = new ReflectionClass($this->request->getCommand()->getControllerCommandName());

		// convert "NULL" to NULL
		$arguments = array_map(function($argument) {
			if ($argument === 'NULL') {
				return NULL;
			}

			return $argument;
		}, func_get_args());

		$command = $class->newInstanceArgs($arguments);
		$this->commandBus->handle($command);
	}

}