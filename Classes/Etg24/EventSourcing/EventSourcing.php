<?php
namespace Etg24\EventSourcing;

use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Exception\EventNotAppliedException;

trait EventSourcing {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var array<DomainEvent>
	 */
	protected $uncommittedEvents = [];

	/**
	 * List of child entities the aggregate manages
	 *
	 * @var array<EntityInterface>
	 */
	protected $entities = [];

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @return DomainEvent[]
	 */
	public function getUncommittedChanges() {
		$events = $this->uncommittedEvents;

		/** @var EntityInterface $entity */
		foreach ($this->entities as $entity) {
			$events = array_merge($events, $entity->getUncommittedChanges());
		}

		usort($events, function(DomainEvent $event1, DomainEvent $event2) {
			return ($event1->version < $event2->version) ? -1 : 1;
		});

		return $events;
	}

	public function markChangesAsCommitted() {
		$this->uncommittedEvents = [];

		/** @var EntityInterface $entity */
		foreach ($this->entities as $entity) {
			$entity->markChangesAsCommitted();
		}
	}

	/**
	 * @param DomainEvent $event
	 * @throws EventNotAppliedException
	 */
	protected function apply(DomainEvent $event) {
		$applyMethod = $this->getEventApplyMethod($event);

		if (method_exists($this, $applyMethod) === TRUE) {
			$this->$applyMethod($event);
			return;
		}

		$entityHandledEvent = FALSE;

		/** @var EntityInterface $entity */
		foreach ($this->entities as $entity) {
			if ($entity->canApplyEvent($event) === TRUE) {
				$entity->apply($event);
				$entityHandledEvent = TRUE;
				break;
			}
		}

		if ($entityHandledEvent === FALSE) {
			throw new EventNotAppliedException('The event "' . get_class($event) . '" could not be applied to the aggregate "' . get_class($this) . '".', 1426252366);
		}
	}

	/**
	 * @param EntityInterface $entity
	 */
	protected function unregisterEntity(EntityInterface $entity) {
		if (array_key_exists($entity->getIdentifier(), $this->entities) === FALSE) {
			throw new \InvalidArgumentException('The entity with identifier "' . $entity->getIdentifier() . '" is not registered.', 1426251309);
		}

		unset($this->entities[$entity->getIdentifier()]);
	}

	/**
	 * @param DomainEvent $event
	 * @return string
	 */
	protected function getEventApplyMethod(DomainEvent $event) {
		$parts = explode('\\', get_class($event));
		$eventName = array_pop($parts);
		$applyMethod = 'on' . $eventName;
		return $applyMethod;
	}

}