<?php
namespace Etg24\EventSourcing\Queue;

use Etg24\EventSourcing\Serializer\ArraySerializer;
use Etg24\EventSourcing\Event\Handler\EventHandlerInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * This queue directly handles the event.
 * For testing purposes only.
 *
 * @Flow\Scope("singleton")
 */
class ImmediateQueue implements QueueInterface {

	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var ArraySerializer
	 * @Flow\Inject
	 */
	protected $arraySerializer;

	/**
	 * @param Message $message
	 * @return void
	 */
	public function queue(Message $message) {
		/** @var EventHandlerInterface $handler */
		$handler = $this->objectManager->get($message->getRecipient());
		$event = $this->arraySerializer->unserialize($message->getPayload());

		$handler->handle($event);
	}

}