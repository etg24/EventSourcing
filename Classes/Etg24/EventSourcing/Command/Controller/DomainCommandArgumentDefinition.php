<?php
namespace Etg24\EventSourcing\Command\Controller;

use TYPO3\Flow\Cli\CommandArgumentDefinition;

class DomainCommandArgumentDefinition extends CommandArgumentDefinition {

	/**
	 * The primitive type of the command argument
	 *
	 * @var string
	 */
	protected $argumentType;

	/**
	 * Constructor
	 *
	 * @param string $name name of the command argument (= parameter name)
	 * @param boolean $required defines whether this argument is required or optional
	 * @param string $description description of the argument
	 * @param string $argumentType The primitive type of the command argument
	 */
	public function __construct($name, $required, $description, $argumentType) {
		if (in_array($argumentType, ['int', 'integer', 'bool', 'boolean', 'string', 'float']) === FALSE) {
			throw new \InvalidArgumentException('Domain command arguments do not support the type "' . $argumentType . '".');
		}

		$this->name = $name;
		$this->required = $required;
		$this->description = $description;
		$this->argumentType = $argumentType;
	}

	/**
	 * @return string
	 */
	public function getArgumentType() {
		return $this->argumentType;
	}

	public function getDescription() {
		return '[' . $this->argumentType . ']' . "\t" . parent::getDescription();
	}

}