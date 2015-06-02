<?php
namespace Etg24\EventSourcing\Auditing;

use TYPO3\Flow\Annotations as Flow;
use Etg24\EventSourcing\Command\Command;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Command Serializer
 *
 * @Flow\Scope("singleton")
 */
class CommandSerializer implements CommandSerializerInterface {

	/**
	 * @param Command $command
	 * @return array
	 */
	public function serialize(Command $command) {
		return ObjectAccess::getGettableProperties($command);
	}

}