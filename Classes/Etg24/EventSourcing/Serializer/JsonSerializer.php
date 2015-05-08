<?php
namespace Etg24\EventSourcing\Serializer;

use Etg24\EventSourcing\Message;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class JsonSerializer implements MessageSerializerInterface {

	/**
	 * @var ArraySerializer
	 * @Flow\Inject
	 */
	protected $arraySerializer;

	/**
	 * @param Message $message
	 * @return string
	 */
	public function serialize(Message $message) {
		return json_encode(
			$this->arraySerializer->serialize($message)
		);
	}

	/**
	 * @param string $serializedMessage
	 * @return Message
	 */
	public function unserialize($serializedMessage) {
		if (is_string($serializedMessage) === FALSE) {
			throw new \InvalidArgumentException('The JsonSerializer can only unserialize strings.', 1427369767);
		}

		return $this->arraySerializer->unserialize(
			json_decode($serializedMessage, TRUE)
		);
	}

}