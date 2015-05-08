<?php
namespace Etg24\EventSourcing;

use Etg24\EventSourcing\Event\DomainEvent;

interface EntityInterface {

	/**
	 * @return string the entities identifier
	 */
	public function getIdentifier();

	/**
	 * @param AggregateRootInterface $aggregateRoot
	 * @return void
	 */
	public function setAggregateRoot(AggregateRootInterface $aggregateRoot);

	/**
	 * @param \Closure $getNextVersion
	 * @return void
	 */
	public function setVersionGenerator(\Closure $getNextVersion);

	/**
	 * @param DomainEvent $event
	 * @return boolean TRUE if this entity is subscribed to the given event and can handle it
	 */
	public function canApplyEvent(DomainEvent $event);

	/**
	 * @param DomainEvent $event
	 * @return void
	 */
	public function apply(DomainEvent $event);

	/**
	 * @return array<DomainEvent>
	 */
	public function getUncommittedChanges();

	/**
	 * Empties the uncommitted changes
	 *
	 * @return void
	 */
	public function markChangesAsCommitted();

}