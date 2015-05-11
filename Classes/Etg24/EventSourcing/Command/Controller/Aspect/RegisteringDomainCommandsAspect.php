<?php
namespace Etg24\EventSourcing\Command\Controller\Aspect;

use Etg24\EventSourcing\Command as Domain;
use Etg24\EventSourcing\Command\Controller\DomainCommand;
use Etg24\EventSourcing\Command\Controller\DomainCommandController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Cli as Cli;
use TYPO3\Flow\Cli\Request;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RegisteringDomainCommandsAspect {

	/**
	 * @var ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @param JoinPointInterface $joinPoint
	 * @return mixed Result of the target method
	 * @Flow\Around("class(TYPO3\Flow\Cli\CommandManager) && method(.*->getAvailableCommands()) && setting(Etg24.EventSourcing.Command.Controller.enabled)")
	 */
	public function registerDomainModelCommands(JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();

		$currentCommands = ObjectAccess::getProperty($proxy, 'availableCommands', TRUE);

		// commands have been initialized
		if ($currentCommands !== NULL) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$commands = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$domainCommands = $this->getDomainCommands();
		$allCommands = array_merge($commands, $domainCommands);

		ObjectAccess::setProperty($proxy, 'availableCommands', $allCommands, TRUE);
		return $allCommands;
	}

	/**
	 * @return Cli\Command[]
	 */
	protected function getDomainCommands() {
		$cliCommands = [];
		$domainCommandClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface(Domain\CommandInterface::class);

		foreach ($domainCommandClassNames as $domainCommandClassName) {
			$cliCommands[] = $this->buildDomainCommand($domainCommandClassName);
		}

		return $cliCommands;
	}

	/**
	 * @param string $commandClassName
	 * @return Cli\Command
	 */
	protected function buildDomainCommand($commandClassName) {
		return new DomainCommand(
			DomainCommandController::class,
			$commandClassName
		);
	}

	/**
	 * @param JoinPointInterface $joinPoint
	 * @return mixed Result of the target method
	 * @Flow\Around("class(TYPO3\Flow\Cli\Request) && method(.*->getCommand())")
	 */
	public function replaceCommandWithDomainCommand(JoinPointInterface $joinPoint) {
		/** @var Request $proxy */
		$proxy = $joinPoint->getProxy();

		if ($proxy->getControllerObjectName() === DomainCommandController::class) {
			ObjectAccess::setProperty(
				$proxy,
				'command',
				$this->buildDomainCommand($proxy->getControllerCommandName()),
				TRUE
			);
		}

		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

}