<?php
namespace Etg24\EventSourcing\Domain\Model;

use TYPO3\Flow\Annotations as Flow;

/**
 *  Message Type
 */
class ObjectName {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @param mixed $subject
	 */
	public function __construct($subject) {
		$name = is_object($subject) ? get_class($subject) : (string)$subject;
		$this->name = str_replace('\\', '.', $name);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

}