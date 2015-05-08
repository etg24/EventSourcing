<?php
namespace Etg24\EventSourcing\Serializer;

use Etg24\EventSourcing\Message;

interface MessageSerializerInterface {

	/**
	 * @param Message $message
	 * @return mixed
	 */
	public function serialize(Message $message);

	/**
	 * @param mixed $serializedMessage
	 * @return Message
	 */
	public function unserialize($serializedMessage);

}