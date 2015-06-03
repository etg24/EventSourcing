<?php
namespace Etg24\EventSourcing\Serializer;

use Etg24\EventSourcing\Domain\Model\ObjectName;
use Etg24\EventSourcing\Message;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ArraySerializer implements MessageSerializerInterface {

	/**
	 * @param Message $message
	 * @return array
	 */
	public function serialize(Message $message) {
		$messageType = new ObjectName($message);
		$data = ObjectAccess::getGettableProperties($message);

		return [
			'messageType' => $messageType->getName(),
			'payload' => $data
		];
	}

	/**
	 * @param array $serializedMessage
	 * @return Message
	 */
	public function unserialize($serializedMessage) {
		if (is_array($serializedMessage) === FALSE) {
			throw new \InvalidArgumentException('The ArraySerializer can only unserialize arrays.', 1427369045);
		}

		if (array_key_exists('messageType', $serializedMessage) === FALSE || array_key_exists('payload', $serializedMessage) === FALSE || is_array($serializedMessage['payload']) === FALSE) {
			throw new \InvalidArgumentException('The serialized message is corrupted.', 1427369459);
		}

		$messageType = str_replace('.', '\\', $serializedMessage['messageType']);

		if (class_exists($messageType) === FALSE) {
			throw new \InvalidArgumentException('Unserialization for message of type "' . $messageType . '" failed. No such class.', 1427369534);
		}

		$message = new $messageType();
		foreach ($serializedMessage['payload'] as $propertyName => $propertyValue) {
			if (ObjectAccess::isPropertySettable($message, $propertyName)) {
				ObjectAccess::setProperty($message, $propertyName, $propertyValue);
			}
		}

		return $message;
	}

}