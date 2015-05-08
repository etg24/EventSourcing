<?php
namespace Etg24\EventSourcing;

use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Exception\EmptyStreamException;

trait AggregateSourcing {

	use EventSourcing;

	/**
	 * @var integer
	 */
	protected $version = -1;

	/**
	 * @param DomainEvent[] $stream
	 * @return static
	 * @throws EmptyStreamException
	 * @throws Exception\EventNotAppliedException
	 */
	public static function loadFromEventStream(array $stream = []) {
		if (count($stream) === 0) {
			throw new EmptyStreamException('Unable to load aggregate "' . get_called_class() . '" with an empty event stream.', 1427292049);
		}

		/** @var AggregateSourcing $aggregate */
		$aggregate = unserialize('O:' . strlen(get_called_class()) . ':"' . get_called_class() . '":0:{}');

		/** @var DomainEvent $event */
		foreach ($stream as $event) {
			$aggregate->apply($event);
		}

		$aggregate->version = $event->version;

		return $aggregate;
	}

	/**
	 * @param DomainEvent $event
	 */
	protected function applyNewEvent(DomainEvent $event) {
		$event->version = $this->getNextVersion();

		$this->apply($event);
		$this->uncommittedEvents[] = $event;
	}

	/**
	 * @param EntityInterface $entity
	 */
	protected function registerEntity(EntityInterface $entity) {
		$aggregate = $this;
		$entityIdentifier = $entity->getIdentifier();

		if (array_key_exists($entityIdentifier, $this->entities) === TRUE) {
			throw new \InvalidArgumentException('The entity with identifier "' . $entityIdentifier . '" is already registered.', 1426251092);
		}

		$entity->setAggregateRoot($this);
		$entity->setVersionGenerator(function() use ($aggregate) {
			return $aggregate->getNextVersion();
		});

		$this->entities[$entityIdentifier] = $entity;
	}

	/**
	 * @return integer
	 */
	protected function getNextVersion() {
		return ++$this->version;
	}

}