<?php
namespace Etg24\EventSourcing;

use Etg24\EventSourcing\Event\DomainEvent;

trait EntitySourcing {

	use EventSourcing { apply as public; }

	/**
	 * @var AggregateRootInterface
	 */
	protected $aggregateRoot;

	/**
	 * @var \Closure
	 */
	protected $versionGenerator;

	/**
	 * @param AggregateRootInterface $aggregateRoot
	 * @return void
	 */
	public function setAggregateRoot(AggregateRootInterface $aggregateRoot) {
		$this->aggregateRoot = $aggregateRoot;
	}

	/**
	 * @param \Closure $getNextVersion
	 * @return void
	 */
	public function setVersionGenerator(\Closure $getNextVersion) {
		$this->versionGenerator = $getNextVersion;
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
		$entityIdentifier = $entity->getIdentifier();

		if (array_key_exists($entityIdentifier, $this->entities) === TRUE) {
			throw new \InvalidArgumentException('The entity with identifier "' . $entityIdentifier . '" is already registered.', 1426251092);
		}

		$entity->setAggregateRoot($this->aggregateRoot);
		$entity->setVersionGenerator($this->versionGenerator);

		$this->entities[$entityIdentifier] = $entity;
	}

	/**
	 * @return integer
	 */
	protected function getNextVersion() {
		$versionGenerator = $this->versionGenerator;
		return $versionGenerator();
	}

}