<?php
namespace Etg24\EventSourcing\Event\Bus;

use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Serializer\ArraySerializer;
use Etg24\EventSourcing\Queue\Message;
use Etg24\EventSourcing\Queue\QueueInterface;
use Etg24\EventSourcing\Event\Handler\EventHandlerInterface;
use Etg24\EventSourcing\Event\Handler\ImmediateEventHandlerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 */
class InternalEventBus implements BusInterface {

	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var QueueInterface
	 * @Flow\Inject
	 */
	protected $queue;

	/**
	 * @var ArraySerializer
	 * @Flow\Inject
	 */
	protected $arraySerializer;

	/**
	 * @var EventHandlerInterface[]
	 */
	protected $eventSubscribers = [];

	public function initializeObject() {
		$eventHandlerClassNames = self::getEventHandlerImplementationClassNames($this->objectManager);

		foreach ($eventHandlerClassNames as $eventHandlerClassName) {
			$this->eventSubscribers[$eventHandlerClassName] = $this->objectManager->get($eventHandlerClassName);
		}
	}

	/**
	 * @param DomainEvent $event
	 * @return void
	 */
	public function publish(DomainEvent $event) {
		foreach ($this->eventSubscribers as $eventHandler) {
			if ($eventHandler->canHandleEvent($event)) {
				if ($eventHandler instanceof ImmediateEventHandlerInterface) {
					$this->handleEvent($event, $eventHandler);
				} else {
					$this->queueEvent($event, $eventHandler);
				}
			}
		}
	}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 */
	protected function handleEvent(DomainEvent $event, EventHandlerInterface $eventHandler) {
		try {
			$eventHandler->handle($event);
			$this->emitEventHandlingSuccess($event, $eventHandler);
		} catch (\Exception $exception) {
			$this->emitEventHandlingFailure($event, $eventHandler, $exception);
		}
	}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 */
	protected function queueEvent(DomainEvent $event, EventHandlerInterface $eventHandler) {
		$serializedEvent = $this->arraySerializer->serialize($event);
		$recipient = get_class($eventHandler);

		$this->queue->queue(new Message($recipient, $serializedEvent));
		$this->emitEventQueueingSuccess($event, $eventHandler);
	}

	/**
	 * @param ObjectManagerInterface $objectManager
	 * @return array Class names of all subscribers
	 * @Flow\CompileStatic
	 */
	static public function getEventHandlerImplementationClassNames(ObjectManagerInterface $objectManager) {
		$reflectionService = $objectManager->get(ReflectionService::class);
		return $reflectionService->getAllImplementationClassNamesForInterface(EventHandlerInterface::class);
	}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitEventHandlingSuccess(DomainEvent $event, EventHandlerInterface $eventHandler) {}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 * @param \Exception $exception
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitEventHandlingFailure(DomainEvent $event, EventHandlerInterface $eventHandler, \Exception $exception) {}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitEventQueueingSuccess(DomainEvent $event, EventHandlerInterface $eventHandler) {}
}