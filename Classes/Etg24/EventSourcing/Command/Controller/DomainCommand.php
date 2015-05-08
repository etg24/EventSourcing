<?php
namespace Etg24\EventSourcing\Command\Controller;

use TYPO3\Flow\Cli\Command;
use TYPO3\Flow\Reflection\ClassReflection;
use TYPO3\Flow\Reflection\ParameterReflection;
use TYPO3\Flow\Annotations as Flow;

class DomainCommand extends Command {

	/**
	 * @var boolean
	 * @Flow\InjectConfiguration("Command.Controller.markAsInternal")
	 */
	protected $internal;

	/**
	 * @var ClassReflection
	 */
	protected $commandReflection;

	/**
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $domainModelCommandClassName FQCN of the domain model command
	 * @throws \InvalidArgumentException
	 */
	public function __construct($controllerClassName, $domainModelCommandClassName) {
		$this->controllerClassName = $controllerClassName;
		$this->controllerCommandName = $domainModelCommandClassName;

		$this->generateCommandIdentifier();
	}

	protected function generateCommandIdentifier() {
		$matchCount = preg_match('/^(?P<PackageNamespace>\w+(?:\\\\\w+)*)\\\\Command\\\\(?P<CommandName>\w+)$/', $this->controllerCommandName, $matches);
		if ($matchCount !== 1) {
			throw new \InvalidArgumentException('Invalid domain command class name "' . $this->controllerCommandName . '". Make sure your domain command is in a folder named "Command".', 1431076873);
		}

		$packageNamespaceParts = explode('\\', $matches['PackageNamespace']);

		$packageKey = implode('.', $packageNamespaceParts);
		array_shift($packageNamespaceParts);
		$packageKeyWithoutVendor = implode('.', $packageNamespaceParts);

		$this->commandIdentifier = strtolower($packageKey . ':' . $packageKeyWithoutVendor . ':' . $matches['CommandName']);
	}

	/**
	 * Returns a short description of this command
	 *
	 * @return string A short description
	 */
	public function getShortDescription() {
		$commandMethodReflection = $this->getCommandReflection();
		$lines = explode(chr(10), $commandMethodReflection->getDescription());
		$shortDescription = ((count($lines) > 0) ? trim($lines[0]) : '<no description available>') . ($this->isDeprecated() ? ' <b>(DEPRECATED)</b>' : '');

		return $shortDescription;
	}

	/**
	 * Returns a longer description of this command
	 * This is the complete method description except for the first line which can be retrieved via getShortDescription()
	 * If The command description only consists of one line, an empty string is returned
	 *
	 * @return string A longer description of this command
	 */
	public function getDescription() {
		$commandReflection = $this->getCommandReflection();
		$lines = explode(chr(10), $commandReflection->getDescription());
		array_shift($lines);
		$descriptionLines = array();
		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if ($descriptionLines !== array() || $trimmedLine !== '') {
				$descriptionLines[] = $trimmedLine;
			}
		}
		$description = implode(chr(10), $descriptionLines);
		return $description;
	}

	/**
	 * @return string
	 */
	public function hasArguments() {
		return TRUE;
	}

	/**
	 * To get the argument definitions, the constructor parameters are used.
	 * Then the necessary information is fetched through reflecting the commands
	 * class properties. This makes it necessary that the constructor parameter
	 * and the corresponding class properties are matched through naming.
	 *
	 * @return DomainCommandArgumentDefinition[]
	 */
	public function getArgumentDefinitions() {
		$commandReflection = $this->getCommandReflection();
		$commandConstructor = $commandReflection->getConstructor();
		$parameters = $commandConstructor->getParameters();

		$argumentDefinitions = [];

		/** @var ParameterReflection $parameter */
		foreach ($parameters as $parameter) {
			$parameterName = $parameter->getName();

			if ($commandReflection->hasProperty($parameterName) === FALSE) {
				throw new \InvalidArgumentException('Unable to reflect property for parameter "' . $parameterName .'" in command "' . $this->controllerCommandName . '". Make sure constructor parameters and command property names match.', 1431083980);
			}

			$propertyReflection = $commandReflection->getProperty($parameterName);
			$argumentDescription = $propertyReflection->getDescription();

			if ($propertyReflection->isTaggedWith('var') === FALSE) {
				throw new \InvalidArgumentException('The parameter type for command parameter "' . $parameterName . '" in command "' . $this->controllerCommandName . '" cannot be determined.', 1431081210);
			}

			$argumentType = current($propertyReflection->getTagValues('var'));
			$argumentDefinitions[] = new DomainCommandArgumentDefinition($parameterName, TRUE, $argumentDescription, $argumentType);
		}

		return $argumentDefinitions;
	}

	/**
	 * @return boolean
	 */
	public function isInternal() {
		return $this->internal;
	}

	/**
	 * @return boolean
	 */
	public function isDeprecated() {
		return FALSE;
	}

	/**
	 * @return boolean
	 */
	public function isFlushingCaches() {
		return FALSE;
	}

	/**
	 * @return array
	 */
	public function getRelatedCommandIdentifiers() {
		return [];
	}

	/**
	 * @return ClassReflection
	 */
	protected function getCommandReflection() {
		if ($this->commandReflection === NULL) {
			$commandReflection = new ClassReflection($this->controllerCommandName);
			$this->commandReflection = $commandReflection->getParentClass();
		}
		return $this->commandReflection;
	}

}