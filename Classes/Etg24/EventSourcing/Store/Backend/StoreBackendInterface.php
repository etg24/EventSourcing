<?php
namespace Etg24\EventSourcing\Store\Backend;

use Etg24\EventSourcing\Event\DomainEvent;
use Etg24\EventSourcing\Store\Backend\Exception\EventStreamNotFoundException;
use Etg24\EventSourcing\Store\Backend\Exception\OptimisticLockException;

interface StoreBackendInterface {

	/**
	 * @param string $identifier
	 * @param DomainEvent[] $events
	 * @return void
	 * @throws OptimisticLockException
	 */
	public function append($identifier, array $events);

	/**
	 * @param string $identifier
	 * @return DomainEvent[]
	 * @throws EventStreamNotFoundException
	 */
	public function load($identifier);

}