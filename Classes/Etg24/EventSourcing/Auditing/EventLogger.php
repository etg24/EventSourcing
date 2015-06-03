<?php
namespace Etg24\EventSourcing\Auditing;

use Etg24\EventSourcing\Command\Command;
use Etg24\EventSourcing\Command\Handler\CommandHandlerInterface;
use Etg24\EventSourcing\Domain\Model\ObjectName;
use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Event\Handler\EventHandlerInterface;
use Etg24\EventSourcing\Serializer\ArraySerializer;
use TYPO3\Flow\Annotations as Flow;

/**
 * Event Auditing
 */
class EventLogger {

	/**
	 * @var LoggerInterface
	 * @Flow\Inject
	 */
	protected $logger;

	/**
	 * @var ArraySerializer
	 * @Flow\Inject
	 */
	protected $commandSerializer;

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 */
	public function onEventHandlingSuccess(DomainEvent $event, EventHandlerInterface $eventHandler) {
		$eventType = new ObjectName($event);
		$eventHandlerType = new ObjectName($eventHandler);
		$additionalData = [
			'status' => 'success',
			'type' => 'event',
			'handlerType' => $eventHandlerType->getName(),
			'command' => $this->getEventData($event)
		];

		$this->logger->log(sprintf('%s handling success', $eventType), LOG_INFO, $additionalData, 'ES.Event');
	}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 * @param \Exception $exception
	 */
	public function onEventHandlingFailure(DomainEvent $event, EventHandlerInterface $eventHandler, \Exception $exception) {
		$eventType = new ObjectName($event);
		$eventHandlerType = new ObjectName($eventHandler);
		$additionalData = [
			'status' => 'failure',
			'type' => 'event',
			'handlerType' => $eventHandlerType->getName(),
			'command' => $this->getEventData($event),
			'exception' => [
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'class' => get_class($exception),
				'line' => $exception->getLine(),
				'code' => $exception->getCode()
			]
		];

		$this->logger->log(sprintf('%s handling failure', $eventType), LOG_ERR, $additionalData, 'ES.Event');
	}

	/**
	 * @param DomainEvent $event
	 * @param EventHandlerInterface $eventHandler
	 */
	public function onEventQueueingSuccess(DomainEvent $event, EventHandlerInterface $eventHandler) {
		$eventType = new ObjectName($event);
		$eventHandlerType = new ObjectName($eventHandler);
		$additionalData = [
			'status' => 'success',
			'type' => 'event',
			'handlerType' => $eventHandlerType->getName(),
			'command' => $this->getEventData($event)
		];

		$this->logger->log(sprintf('%s queueing success', $eventType), LOG_INFO, $additionalData, 'ES.Event');
	}

	/**
	 * @param DomainEvent $event
	 * @return array
	 */
	protected function getEventData(DomainEvent $event) {
		return $this->commandSerializer->serialize($event);
	}
}